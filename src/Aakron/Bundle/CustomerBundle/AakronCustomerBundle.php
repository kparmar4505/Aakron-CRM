<?php

namespace Aakron\Bundle\CustomerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AakronCustomerBundle extends Bundle
{
    public function getParent()
    {
        return 'OroCustomerBundle';
    }
}
