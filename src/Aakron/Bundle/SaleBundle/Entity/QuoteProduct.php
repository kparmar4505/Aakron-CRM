<?php
namespace Aakron\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Oro\Bundle\SaleBundle\Entity\QuoteProduct as baseQuoteProduct;
/**
 * @ORM\Entity()
 * @Config()
 */
class QuoteProduct extends baseQuoteProduct
{
    /**
     * A skeleton method for the getter. You can add it to use autocomplete hints from the IDE.
     * The real implementation of this method is auto generated.
     *
     * @return string
     */
    public function getPartnerSince()
    {
    }
    
    /**
     * A skeleton method for the setter. You can add it to use autocomplete hints from the IDE.
     * The real implementation of this method is auto generated.
     *
     * @param string $quoteSetUpCharge
     */
    public function setQuoteSetUpCharge($quoteSetUpCharge)
    {
    }
}