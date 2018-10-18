<?php
/** 
 * @author software 
 **/
namespace Aakron\Bundle\CustomerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Lsw\ApiCallerBundle\Call\HttpPostJsonBody as HttpPostJsonBody;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
use Lsw\ApiCallerBundle\Call\HttpPostJson as HttpPostJson;
use Symfony\Component\Console\Helper\ProgressBar;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
class CustomerApiCommand extends ContainerAwareCommand implements CronCommandInterface
{

    const COMMAND_NAME = 'oro:cron:aakron-customer-api-command';

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
        $importApiManager = $this->getContainer()->get('aakron_import_customer_api');
        $apiCallerManager = $this->getContainer()->get('api_caller');
     
        $responseData = $apiCallerManager->call(new HttpGetJson($importApiManager->getSourceApi(), array()));
       
        $progressBar = new ProgressBar($output, count($responseData));
        $progressBar->start();
        $progressBar->setRedrawFrequency(1);
        
        $responseArray = array();
        $accountData = array();
        $i = 0;
        $customerUserArray = array();
        $responseCustomer = array();
        $responseData = (array) $responseData;
       
        foreach ($responseData as $key => $customerDataArray) {
            
            $customerData = (array) $customerDataArray;
            $customerUserArray[] = $importApiManager->getAddCustomerArray();
            if ($importApiManager->validateCscData($customerData)) {
                /**
                 * *******add customers of CRM ****** Start
                 */
                    
                $responseDuplicateCheck = $this->checkDuplicateCustomer(trim($customerData['accountName']));
                if (isset($responseDuplicateCheck["id"]) && $responseDuplicateCheck["id"]>0) {
                    $customerUserArray[$key]['data']['relationships']["customer"]["data"]=array("type"=>"customers","id"=>$responseDuplicateCheck["id"]);                        
                } else {
                    $customerArrayTmp['data']['type'] = 'customers';
                    $customerArrayTmp['data']['attributes']["name"] = trim($customerData['accountName']);
                    $customerArrayTmp['data']['attributes']["account_number"] = trim($customerData['account']);
                    $customerArrayTmp['data']['relationships']['children']['data'] = array();
                    $customerArrayTmp['data']['relationships']['group']['data'] = array("type"=>"customer_groups","id"=>'1'); 
                    $customerArrayTmp['data']['relationships']['users']['data'] = array();
                  
                    $options = $importApiManager->generatAuthentication();
                    $responseData = $apiCallerManager->call(new HttpPostJsonBody($this->getContainer()->getParameter("customer.destination.url"), $customerArrayTmp, false, $options));
                    $responseData = $this->objectToArray($responseData); 
                    if(isset($responseData['data']))
                    {
                        if(isset($responseData['data']['id']) && $customerData['postalCode']!="")
                        {
                            
                            $customerUserArray[$key]['data']['relationships']["customer"]["data"]=array("type"=>"customers","id"=>(string)$responseData["data"]["id"]);
                        }
                    }                    
                
                }
                /**
                 * *******add customers of CRM ****** End
                 */
                
                /**
                 * *******customer users of CRM ****** Start
                 */
                $customerUserArray[$key]['data']['attributes']['email'] = $customerData['emails'];
                $customerUserArray[$key]['data']['attributes']['username'] = $customerData['emails'];
                $customerUserArray[$key]['data']['attributes']['nameSuffix'] = $customerData['nameSuffix'];
                $customerUserArray[$key]['data']['attributes']['lastName'] = $customerData['lastName'];
                $customerUserArray[$key]['data']['attributes']['firstName'] = $customerData['firstName'];
                $customerUserArray[$key]['data']['attributes']['middleName'] = $customerData['middleName'];
                // $customerUserArray['data']['attributes']['title'] = isset($customerData['jobTitle'])?$customerData['jobTitle']:"";
                $customerUserArray[$key]['data']['attributes']['password'] = ucfirst($customerData['firstName']) . '123456';
                $customerUserArray[$key]['data']['attributes']['enabled'] = false;
                $customerUserArray[$key]['data']['attributes']['confirmed'] = true;
                $customerUserArray[$key]['data']['relationships']['roles']['data']['0']=array("type" => "customer_user_roles","id"=> "1");
                
               
                $options = $importApiManager->generatAuthentication();                
                $responseData = $apiCallerManager->call(new HttpPostJsonBody($importApiManager->getDestinationApi(), $customerUserArray[$key], false, $options));                
                /**
                 * *******customer users of CRM ****** End
                 */
                
                /**
                 * *******customer users addresses of CRM ****** Start
                 */
                
                
                $responseData=$this->objectToArray($responseData);
                
                if(isset($responseData['data']))
                {
                    if(isset($responseData['data']['id']))
                    {
                        $customerUserAddressArray[$key]['data']['type']="customer_user_addresses";
                        
                        $customerUserAddressArray[$key]['data']['attributes']['city']=$customerData['city'];
                        $customerUserAddressArray[$key]['data']['attributes']['firstName']=$customerData['firstName'];
                        $customerUserAddressArray[$key]['data']['attributes']['label']="Primary address";
                        $customerUserAddressArray[$key]['data']['attributes']['lastName']=$customerData['lastName'];
                        $customerUserAddressArray[$key]['data']['attributes']['middleName']=$customerData['middleName'];
                        $customerUserAddressArray[$key]['data']['attributes']['namePrefix']="";
                        $customerUserAddressArray[$key]['data']['attributes']['nameSuffix']=$customerData['nameSuffix'];
                        $customerUserAddressArray[$key]['data']['attributes']['organization']=$customerData['organization'];
                        $customerUserAddressArray[$key]['data']['attributes']['phone']=$customerData['phones'];
                        $customerUserAddressArray[$key]['data']['attributes']['postalCode']=$customerData['postalCode'];
                        $customerUserAddressArray[$key]['data']['attributes']['primary']=true;
                        $customerUserAddressArray[$key]['data']['attributes']['street']=$customerData['street'];
                        $customerUserAddressArray[$key]['data']['attributes']['street2']=$customerData['street2'];
                        
                        $customerUserAddressArray[$key]['data']['relationships']['country']['data']=array('type' => 'countries','id' => 'US');
                        $customerUserAddressArray[$key]['data']['relationships']['frontendOwner']['data']=array('type' => 'customer_users','id' => $responseData['data']['id']);
                        $customerUserAddressArray[$key]['data']['relationships']['owner']['data']=array('type' => 'users','id' => '1');
                        $customerUserAddressArray[$key]['data']['relationships']['region']['data']=array('type' => 'regions','id' => 'US-'.$customerData['region']);
                        
                        $optionsForAddress = $importApiManager->generatAuthentication();
                        $responseData = $apiCallerManager->call(new HttpPostJsonBody($this->getContainer()->getParameter("customeruser_address.destination.url"), $customerUserAddressArray[$key], false, $optionsForAddress)); 
                       
                    }
                  
                }
             
                /**
                 * *******customer users addresses of CRM ****** End
                 */
                
                $i ++;
                unset($tempArray);
                unset($customerData);
        

            $progressBar->advance();
            }
        }
       
        
        $responseArray[] = $responseData;

        $responseAccountData = array();
        
        
        // ensures that the progress bar is at 100%
        $progressBar->finish();
        $output->write("Import done");
        // exit;
        
        // $this->getCustomerData();
        // $output->write("Import done");
    }

    public function isActive()
    {}

    public function objectToArray($data)
    {
        return json_decode(json_encode($data), True);
    }
    public function checkDuplicateCustomer($customer)
    {        
       // echo $customer."-----------";
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