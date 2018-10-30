<?php

namespace Aakron\Bundle\SaleBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        
        $table->dropColumn('additional_note');        
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
