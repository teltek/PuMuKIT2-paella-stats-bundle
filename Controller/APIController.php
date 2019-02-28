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
use Pumukit\PaellaStatsBundle\Document\Geolocation;


/**
 * @Route("/paella")
 */
class APIController extends Controller
{


    /**
     * @Route("/save_single/{videoID}", requirements={"in": "\d+", "out": "\d+"})
     * @Method("POST")
     * @Security("is_granted('IS_AUTHENTICATED_ANONYMOUSLY')")
     */
    public function saveSingleAction(Request $request, $videoID)
    {

        $this->saveAction($request, $videoID, $request->get('in'), $request->get('out'));

        return new JsonResponse(
            array(
                'id' => $videoID
            )
        );
    }


    /**
     * @Route("/save_group/{videoID}")
     * @Method("POST")
     * @Security("is_granted('IS_AUTHENTICATED_ANONYMOUSLY')")
     */
    public function saveGroupAction(Request $request, $videoID)
    {
        $intervals = $request->get('intervals');

        if (is_array($intervals)){
            foreach ($intervals as $interval){
                if(isset($interval['in']) && isset($interval['out'])){
                    $this->saveAction($request, $videoID, $interval['in'], $interval['out']);
                }
            }
        }

        return new JsonResponse(
            array(
                'id' => $videoID
            )
        );
    }


    /**
     * @Route("/process_audience.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function processAudience(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($processed, $total) = $viewsService->processAudienceUserAction();

        $log = array(
            'processed' => $processed,
            'total' => $total,
            'date' => new \DateTime('now')
        );

        $data = $serializer->serialize($log, $request->getRequestFormat());

        return new Response($data);
    }


	/**
     * @Route("/get_audience/{videoID}")
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function getAudience(Request $request, $videoID)
    {
        return new Response("TO-DO");
    }


    /**
     * @Route("/test.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_ANONYMOUSLY')")
     */
    public function testAction(Request $request)
    {
        return new Response("hello world!");
    }


    /**
     * @Route("/mmobj/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function mmobjMostViewedAction(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mostViewed, $total) = $viewsService->getMmobjsMostViewed($criteria, $options);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'mmobjs' => $mostViewed,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }


    /**
     * @Route("/series/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function seriesMostViewedAction(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mostViewed, $total) = $viewsService->getSeriesMostViewed($criteria, $options);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'series' => $mostViewed,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }


     /**
     * @Route("/views.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
	 * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function viewsAction(Request $request)
    {

        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        //NOTE: $criteria is the same as $criteria_mmobj to provide backwards compatibility.
        $criteria_mmobj = $request->get('criteria_mmobj') ?: $criteria;
        $criteria_series = $request->get('criteria_series') ?: array();

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;
        $options['group_by'] = $groupBy;
        $options['criteria_mmobj'] = $criteria_mmobj;
        $options['criteria_series'] = $criteria_series;

        list($views, $total) = $viewsService->getTotalViewedGrouped($options);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => array(
                'criteria_mmobj' => $criteria_mmobj,
                'criteria_series' => $criteria_series,
            ),
            'sort' => $sort,
            'group_by' => $groupBy,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'views' => $views,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }


    /**
     * @Route("/most_used_agents.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function mostUsedAgentsAction(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mostUsed, $total) = $viewsService->getMostUsedAgents($criteria, $options);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'm_used' => $mostUsed,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }


    /**
     * @Route("/city_from_most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function cityFromMostViewedAction(Request $request)
    {
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_paella_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mostViewed, $total) = $viewsService->getCityFromMostViewed($criteria, $options);

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

        $viewsService = $this->get('pumukit_paella_stats.stats');

        $ip = $request->getClientIp();
        $userAgent =  $request->server->get('HTTP_USER_AGENT');
        $user = ($this->getUser()) ? $this->getUser()->getId() : null;
        $session = new Session();
        $session = $session->getId();
        $isLive = json_decode($request->get('isLive'));


        $series = $viewsService->getSerieFromVideo($multimediaObject);

        $userAction = new UserAction($ip, $session, $userAgent, $multimediaObject, $series, $in, $out, $isLive, $user);
/*
        try {

            $record = $this->get('geoip2.reader')->city($ip);

            $userGeolocation = new Geolocation();
            $userGeolocation->setContinent( $record->continent->name);
            $userGeolocation->setContinentCode($record->continent->code);
            $userGeolocation->setCountry($record->country->name);
            $userGeolocation->setCountryCode($record->country->isoCode);
            $userGeolocation->setSubCountry($record->mostSpecificSubdivision->name);
            $userGeolocation->setSubCountryCode($record->mostSpecificSubdivision->isoCode);
            $userGeolocation->setCity($record->city->name);
            $userGeolocation->setLatitude($record->location->latitude);
            $userGeolocation->setLongitude($record->location->longitude);
            $userGeolocation->setTimeZone($record->location->timeZone);
            $userGeolocation->setPostal($record->postal->code);

            $userAction->setGeolocation($userGeolocation);

        } catch (\Exception $e) {}
*/

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
