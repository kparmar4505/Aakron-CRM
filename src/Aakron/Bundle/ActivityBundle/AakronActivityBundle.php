<?php

namespace Aakron\Bundle\ActivityBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AakronActivityBundle extends Bundle
{
    public function getParent()
    {
        return 'OroActivityBundle';
    }
}
