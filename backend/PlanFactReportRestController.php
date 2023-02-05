<?php


namespace App\ReportsBundle\Controller;

use App\ReportsBundle\QueryObjects\PlanFactReportQueryObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Controller\BaseRestController;
use Symfony\Component\Routing\Annotation\Route;

class PlanFactReportRestController extends BaseRestController
{

    /**
     * @var PlanFactReportQueryObject
     */
    private $planFactReportQueryObject;

    public function __construct(PlanFactReportQueryObject $planFactReportQueryObject)
    {
        $this->planFactReportQueryObject = $planFactReportQueryObject;
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/plan-fact-report", name="trol.reports.plan-fact-report.generate", methods={"GET","POST"})
     */
    public function getReportAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_PLAN_FACT_REPORT');
        $submittedData = json_decode($request->getContent());
        $this->planFactReportQueryObject
            ->setOptions($submittedData);

        $view = $this->view([
            'data' => [
                'page' => $this->planFactReportQueryObject->fetch(),
            ],
        ]);
        return $this->handleView($view);
    }
}
