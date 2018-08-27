<?php

namespace Aakron\Bundle\PricingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AakronPricingBundle:Default:index.html.twig');
    }
}
