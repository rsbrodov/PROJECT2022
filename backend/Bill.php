<?php

namespace App\TROLFinance\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Base\Entity\Interfaces\ChangedFieldsInterface;
use App\Base\Entity\Interfaces\CreatedTimestampInterface;
use App\Base\Entity\Traits\CachedValuesTrait;
use App\Base\Entity\Traits\ChangedFieldsTrait;
use App\Base\Entity\Traits\CreatedTimestampTrait;
use App\Base\Exceptions\DateTimeException;
use App\CurrencyRatesBundle\Entity\CurrencyConversionRate;
use App\CurrencyRatesBundle\Entity\Interfaces\AssignedConversionRateInterface;
use App\CurrencyRatesBundle\Entity\Interfaces\HasCurrencyConversionRatesInterface;
use App\TROLFinance\Exceptions\BillOrdLinkException;
use App\TROLFinance\Exceptions\NoConversionRateAssignedException;
use App\TROLMain\Entity\holding\LegalEntity;
use App\TROLMain\Entity\logistics\Job;
use App\TROLMain\Entity\logistics\Order;
use App\Base\Entity\Traits\UnixTimeDates;
use App\TROLMain\Entity\Counterparty;
use App\TROLMain\Entity\Company;
use Doctrine\Common\Collections\ArrayCollection;
use App\TROLMain\Entity\logistics\Ord;
use JMS\Serializer\Annotation as JMS;
use App\SettingsBundle\Entity\Office;
use App\TROLMain\Entity\User;

/**
 * Bill
 *
 * @ORM\Table(name="bill", indexes={
 *    @ORM\Index(columns={"ord_id"}),
 *    @ORM\Index(columns={"user_id"}),
 *    @ORM\Index(columns={"pay_date"}),
 *    @ORM\Index(columns={"recognition_date"}),
 *    @ORM\Index(columns={"bill_tax"})
 * })
 * @ORM\Entity(repositoryClass="App\TROLFinance\Entity\BillRepository")
 * @JMS\ExclusionPolicy("all")
 * @ORM\HasLifecycleCallbacks()
 */
class Bill implements ChangedFieldsInterface, HasCurrencyConversionRatesInterface, CreatedTimestampInterface
{
    use ChangedFieldsTrait;
    use UnixTimeDates;
    use CachedValuesTrait;
    use CreatedTimestampTrait;

    const ORIGIN_EXPENSES = 0;
    const ORIGIN_INCOME   = 1;

    const ORIGIN_DIRECTIONS = [
        'income'   => 1,
        'expenses' => 0,
    ];

    const INVERSE_ORIGIN_DIRECTIONS = [
        1 => 'income',
        0 => 'expenses',
    ];

    public $isLogged = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="bill_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Expose()
     * @JMS\Groups({"pb-bill-list","Default","lazy-select","id-only"})
     */
    private $id;

    /**
     * @var Counterparty
     *
     * @ORM\ManyToOne(targetEntity="App\TROLMain\Entity\Counterparty", cascade={"detach"})
     * @ORM\JoinColumn(name="cust_id",referencedColumnName="cust_id",nullable=false)
     * @JMS\MaxDepth(1)
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop","payment-prop","bills-for-payment", "with-counterparty"})
     */
    private $counterparty;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="App\TROLMain\Entity\Company", cascade={"detach"})
     * @ORM\JoinColumn(name="company_id", referencedColumnName="company_id", nullable=true)
     * @JMS\MaxDepth(1)
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop", "with-company"})
     */
    private $company;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="bill_origin", type="boolean")
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop"})
     */
    private $origin = true; //bill direction 0-expense, 1-income

    /**
     * @var boolean
     * @JMS\Expose()
     * @ORM\Column(name="bill_type", type="boolean")
     */
    private $type; //bill type 0-direct, 1-overhead

    /**
     * @var Ord
     * @ORM\ManyToOne(targetEntity="App\TROLMain\Entity\logistics\Ord", inversedBy="bills", cascade={"detach"})
     * @ORM\JoinColumn(name="ord_id", referencedColumnName="ord_id", nullable=true)
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop","with-ord","with-operation"})
     */
    private $ord;

    /**
     * @var Office
     * @ORM\ManyToOne(targetEntity="App\SettingsBundle\Entity\Office")
     * @ORM\JoinColumn(name="office_id", referencedColumnName="office_id", nullable=false)
     * @JMS\Expose()
     * @JMS\Groups({"with-office","bill-list","bill-prop"})
     */
    private $office;

    /**
     * @var PaymentForm
     *
     * @ORM\ManyToOne(targetEntity="App\TROLFinance\Entity\PaymentForm")
     * @ORM\JoinColumn(name="money_id",referencedColumnName="money_id")
     * @JMS\Expose()
     * @JMS\Groups({"Default", "with-payment-form"})
     */
    private $paymentForm;

    /**
     * @var integer
     * @deprecated
     * @ORM\Column(name="bill_tax", type="integer")
     */
    private $tax = 0;

    /**
     * @var string
     * @deprecated
     * @ORM\Column(name="tax_rate", type="decimal",scale=6,precision=18)
     */
    private $taxRate = 0;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="App\TROLFinance\Entity\Currency")
     * @ORM\JoinColumn(name="valuta_id", referencedColumnName="valuta_id", nullable=false)
     * @JMS\Expose()
     * @JMS\Groups({"Default", "with-currency"})
     */
    private $currency;

    /**
     * @var BillConversionRate[]|Collection
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\BillConversionRate",
     *     cascade={"persist", "detach"},
     *     mappedBy="bill",
     *     orphanRemoval=true)
     * @JMS\Expose()
     * @JMS\Groups({"with-conversion-rates"})
     */
    private $conversionRates;

    /**
     * @var string
     *
     * @ORM\Column(name="bill_amount", type="decimal", scale=6, precision=18)
     * @JMS\Expose()
     * @JMS\Groups({"pb-bill-list", "Default"})
     */
    private $amount;

    /**
     * @var BillAmountHistory[] | Collection
     * @ORM\OneToMany(
     *     targetEntity="App\TROLFinance\Entity\BillAmountHistory",
     *      mappedBy="bill",
     *      cascade={"persist"})
     */
    private $amountHistory;

    /**
     * @var string
     *
     * @ORM\Column(name="bill_rest", type="decimal",scale=6,precision=18)
     * @JMS\Expose()
     * @JMS\Groups({"pb-bill-list", "Default"})
     */
    private $balance;

    /**
     * @var boolean
     * @deprecated
     * @ORM\Column(name="bill_pay_type", type="boolean")
     */
    private $paymentType;

    /**
     * @var integer
     * @deprecated
     * @ORM\Column(name="pay_id", type="integer", nullable=true)
     */
    private $payId;

    /**
     * @var string
     *
     * @ORM\Column(name="bill_comment", type="text", nullable=true)
     * @JMS\Expose()
     * @JMS\Groups({"bill-list", "Default"})
     */
    private $comment;

    /**
     * @var integer
     * @deprecated
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\TROLMain\Entity\User", inversedBy="bills" ,cascade={"detach"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop", "with-user"})
     */
    private $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="bill_date", type="integer")
     * @deprecated
     */
    private $billDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rate_date", type="date")
     */
    private $rateDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pay_date", type="date")
     * @JMS\Expose()
     * @JMS\Groups({"bill-list", "Default"})
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $payDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pay_date_first_incoming", type="date")
     */
    private $payDateFirstIncoming; //firs set payment date

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="recognition_date", type="date", nullable=false)
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop","Default"})
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $recognitionDate;

    /**
     * @var integer
     * @deprecated
     * @ORM\Column(name="bill_category_id", type="integer")
     */
    private $categoryId;

    /**
     * @var BillCategory
     * @ORM\ManyToOne(targetEntity="App\TROLFinance\Entity\BillCategory", inversedBy="bills")
     * @ORM\JoinColumn(name="bill_category_id", referencedColumnName="bill_category_id", nullable=false)
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","bill-prop", "with-bill-category"})
     */
    private $category;

    /**
     * @var InternalTransfer
     * @ORM\ManyToOne(targetEntity="App\TROLFinance\Entity\InternalTransfer", cascade={"detach"})
     * @ORM\JoinColumn(name="internalTransfer_id", nullable=true, onDelete="SET NULL")
     * @JMS\Expose()
     * @JMS\Groups({"with-internal-transfer"})
     */
    private $internalTransfer;

    /**
     * @var Payment2Bill[]
     *
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\Payment2Bill", mappedBy="bill", cascade={"persist",
     *                                                                                                     "detach"})
     * @ORM\JoinColumn(name="bill_id", referencedColumnName="bill_id")
     * @JMS\Expose()
     * @JMS\Groups({"bill-list","internal-transfer-prop", "with-payment-links"})
     * @JMS\MaxDepth(2)
     */
    private $payment2bill;

    /**
     * @var PrintableBill
     * @ORM\ManyToOne(targetEntity="App\TROLFinance\Entity\PrintableBill", inversedBy="bills", cascade={"detach"})
     * @ORM\JoinColumn(name="printable_bill_id", referencedColumnName="bill_print_id", onDelete="SET NULL", nullable=true)
     * @JMS\Expose()
     * @JMS\Groups({"with-printable-bills"})
     */
    private $printableBill;

    /**
     * @var BillOrdLink[]|Collection
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\BillOrdLink", mappedBy="bill", cascade={"persist"})
     */
    private $ordLinks;

    /**
     * @var BillCounterBillLink[]|Collection
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\BillCounterBillLink", mappedBy="bill", cascade={"detach"})
     * @JMS\Expose()
     * @JMS\Groups({"with-counter-bill-links"})
     */
    private $counterBillLinks;

    /**
     * @var PaymentBillRateDiffLink[]|Collection
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\PaymentBillRateDiffLink", mappedBy="sourceBill",
     *                                                                                                                cascade={"persist", "detach"})
     */
    private $rateDiffLinksForSource;

    /**
     * @var PaymentBillRateDiffLink[]|Collection
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\PaymentBillRateDiffLink", mappedBy="rateDifferenceBill",
     *                                                                                                                cascade={"persist", "detach"})
     */
    private $rateDiffLinksForRateDiff;

    /**
     * @var bool
     * @ORM\Column(name="locked", type="boolean", nullable=false)
     * @JMS\Expose()
     */
    private $locked = false;

    /**
     * @var bool
     * @ORM\Column(name="bad_debt", type="boolean", nullable=false)
     */
    private $badDebt = false;

    /**
     * @var float
     * @ORM\Column(name="create_vat", type="decimal", scale=2, precision=6, nullable=true)
     * @JMS\Expose()
     * @JMS\Groups({"Default", "transport-order"})
     */
    private $createVat;

    /**
     * @var Bill | null
     * @ORM\ManyToOne(targetEntity="App\TROLFinance\Entity\Bill", cascade={"persist", "detach"},
     *                                                                                             inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="bill_id", nullable=true, onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var Bill[] | Collection
     * @ORM\OneToMany(targetEntity="App\TROLFinance\Entity\Bill", cascade={"persist", "detach"}, mappedBy="parent")
     */
    private $children;

    function __construct()
    {
        $this->ordLinks         = new ArrayCollection();
        $this->payment2bill     = new ArrayCollection();
        $this->counterBillLinks = new ArrayCollection();
        $this->amountHistory    = new ArrayCollection();
        $this->conversionRates  = new ArrayCollection();
        try{
            $this
                ->setBalance(0)
                ->setPaymentType(false)
                ->setBillDate(new \DateTime());
        }catch(\Exception $e){
            throw new DateTimeException($e);
        }
    }


    /**
     * @ORM\PreUpdate()
     * @ORM\PrePersist()
     */
    public function checkRates()
    {
        if($this->conversionRates->count() < 1){
            throw new \RuntimeException('Empty conversion rates.');
        }

        foreach($this->conversionRates as $rate){
            if($rate->getSourceCurrencyCode() !== $this->getCurrencyName()){
                throw new \RuntimeException(
                    'Conversion rates currency mismatch, expected: '
                    .$this->getCurrencyName()
                    .', got: '
                    .$rate->getSourceCurrencyCode()
                );
            }
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set origin
     *
     * @param bool $billOrigin
     * bill direction 0-expense, 1-income
     *
     * @return Bill
     */
    public function setOrigin(?bool $billOrigin): Bill
    {
        $this->origin = $billOrigin;

        return $this;
    }

    /**
     * Get origin
     * bill direction 0-expense, 1-income
     * @return bool
     */
    public function getOrigin(): bool
    {
        return $this->origin;
    }

    public function isExpenses(): bool
    {
        return !$this->origin;
    }

    public function isIncome(): bool
    {
        return $this->origin;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("paymentDirection")
     */
    public function getPaymentDirection(): ?string
    {
        if($this->origin === null) return null;
        return self::INVERSE_ORIGIN_DIRECTIONS[$this->origin];
    }

    public function setPaymentDirection(?string $paymentDirection): self
    {
        if($paymentDirection === null){
            $this->origin = null;
        }else{
            $this->origin = self::ORIGIN_DIRECTIONS[$paymentDirection];
        }
        return $this;
    }

    /**
     * Set type
     *
     * @param bool $billType
     *
     * @return Bill
     */
    public function setType(bool $billType): Bill
    {
        $this->type = $billType;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean
     */
    public function getType(): bool
    {
        return $this->type;
    }

    /**
     * Get ordId
     *
     * @return integer
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("ordId")
     * @JMS\Groups({"pb-bill-list","payment-prop","bills-for-payment"})
     * @deprecated
     */
    public function getOrdId(): ?int
    {
        if(!empty($this->getOrd()))
            return $this->getOrd()->getId();
        else
            return null;
    }

    /**
     * Set amount
     *
     * @param string $billAmount
     *
     * @return Bill
     */
    public function setAmount(string $billAmount): Bill
    {
        $this->markFieldChanged('amount', round($this->amount, 2), round($billAmount, 2));
        $this->amount = round($billAmount, 6);
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getAmountInDifferentCurrency(Currency $targetCurrency): ?string
    {
        if($targetCurrency === $this->getCurrency()) return $this->amount;

        return round($this->amount / $this->getConversionRateByCurrency($targetCurrency->getName()), 2);
    }

    public function getBalanceInDifferentCurrency(Currency $targetCurrency): ?string
    {
        if($targetCurrency === $this->getCurrency()) return $this->balance;

        return round($this->balance / $this->getConversionRateByCurrency($targetCurrency->getName()), 2);
    }

    /**
     * Set balance
     *
     * @param string|null $billRest
     *
     * @return Bill
     */
    public function setBalance(?string $billRest): Bill
    {
        $this->balance = round($billRest, 2);

        return $this;
    }

    /**
     * Get balance
     *
     * @return string|null
     */
    public function getBalance(): ?string
    {
        return $this->balance;
    }

    /**
     * Set paymentType
     *
     * @param boolean $billPayType
     *
     * @return Bill
     * @deprecated
     */
    public function setPaymentType(bool $billPayType): Bill
    {
        $this->paymentType = $billPayType;

        return $this;
    }

    /**
     * Get paymentType
     * @return boolean
     * @deprecated
     */
    public function getPaymentType(): bool
    {
        return $this->paymentType;
    }

    /**
     * Set payId
     *
     * @param integer|null $payId
     *
     * @return Bill
     * @deprecated Use difference between amount and balance
     */
    public function setPayId(?int $payId): Bill
    {
        $this->payId = $payId;

        return $this;
    }

    /**
     * Get payId
     * @return integer|null
     * @deprecated Use difference between amount and balance
     */
    public function getPayId(): ?int
    {
        return $this->payId;
    }

    /**
     * Set comment
     *
     * @param string|null $billComment
     *
     * @return Bill
     */
    public function setComment(?string $billComment): Bill
    {
        $this->comment = $billComment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Set billDate
     *
     * @param \DateTime|null $billDate
     *
     * @return Bill
     * @deprecated
     */
    public function setBillDate(?\DateTime $billDate): Bill
    {
        $this->billDate = $this->toUnixTime($billDate);

        return $this;
    }

    /**
     * @return \DateTime|null
     * @deprecated
     */
    public function getBillDate(): ?\DateTime
    {
        return $this->fromUnixTime($this->billDate);
    }

    /**
     * @param \DateTime|null $rateDate
     *
     * @return Bill
     */
    public function setRateDate(?\DateTime $rateDate): HasCurrencyConversionRatesInterface
    {
        $this->rateDate = $rateDate;

        return $this;
    }

    public function getRateDate(): ?\DateTime
    {
        return $this->rateDate;
    }

    /**
     * Set payDate
     *
     * @param \DateTime|null $payDate
     *
     * @return Bill
     */
    public function setPayDate(?\DateTime $payDate): Bill
    {
        $this->payDate = $payDate;

        return $this;
    }

    /**
     * Get payDate
     *
     * @return \DateTime|null
     *
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("payDate")
     * @JMS\Groups({"pb-bill-list"})
     */
    public function getPayDate(): ?\DateTime
    {
        return $this->payDate;
    }

    /**
     * Set payDateFirstIncoming
     *
     * @param \DateTime|null $payDateFirstIncoming
     *
     * @return Bill
     */
    public function setPayDateFirstIncoming(?\DateTime $payDateFirstIncoming): Bill
    {
        $this->payDateFirstIncoming = $payDateFirstIncoming;

        return $this;
    }

    /**
     * Get payDateFirstIncoming
     *
     * @return \DateTime|null
     */
    public function getPayDateFirstIncoming(): ?\DateTime
    {
        return $this->payDateFirstIncoming;
    }

    /**
     * Set recognitionDate
     *
     * @param \DateTime|null $recognitionDate
     *
     * @return Bill
     */
    public function setRecognitionDate(?\DateTime $recognitionDate = null): Bill
    {
//        if(!empty($recognitionDate)){
//            $this->recognitionDate = DateTimeFactory::create($recognitionDate->format('Y-m-01'));
//        }else{
        $this->recognitionDate = $recognitionDate;
//        }

        return $this;
    }

    /**
     * Get recognitionDate
     *
     * @return \DateTime|null
     */
    public function getRecognitionDate(): ?\DateTime
    {
        return $this->recognitionDate;
    }

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * @param Company|null $company
     *
     * @return $this
     */
    public function setCompany(?Company $company): Bill
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return Counterparty|null
     */
    public function getCounterparty(): ?Counterparty
    {
        return $this->counterparty;
    }

    /**
     * @param Counterparty|null $counterparty
     *
     * @return $this
     */
    public function setCounterparty(?Counterparty $counterparty): Bill
    {
        $this->counterparty = $counterparty;
        return $this;
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency|null $currency
     *
     * @return $this
     */
    public function setCurrency(?Currency $currency): Bill
    {
        $this->markFieldChanged('currency', $this->currency, $currency);
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return PaymentForm|null
     */
    public function getPaymentForm(): ?PaymentForm
    {
        return $this->paymentForm;
    }

    /**
     * @param PaymentForm|null $paymentForm
     *
     * @return $this
     */
    public function setPaymentForm(?PaymentForm $paymentForm): Bill
    {
        $this->paymentForm = $paymentForm;
        return $this;
    }

    /**
     * @return Payment2Bill[]|Collection
     */
    public function getPayment2bill()
    {
        return $this->payment2bill;
    }

    /**
     * @param Payment2Bill[]|Collection $payment2bill
     *
     * @return $this
     */
    public function setPayment2bill($payment2bill): Bill
    {
        $this->payment2bill = $payment2bill;
        return $this;
    }

    /**
     * @return Ord | Order | Job | null
     */
    public function getOrd(): ?Ord
    {
        return $this->ord;
    }

    /**
     * @param Ord|null $ord
     *
     * @return $this
     */
    public function setOrd(?Ord $ord): Bill
    {
        if(!empty($ord) && $ord instanceof Order && !$ord->isManagerApproved()){
            throw new BillOrdLinkException('Order must be approved');
        }
        $this->ord = $ord;
        return $this;
    }

    /**
     * @return InternalTransfer|null
     */
    public function getInternalTransfer(): ?InternalTransfer
    {
        return $this->internalTransfer;
    }

    /**
     * @param InternalTransfer|null $internalTransfer
     *
     * @return $this
     */
    public function setInternalTransfer(?InternalTransfer $internalTransfer): Bill
    {
        $this->internalTransfer = $internalTransfer;
        return $this;
    }


    /**
     * @return string|null
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("currencyName")
     */
    public function getCurrencyName(): ?string
    {
        if(empty($this->getCurrency())) return null;
        return $this->getCurrency()->getName();
    }

    /**
     * @return Collection|PrintableBill[]
     * @JMS\VirtualProperty
     * @JMS\SerializedName("printableBills")
     * @JMS\Groups({"with-printable-bills"})
     * @deprecated left for front-end compatibility
     */
    public function getPrintableBills(): Collection
    {
        return new ArrayCollection([$this->printableBill]);
    }

    public function getPrintableBill(): ?PrintableBill
    {
        return $this->printableBill;
    }

    public function setPrintableBill(?PrintableBill $printableBill, bool $cascade = true): Bill
    {
        if($cascade){
            if(!empty($printableBill)){
                $printableBill->addBill($this, false);
            }else{
                $this->printableBill->removeBill($this, false);
            }
        }
        $this->printableBill = $printableBill;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return $this
     */
    public function setUser(?User $user): Bill
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return BillCategory|null
     */
    public function getCategory(): ?BillCategory
    {
        return $this->category;
    }

    /**
     * @param BillCategory|null $billCategory
     *
     * @return $this
     */
    public function setCategory(?BillCategory $billCategory): Bill
    {
        $this->markFieldChanged('category', $this->category, $billCategory);
        $this->category = $billCategory;
        $this->setType($billCategory->isOverhead());
        return $this;
    }

    /**
     * @return Office|null
     */
    public function getOffice(): ?Office
    {
        return $this->office;
    }

    /**
     * @param Office|null $office
     *
     * @return $this
     */
    public function setOffice(?Office $office): Bill
    {
        $this->office = $office;
        return $this;
    }

    /**
     * @return BillOrdLink[]
     */
    public function getOrdLinks()
    {
        return $this->ordLinks;
    }

    public function resetOrdLinks(): Bill
    {
        $this->ordLinks = new ArrayCollection();
        return $this;
    }

    /**
     * @return BillCounterBillLink[]
     */
    public function getCounterBillLinks()
    {
        return $this->counterBillLinks;
    }

    public function addCounterBillLink(BillCounterBillLink $link, $cascade = true): Bill
    {
        $this->counterBillLinks->add($link);

        if($cascade){
            $link->setBill($this, false);
        }

        return $this;
    }

    public function removeCounterBillLink(BillCounterBillLink $link): Bill
    {
        $this->counterBillLinks->removeElement($link);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return str_pad($this->getId(), 6, '0');
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("paymentFormName")
     */
    public function getPaymentFormName(): string
    {
        return !empty($this->paymentForm) ? $this->paymentForm->getName() : '';
    }

    public function isExisting(): bool
    {
        return !empty($this->getId());
    }

    public function isOverhead(): bool
    {
        return empty($this->ord);
    }

    /**
     * @return boolean
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param boolean $locked
     *
     * @return $this
     */
    public function setLocked(bool $locked): Bill
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @return PaymentBillRateDiffLink[]|Collection
     */
    public function getRateDiffLinksForSource()
    {
        return $this->rateDiffLinksForSource;
    }

    /**
     * @param PaymentBillRateDiffLink[]|Collection $rateDiffLinksForSource
     *
     * @return $this
     */
    public function setRateDiffLinksForSource($rateDiffLinksForSource): Bill
    {
        $this->rateDiffLinksForSource = $rateDiffLinksForSource;
        return $this;
    }

    /**
     * @return PaymentBillRateDiffLink[]|Collection
     */
    public function getRateDiffLinksForRateDiff()
    {
        return $this->rateDiffLinksForRateDiff;
    }

    /**
     * @param PaymentBillRateDiffLink[]|Collection $rateDiffLinksForRateDiff
     *
     * @return $this
     */
    public function setRateDiffLinksForRateDiff($rateDiffLinksForRateDiff): Bill
    {
        $this->rateDiffLinksForRateDiff = $rateDiffLinksForRateDiff;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBadDebt(): bool
    {
        return $this->badDebt;
    }

    /**
     * @param bool $badDebt
     */
    public function setBadDebt(bool $badDebt)
    {
        $this->badDebt = $badDebt;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"transport-order"})
     */
    public function getLegalEntity(): ?LegalEntity
    {
        return $this->getPaymentForm()->getLegalEntity();
    }

    /**
     * @return Collection|BillAmountHistory[]
     */
    public function getAmountHistory()
    {
        return $this->amountHistory;
    }

    public function addAmountHistory(BillAmountHistory $amountHistory, bool $cascade = true)
    {
        if($cascade){
            $amountHistory->setBill($this, false);
        }
        $this->amountHistory->add($amountHistory);
    }

    public function removeAmountHistory(BillAmountHistory $amountHistory)
    {
        $this->amountHistory->removeElement($amountHistory);
    }

    /**
     * @return float|null
     */
    public function getCreateVat(): ?float
    {
        return $this->createVat;
    }

    /**
     * @param float|null $createVat
     *
     * @return Bill
     */
    public function setCreateVat(?float $createVat): Bill
    {
        $this->createVat = $createVat;
        return $this;
    }

    /**
     * @return Bill|null
     */
    public function getParent(): ?Bill
    {
        return $this->parent;
    }

    /**
     * @param Bill|null $parent
     *
     * @return Bill
     */
    public function setParent(?Bill $parent): Bill
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection|Bill[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function createConversionRate(CurrencyConversionRate $currencyConversionRate): void
    {
        $this->addConversionRate((new BillConversionRate())
            ->setSourceCurrencyCode($currencyConversionRate->getSourceCurrencyCode())
            ->setTargetCurrencyCode($currencyConversionRate->getTargetCurrencyCode())
            ->setRate($currencyConversionRate->getRate())
        );
    }

    /**
     * @return BillConversionRate[]|Collection
     */
    public function getConversionRates(): Collection
    {
        return $this->conversionRates;
    }

    /**
     * @return string[]
     * @JMS\VirtualProperty()
     * @JMS\Groups({"with-indexed-conversion-rates"})
     */
    public function getIndexedConversionRates(): array
    {
        return $this->calculateCachedValue('indexedConversionRates', function (){
            return array_reduce($this->conversionRates->toArray(), function ($accumulator, $item){
                /** @var $item BillConversionRate */
                $accumulator[$item->getTargetCurrencyCode()] = $item->getRate();
                return $accumulator;
            }, []);
        });
    }

    public function getConversionRateByCurrency(string $targetCurrency): string
    {
        if(empty($this->getIndexedConversionRates()[$targetCurrency])){
            throw new NoConversionRateAssignedException(
                'Bill '.$this->getId() ?? '',
                $this->getCurrency()->getName(),
                $targetCurrency);
        }
        return $this->getIndexedConversionRates()[$targetCurrency];
    }

    /**
     * @param BillConversionRate[]|Collection $conversionRates
     *
     * @return Bill
     */
    public function setConversionRates(Collection $conversionRates): Bill
    {
        $this->resetCachedValue('indexedConversionRates');
        $this->conversionRates = $conversionRates;
        return $this;
    }

    /**
     * @param BillConversionRate $rate
     * @param bool               $cascade
     *
     * @return $this
     */
    public function addConversionRate(AssignedConversionRateInterface $rate, bool $cascade = true): Bill
    {
        $this->resetCachedValue('indexedConversionRates');
        if($cascade){
            $rate->setBill($this, false);
        }

        $this->conversionRates->add($rate);
        return $this;
    }

    public function resetConversionRates(): HasCurrencyConversionRatesInterface
    {
        $this->resetCachedValue('indexedConversionRates');
        $this->conversionRates = new ArrayCollection();
        return $this;
    }

    public function hasPayments(): bool
    {
        return round((float)$this->getBalance(), 6) !== round((float)$this->getAmount(), 6);
    }

    /**
     * @JMS\VirtualProperty()
     */
    public function getOperationId(): ?int
    {
        return $this->ord ? $this->ord->getId() : null;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"with-converted-amounts"})
     */
    public function getConvertedAmounts(): array
    {
        $result = [];

        foreach($this->getConversionRates() as $conversionRate){
            $result[$conversionRate->getTargetCurrencyCode()] = round($this->amount / $conversionRate->getRate(), 2);
        }
        return $result;
    }
}
