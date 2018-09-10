<?php
namespace Aakron\Bundle\GeneratePdfBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
class PdfController extends Controller
{
    protected $knpSnappy;
    protected $customEntityManager;
    public function __construct()
    {
        $this->knpSnappy=null;
        $this->customEntityManager=null;
    }
    
    public function __destruct()
    {
        unset($this->knpSnappy);
        unset($this->customEntityManager);
    }
    
    /**
     * @Route("/generate-pdf/{entityName}/{id}", name="aakron_generate_pdf")
     */
    public function generatePdfByIdAction($entityName,$id)
    {
        $this->initAction();
      
        $filename = $this->generatePDFName($entityName,$id);
        $parameters = $this->getEntityData($entityName,$id);           
        
        $html = $this->generateHtmlForPdf($parameters);
 
        return new Response(            
                $this->knpSnappy->getOutputFromHtml($html),200,array(                
                    'Content-Type'          => 'application/pdf',                
                    'Content-Disposition'   => 'attachment; filename="'.$filename.'.pdf"'                
                )            
            );
    }
    private function getEntityData($entityName,$id)
    {
        $parametersArray = array();
       $quoteClass = $this->getParameter("entity_manager.".$entityName);
       $parametersObject = $this->getDoctrine()->getRepository($quoteClass)->getQuote($id);
       //dump($parametersObject);exit;
       $quote["customer_number"] = $parametersObject->getCustomer()->getAccountNumber()??"";
       $quote["quote_id"] = $parametersObject->getId()??"0";
       $quote["company"] = $parametersObject->getCustomer()->getName()??"";
       $quote["contact_name"] = $this->getContactName($parametersObject->getCustomerUser()??"");
       $quote["email"] = $parametersObject->getEmail()??"";
       $quote["address"] = "";
       $quote["phone"] = $this->getCustomerPhone($parametersObject->getShippingAddress()??"");   
       $quote["date"] = $parametersObject->getCreatedAt()??"";
       $quote["powered_by"] = $this->getPowerdBy($parametersObject->getOwner()??"");
       $quote["quote_status"] = $parametersObject->getQuoteStatus()??"";
       $quote["fob"] = $parametersObject->getFob()??"";
       $quote["additional_info"] = $parametersObject->getQuoteAdditionalNote()??"";
       $quote["products"] = array();
       $quote["address"] = $this->getAddress($parametersObject->getShippingAddress()??"");   
   
       foreach($parametersObject->getQuoteProducts() as $key=>$products)
       {           
           $productId=$products->getId();
           
           $quote["products"][$productId]["sku"]= $products->getProductSku()??"";
           $quote["products"][$productId]["comment"] = $products->getComment()??"";
           $quote["products"][$productId]["id"] = $products->getId()??"";
           $quote["products"][$productId]["name"]=$products->getProductName()??"";
           $quote["products"][$productId]["image"]=$this->getProductImage($quote["products"][$productId]["sku"])??"";
                      
           $productSlug = $this->getProductAPIData($quote["products"][$productId]["sku"]);
           
           $quote["products"][$productId]["product_url"]=$this->getParameter("aakron_product_url").$productSlug;
           $customProductDataArray = $this->getProductData($productSlug);
           
           $quote["products"][$productId]["setup_charge"]= $customProductDataArray["setup_charge"]??"";
           $quote["products"][$productId]["pricint_includes"]= $customProductDataArray["pricint_includes"]??"";
           foreach($products->getQuoteProductOffers() as $productOffers)
           {
               $productArray["quantity"]=$productOffers->getQuantity()??"";
               $productArray["price"]=$productOffers->getPrice()->getValue()??"";
               $productArray["currency"]=$productOffers->getPrice()->getCurrency()??"";
               $productArray["name"]=$products->getProductName()??"";
               $productArray["sku"]=$productOffers->getProductSku()??"";
               $productArray["sub_total"]=($productArray["price"])*($productArray["quantity"]);

               $quote["grid_products"][$productId] = $productArray;
              
               unset($productArray);               
           }

       }
       $poid=0;

       return $quote;
    }
    private function getAddress($address)
    {        
        if($address)
        {
            $addressArray["street1"] = $address->getStreet()??"";
            $addressArray["street2"] = $address->getStreet2()??"";
            $addressArray["city"] = $address->getCity()??"";
           // $addressArray["country_code"] = $address->getCountry()->getIso2Code()??"";
            $addressArray["region_code"] = ($address->getRegion()->getCode()??"")." ".$address->getPostalCode()??"";
            
            $addressArray =  array_filter($addressArray);
            
            return implode(", ",$addressArray);
        }
        
        return "";
    }
    private function getCustomerPhone($address)
    {
        if($address)
        {
            return $address->getPhone()??"";
        }
        
        return "";        
    }
    private function getPowerdBy($owner)
    {
        
        $poweredBy = array();
        $poweredBy["fname"] = $owner->getFirstName()??"";
        $poweredBy["mname"] = $owner->getMiddleName()??"";
        $poweredBy["lname"] = $owner->getLastName()??"";
        $poweredBy =  array_filter($poweredBy);
     
        return implode(" ",$poweredBy);
    }
    private function getContactName($ustomerUser)
    {
        $customer = array();
        $customer["fname"] = $ustomerUser->getFirstName()??"";
        $customer["mname"] = $ustomerUser->getMiddleName()??"";
        $customer["lname"] = $ustomerUser->getLastName()??"";
        $customer =  array_filter($customer);
        
        return implode(" ",$customer);
    }
    private function getProductImage($sku)
    {
        $searchProduct = "sku_s:".$sku;
        
        $url = $this->getParameter("aakron.solr.url")."q=*:*&fq=".$searchProduct."&fl=image_path_t&wt=json&indent=true";
        
        $responseData = $this->get('api_caller')->call(new HttpGetJson($url,array()));
        $responseData = $this->objectToArray($responseData);
        
        return $responseData["response"]["docs"][0]["image_path_t"];
    }
    public function getProductData($slug)
    {
        $apiUrl = $this->getParameter("ob_product_api");
        $responseData = $this->get('api_caller')->call(new HttpGetJson($apiUrl."/product",array("_format"=>"json","product_slug"=>$slug,"culture"=>"en_us")));
        $responseData = $this->objectToArray($responseData);
        $customResponse = array();
        if(isset($responseData["imprint_information"]["imprint_method_information"]))
        {
            $first = array_shift($responseData["imprint_information"]["imprint_method_information"]);
            if(isset($first["charges"]["SETUP_CHARGE"]["code"]) && isset($first["charges"]["SETUP_CHARGE"]["charge"]))
            {
                
                $customResponse["setup_charge"]=$first["charges"]["SETUP_CHARGE"]["charge"]." (".$first["charges"]["SETUP_CHARGE"]["code"].")";
            }
           
        }
        if(isset($responseData["feature_details"]))
        {
            foreach($responseData["feature_details"] as $key=>$value)
            {
                if($value["label"]=="Pricing Includes")
                {
                    $customResponse["pricint_includes"]=$value["value"];
                }
            }
        }
        
        
        return $customResponse;
    }
    public function objectToArray($data)
    {
        return json_decode(json_encode($data), True);
    }
    private function getProductAPIData($sku)
    {
        $searchProduct = "sku_s:".$sku;
        
        $url = $this->getParameter("aakron.solr.url")."q=*:*&fq=".$searchProduct."&fl=slug_t&wt=json&indent=true";
        
        $responseData = $this->get('api_caller')->call(new HttpGetJson($url,array()));
        $responseData = $this->objectToArray($responseData);
        
        $slug = $responseData["response"]["docs"][0]["slug_t"]??"";
        
//         if($slug!="")
//         {
//             return $this->getProductData($slug);
//         }
        return $slug;
    }
    private function initAction()
    {
        $this->knpSnappy = $this->get('knp_snappy.pdf');
    }
    private function generatePDFName($entityName,$id)
    {
        return $entityName."_".base64_encode( $entityName."_".$id."_".time());
    }
    private function generateHtmlForPdf($parameters)
    {
        return $this->renderView('AakronGeneratePdfBundle:template:quote.html.twig',array( "data"=>$parameters));
    }
}
