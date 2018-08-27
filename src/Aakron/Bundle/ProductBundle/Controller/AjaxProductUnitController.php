<?php

namespace Aakron\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Controller\AbstractAjaxProductUnitController;

use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;

class AjaxProductUnitController extends AbstractAjaxProductUnitController
{
    /**
     * @Route("/product-units", name="oro_product_unit_all_product_units")
     * @AclAncestor("oro_product_view")
     *
     * @return JsonResponse
     */
    public function getAllProductUnitsAction()
    {
        return $this->getAllProductUnits();
    }

    /**
     * @Route("/product-units/{id}", name="oro_product_unit_product_units", requirements={"id"="\d+"})
     * @AclAncestor("oro_product_view")
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductUnitsAction(Request $request)
    {
        $string = array (
            'units' =>
            array (
                'item' => 0
            ),
        );        
        return new JsonResponse($string);
    }
}
