<?php
namespace Aduser\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Aduser\Helper\Utils;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        
        return $response;
    }

    /**
     * @Route("/get/{id}")
     */
    public function dataAction(Request $request, $id)
    {
        $response = new JsonResponse();
        
        $response->setData([
            'id' => $id,
            'uid' => uniqid(),
            'tor' => 0
        ]);
        
        return $response;
    }
}
