<?php


namespace App\WarehouseBundle\Services;


use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use App\Base\Exceptions\FormValidationException;
use App\Base\Traits\SubmitFormTrait;
use App\WarehouseBundle\Entity\LinkWarehouseWarehouseService;
use App\WarehouseBundle\Entity\LinkWarehouseWarehouseServiceRepository;
use App\WarehouseBundle\Entity\WarehouseServiceRepository;
use App\WarehouseBundle\Form\LinkWarehouseWarehouseServiceFormType;
use App\WarehouseBundle\Form\LocationType;
use App\TROLMain\Entity\logistics\LocationRepository;
use App\TROLMain\Entity\logistics\Location;

class WarehouseSettingsService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use SubmitFormTrait;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var LocationRepository
     */
    private $locationRepository;


    public function __construct(FormFactoryInterface $formFactory, LocationRepository $locationRepository)
    {
        $this->formFactory        = $formFactory;
        $this->locationRepository = $locationRepository;
    }

    /**
     * @param Location    $location
     * @param             $submittedData
     *
     * @return Location
     * @throws FormValidationException
     */
    public function save(Location $location, $submittedData): Location
    {
        if($location->getLocationType() !== Location::TYPE_DEPOT){
            throw new NotFoundHttpException;
        }

        $form = $this->formFactory->create(LocationType::class, $location);
        $this->submitForm($form, $submittedData);
        $this->locationRepository->save($location);

        return $location;
    }

    /**
     * @throws FormValidationException
     */
    public function create(Request $submittedData): Location
    {
        $location = new Location();
        $location->setLocationType(Location::TYPE_DEPOT);
        $location->setStatus(Location::STATUS_ENABLED);

        return $this->save($location, $submittedData);
    }

    public function toggleEnabled(Location $location)
    {
        $location->setDisabled(!$location->isDisabled());
        $this->locationRepository->save($location);
    }

    /**
     * @throws FormValidationException
     */
    public function createServiceLink(Request $submittedData, Location $location): LinkWarehouseWarehouseService
    {
        $link = new LinkWarehouseWarehouseService();
        $link->setWarehouse($location);

        return $this->saveServiceLink($link, $submittedData);
    }

    /**
     * @throws FormValidationException
     */
    public function saveServiceLink(LinkWarehouseWarehouseService $link, Request $submittedData): LinkWarehouseWarehouseService
    {
        $form = $this->formFactory->create(LinkWarehouseWarehouseServiceFormType::class, $link);
        $this->submitForm($form, $submittedData);
        $this->container
            ->get(LinkWarehouseWarehouseServiceRepository::class)
            ->save($link);

        return $link;
    }

    public function deleteServiceLink()
    {
    }

    public static function getSubscribedServices(): array
    {
        return [
            WarehouseServiceRepository::class,
            LinkWarehouseWarehouseServiceRepository::class,
        ];
    }
}
