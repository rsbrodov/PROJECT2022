<?php


namespace App\ReportsBundle\QueryObjects;

use App\Base\NativeQueryBuilder\Query\SelectQuery;
use App\Base\NativeQueryBuilder\Services\NativeQueryBuilder;
use App\Base\Utils\DateTimeFactory;
use App\TROLFinance\Entity\CurrencyRepository;
use DateTimeZone;
use App\Base\QueryObject\AbstractQueryObject;
use App\Base\QueryObject\PaginationTrait;
use App\Base\QueryObject\QueryObjectPostFetchInterface;
use App\Base\QueryObject\SubmittedDataTrait;

class PlanFactReportQueryObject extends AbstractQueryObject implements QueryObjectPostFetchInterface
{
    use SubmittedDataTrait;
    use PaginationTrait;

    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;


    public function __construct(
        NativeQueryBuilder $nativeQueryBuilder,
        CurrencyRepository $currencyRepository
    )
    {
        $this->nativeQueryBuilder = $nativeQueryBuilder;
        $this->currencyRepository = $currencyRepository;
    }


    public function setOptions($submittedData): PlanFactReportQueryObject
    {
        $this->setSubmittedData($submittedData);
        return $this;
    }

    protected function createQuery()
    {
        if(!$this->getSubmittedData()->isEmpty('searchBy.currency')){
            $this->currency = $this->currencyRepository->findOneById($this->getSubmittedData()->getProperty('searchBy.currency'));
        }else{
            $this->currency = $this->currencyRepository->findOneById(3);
        }
        $this->nativeQueryBuilder->createCustomQuery()
            ->setSQL('drop table if exists ORDER_TMP')
            ->execute()
        ;

        $dataQuery = $this->nativeQueryBuilder->createSelectQuery()
            ->from('ord', 'ord')
            ->join('req', 'customerRequest', 'ord.req_id = customerRequest.req_id')
            ->join('service', 'service', 'ord.service_id = service.service_id')
            ->join('product', 'product', 'ord.product_id = product.product_id')
            ->join('cust', 'counterparty', 'ord.cust_id = counterparty.cust_id')
            ->join('cont', 'manager', 'ord.cont_id = manager.cont_id')
            ->join('web_user', 'user', 'manager.cont_id = user.cont_id')
            ->join('country', 'dispatchCountry', 'customerRequest.req_country_load = dispatchCountry.country_id')
            ->join('country', 'deliveryCountry', 'customerRequest.req_country_unl = deliveryCountry.country_id')

            ->andWhere('ord.status = :status')
            ->setParameter('status', 'ready')
            ->addSelect('ord.ord_id', 'ordersId')
            ->addSelect('customerRequest.req_id', 'customerRequestId')
            ->addSelect('service.service_id as serviceId')
            ->addSelect('service.service_name as serviceName')
            ->addSelect('product.product_id as productId')
            ->addSelect('product.product_name as productName')
            ->addSelect('counterparty.cust_id as counterpartyId')
            ->addSelect('counterparty.cust_name as counterpartyName')
            ->addSelect('manager.cont_id as managerId')
            ->addSelect('manager.cont_name as managerName')
            ->addSelect('user.user_id as userId')

            ->addSelect('dispatchCountry.country_id as dispatchCountryId')
            ->addSelect('dispatchCountry.country_code as dispatchCountryCode')
            ->addSelect('deliveryCountry.country_id as deliveryCountryId')
            ->addSelect('ord.status_timestamp as ordCreatedTimeStamp')
            ->addSelect('deliveryCountry.country_code as deliveryCountryCode')
            ->addSelect('customerRequest.sale_price_id as salePriceId')
        ;

        $this->applySearchBy($dataQuery);
        $dataQuery->createTemporaryTable('ORDER_TMP', '(primary key (ordersId))');

        $this->nativeQueryBuilder->createCustomQuery()->setSQL('ALTER TABLE ORDER_TMP 
                                                                    ADD planAmountPrice decimal(16, 2) NOT NULL,
                                                                    ADD planAmountCost decimal(16, 2) NOT NULL,
                                                                    ADD factAmountProfit decimal(16, 2) NOT NULL,
                                                                    ADD factAmountProfitOrder decimal(16, 2) NOT NULL,
                                                                    ADD factAmountCost decimal(16, 2) NOT NULL,
                                                                    ADD factAmountCostOrder decimal(16, 2) NOT NULL,
                                                                    ADD planProfit decimal(16, 2) NOT NULL, 
                                                                    ADD factProfit decimal(16, 2) NOT NULL,
                                                                    ADD conclusion decimal(16, 2) NOT NULL,
                                                                    ADD currencyName varchar(20) NOT NULL
    
        ')->execute();

        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET planAmountPrice = IFNULL((SELECT RP.amount/R.rate
            from request_price RP
            inner join valuta on RP.currency_id = valuta.valuta_id
            inner join currency_conversion_rate R on valuta.valuta_name = R.source_currency_code
            where RP.id = ORDER_TMP.salePriceId and R.target_currency_code = "'.$this->currency->getName().'" and R.date = DATE_FORMAT(RP.created_timestamp_utc, \'%Y-%m-%d\')
            ), 0);')
            ->execute();

        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET planAmountCost = IFNULL((SELECT sum(RO.amount / R.rate)
            from request_price RP
            join request_sale_price_offers RSPO on RP.id = RSPO.price_id
            join request_offer RO on RSPO.offer_id = RO.id
            inner join valuta on RO.currency_id = valuta.valuta_id
            inner join currency_conversion_rate R on valuta.valuta_name = R.source_currency_code
            where RP.id = ORDER_TMP.salePriceId and R.target_currency_code = "'.$this->currency->getName().'" and R.date = DATE_FORMAT(RO.created_timestamp_utc, \'%Y-%m-%d\')
            group by RSPO.price_id
            ), 0);')
            ->execute();

        /*по работам*/
        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET factAmountProfit = IFNULL((SELECT sum(bill.bill_amount / BCR.rate)
            from tms_link_order_job TLOB
            inner join bill on TLOB.job_id = bill.ord_id
            inner join bill_conversion_rate BCR on bill.bill_id = BCR.bill_id
            inner join valuta on bill.valuta_id = valuta.valuta_id
            where TLOB.order_id = ORDER_TMP.ordersId and bill.bill_amount > 0 and bill.bill_type = 0 and BCR.source_currency_code = valuta.valuta_name and BCR.target_currency_code = "'.$this->currency->getName().'"
            group by TLOB.order_id
            ), 0);')
            ->execute();

        /*по заказам*/
        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET factAmountProfitOrder = IFNULL((SELECT sum(bill.bill_amount / BCR.rate)
            from bill
            inner join valuta on bill.valuta_id = valuta.valuta_id
            inner join bill_conversion_rate BCR on bill.bill_id = BCR.bill_id
            where ORDER_TMP.ordersId = bill.ord_id and bill.bill_amount > 0 and bill.bill_type = 0 and BCR.source_currency_code = valuta.valuta_name and BCR.target_currency_code = "'.$this->currency->getName().'"
            group by ORDER_TMP.ordersId
            ), 0);')
            ->execute();

        /*по работам*/
        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET factAmountCost = IFNULL((SELECT sum(ABS(bill.bill_amount) / BCR.rate)
            from tms_link_order_job TLOB
            inner join bill on TLOB.job_id = bill.ord_id
            inner join bill_conversion_rate BCR on bill.bill_id = BCR.bill_id
            inner join valuta on bill.valuta_id = valuta.valuta_id
            where TLOB.order_id = ORDER_TMP.ordersId and bill.bill_amount < 0 and bill.bill_type = 0 and BCR.source_currency_code = valuta.valuta_name and BCR.target_currency_code = "'.$this->currency->getName().'"
            group by TLOB.order_id
            ), 0);')
            ->execute();

        /*по заказам*/
        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET factAmountCostOrder = IFNULL((SELECT sum(ABS(bill.bill_amount) / BCR.rate)
            from bill
            inner join valuta on bill.valuta_id = valuta.valuta_id
            inner join bill_conversion_rate BCR on bill.bill_id = BCR.bill_id
            where ORDER_TMP.ordersId = bill.ord_id and bill.bill_amount < 0 and bill.bill_type = 0 and BCR.source_currency_code = valuta.valuta_name and BCR.target_currency_code = "'.$this->currency->getName().'"
            group by ORDER_TMP.ordersId
            ), 0);')
            ->execute();
        /*fact order+job bills*/
        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET factAmountProfit = (ORDER_TMP.factAmountProfit + ORDER_TMP.factAmountProfitOrder), 
            factAmountCost = (ORDER_TMP.factAmountCost + ORDER_TMP.factAmountCostOrder);')
            ->execute();

        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET planProfit = (select (ORDER_TMP.planAmountPrice - ABS(ORDER_TMP.planAmountCost))), 
            factProfit = (select (ORDER_TMP.factAmountProfit - ABS(ORDER_TMP.factAmountCost))),
            currencyName = "'.$this->currency->getName().'";')
            ->execute();

        $this->nativeQueryBuilder->createCustomQuery()->setSQL('
        UPDATE ORDER_TMP 
        SET conclusion = (select ORDER_TMP.factProfit/ORDER_TMP.planProfit*100) where ORDER_TMP.planProfit > 0;')
            ->execute();
        $resultQuery = $this->nativeQueryBuilder->createSelectQuery()
            ->from('ORDER_TMP', 'ORDER_TMP')
            ->addSelect('ORDER_TMP.*');

        return $resultQuery;
    }

    private function applySearchBy(SelectQuery $tempQuery): PlanFactReportQueryObject
    {
        if(!$this->getSubmittedData()->isEmpty('searchBy.dateFrom')){
            $dateParameter = DateTimeFactory::create($this->getSubmittedData()->getProperty('searchBy.dateFrom'), new DateTimeZone('UTC'));
            $tempQuery
                ->andWhere('ord.status_timestamp >= :dateFrom')
                ->setParameter('dateFrom', date('Y-m-d',$dateParameter->getTimestamp()));
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.dateUntil')){
            $dateParameter = DateTimeFactory::create($this->getSubmittedData()->getProperty('searchBy.dateUntil'), new DateTimeZone('UTC'));
            $tempQuery
                ->andWhere('ord.status_timestamp < :dateUntil')
                ->setParameter('dateUntil', date('Y-m-d',$dateParameter->getTimestamp()));
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.service')){
            $tempQuery
                ->andWhere('service.service_id = :service')
                ->setParameter('service', $this->getSubmittedData()->getProperty('searchBy.service'));
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.counterparty')){
            $tempQuery
                ->andWhere('counterparty.cust_id = :counterparty')
                ->setParameter(
                    'counterparty',
                    $this->getSubmittedData()->getProperty('searchBy.counterparty')
                );
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.dispatchCountry')){
            $tempQuery
                ->andWhere('dispatchCountry.country_id = :dispatchCountry')
                ->setParameter('dispatchCountry', $this->getSubmittedData()->getProperty('searchBy.dispatchCountry'));
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.deliveryCountry')){
            $tempQuery
                ->andWhere('deliveryCountry.country_id = :deliveryCountry')
                ->setParameter('deliveryCountry', $this->getSubmittedData()->getProperty('searchBy.deliveryCountry'));
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.clientManager')){
            $tempQuery
                ->andWhere('manager.cont_id = :manager')
                ->setParameter('manager', $this->getSubmittedData()->getProperty('searchBy.clientManager'));
        }

        if(!$this->getSubmittedData()->isEmpty('searchBy.product')){
            $tempQuery
                ->andWhere('product.product_id = :product')
                ->setParameter(
                    'product',
                    $this->getSubmittedData()->getProperty('searchBy.product')
                );
        }

        return $this;
    }

    public function postFetch($resultQuery)
    {
        $data               = [];
        $count              = -1;
        $countManager       = -1;
        $countProduct       = -1;
        $countPath          = -1;
        $uniqueManagers     = [];
        $uniqueProducts     = [];
        $uniquePath         = [];
        $temp               = [];
        $data['totalTable'] = $resultQuery;
        foreach($resultQuery as $queryKey => $result){
            $count++;
            if(!array_key_exists($result['managerId'], $uniqueManagers)){
                $uniqueManagers[$result['managerId']]                  = $result['managerId'];
                $temp['managers'][$result['managerId']]['managerId']   = $result['managerId'];
                $temp['managers'][$result['managerId']]['userId']      = $result['userId'];
                $temp['managers'][$result['managerId']]['managerName'] = $result['managerName'];
                $temp['managers'][$result['managerId']]['conclusion']  = $result['conclusion'];
                $temp['managersCount'][$result['managerId']]           = 1;
            }else{
                $temp['managers'][$result['managerId']]['conclusion'] += $result['conclusion'];
                $temp['managersCount'][$result['managerId']]          += 1;
            }

            if(!array_key_exists($result['productId'], $uniqueProducts)){
                $uniqueProducts[$result['productId']]                  = $result['productId'];
                $temp['products'][$result['productId']]['productId']   = $result['productId'];
                $temp['products'][$result['productId']]['productName'] = $result['productName'];
                $temp['products'][$result['productId']]['planProfit']  = $result['planProfit'];
                $temp['products'][$result['productId']]['factProfit']  = $result['factProfit'];
                $temp['products'][$result['productId']]['conclusion']  = $result['conclusion'];
                $temp['productsCount'][$result['productId']]           = 1;
            }else{
                $temp['products'][$result['productId']]['conclusion'] += $result['conclusion'];
                $temp['products'][$result['productId']]['planProfit'] += $result['planProfit'];
                $temp['products'][$result['productId']]['factProfit'] += $result['factProfit'];
                $temp['productsCount'][$result['productId']]          += 1;
            }

            if(!array_key_exists($result['dispatchCountryId'].'_'.$result['deliveryCountryId'], $uniquePath)){
                $uniquePath[$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]                          = $result['dispatchCountryId'].'_'.$result['deliveryCountryId'];
                $temp['path'][$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]['dispatchCountryId']   = $result['dispatchCountryId'];
                $temp['path'][$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]['deliveryCountryId']   = $result['deliveryCountryId'];
                $temp['path'][$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]['dispatchCountryCode'] = $result['dispatchCountryCode'];
                $temp['path'][$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]['deliveryCountryCode'] = $result['deliveryCountryCode'];
                $temp['path'][$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]['conclusion']          = $result['conclusion'];
                $temp['pathCount'][$result['dispatchCountryCode'].'_'.$result['deliveryCountryCode']]               = 1;
            }else{
                $temp['path'][$result['dispatchCountryId'].'_'.$result['deliveryCountryId']]['conclusion'] += $result['conclusion'];
                $temp['pathCount'][$result['dispatchCountryCode'].'_'.$result['deliveryCountryCode']]      += 1;
            }
            unset($resultQuery[$queryKey]);
        }
        if($temp){
            foreach($temp['managers'] as $manager){
                $countManager++;
                $data['managers'][$countManager]['managerId']   = $manager['managerId'];
                $data['managers'][$countManager]['userId']      = $manager['userId'];
                $data['managers'][$countManager]['managerName'] = $manager['managerName'];
                $data['managers'][$countManager]['conclusion']  = $manager['conclusion'] / $temp['managersCount'][$manager['managerId']];
            }
            unset($temp['managers']);

            foreach($temp['products'] as $product){
                $countProduct++;
                $data['products'][$countProduct]['productId']    = $product['productId'];
                $data['products'][$countProduct]['productName']  = $product['productName'];
                $data['products'][$countProduct]['currencyName'] = $this->currency->getName();
                $data['products'][$countProduct]['conclusion']   = $product['conclusion'] / $temp['productsCount'][$product['productId']];
                $data['products'][$countProduct]['planProfit']   = $product['planProfit'];
                $data['products'][$countProduct]['factProfit']   = $product['factProfit'];
            }
            unset($temp['products']);

            foreach($temp['path'] as $path){
                $countPath++;
                $data['path'][$countPath]['dispatchCountryId']   = $path['dispatchCountryId'];
                $data['path'][$countPath]['deliveryCountryId']   = $path['deliveryCountryId'];
                $data['path'][$countPath]['dispatchCountryCode'] = $path['dispatchCountryCode'];
                $data['path'][$countPath]['deliveryCountryCode'] = $path['deliveryCountryCode'];
                $data['path'][$countPath]['conclusion']          = $path['conclusion'] / $temp['pathCount'][$path['dispatchCountryCode'].'_'.$path['deliveryCountryCode']];
            }
            unset($temp['path']);
        }
        return $data;
    }
}
