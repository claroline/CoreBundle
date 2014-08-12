<?php

namespace Claroline\CoreBundle\Migrations\ibm_db2;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/07/24 02:39:14
 */
class Version20140724143857 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_user 
            ADD COLUMN exchange_token VARCHAR(255) NOT NULL
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_EB8D28524100ED73 ON claro_user (exchange_token)
        ");
        $this->addSql("
            ALTER TABLE claro_resource_node 
            ADD COLUMN hash_name VARCHAR(50) NOT NULL
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FFE1F029B6 ON claro_resource_node (hash_name)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_resource_node 
            DROP COLUMN hash_name
        ");
        $this->addSql("
            DROP INDEX UNIQ_A76799FFE1F029B6
        ");
        $this->addSql("
            ALTER TABLE claro_user 
            DROP COLUMN exchange_token
        ");
        $this->addSql("
            DROP INDEX UNIQ_EB8D28524100ED73
        ");
    }
}