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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
class CustomerUsersCSVCommand extends ContainerAwareCommand implements CronCommandInterface
{

    const COMMAND_NAME = 'oro:cron:aakron-customer-users-csv-command';

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
        $this->setName(self::COMMAND_NAME)->setDescription('Aakron Customer User csc api')
        
            ->setDescription(
                'Import price list data from specified file. The import log is sent to the provided email.'
                )
                
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File name, to import CSV data from'
                )
                ;
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
        //echo $sourceFile = $input->getArgument('file');exit;
        if (! is_file($sourceFile = $input->getArgument('file'))) {
            throw new \InvalidArgumentException(sprintf('File not found: %s', $sourceFile));
        }
        $csvData = $this->readCsv($sourceFile);        
        
        $progressBar = new ProgressBar($output, $csvData["count"]??"0");
        $progressBar->start();
        $progressBar->setRedrawFrequency(1);     
        //print_r($csvData["customer"]);exit;
        foreach($csvData["customer"] as $key=>$data)
       {
           $customerId = $this->addCustomerToCRM($data);
           if($customerId>0)
           {               
               if (isset($data["users"]) && count($data["users"])>0)
               {
                   foreach($data["users"] as $users)
                   {
                        $this->addCustomerUser($users,$customerId);
                        $progressBar->advance();
                   }
               }
           }
       }
       $progressBar->finish();
       $output->write("Import done");
        
    }

    public function isActive()
    {}
    public function addCustomerToCRM($customer)
    {
        $responseArray = array();
        $responseId = 0;
        $responseDuplicateCheck = $this->checkDuplicateCustomer(trim($customer['accountName']));
        if (isset($responseDuplicateCheck["id"]) && $responseDuplicateCheck["id"]>0) {
            return $responseDuplicateCheck["id"];
          
        } else {
            $options = $this->getContainer()->get('aakron_import_customer_api')->generatAuthentication();
            
            $customerArrayTmp['data']['type'] = 'customers';
            $customerArrayTmp['data']['attributes']["name"] = trim($customer['accountName'])??trim($customer['accountNumber']);
            $customerArrayTmp['data']['attributes']["account_number"] = trim($customer['accountNumber']);
            $customerArrayTmp['data']['relationships']['children']['data'] = array();
            $customerArrayTmp['data']['relationships']['group']['data'] = array("type"=>"customer_groups","id"=>'1');
            $customerArrayTmp['data']['relationships']['users']['data'] = array();
           
            $responseData = $this->getContainer()->get('api_caller')->call(new HttpPostJsonBody($this->getContainer()->getParameter("customer.destination.url"), $customerArrayTmp, false, $options));
            $responseData = $this->objectToArray($responseData);
            
            
            if(isset($responseData['data']))
            {
                if(isset($responseData['data']['id']))
                {
                    return $responseId = $responseData["data"]["id"];
                }
            }
            else if(isset($responseData['errors']))
            {
                //                 echo "Error Block Start:>>\n";
                //                 print_r($responseData['errors']);
                //                 print_r($customer);
                //                 echo "Error Block End:<<\n";
            }
        }
        return $responseId;
    }
    public function readCsv($sourceFile){
        $rowNo = 0;
        $responseArray = array();
        
        $countryArray = array("Puerto Rico"=>"PR","Canada"=>"CA","United States of America"=>"US");
        if (($fp = fopen($sourceFile, "r")) !== FALSE) {
            while (($rows = fgetcsv($fp, 10000, ",")) !== FALSE) {
                $num = count($rows);
                if($rowNo>0){
                    // echo $rows[0]."==>".$rows[1]."<br>";
                    $accountNumber = $rows[7]??"";
                    
                    
                    /*****************Customer Detail Start******************/
                    $responseArray["customer"][$accountNumber]["accountName"] = $rows[6]??"";
                    $responseArray["customer"][$accountNumber]["accountNumber"] = $accountNumber;
                    /*****************Customer Detail End******************/
                    
                    /*****************Customer User Detail Start******************/
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["firstName"] = $rows[2]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["middleName"] = $rows[3]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["lastName"] = $rows[4]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["nameSuffix"] = $rows[5]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["title"] = $rows[9]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["emails"] = $rows[27]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["username"] = $rows[27]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]["password"] = ucfirst($responseArray["customer"][$accountNumber]["users"][$rowNo]["firstName"]) . '123456';
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['enabled'] = false;
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['confirmed'] = true;
                    /*****************Customer User Detail End******************/
                    
                    /*****************Customer User Address Detail Start******************/
                    $country = $rows[14]??"";
                    if(isset($countryArray[$country]))
                        $country = $countryArray[$country];
                    else 
                        $country ="";
                    $state= $rows[12]??"";
                    
                    
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['label']="Primary address";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['organization']=$rows[6]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['phones']=$rows[20]??($rows[21]??"");
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['primary']=true;
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['city']=$rows[11]??"";                    
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['postalCode']=$rows[13]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['region']=$country."-".$state;                    
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['country']=$country;
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['street']=$rows[11]??"";
                    $responseArray["customer"][$accountNumber]["users"][$rowNo]['street2']="";
                    /*****************Customer User Address Detail End******************/
                    
                }
                $rowNo++;
            }
        }
        $responseArray["count"]=$rowNo;
        return $responseArray;
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
        $customerUserArray['data']['relationships']["customer"]["data"]=array("type"=>"customers","id"=>$customerId);
        
        
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

                $customerUserAddressArray['data']['relationships']['country']['data']=array('type' => 'countries','id' => $customerData['country']??"US");
                $customerUserAddressArray['data']['relationships']['frontendOwner']['data']=array('type' => 'customer_users','id' => $responseUserData['data']['id']);
                $customerUserAddressArray['data']['relationships']['owner']['data']=array('type' => 'users','id' => '1');
                $customerUserAddressArray['data']['relationships']['region']['data']=array('type' => 'regions','id' => $customerData['region']);
                
                $responseAddressData = $apiCallerManager->call(new HttpPostJsonBody($this->getContainer()->getParameter("customeruser_address.destination.url"), $customerUserAddressArray, false, $optionsForAddress));
            }
        }
        return true;
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
    public function objectToArray($data)
    {
        return json_decode(json_encode($data), True);
    }
    public function addAddress($customerData)
    {}
}