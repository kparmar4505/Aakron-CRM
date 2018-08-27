<?php

namespace Aakron\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AakronCustomerBundle:Default:index.html.twig');
    }
}
