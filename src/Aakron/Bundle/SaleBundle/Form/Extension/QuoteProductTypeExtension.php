<?php
namespace Aakron\Bundle\SaleBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
class QuoteProductTypeExtension extends AbstractTypeExtension
{
    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return QuoteProductType::class;
    }
    
//     /**
//      * Add the image_path option
//      *
//      * @param OptionsResolver $resolver
//      */
//     public function configureOptions(OptionsResolver $resolver)
//     {
//         $resolver->setDefined(array('setupCharge','pricingIncluded'));
//     }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('setupCharge', TextType::class, [
            'required' => false,
            'label' => 'Setup Charge',
            'attr' => array(
                'class' => 'js-setup-charge'
            )
        ])
        ->add('pricingIncluded', HiddenType::class, [
            'required' => false,
            'label' => 'Pricing Included',
            'attr' => array(
                'class' => 'js-pricing-included'
            )
        ])
        ;
    }
    
//     /**
//      * Pass the image URL to the view
//      *
//      * @param FormView $view
//      * @param FormInterface $form
//      * @param array $options
//      */
//     public function buildView(FormView $view, FormInterface $form, array $options)
//     {
//         if (isset($options['setupCharge'])) {
//             $parentData = $form->getParent()->getData();
            
//             $imageUrl = null;
//             if (null !== $parentData) {
//                 $accessor = PropertyAccess::createPropertyAccessor();
//                 $imageUrl = $accessor->getValue($parentData, $options['setupCharge']);
//             }
            
//             // sets an "image_url" variable that will be available when rendering this field
//             $view->vars['setupCharge'] = $imageUrl;
//         }
//         if (isset($options['pricingIncluded'])) {
//             $parentData = $form->getParent()->getData();
            
//             $imageUrl = null;
//             if (null !== $parentData) {
//                 $accessor = PropertyAccess::createPropertyAccessor();
//                 $imageUrl = $accessor->getValue($parentData, $options['pricingIncluded']);
//             }
            
//             // sets an "image_url" variable that will be available when rendering this field
//             $view->vars['pricingIncluded'] = $imageUrl;
//         }
//     }
    
}