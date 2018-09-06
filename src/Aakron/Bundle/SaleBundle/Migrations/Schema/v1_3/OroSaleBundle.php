<?php

namespace Aakron\Bundle\SaleBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class OroSaleBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
     
        $this->addAdditinalNotes($schema);
        
      
    }
   
    
    /**
     * @param Schema $schema
     */
    protected function addAdditinalNotes(Schema $schema)
    {
        $table = $schema->getTable('oro_sale_quote');
        
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
