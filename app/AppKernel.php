<?php

use Symfony\Component\Config\Loader\LoaderInterface;

use Oro\Bundle\DistributionBundle\OroKernel;

class AppKernel extends OroKernel
{
    public function registerBundles()
    {
        $bundles = array(
            // bundles
            new Aakron\Bundle\CscApiBundle\AakronCscApiBundle(),
            new Lsw\ApiCallerBundle\LswApiCallerBundle(),
            new Aakron\Bundle\SaleBundle\AakronSaleBundle(),
            new Aakron\Bundle\ActivityBundle\AakronActivityBundle(),
            new Aakron\Bundle\ProductBundle\AakronProductBundle(),
            new Aakron\Bundle\PricingBundle\AakronPricingBundle(),
            new Aakron\Bundle\CustomerBundle\AakronCustomerBundle(),
            new Ibnab\Bundle\PmanagerBundle\IbnabPmanagerBundle(),
        );

        if ('dev' === $this->getEnvironment()) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            if (class_exists('Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle')) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            }
        }

        if ('test' === $this->getEnvironment()) {
            $bundles[] = new Oro\Bundle\TestFrameworkBundle\OroTestFrameworkBundle();
            $bundles[] = new Oro\Bundle\TestFrameworkCRMBundle\OroTestFrameworkCRMBundle();
            $bundles[] = new Oro\Bundle\FrontendTestFrameworkBundle\OroFrontendTestFrameworkBundle();
        }

        return array_merge(parent::registerBundles(), $bundles);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
