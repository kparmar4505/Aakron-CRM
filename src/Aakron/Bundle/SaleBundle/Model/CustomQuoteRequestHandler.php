<?php

namespace Aakron\Bundle\SaleBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;

class CustomQuoteRequestHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $quoteClass;

    /** @var string */
    protected $requestClass;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $contactClass;

    /**
     * @param ManagerRegistry $registry
     * @param RequestStack $requestStack
     * @param $accountClass
     * @param $contactClass
     */
    public function __construct(
        ManagerRegistry $registry,
        RequestStack $requestStack,
        $accountClass,
        $contactClass
    ) {
        $this->registry = $registry;
        $this->accountClass = $accountClass;
        $this->contactClass = $contactClass;
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityRepository
     */
    public function getEntityRepositoryForClass($entityClass)
    {
        return $this->registry
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }

    /**
     * @return Account|null
     */
    public function getCustomer()
    {
        $customerId = $this->getFromRequest('customer');
        $customer = null;
        if ($customerId) {
            $customer = $this->findEntity($this->accountClass, $customerId);
        }

        return $customer;
    }

    /**
     * @return Contact|null
     */
    public function getCustomerUser()
    {
        $customerUserId = $this->getFromRequest('customerUser');
        $customerUser = null;
        if ($customerUserId) {
            $customerUser = $this->findEntity($this->contactClass, $customerUserId);
        }

        return $customerUser;
    }

    /**
     * @param string $var
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getFromRequest($var, $default = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $default;
        }

        $orderType = $request->get(QuoteType::NAME);
        if (!is_array($orderType) || !array_key_exists($var, $orderType)) {
            return $default;
        } else {
            return $orderType[$var];
        }
    }

    /**
     * @param string $entityClass
     * @param int $id
     *
     * @return object
     */
    protected function findEntity($entityClass, $id)
    {
        return $this->registry->getManagerForClass($entityClass)->find($entityClass, $id);
    }
}
