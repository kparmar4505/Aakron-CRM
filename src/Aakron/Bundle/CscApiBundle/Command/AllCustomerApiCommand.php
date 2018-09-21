<?php
/** 
 * @author software 
 **/
namespace Aakron\Bundle\CscApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Lsw\ApiCallerBundle\Call\HttpPostJsonBody as HttpPostJsonBody;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
use Lsw\ApiCallerBundle\Call\HttpPostJson as HttpPostJson;
use Symfony\Component\Console\Helper\ProgressBar;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
class AllCustomerApiCommand extends ContainerAwareCommand implements CronCommandInterface
{

    const COMMAND_NAME = 'oro:cron:aakron-all-customer-api-command';

    /**
     *
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)->setDescription('Aakron Customer csc api');
    }

    /**
     * Runs command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        
        $i = 1;
        $k = 500;// batch of records
        $_k=-500;
        $progressBar = new ProgressBar($output, 31000);
        $progressBar->start();
        $progressBar->setRedrawFrequency(1);
        
        while (1) {
            $start = $i;
            $end = $i+($k-1);
            
            $allCustomersDataJson = $this->getContainer()->get('api_caller')->call(new HttpGetJson($this->getContainer()->getParameter("all.customers.source.url"), array("record_from"=>$start,"record_to"=>$end)));
            
            $allCustomersData=$this->objectToArray($allCustomersDataJson);
            if(count($allCustomersData)>$k)
            {
                $allCustomersData = array_slice($allCustomersData, $_k, $k, true);
            }

            if(count($allCustomersData)<= 0)
            {
               return false;
            }
            else{
                /** Sync logic ***/
                foreach($allCustomersData as $key=>$customerRecord)
                {
                    if($key>=$start)
                    {
                        $customer = $this->addCustomerToCRM($customerRecord);
//                         if(count($customer)>0 && isset($customer["id"]))
//                         {
//                             $customerUserArray = $this->getCustomerUsersFromCSC($customer["account_number"]);
//                             if(isset($customerUserArray) && count($customerUserArray)>0)
//                             {
//                                 foreach($customerUserArray as $customerUserData)
//                                 {
//                                     $this->addCustomerUser($customerUserData,$customer["id"]);
//                                 }
//                             }
//                         }
                        $progressBar->advance();
                    }
                }
                
                /*****/
            }
            
            unset($allCustomersData);
           
            $i=$i+$k;                    
        };
        $progressBar->finish();
        $output->write("Import done");
    }

    public function isActive()
    {}

    public function addCustomerToCRM($customer)
    {
        $responseArray = array();
       // $responseDuplicateCheck = $this->checkDuplicateCustomer(trim($customer['accountName']));
//         if (isset($responseDuplicateCheck["id"]) && $responseDuplicateCheck["id"]>0) {
//             $responseArray=array("type"=>"customers","id"=>$responseDuplicateCheck["id"]);
//         } else {
            $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
            
            $customerArrayTmp['data']['type'] = 'customers';
            $customerArrayTmp['data']['attributes']["name"] = trim($customer['accountName'])??trim($customer['accountNumber']);
            $customerArrayTmp['data']['attributes']["account_number"] = trim($customer['accountNumber']);
            $customerArrayTmp['data']['relationships']['children']['data'] = array();
            $customerArrayTmp['data']['relationships']['group']['data'] = array("type"=>"customer_groups","id"=>'1');
            $customerArrayTmp['data']['relationships']['users']['data'] = array();    
        //    print_r($customerArrayTmp);
            $responseData = $this->getContainer()->get('api_caller')->call(new HttpPostJsonBody($this->getContainer()->getParameter("customer.destination.url"), $customerArrayTmp, false, $options));
            $responseData = $this->objectToArray($responseData);
            
   
            if(isset($responseData['data']))
            {
                if(isset($responseData['data']['id']) && $customer['postalCode']!="")
                {
                    $responseArray=array("type"=>"customers","id"=>(string)$responseData["data"]["id"],"account_number"=>(string)$responseData["data"]["attributes"]["account_number"]);                    
                }
            }    
            else if(isset($responseData['errors']))
            {
//                 echo "Error Block Start:>>\n";
//                 print_r($responseData['errors']);
//                 print_r($customer);
//                 echo "Error Block End:<<\n";
            }
//         }
        return $responseArray;
    }
    public function getCustomerUsersFromCSC($accountNumber)
    {
        $responseDuplicateCheck = $this->getContainer()->get('api_caller')->call(new HttpGetJson($this->getContainer()->getParameter("customer.users.source.url")."?_format=json&account_number=".$accountNumber, array()));
        
        return $this->objectToArray($responseDuplicateCheck);
    }
    public function addCustomerUser($customerData,$customerId)
    {
        $importApiManager = $this->getContainer()->get('aakron_import_customer_api');
        $apiCallerManager = $this->getContainer()->get('api_caller');
        $options = $importApiManager->generatAuthentication();
        
        $customerUserArray["data"]["type"]="customer_users";
        $customerUserArray['data']['attributes']['email'] = $customerData['emails'];
        $customerUserArray['data']['attributes']['username'] = $customerData['emails'];
        $customerUserArray['data']['attributes']['nameSuffix'] = $customerData['nameSuffix'];
        $customerUserArray['data']['attributes']['lastName'] = $customerData['lastName'];
        $customerUserArray['data']['attributes']['firstName'] = $customerData['firstName'];
        $customerUserArray['data']['attributes']['middleName'] = $customerData['middleName'];
        // $customerUserArray['data']['attributes']['title'] = isset($customerData['jobTitle'])?$customerData['jobTitle']:"";
        $customerUserArray['data']['attributes']['password'] = ucfirst($customerData['firstName']) . '123456';
        $customerUserArray['data']['attributes']['enabled'] = false;
        $customerUserArray['data']['attributes']['confirmed'] = true;
        $customerUserArray['data']['relationships']['roles']['data']['0']=array("type" => "customer_user_roles","id"=> "1");
        $customerUserArray['data']['relationships']["customer"]["data"]=array("type"=>"customers","id"=>(string)$customerId);
        
        
        $responseData = $apiCallerManager->call(new HttpPostJsonBody($importApiManager->getDestinationApi(), $customerUserArray, false, $options));
        $responseUserData=$this->objectToArray($responseData);
       
        if(isset($responseUserData['data']))
        {
            if(isset($responseUserData['data']['id']))
            {
                $optionsForAddress = $importApiManager->generatAuthentication();
                
                $customerUserAddressArray['data']['type']="customer_user_addresses";
                $customerUserAddressArray['data']['attributes']['city']=$customerData['city'];
                $customerUserAddressArray['data']['attributes']['firstName']=$customerData['firstName'];
                $customerUserAddressArray['data']['attributes']['label']="Primary address";
                $customerUserAddressArray['data']['attributes']['lastName']=$customerData['lastName'];
                $customerUserAddressArray['data']['attributes']['middleName']=$customerData['middleName'];
                $customerUserAddressArray['data']['attributes']['namePrefix']="";
                $customerUserAddressArray['data']['attributes']['nameSuffix']=$customerData['nameSuffix'];
                $customerUserAddressArray['data']['attributes']['organization']=$customerData['organization'];
                $customerUserAddressArray['data']['attributes']['phone']=$customerData['phones'];
                $customerUserAddressArray['data']['attributes']['postalCode']=$customerData['postalCode'];
                $customerUserAddressArray['data']['attributes']['primary']=true;
                $customerUserAddressArray['data']['attributes']['street']=$customerData['street'];
                $customerUserAddressArray['data']['attributes']['street2']=$customerData['street2'];
                
                $customerUserAddressArray['data']['relationships']['country']['data']=array('type' => 'countries','id' => 'US');
                $customerUserAddressArray['data']['relationships']['frontendOwner']['data']=array('type' => 'customer_users','id' => $responseUserData['data']['id']);
                $customerUserAddressArray['data']['relationships']['owner']['data']=array('type' => 'users','id' => '1');
                $customerUserAddressArray['data']['relationships']['region']['data']=array('type' => 'regions','id' => 'US-'.$customerData['region']);
                
                $responseAddressData = $apiCallerManager->call(new HttpPostJsonBody($this->getContainer()->getParameter("customeruser_address.destination.url"), $customerUserAddressArray, false, $optionsForAddress));
            }
        }
        return true;
    }
    public function objectToArray($data)
    {
        return json_decode(json_encode($data), True);
    }
    public function checkDuplicateCustomerUser($customer)
    {
        
        $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
        
        $checkDuplicate["fields"]["customers"] = $customer;
        $checkDuplicate["page"]["number"]="1";
        $checkDuplicate["page"]["size"]="10";
        $checkDuplicate["sort"]="id";
        $apiUrl = "http://dev.aakroncrm.com/app_dev.php/admin/api/customer_users";
        $responseDuplicateCheck = $this->getContainer()->get('api_caller')->call(new HttpGetJson( $apiUrl, $checkDuplicate, false, $options));
        $responseDuplicateCheck = $this->objectToArray($responseDuplicateCheck);
        
        unset($checkDuplicate);
        return isset($responseDuplicateCheck["data"][0])?$responseDuplicateCheck["data"][0]:array();
    }
    public function checkDuplicateCustomer($customer)
    {        
       
        $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
        
        $checkDuplicate["filter"]["name"] = $customer;
        $checkDuplicate["page"]["number"]="1";
        $checkDuplicate["page"]["size"]="10";
        $checkDuplicate["sort"]="id";
        
        $responseDuplicateCheck = $this->getContainer()->get('api_caller')->call(new HttpGetJson( $this->getContainer()->getParameter("customer.destination.url"), $checkDuplicate, false, $options));
        $responseDuplicateCheck = $this->objectToArray($responseDuplicateCheck);

        unset($checkDuplicate);
        return isset($responseDuplicateCheck["data"][0])?$responseDuplicateCheck["data"][0]:array();
    }

    public function addAddress($customerData)
    {}
}