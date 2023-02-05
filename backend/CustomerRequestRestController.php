<?php
/**
 * Created by PhpStorm.
 * User: Gideon
 * Date: 26.06.2018
 * Time: 13:21
 */

namespace App\CustomerRequestBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Base\Exceptions\FormValidationException;
use App\CustomerRequestBundle\Entity\CustomerRequest;
use App\CustomerRequestBundle\Entity\CustomerRequestRepository;
use App\CustomerRequestBundle\QueryObjects\CustomerRequestListQueryObject;
use App\CustomerRequestBundle\Services\CustomerRequestService;

class CustomerRequestRestController extends AbstractFOSRestController
{
    const SEARCH_GROUPS = [
        'Default',
        'properties',
        'with-request-group',
        'requestGroup' => [
            'with-requests',
            'requests' => ['Default'],
        ],
    ];

    const LIST_GROUPS = [
        'Default',
        'list',
        'with-request-group',
        'with-request-offer-comments',
        'baseOfferCurrency' => ['Default'],
        'manager'           => ['Default', 'with-office'],
        'requestGroup'      => [
            'with-requests',
            'requests' => ['id-only'],
        ],
        'requestOfferComments' => [
            'Default',
            'with-created-by',
            'createdBy' => [
                'Default',
                'with-photo',
                'photoFile' => [
                    'Default',
                    'shared-file-list'
                ]
            ]
        ],
    ];

    private CustomerRequestService         $requestService;
    private CustomerRequestListQueryObject $listQueryObject;

    public function __construct(
        CustomerRequestService         $requestService,
        CustomerRequestListQueryObject $listQueryObject,
    )
    {
        $this->requestService      = $requestService;
        $this->listQueryObject     = $listQueryObject;
    }

    private function setAccessFlags(CustomerRequest $customerRequest)
    {
        $customerRequest
            ->setUserCanManage($this->isGranted('manage', $customerRequest))
            ->setUserCanAssignSubstitute($this->isGranted('assign_substitute', $customerRequest));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route(
     *   "/requests",
     *   methods={"POST","GET"}
     * )
     */
    public function listRequests(Request $request): Response
    {
        $view = $this->view([
            'data' => [
                'page'   => $this->listQueryObject
                    ->setOptions($request)
                    ->fetch(),
                'totals' => $this->listQueryObject->fetchTotals(),
            ],
        ]);

        $view->getContext()->setGroups(self::LIST_GROUPS);

        return $this->handleView($view);
    }

    /**
     * @param Request         $request
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/available-for-grouping",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"GET","POST"}
     * )
     */
    public function listAvailableToGroup(Request $request, CustomerRequest $customerRequest): Response
    {
        $view = $this->view([
            'data' => [
                'page'   => $this->listQueryObject
                    ->setOptions($request,
                        CustomerRequestListQueryObject::MODE_GROUP,
                        $customerRequest)
                    ->fetch(),
                'totals' => $this->listQueryObject->fetchTotals(),
            ],
        ]);

        $view->getContext()->setGroups(self::LIST_GROUPS);

        return $this->handleView($view);
    }

    /**
     * @param Request         $request
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/group",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function createGroup(Request $request, CustomerRequest $customerRequest): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        try{
            $this->requestService->createGroup($customerRequest, $request);
            $this->setAccessFlags($customerRequest);
            $view = $this->view(['data' => $customerRequest,]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/group",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"DELETE"}
     * )
     */
    public function removeFromGroup(CustomerRequest $customerRequest): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        $this->requestService->removeFromGroup($customerRequest);
        $view = $this->view(['data' => $customerRequest,]);
        $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        return $this->handleView($view);
    }

    /**
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"GET"}
     * )
     */
    public function properties(CustomerRequest $customerRequest): Response
    {
        $this->denyAccessUnlessGranted('view', $customerRequest);

        $this->setAccessFlags($customerRequest);

        $view = $this->view(['data' => $customerRequest]);
        $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);

        return $this->handleView($view);
    }

    /**
     * @Route(
     *     "/requests/search/{customerRequestId}",
     *     requirements={
     *         "customerRequestId":"^[\d]+$"
     *     },
     *     methods={"GET"}
     * )
     */
    public function search(int $customerRequestId, CustomerRequestRepository $customerRequestRepository): Response
    {
        $customerRequest = $customerRequestRepository->findOneBy(['id' => $customerRequestId]);
        if(!empty($customerRequest)){
            $this->denyAccessUnlessGranted('view', $customerRequest);
            $this->setAccessFlags($customerRequest);
        }
        $view = $this->view(['data' => $customerRequest]);

        $view->getContext()->setGroups(self::SEARCH_GROUPS);

        return $this->handleView($view);
    }

    /**
     * @param Request         $request
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST","PUT"}
     * )
     */
    public function save(Request $request, CustomerRequest $customerRequest): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        if(!empty($customerRequest->getCurrentSalePrice())){
            throw new AccessDeniedException();
        }
        try{
            $this->requestService->save($customerRequest, $request);
            $this->setAccessFlags($customerRequest);
            $view = $this->view(['data' => $customerRequest,]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @Route(
     *     "/requests/{customerRequest}/block/{block}",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST","PUT"}
     * )
     */
    public function saveBlock(Request $request, CustomerRequest $customerRequest, string $block): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        if(
            (
                !empty($customerRequest->getCurrentSalePrice())
                || (!empty($customerRequest->getFirstOfferTimestamp()) && $block === CustomerRequestService::BLOCK_CARGO)
            )
            && $block !== CustomerRequestService::BLOCK_TNVED
        ){
            throw new AccessDeniedException();
        }
        try{
            $this->requestService->save($customerRequest, $request, $block);
            $this->setAccessFlags($customerRequest);
            $view = $this->view(['data' => $customerRequest,]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route(
     *     "/requests/new",
     *     name="trol.rest.customer_request.create",
     *     methods={"POST"}
     * )
     */
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TO_CUST_REQUEST_CREATE');
        try{
            $customerRequest = $this->requestService->create($request);
            $this->setAccessFlags($customerRequest);
            $view = $this->view(['data' => $customerRequest]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @param Request         $request
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/base-currency",
     *     name="trol.rest.customer_request.base-currency.change",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function updateBaseCurrency(CustomerRequest $customerRequest, Request $request): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        $this->requestService->updateBaseCurrency($customerRequest, $request);
        $view = $this->view([]);
        return $this->handleView($view);
    }

    /**
     * @param Request         $request
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/status",
     *     name="trol.rest.customer_request.status.change",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function changeStatus(CustomerRequest $customerRequest, Request $request): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        try{
            $this->requestService->changeStatus($customerRequest, $request);
            $this->setAccessFlags($customerRequest);
            $view = $this->view(['data' => $customerRequest]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @param CustomerRequest $customerRequest
     * @param Request         $request
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/change-statuses",
     *      requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function changeStatuses(CustomerRequest $customerRequest, Request $request): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        try{
            $view = $this->view([
                'data' => $this->requestService->changeStatuses($request),
            ]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }
        return $this->handleView($view);
    }

    /**
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/restore-status",
     *     name="trol.rest.customer_request.restore-status",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function restoreStatus(CustomerRequest $customerRequest): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        if($this->requestService->isRestoreStatusTimeout($customerRequest->getStatusChangeTimestamp())){
            throw new AccessDeniedException();
        }
        $this->requestService->restoreStatus($customerRequest);
        $this->setAccessFlags($customerRequest);
        $view = $this->view(['data' => $customerRequest]);
        $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        return $this->handleView($view);
    }

    /**
     * @param CustomerRequest $customerRequest
     * @param Request         $request
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/prevalidate-status",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function preValidateStatus(CustomerRequest $customerRequest, Request $request): Response
    {
        $this->denyAccessUnlessGranted('manage', $customerRequest);
        try{
            $this->requestService->preValidateStatus($customerRequest, $request);
            $view = $this->view(['data' => $customerRequest]);
            $view->getContext()->setGroups(['Default']);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @param Request         $request
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/substitute",
     *     name="trol.rest.customer_request.substitute.change",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST"}
     * )
     */
    public function changeSubstitute(CustomerRequest $customerRequest, Request $request): Response
    {
        $this->denyAccessUnlessGranted('assign_substitute', $customerRequest);
        try{
            $this->requestService->changeSubstitute($customerRequest, $request);
            $this->setAccessFlags($customerRequest);
            $view = $this->view(['data' => $customerRequest]);
            $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        }catch(FormValidationException $exception){
            $view = $this->view($exception->getForm());
        }

        return $this->handleView($view);
    }

    /**
     * @param CustomerRequest $customerRequest
     *
     * @return Response
     *
     * @Route(
     *     "/requests/{customerRequest}/mark-complex-request",
     *     requirements={
     *         "customerRequest":"\d+"
     *     },
     *     methods={"POST","GET"}
     * )
     */
    public function markComplexRequest(CustomerRequest $customerRequest): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CUST_REQUEST_COMPLEX');
        $this->requestService->markComplexRequest($customerRequest);
        $this->setAccessFlags($customerRequest);
        $view = $this->view(['data' => $customerRequest]);
        $view->getContext()->setGroups(CustomerRequest::PROPERTIES_GROUPS);
        return $this->handleView($view);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            CustomerRequestRepository::class,
        ]);
    }
}
