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
class CustomerUsersApiCommand extends ContainerAwareCommand implements CronCommandInterface
{

    const COMMAND_NAME = 'oro:cron:aakron-customer-users-api-command';

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
        $this->setName(self::COMMAND_NAME)->setDescription('Aakron Customer User csc api');
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
        
        $page=1;
        $records=100;
        $progressBar = new ProgressBar($output, 30000);
        $progressBar->start();
        $progressBar->setRedrawFrequency(1);
        while (1) {
            $customerArray = $this->getCustomerArray($page,$records);
            
            if(isset($customerArray["data"]) && count($customerArray["data"])>0)
            {
               
                foreach ($customerArray["data"] as $customerData)
                {
                    $customerUserArray = $this->getCustomerUsersFromCSC($customerData["attributes"]["account_number"]);
                   
                    if(isset($customerUserArray) && count($customerUserArray)>0)
                    {
                        foreach($customerUserArray as $customerUserData)
                        {
                            $this->addCustomerUser($customerUserData,$customerData["id"]);
                        }
                    }
                    
                    $progressBar->advance();
                }                
            }
            else {
                $progressBar->finish();
                $output->write("Import done");
                return false;
            }
            $page=$page+1;
        };
        
        $progressBar->finish();
        $output->write("Import done");
    }

    public function isActive()
    {}

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
        $customerUserArray['data']['relationships']['roles']['data']['0']=array("type" => "customer_user_roles","id"=> "2");        
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

    public function getCustomerArray($page,$records)
    {
        $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();       
        
        $checkDuplicate["page"]["number"]=$page;
        $checkDuplicate["page"]["size"]=$records;
        $checkDuplicate["sort"]="id";
        
        $responseDuplicateCheck = $this->getContainer()->get('api_caller')->call(new HttpGetJson( $this->getContainer()->getParameter("customer.destination.url"), $checkDuplicate, false, $options));
  
        return $this->objectToArray($responseDuplicateCheck);
    }
    public function addAddress($customerData)
    {}
}