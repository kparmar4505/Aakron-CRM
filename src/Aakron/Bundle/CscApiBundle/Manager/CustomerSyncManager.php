<?php
namespace Aakron\Bundle\CscApiBundle\Manager;
use Lsw\ApiCallerBundle\Call\HttpPostJsonBody as HttpPostJsonBody;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
use Lsw\ApiCallerBundle\Call\HttpPostJson as HttpPostJson;
use Symfony\Component\Console\Helper\ProgressBar;
/**
 *
 * @author OfficeBrain 4505 <info@officebrain.com>
 *
 * Description : Extended class
 *
 */
class CustomerSyncManager
{
    public function __construct($container)
    {
        $this->container = $container->getContainer();        
        $this->destinationApiUrl = $this->container->getParameter("customeruser.destination.url");
        $this->sourceApiUrl =$this->container->getParameter("customers.source.url");        
        $this->userName = $this->container->getParameter("aakron.crm.username");
        $this->userApiKey = $this->container->getParameter("aakron.crm.userapikey");
        
        $this->addCustomerArray = $this->getAddCustomerArray();
        $this->updateCustomerArray = $this->getUpdateCustomerArray();
        $this->unValidatedCustomers = array();
       
    }
    public function getSourceApi()
    {
        return $this->sourceApiUrl;
    }
    public function getDestinationApi()
    {
        return $this->destinationApiUrl;
    }
    public function getAddCustomerArray()
    {        
       /* array (
            'data' =>
            array (
                'type' => 'customer_users',
                'attributes' =>
                array (
                    'confirmed' => true,
                    'email' => 'AmandaFCole@example.org',
                    'firstName' => 'Amanda',
                    'lastName' => 'Cole',
                    'enabled' => true,
                    'username' => 'AmandaFCole@example.org',
                    'password' => 'Password000!',
                ),
                'relationships' =>
                array (
                    'roles' =>
                    array (
                        'data' =>
                        array (
                            0 =>
                            array (
                                'type' => 'customer_user_roles',
                                'id' => '1',
                            ),
                        ),
                    ),
                    'customer' =>
                    array (
                        'data' =>
                        array (
                            'type' => 'customers',
                            'id' => '1',
                        ),
                    ),
                    'website' =>
                    array (
                        'data' =>
                        array (
                            'type' => 'websites',
                            'id' => '1',
                        ),
                    ),
                ),
            ),
        )*/
        $addCustomerArray["data"]["type"]="customer_users";
        $addCustomerArray["data"]["attributes"]=array();
//         $addCustomerArray["data"]["relationships"]["roles"]["data"]=array();
         $addCustomerArray["data"]["relationships"]["customer"]["data"]=array();
//         $addCustomerArray["data"]["relationships"]["website"]["data"]=array();
       
        return $addCustomerArray;
    }
    public function getAddAccountArray()
    {
        $addCustomerArray["data"]["type"]="accounts";
        $addCustomerArray["data"]["attributes"]["extend_description"]="null";
        $addCustomerArray["data"]["attributes"]["name"]=""; //Dynamically update customer name
        $addCustomerArray["data"]["relationships"]["owner"]["data"]["type"]="users"; 
        $addCustomerArray["data"]["relationships"]["owner"]["data"]["id"]="1";//If user need to change the change user id
        $addCustomerArray["data"]["relationships"]["customers"]["data"]=array();   //Dynamically push array of customer
        $addCustomerArray["data"]["relationships"]["defaultCustomer"]["data"]=array(); //Dynamically push array of default customer
        $addCustomerArray["data"]["relationships"]["organization"]["data"]["type"]="organizations";
        $addCustomerArray["data"]["relationships"]["organization"]["data"]["id"]="1"; //it should be 1 always because we have only one organization
        return $addCustomerArray;
    }
    public function getUpdateCustomerArray()
    {
        $updateCustomerArray["data"]["type"]="customers";
        $updateCustomerArray["data"]["id"]="1";
        $updateCustomerArray["data"]["attributes"]=array();
        return $updateCustomerArray;
    }
    public function generatAuthentication()
    {
        
        $nonce = base64_encode(substr(md5(uniqid()), 0, 16));
        $created  = date('c');
        $digest   = base64_encode(sha1(base64_decode($nonce) . $created . $this->userApiKey, true));
        $options = array(
            "httpheader" => array("Content-type: application/vnd.api+json",
                "Accept: application/vnd.api+json",
                "Authorization: WSSE profile=\"UsernameToken\"",
                "X-WSSE: UsernameToken Username=\"".$this->userName."\", PasswordDigest=\"".$digest."\", Nonce=\"".$nonce."\", Created=\"".$created."\""
            )
        );
        return $options;
    }
    public function syncCscCustomers()
    {
        $requestArray = $this->callAakronCscApi();
 
        $responseData = array();
        $i = 1;
        foreach($requestArray as $customerRequest){
            if($i>0){
            $options = $this->generatAuthentication();
            $responseData[] = $this->container->get('api_caller')->call(new HttpPostJsonBody($this->destinationApiUrl, $customerRequest, false,$options)); // true to have an associative array as answer
     //       print_r($responseData);exit;
            }
            $i = 0;
        }
        return $responseData;
    }
    
    public function callAakronCscApi()
    {
        $crmParameters= array();
        $responseData = $this->container->get('api_caller')->call(new HttpGetJson($this->sourceApiUrl,array()));
       
        foreach($responseData as $key=>$customerData)
        {
            $customerData1 = (Array)$customerData;
            if($this->validateCscData($customerData1))
            {
                $checkDuplicate = $this->checkDuplicateRecord($customerData1);
                
                if($checkDuplicate<=0)
                {
                    $tempArray = $this->addCustomerArray;                    
                }
                else {
                    $tempArray = $this->updateCustomerArray;     
                    $tempArray["data"]["id"] = $checkDuplicate; 
                }
                
                $customerData1 = $this->updateSocialForCustomer($customerData1);
                $tempArray['data']['attributes'] = $customerData1;
                $tempArray['data']['attributes']['primaryEmail'] =  $customerData1['emails'];
                $tempArray['data']['attributes']['primaryPhone'] =   $customerData1['phones'];
                unset($tempArray['data']['attributes']['emails']);
                unset($tempArray['data']['attributes']['phones']);
                $crmParameters[] = $tempArray;
               // print_r($tempArray);exit;
                unset($tempArray);
            }
            else {
                $this->unValidatedCustomers[$key] = $customerData1;
            }
           // print_r($customerData1);exit;
            unset($customerData1);
        }
       
        return $crmParameters;
    }
    public function validateCscData($customerRequest)
    {
        //if(empty($customerRequest['firstName']) || empty($customerRequest['lastName']) || empty($customerRequest['emails']) || empty($customerRequest['phones']))
        if(empty($customerRequest['firstName']) || empty($customerRequest['emails'] || empty($customerRequest['lastName']) || empty($customerRequest['emails']) ))
        {
            return false;
        }
        return true;
    }
    public function updateSocialForCustomer($customerData)
    {
        if(!empty($customerData['social_account']))
        {
            $socialAcountType = strtolower(trim($customerData['social_type']));
            
            switch ($socialAcountType) {
                case facebook:
                    $customerData['facebook'] = $customerData['social_account'];
                    break;
                case skype:
                    $customerData['skype'] = $customerData['social_account'];
                    break;
                case twitter:
                    echo "test";
                    $customerData['twitter'] = $customerData['social_account'];
                    echo "test";
                    break;
                case linkedIn:
                    $customerData['linkedIn'] = $customerData['social_account'];
                    break;
                default:
                    
            }
            unset($customerData['social_type']);
            unset($customerData['social_account']);
        }
        return $customerData;
    }
    public function checkDuplicateRecord($customerData)
    {
        $requestArray = array();
        $requestArray['page']['number']=1;
        $requestArray['page']['size']=10;
        $requestArray['sort']='';
        $requestArray['filter']['emails']=$customerData['emails'];
      
        $options = $this->generatAuthentication();
        $responseData = $this->container->get('api_caller')->call(new HttpGetJson($this->destinationApiUrl, $requestArray,false,$options));
        $responseData = (Array)$responseData;
        if(count($responseData['data'])<=0)
        {
            return 0;
        }
        else {
            $record = (Array)$responseData["data"]["0"];
            return $record["id"];
        }
    }
    public function extractAccountData($customerData)
    {
       // $this->container->get('api_caller')->call(new HttpPostJsonBody($this->destinationApiUrl, $customerRequest, false,$options));
       $responseArray = array();
       foreach($customerData as $key=>$value)
       {
           if(isset($customerData["account"]) && isset($customerData["accountName"]))
           {
               $responseArray["account"]["account_number"]=$customerData["account"];
               $responseArray["account"]["account_name"]=$customerData["accountName"];
               
               unset($customerData["account"]);
               unset($customerData["accountName"]);
           }
           
       }
       $responseArray["customer"]=$customerData;
       
       return $responseArray;
    }
}