<?php

namespace Aakron\Bundle\ProductBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AakronProductBundle extends Bundle
{
    public function getParent()
    {
        return 'OroProductBundle';
    }
}
