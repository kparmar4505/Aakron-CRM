<?php

namespace Aakron\Bundle\ActivityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AakronActivityBundle:Default:index.html.twig');
    }
}
