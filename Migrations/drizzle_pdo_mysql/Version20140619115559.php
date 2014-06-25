<?php

namespace Claroline\CoreBundle\Migrations\drizzle_pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/06/19 11:56:01
 */
class Version20140619115559 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_badge_rule 
            ADD resultMax VARCHAR(255) DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_activity_rule_action 
            DROP rule_type
        ");
        $this->addSql("
            ALTER TABLE claro_activity_rule 
            ADD resultMax VARCHAR(255) DEFAULT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_activity_rule 
            DROP resultMax
        ");
        $this->addSql("
            ALTER TABLE claro_activity_rule_action 
            ADD rule_type VARCHAR(255) DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_badge_rule 
            DROP resultMax
        ");
    }
}