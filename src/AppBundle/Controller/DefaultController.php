<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     * @Route("/data.js")
     */
    public function jsAction(Request $request)
    {
       $response = new Response();
       
       $domain = $request->getHost();
       $data = [
           'id' => uniqid(),
           'tor' => 0
       ];
       
       $response->setContent("
window.adsharesData=window.adsharesData||{};   
adsharesData[". json_encode($domain) . "] = ". json_encode($data) 
);
       
       return $response;
    }
    
    /**
     * @Route("/get_data/{id}")
     */
    public function dataAction(Request $request, $id)
    {
        $response = new JsonResponse();
        
        $response->setData([
            'id' => $id,
            'tor' => 0
        ]);
        
        return $response;
    }
}
