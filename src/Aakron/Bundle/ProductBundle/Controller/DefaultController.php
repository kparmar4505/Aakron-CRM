<?php

namespace Aakron\Bundle\ProductBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AakronProductBundle:Default:index.html.twig');
    }
}
