<?php

namespace Aakron\Bundle\SaleBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
use Lsw\ApiCallerBundle\Call\HttpPostJsonBody as HttpPostJsonBody;

class OroCronProductImportCommandCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:cron:product-import-command') 
            ->setDescription('Aakron Product Import')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('onetime-sync', null, InputOption::VALUE_REQUIRED, 'One time sync(1=true and 0=false)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importApiManager = $this->getContainer()->get('aakron_import_customer_api');
        $apiCallerManager = $this->getContainer()->get('api_caller');
        $oneTimeSync =(int) $input->getOption('onetime-sync', null);
     
        $argument = $input->getArgument('argument');

        if ($oneTimeSync==1) {
            $supplierIds=$this->getProjectSupplierIds();
            $supplierId = $supplierIds[0]["supplier_id"];
            $productCount = $supplierIds[0]["product_count"];
            $importLimit = 10;
           
            $output->writeln('Total Products = '.$productCount);
            for($i=0;$i<=$productCount;$i=$i+$importLimit)
            {
                $output->writeln('Product import  '.$i."<".$importLimit." == ".$productCount);
                $products = $this->getAllProductIds($supplierId,$i,$importLimit);
                $productCountSub = count($products["products"]);
                $this->importProductToCRM($productCountSub,$products["products"]);
            }
            
        }
        else {
            
        }

        $output->writeln('Command result.');
    }
    public function importProductToCRM($productCount,$products){
        if($productCount>0)
        {
            foreach($products as $product)
            { 
                if(isset($product["basic_information"]["sku_number"]))
                {
                    $postProductData["data"]["type"]='products';
                    //  $postProductData["data"]["attributes"]=$this->getAttribute($product);
                    $postProductData["data"]["attributes"]["sku"]=$product["basic_information"]["sku_number"];
                    $postProductData["data"]["attributes"]["status"]='enabled';
                    $postProductData["data"]["attributes"]["variantFields"]=array ();
                    $postProductData["data"]["attributes"]["productType"]='simple';
                    $postProductData["data"]["attributes"]["featured"]=true;
                    $postProductData["data"]["attributes"]["newArrival"]=false;
                    
                    $postProductData["data"]["relationships"]["category"]["data"]["type"]="categories";
                    $postProductData["data"]["relationships"]["category"]["data"]["id"]="1";
                    $postProductData["data"]["relationships"]["owner"]["data"]["type"]="businessunits";
                    $postProductData["data"]["relationships"]["owner"]["data"]["id"]="1";
                    $postProductData["data"]["relationships"]["organization"]["data"]["type"]="organizations";
                    $postProductData["data"]["relationships"]["organization"]["data"]["id"]="1";
                    
                    $postProductData["data"]["relationships"]["names"]["data"]["0"]["type"]="localizedfallbackvalues";
                    $postProductData["data"]["relationships"]["names"]["data"]["0"]["id"]="names-1";
                    
                    $postProductData["data"]["relationships"]["unitPrecisions"]["data"]["0"]["type"]="productunitprecisions";
                    $postProductData["data"]["relationships"]["unitPrecisions"]["data"]["0"]["id"]="product-unit-precision-id-1";
                    $postProductData["data"]["relationships"]["primaryUnitPrecision"]["data"]["type"]="productunitprecisions";
                    $postProductData["data"]["relationships"]["primaryUnitPrecision"]["data"]["id"]="product-unit-precision-id-1";
                    
                    $postProductData["data"]["relationships"]["attributeFamily"]["data"]["type"]="attributefamilies";
                    $postProductData["data"]["relationships"]["attributeFamily"]["data"]["id"]="1";
                    
                    $postProductData["data"]["relationships"]["inventory_status"]["data"]["type"]="prodinventorystatuses";
                    $postProductData["data"]["relationships"]["inventory_status"]["data"]["id"]="out_of_stock";
                    
                    $postProductData["data"]["relationships"]["slugPrototypes"]["data"]["0"]["type"]="localizedfallbackvalues";
                    $postProductData["data"]["relationships"]["slugPrototypes"]["data"]["0"]["id"]="slug-prototype-1";
                    
                    
                    
                    $postProductData["included"]["0"]["type"]="localizedfallbackvalues";
                    $postProductData["included"]["0"]["id"]="names-1";
                    $postProductData["included"]["0"]["attributes"]["fallback"]=NULL;
                    $postProductData["included"]["0"]["attributes"]["string"]=$product["basic_information"]["title"];
                    $postProductData["included"]["0"]["attributes"]["text"]=NULL;
                    
                    $postProductData["included"]["1"]["type"]="localizedfallbackvalues";
                    $postProductData["included"]["1"]["id"]="slug-prototype-1";
                    $postProductData["included"]["1"]["attributes"]["fallback"]=NULL;
                    $postProductData["included"]["1"]["attributes"]["string"]=$product["basic_information"]["slug"];
                    $postProductData["included"]["1"]["attributes"]["text"]=NULL;
                    $postProductData["included"]["1"]["relationships"]["localization"]["data"]=NULL;
                    
                    $postProductData["included"]["2"]["type"]="productunitprecisions";
                    $postProductData["included"]["2"]["id"]="product-unit-precision-id-1";
                    $postProductData["included"]["2"]["attributes"]["precision"]="0";
                    $postProductData["included"]["2"]["attributes"]["conversionRate"]="1";
                    $postProductData["included"]["2"]["attributes"]["sell"]="1";
                    $postProductData["included"]["2"]["relationships"]["unit"]["data"]["type"]="productunits";
                    $postProductData["included"]["2"]["relationships"]["unit"]["data"]["id"]="item";
                    
                    /*******************************************/
                    
                    
                    
                    $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
                    
                    $responseData = $this->getContainer()->get('api_caller')->call(new HttpPostJsonBody("http://localhost/Aakron/orocommerce-application/web/admin/api/products", $postProductData, false, $options));
                    $responseData = $this->objectToArray($responseData);
                    
                    if(isset($responseData["data"]["id"])){
                        //  echo $responseData["data"]["id"];//exit;
                        $this->getUnitPrecision($postProductData["data"]["attributes"]["sku"],$responseData["data"]["id"]);
                       // exit;
                    }                    
                }                
            }
        }
    }
    public function getUnitPrecision($productSku,$productId)
    {
        $productPriceArray = $this->getProductPriceFromCSCDB($productSku);
       
        foreach($productPriceArray as $productPrice){
            $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
            
            $productprices["data"]["type"]='productprices';
            $productprices["data"]["attributes"]["quantity"]=$productPrice["quantity"];
            $productprices["data"]["attributes"]["currency"]=$productPrice["currency"];
            $productprices["data"]["attributes"]["value"] = $productPrice["price"];
            
            $productprices["data"]["relationships"]["priceList"]["data"]["type"] = 'pricelists';
            $productprices["data"]["relationships"]["priceList"]["data"]["id"] = '1';
            $productprices["data"]["relationships"]["product"]["data"]["type"] = 'products';
            $productprices["data"]["relationships"]["product"]["data"]["id"] = $productId;
            $productprices["data"]["relationships"]["unit"]["data"]["type"] = 'productunits';
            $productprices["data"]["relationships"]["unit"]["data"]["id"] = 'item';
            
            $responseData = $this->getContainer()->get('api_caller')->call(new HttpPostJsonBody("http://localhost/Aakron/orocommerce-application/web/admin/api/productprices", $productprices, false, $options));
            $responseData = $this->objectToArray($responseData); 
            
           // var_dump($responseData);exit;
        }
        
        
    }
    
    public function getProductPriceFromCSCDB($productSku)
    {
        $productType = 1;
        $productYear = date("Y");
        $apiUrl = $this->getContainer()->getParameter("aakrib_api_get_netprice");
        $apiKey = $this->getContainer()->getParameter("aakrib_api_access_key");
        
        $requestArray = array(
            "_format"=>"json",
            "access_key"=>$apiKey,
            "product_sku"=>$productSku,
            "product_type"=>$productType,
            "product_year"=>$productYear
        );
        
        $responseData = $this->getContainer()->get('api_caller')->call(new HttpGetJson($apiUrl,$requestArray));
        
        $responseData = $this->objectToArray($responseData); 
        return $responseData;
    }
//     public function getProductNames($product){
//         $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
        
//         $returnProductName = array();        
        
//         $postNames["data"]["type"]="localizedfallbackvalues";
//         $postNames["data"]["attributes"]["fallback"]=null;
//         $postNames["data"]["attributes"]["string"]=$product["basic_information"]["title"];
//         $postNames["data"]["attributes"]["text"]=$product["basic_information"]["description"];
//         $postNames["data"]["relationships"]["localization"]["data"]["type"]="localizations";
//         $postNames["data"]["relationships"]["localization"]["data"]["id"]="1";
        
//         $checkDuplicateData = $this->getContainer()->get('api_caller')->call(new HttpGetJson("http://localhost/Aakron/orocommerce-application/web/admin/api/localizedfallbackvalues", array("filter"=>array("string"=>$postNames["data"]["attributes"]["string"]),"page"=>array("number"=>1,"size"=>10),"sort"=>"id"), false, $options));
//         $checkDuplicateData = $this->objectToArray($checkDuplicateData); 
//         if(isset($checkDuplicateData["data"]) && count($checkDuplicateData["data"])>0)
//         {
//             $returnProductName["data"]["0"]["type"] = "localizedfallbackvalues";
//             $returnProductName["data"]["0"]["id"] = $checkDuplicateData["data"]["0"]["id"];
//         }
//         else {        
//             $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
//             $responseData = $this->getContainer()->get('api_caller')->call(new HttpPostJsonBody("http://localhost/Aakron/orocommerce-application/web/admin/api/localizedfallbackvalues", $postNames, false, $options));           
//             $responseData = $this->objectToArray($responseData); 
            
//             if(isset($responseData["data"]["id"])){
//                 $returnProductName["data"]["0"]["type"] = "localizedfallbackvalues";
//                 $returnProductName["data"]["0"]["id"] = $responseData["data"]["id"];
//             }
//         }  
//      //   print_r($returnProductName);exit;
//         return $returnProductName;
//     }
//     public function getAttribute($product){
//         $attributes["sku"]=$product["basic_information"]["sku_number"];
//         $attributes["status"]='enabled';
//         $attributes["variantFields"]=array (  );
//         $attributes["productType"]='simple';
//         $attributes["featured"]=true;
//         $attributes["newArrival"]=false;
//         return $attributes;
//     }
    public function getLatestProductIds($timeslot)
    {
        $requestArray["_format"] = "json";
        $requestArray["culture"] = "en_us";
        $requestArray["project_id"] = "2";
        $requestArray["duration"] = $timeslot;
        
        $responseData = $this->getContainer()->get('api_caller')->call(new HttpGetJson("http://209.50.53.113/migration-api-hidden-new/web/api/v1/product/latest-updated",$requestArray));
        
        return $this->objectToArray($responseData);
    }
    public function getProjectSupplierIds()
    {
        $requestArray["_format"] = "json";
        $requestArray["culture"] = "en_us";
        $requestArray["project_id"] = "2";        
        
        $responseData = $this->getContainer()->get('api_caller')->call(new HttpGetJson("http://209.50.53.113/migration-api-hidden-new/web/api/v1/product/supplier/count",$requestArray));
        
        return $this->objectToArray($responseData);
    }
    public function getAllProductIds($supplierId,$start,$limit)
    {       
        $requestArray["_format"] = "json";
        $requestArray["culture"] = "en_us";
        $requestArray["supplier_id"] =$supplierId;
        $requestArray["start"] =$start;
        $requestArray["limit"] =$limit;        
       
        $responseData = $this->getContainer()->get('api_caller')->call(new HttpGetJson("http://209.50.53.113/migration-api-hidden-new/web/api/v1/product/virtual",$requestArray));
        
        return $this->objectToArray($responseData);
    }
    public function objectToArray($data)
    {
        return json_decode(json_encode($data), True);
    }
}
