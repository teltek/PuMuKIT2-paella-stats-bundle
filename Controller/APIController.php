<?php

namespace Pumukit\PaellaStatsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Pumukit\PaellaStatsBundle\Document\UserAction;


/**
 * @Route("/paella")
 */
class APIController extends Controller
{

    /**
     * @Route("/group/{idVideo}")
     * @Method("POST")
     * @Security("is_granted('IS_AUTHENTICATED_ANONYMOUSLY')")
     */
    public function groupSaveAction(Request $request, $idVideo)
    {

        $intervals = $request->get('intervals');

        if (is_array($intervals) && $idVideo){
            foreach ($intervals as $interval){
                if($interval['in'] && $interval['out']){
                    $this->saveAction($request, $idVideo, $interval['in'], $interval['out']);
                }
            }
        }

        return new JsonResponse(
            array(
                'id' => $idVideo
            )
        );
    }



    /**
     * @Route("/single/{idVideo}")
     * @Method("POST")
     * @Security("is_granted('IS_AUTHENTICATED_ANONYMOUSLY')")
     */
    public function singleSaveAction(Request $request, $idVideo)
    {

        if($idVideo){
            $this->saveAction($request, $idVideo, $request->get('in'), $request->get('out'));
        }

        return new JsonResponse(
            array(
                'id' => $idVideo
            )
        );
    }



    /**
     * @Route("/test.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function testAction(Request $request)
    {
        return new Response("hello world!");
    }


    /**
     * @Route("/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function mostViewedAction(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mostViewed, $total) = $viewsService->getMostViewed($criteria, $options);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'm_viewed' => $mostViewed,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }



    /**
     * @Route("/most_used_browser.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function mostUsedBrowserAction(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mostViewed, $total) = $viewsService->getMostUsedBrowser($criteria, $options);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'm_viewed' => $mostViewed,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }



    private function saveAction(Request $request, $multimediaObject, $in, $out){

        $ip = $request->getClientIp();
        $userAgent =  $request->server->get('HTTP_USER_AGENT');
        $user = ($this->getUser()) ? $this->getUser()->getId() : null;
        $session = new Session();
        $session = $session->getId();
        $isLive = json_decode($request->get('isLive'));

        $userAction = new UserAction($ip, $session, $userAgent, $multimediaObject, $in, $out, $isLive, $user);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($userAction);
        $dm->flush();
    }

    protected function processRequestData(Request $request)
    {
        $MAX_LIMIT = 1000;

        //Request variables.
        $criteria = $request->get('criteria') ?: array();
        $sort = intval($request->get('sort'));
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $limit = intval($request->get('limit'));
        $page = intval($request->get('page')) ?: 0;

        //Processing variables.
        if (!$limit || $limit > $MAX_LIMIT) {
            $limit = $MAX_LIMIT;
        }

        if (!in_array($sort, array(1, -1))) {
            $sort = -1;
        }

        if (!strpos($fromDate, 'T')) {
            $fromDate .= 'T00:00:00';
        }
        if (!strpos($toDate, 'T')) {
            $toDate .= 'T23:59:59';
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate)?:null;
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate)?:null;

        return array($criteria, $sort, $fromDate, $toDate, $limit, $page);
    }
}