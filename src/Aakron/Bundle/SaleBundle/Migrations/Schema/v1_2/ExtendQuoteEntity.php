<?php
namespace Aakron\Bundle\SaleBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ExtendQuoteEntity implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote_product');
        $table->addColumn(
            'additional_notes',
            'string',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid'  => ['is_visible' => false],
                ],
                'notnull' => false, 
                'length' => 255
            ]
            );
        
        
    }
}
