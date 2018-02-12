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
                {"label": "coinmarketcap.com", "value": "coinmarketcap.com", "key": "domain_coinmarketcap.com", "parent_label": "Site domain"},
                {"label": "icoalert.com", "value": "icoalert.com", "key": "domain_icoalert.com", "parent_label": "Site domain"}
                ],
                "value_type": "string",
                "allow_input": true
            },
            {
                "label": "Inside frame",
                "key": "inframe",
                "value_type": "boolean",
                "values": [
                {"label": "Yes", "value": "true", "key": "inframe_yes", "parent_label": "Inside frame"},
                {"label": "No", "value": "false", "key": "inframe_no", "parent_label": "Inside frame"}
                ],
                "allow_input": false
            },
            {
                "label": "Language",
                "key": "lang",
                "values": [
                {"label": "Polish", "value": "pl", "key": "site_lang_pol", "parent_label": "Site Language"},
                {"label": "English", "value": "en", "key": "site_lang_en", "parent_label": "Site Language"},
                {"label": "Italian", "value": "it", "key": "site_lang_it", "parent_label": "Site Language"},
                {"label": "Japanese", "value": "jp", "key": "site_lang_jp", "parent_label": "Site Language"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Content keywords",
                "key": "keywords",
                "values": [
                {"label": "blockchain", "value": "blockchain", "key": "keywords_blockchain", "parent_label": "Content keywords"},
                {"label": "ico", "value": "ico", "key": "keywords_ico", "parent_label": "Content keywords"}
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
                {"label": "18-35", "value": "18,35", "key": "user_age_18", "parent_label": "Age"},
                {"label": "36-65", "value": "36,65", "key": "user_age_35", "parent_label": "Age"}
                ],
                "value_type": "number",
                "allow_input": true
            },
            {
                "label": "Interest keywords",
                "key": "keywords",
                "values": [
                {"label": "blockchain", "value": "blockchain", "key": "keywords_blockchain", "parent_label": "Interest keywords"},
                {"label": "ico", "value": "ico", "key": "keywords_ico", "parent_label": "Interest keywords"}
                ],
                "value_type": "string",
                "allow_input": true
            },
            {
                "label": "Language",
                "key": "lang",
                "values": [
                {"label": "Polish", "value": "pl", "key": "user_lang_pol", "parent_label": "User Language"},
                {"label": "English", "value": "en", "key": "user_lang_en", "parent_label": "User Language"},
                {"label": "Italian", "value": "it", "key": "user_lang_it", "parent_label": "User Language"},
                {"label": "Japanese", "value": "jp", "key": "user_lang_jp", "parent_label": "User Language"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Gender",
                "key": "gender",
                "values": [
                {"label": "Male", "value": "pl", "key": "gender_female", "parent_label": "Gender"},
                {"label": "Female", "value": "en", "key": "gender_male", "parent_label": "Gender"}
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
                    {"label": "Africa", "value": "af", "key": "user_continent_af", "parent_label": "User Continent"},
                    {"label": "Asia", "value": "as", "key": "user_continent_as", "parent_label": "User Continent"},
                    {"label": "Europe", "value": "eu", "key": "user_continent_eu", "parent_label": "User Continent"},
                    {"label": "North America", "value": "na", "key": "user_continent_na", "parent_label": "User Continent"},
                    {"label": "South America", "value": "sa", "key": "user_continent_sa", "parent_label": "User Continent"},
                    {"label": "Oceania", "value": "oc", "key": "user_continent_oc", "parent_label": "User Continent"},
                    {"label": "Antarctica", "value": "an", "key": "user_continent_an", "parent_label": "User Continent"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Country",
                    "key": "country",
                    "values": [
                    {"label": "United States", "value": "us", "key": "user_country_us", "parent_label": "User Country"},
                    {"label": "Poland", "value": "pl", "key": "user_country_pl", "parent_label": "User Country"},
                    {"label": "Spain", "value": "eu", "key": "user_country_spain", "parent_label": "User Country"},
                    {"label": "China", "value": "cn", "key": "user_country_cn", "parent_label": "User Country"}
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
                    {"label": "1200 or more", "value": "<1200,>", "key": "width_1200", "parent_label": "Width"},
                    {"label": "between 1200 and 1800", "value": "<1200,1800>", "key": "width_1200_1800", "parent_label": "Width"}
                    ],
                    "value_type": "number",
                    "allow_input": true
                },
                {
                    "label": "Height",
                    "key": "height",
                    "values": [
                    {"label": "1200 or more", "value": "<1200,>", "key": "height_1200", "parent_label": "Height"},
                    {"label": "between 1200 and 1800", "value": "<1200,1800>", "key": "height_1200_1800", "parent_label": "Height"}
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
                {"label": "Polish", "value": "pl", "key": "device_lang_pol", "parent_label": "Device Language"},
                {"label": "English", "value": "en", "key": "device_lang_en", "parent_label": "Device Language"},
                {"label": "Italian", "value": "it", "key": "device_lang_it", "parent_label": "Device Language"},
                {"label": "Japanese", "value": "jp", "key": "device_lang_jp", "parent_label": "Device Language"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Browser",
                "key": "browser",
                "values": [
                {"label": "Chrome", "value": "Chrome", "key": "browser_chrome", "parent_label": "Browser"},
                {"label": "Edge", "value": "Edge", "key": "browser_edge", "parent_label": "Browser"},
                {"label": "Firefox", "value": "Firefox", "key": "browser_firefox", "parent_label": "Browser"}
                ],
                "value_type": "string",
                "allow_input": false
            },
            {
                "label": "Operating system",
                "key": "os",
                "values": [
                {"label": "Linux", "value": "Linux", "key": "os_linux", "parent_label": "Operating system"},
                {"label": "Mac", "value": "Mac", "key": "os_mac", "parent_label": "Operating system"},
                {"label": "Windows", "value": "Windows", "key": "os_windows", "parent_label": "Operating system"}
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
                    {"label": "Africa", "value": "af", "key": "device_continent_af", "parent_label": "Device Continent"},
                    {"label": "Asia", "value": "as", "key": "device_continent_as", "parent_label": "Device Continent"},
                    {"label": "Europe", "value": "eu", "key": "device_continent_eu", "parent_label": "Device Continent"},
                    {"label": "North America", "value": "na", "key": "device_continent_na", "parent_label": "Device Continent"},
                    {"label": "South America", "value": "sa", "key": "device_continent_sa", "parent_label": "Device Continent"},
                    {"label": "Oceania", "value": "oc", "key": "device_continent_oc", "parent_label": "Device Continent"},
                    {"label": "Antarctica", "value": "an", "key": "device_continent_an", "parent_label": "Device Continent"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                },
                {
                    "label": "Country",
                    "key": "country",
                    "values": [
                    {"label": "United States", "value": "us", "key": "device_country_us", "parent_label": "Device Country"},
                    {"label": "Poland", "value": "pl", "key": "device_country_pl", "parent_label": "Device Country"},
                    {"label": "Spain", "value": "eu", "key": "device_country_spain", "parent_label": "Device Country"},
                    {"label": "China", "value": "cn", "key": "device_country_cn", "parent_label": "Device Country"}
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
                {"label": "Yes", "value": "true", "key": "js_enabled_yes", "parent_label": "Javascript support"},
                {"label": "No", "value": "false", "key": "js_enabled_no", "parent_label": "Javascript support"}
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
