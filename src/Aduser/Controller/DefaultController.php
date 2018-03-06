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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

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
     * @Route("/setimg/{id}", name="pixel")
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
     * @Route("/get/{id}", name="get_data")
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
     * @Route("/info")
     */
    public function schemaAction(Request $request)
    {
        $response = new JsonResponse();
        
        $schema = <<<EOF
        [
        {
            "label": "Site",
            "key":"site",
            "children": [
            {
                "label": "Site domain",
                "key": "domain",
                "values": [
                {"label": "coinmarketcap.com", "value": "coinmarketcap.com"},
                {"label": "icoalert.com", "value": "icoalert.com"}
                ],
                "value_type": "string",
                "allow_input": true
            },
            {
                "label": "Inside frame",
                "key": "inframe",
                "value_type": "boolean",
                "values": [
                {"label": "Yes", "value": "true"},
                {"label": "No", "value": "false"}
                ],
                "allow_input": false
            },
            {
                "label": "Language",
                "key": "lang",
                "values": [
                {"label": "Polish", "value": "pl"},
                {"label": "English", "value": "en"},
                {"label": "Italian", "value": "it"},
                {"label": "Japanese", "value": "jp"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Content keywords",
                "key": "keywords",
                "values": [
                {"label": "blockchain", "value": "blockchain"},
                {"label": "ico", "value": "ico"}
                ],
                "value_type": "string",
                "allow_input": true
            }
            ]
        },
        {
            "label": "User",
            "key":"user",
            "children": [
            {
                "label": "Age",
                "key": "age",
                "values": [
                {"label": "18-35", "value": "18,35"},
                {"label": "36-65", "value": "36,65"}
                ],
                "value_type": "number",
                "allow_input": true
            },
            {
                "label": "Interest keywords",
                "key": "keywords",
                "values": [
                {"label": "blockchain", "value": "blockchain"},
                {"label": "ico", "value": "ico"}
                ],
                "value_type": "string",
                "allow_input": true
            },
            {
                "label": "Language",
                "key": "lang",
                "values": [
                {"label": "Polish", "value": "pl"},
                {"label": "English", "value": "en"},
                {"label": "Italian", "value": "it"},
                {"label": "Japanese", "value": "jp"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Gender",
                "key": "gender",
                "values": [
                {"label": "Male", "value": "pl"},
                {"label": "Female", "value": "en"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Geo",
                "key":"geo",
                "children": [
                {
                    "label": "Continent",
                    "key": "continent",
                    "values": [
                    {"label": "Africa", "value": "af"},
                    {"label": "Asia", "value": "as"},
                    {"label": "Europe", "value": "eu"},
                    {"label": "North America", "value": "na"},
                    {"label": "South America", "value": "sa"},
                    {"label": "Oceania", "value": "oc"},
                    {"label": "Antarctica", "value": "an"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Country",
                    "key": "country",
                    "values": [
                    {"label": "United States", "value": "us"},
                    {"label": "Poland", "value": "pl"},
                    {"label": "Spain", "value": "eu"},
                    {"label": "China", "value": "cn"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                }
                ]
            }
            ]
        },
        {
            "label": "Device",
            "key":"device",
            "children": [
            {
                "label": "Screen size",
                "key":"screen",
                "children": [
                {
                    "label": "Width",
                    "key": "width",
                    "values": [
                    {"label": "1200 or more", "value": "<1200,>"},
                    {"label": "between 1200 and 1800", "value": "<1200,1800>"}
                    ],
                    "value_type": "number",
                    "allow_input": true
                },
                {
                    "label": "Height",
                    "key": "height",
                    "values": [
                    {"label": "1200 or more", "value": "<1200,>"},
                    {"label": "between 1200 and 1800", "value": "<1200,1800>"}
                    ],
                    "value_type": "number",
                    "allow_input": true
                }
                ]
            },
            {
                "label": "Language",
                "key": "lang",
                "values": [
                {"label": "Polish", "value": "pl"},
                {"label": "English", "value": "en"},
                {"label": "Italian", "value": "it"},
                {"label": "Japanese", "value": "jp"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Browser",
                "key": "browser",
                "values": [
                {"label": "Chrome", "value": "Chrome"},
                {"label": "Edge", "value": "Edge"},
                {"label": "Firefox", "value": "Firefox"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Operating system",
                "key": "os",
                "values": [
                {"label": "Linux", "value": "Linux"},
                {"label": "Mac", "value": "Mac"},
                {"label": "Windows", "value": "Windows"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Geo",
                "key":"geo",
                "children": [
                {
                    "label": "Continent",
                    "key": "continent",
                    "values": [
                    {"label": "Africa", "value": "af"},
                    {"label": "Asia", "value": "as"},
                    {"label": "Europe", "value": "eu"},
                    {"label": "North America", "value": "na"},
                    {"label": "South America", "value": "sa"},
                    {"label": "Oceania", "value": "oc"},
                    {"label": "Antarctica", "value": "an"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Country",
                    "key": "country",
                    "values": [
                    {"label": "United States", "value": "us"},
                    {"label": "Poland", "value": "pl"},
                    {"label": "Spain", "value": "eu"},
                    {"label": "China", "value": "cn"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                }
                ]
            },
            {
                "label": "Javascript support",
                "key": "js_enabled",
                "value_type": "boolean",
                "values": [
                {"label": "Yes", "value": "true"},
                {"label": "No", "value": "false"}
                ],
                "allow_input": false
            }
            ]
        }
        ]
EOF;
        $router = $this->container->get('router');
        assert($router instanceof Router);
        
        $response->setData([
                'pixel_url' => $router->generate('pixel', ['id' => ':id'] , UrlGeneratorInterface::ABSOLUTE_URL),
                'data_url' => $router->generate('get_data', ['id' => ':id'] , UrlGeneratorInterface::ABSOLUTE_URL),
                'schema' => json_decode($schema)
            ]
        );
        
        return $response;
    }
}
