<?php

namespace Aakron\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AakronSaleBundle:Default:index.html.twig');
    }
}
