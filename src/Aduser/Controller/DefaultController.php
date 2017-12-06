<?php
namespace Aduser\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Aduser\Helper\Utils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Aduser\Entity\User;
use Aduser\Entity\RequestLog;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
        ]);
    }

    /**
     * @Route("/setimg/{id}")
     */
    public function setAction(Request $request, $id)
    {   
        if ($request->query->get('r')) {
            $url = Utils::UrlSafeBase64Decode($request->query->get('r'));
            
            $response = new RedirectResponse($url);
        } else {
            $response = new Response();
            
            // transparent 1px gif
            $response->setContent(base64_decode('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='));
            $response->headers->set('Content-Type', 'image/gif');
        }
        
        $encodedId = Utils::attachTrackingCookie($this->getParameter('secret'), $request, $response, "", new \DateTime());
        $uid = Utils::getRawTrackingId($encodedId);
        
        $id = Utils::getRawTrackingId($id);
        
//         var_dump($id);
//         exit;
        
        $em = $this->getDoctrine()->getManager();
        $user = User::getRepository($em)->find($uid);
        
        if(!$user) {
            $user = new User();
            $user->setUid($uid);
            $em->persist($user);
        }
        
        $user->setVisits($user->getVisits()+1);
        
        $log = new RequestLog();
        $log->setId($id);
        $log->setUid($uid);
        $log->setUserAgent($request->headers->get('User-Agent'));
        $log->setReferer($request->headers->get('Referer'));
        $log->setHeaders($request->headers->__toString());
        
        $logIp = bin2hex(inet_pton($request->getClientIp()));
        $log->setIp($logIp);
        $log->setTimestamp(time());
        
        $em->persist($log);
        
        try {
            $em->flush();
        } catch(\Exception $e) {}
        
        
        return $response;
    }

    /**
     * @Route("/get/{id}")
     */
    public function dataAction(Request $request, $id)
    {
        $response = new JsonResponse();
        
        $decodedId = Utils::getRawTrackingId($id);
        
//         var_dump($decodedId);
//         exit;
        
        $em = $this->getDoctrine()->getManager();
        $log = RequestLog::getRepository($em)->find($decodedId);
        
        if(!$log) {
            throw new NotFoundHttpException();
        }
        
        $user = User::getRepository($em)->find($log->getUid());
        $user instanceof User;
        
        if(!$user) {
            throw new NotFoundHttpException();
        }
        
        $response->setData([
            'user_id' => $user->getUid(),
            'request_id' => $id,
            'human_score' => 0.5,
            'keywords' => [
                'tor' => false, 
                'age' => 24,
                'visits' => $user->getVisits(),
            ],
        ]);
        
        return $response;
    }
    
    /**
     * @Route("/schema")
     */
    public function schemaAction(Request $request)
    {
        $response = new JsonResponse();
        
        $response->setData([
            [
                "label" => "Use Tor network",
                "key" => "tor",
                "values" => [
                    ["label" =>  "Yes", "value" => true],
                    ["label" => "No", "value" => false]
                ],
                "value_type" => "boolean",
                "allow_input" => false
            ],
            [
                "label" => "User age",
                "key" => "age",
                "values" => [
                    ["label" => "19 - 35", "value" => "19,36"],
                    ["label" => "over 65", "value" => "65,"],
                ],
                "value_type" => "number",
                "allow_input" => true
            ],
            [
                "label" => "Number of visits",
                "key" => "visits",
                "value_type" => "number",
                "allow_input" => true
            ],
        ]);
        
        return $response;
    }
}
