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
use Oro\Bundle\CustomerBundle\Controller\CustomerUserAddressController;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerTypedAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
class CustomerApiTestCommand extends ContainerAwareCommand implements CronCommandInterface
{

    const COMMAND_NAME = 'oro:cron:aakron-customer-api-test-command';

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
       // $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        
       // $customer = new Customer(4251);
        $customer=$this->getContainer()->get('doctrine')->getEntityManager()->find(Customer::class, 4251);
       
        $customerAddress=$this->getContainer()->get('doctrine')->getEntityManager()->find(CustomerAddress::class, 3);
        $customerAddress->setPrimary(true);
      
        
        $status = $customer->addAddress($customerAddress);
        
      //  echo $customer->getAddresses()->count();
        var_dump($customer->getAddressByTypeName("shipping"));
        exit;
//         $customerAddressController = new CustomerUserAddressController();
//         $status = $customerAddressController->update($customer, $customerAddress);
        
       // var_dump($status);exit;
        //$this->getContainer()->forward('oro_customer.customer_address.manager.api:updateAction', array());
        
    }

    public function isActive()
    {}

    public function objectToArray($data)
    {
        return json_decode(json_encode($data), True);
    }
    
    public function addAddress($customerData)
    {}
}