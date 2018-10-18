<?php
namespace Aakron\Bundle\CustomerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ExtendCustomerEntity implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_customer');
        $table->addColumn(
            'account_number',
            'string',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid'  => ['is_visible' => false],
                ],
                'notnull' => false,
                'length' => 100
            ]
            );
        $table->addUniqueIndex(['account_number'], 'oro_idx_account_number');
    }
}
