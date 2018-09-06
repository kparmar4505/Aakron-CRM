<?php

namespace Aakron\Bundle\CscApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;
use Lsw\ApiCallerBundle\Call\HttpPostJsonBody as HttpPostJsonBody;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
use Lsw\ApiCallerBundle\Call\HttpPostJson as HttpPostJson;

class DefaultController extends Controller
{
    /**
     * @Route("/aakron-contacts", name="insert_sql")
     */
    public function aakronContactsAction()
    {
        
        $response = $this->get('aakron_import_contact_api')->syncCscContacts();
//         echo "<pre>";
//         print_r($response);
//         $response = new Response();
//         $serializer = $this->get('jms_serializer');
        
//         // set header content type as json for all response.
//         $response->headers->set('Content-Type', 'application/json');
//         $response->setStatusCode(200);
        
//         $response->setContent($serializer->serialize($returnData, 'json'));
        return $response;
    }
    
    /**
     * @Route("/myindex", name="my_index")
     */
    public function myindexAction()
    {
        $test = $this->get('aakron_import_customer_api')->generatAuthentication();
        echo "<pre>";
        print_r($test);exit;
        return $this->render('AakronCscApiBundle:Default:index.html.twig');
    }
    /**
     * @Route("/admin/product-details/{id}", name="aakron_product_details")
     */
    public function getProductDetailsAction($id)
    {
        $pid=$this->getProductID($id);
        $apiCallerManager = $this->get('api_caller');  
        $apiUrl = $this->getParameter("ob_product_api");
        $responseData = $apiCallerManager->call(new HttpGetJson($apiUrl."/product",array("_format"=>"json","product_ids"=>$pid,"culture"=>"en_us")));
     
        $response = new Response();
        $serializer = $this->get('jms_serializer');
        $response->headers->set('Content-Type', 'application/json');
        $response->setStatusCode("200");
      //  $response->setContent($serializer->serialize(json_decode(json_encode($responseData), True), 'json'));
        
        $myresponse = json_decode(json_encode($responseData), True);
     //   print_r($myresponse);exit;
        $response->setContent($serializer->serialize($myresponse[$pid], 'json'));
        
        return $response;
    }
    
    public function getProductID($sku)
    {
        $searchProduct = "sku_s:".$sku;
        
        $url = $this->getParameter("aakron.solr.url")."q=*:*&fq=".$searchProduct."&fl=product_id_i&wt=json&indent=true";
        
        $responseData = $this->get('api_caller')->call(new HttpGetJson($url,array()));
        $json  = json_encode($responseData);
        $responseData = json_decode($json, true);        
        return $responseData["response"]["docs"][0]["product_id_i"];
    }
}
