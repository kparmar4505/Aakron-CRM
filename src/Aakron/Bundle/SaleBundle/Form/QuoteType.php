<?php

namespace Aakron\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType as baseType; 

class QuoteType extends baseType
{
    public function __construct() {
        parent::__construct();
     //   die('Here we are'); //Checking if this class is loaded at all
    }
    
   
    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
        ->add('additional_notes', TextType::class, [
            'required' => false,
            'label' => 'Additinal Notes',
            'attr' => array(
                'class' => 'js-additional-notes'
            )
        ]);
    }
    
  
}
