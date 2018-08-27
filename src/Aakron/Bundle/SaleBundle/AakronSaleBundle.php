<?php

namespace Aakron\Bundle\SaleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AakronSaleBundle extends Bundle
{
    public function getParent()
    {
        return 'OroSaleBundle';
    }
}
