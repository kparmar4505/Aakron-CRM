<?php

namespace Aakron\Bundle\PricingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AakronPricingBundle extends Bundle
{
    public function getParent()
    {
        return 'OroPricingBundle';
    }
}
