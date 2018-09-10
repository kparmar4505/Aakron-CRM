<?php
namespace Aakron\Bundle\SaleBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\SaleBundle\Form\Type\QuoteType;


use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class QuoteTypeExtension extends AbstractTypeExtension
{
    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return QuoteType::class;
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
        ->add('additional_notes', TextareaType::class, [
            'required' => false,
            'label' => 'oro.sale.quote.additional_notes.label',
            'attr' => array(
                'class' => 'js-additional-notes'
            )
        ]) 
      
       ->add('fob', ChoiceType::class, array(
         
            'choices'  => array(
                'NY'=>'NY',
                'TN'=>'TN',
                'NY & TN'=>'NY & TN',
                'Overseas'=>'Overseas',
            ),
           'label' => 'oro.sale.quote.fob.label',
            'choices_as_values' => true,
        ))
        ->add('quote_status', ChoiceType::class, array(
     
            'choices'  => array(
                'New' => 'New',
                'Accepted' => 'Accepted',
                'Rejected' => 'Rejected',
                'Expired' => 'Expired',
            ),
            'label' => 'oro.sale.quote.quote_status.label',
            'choices_as_values' => true,
        ))
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