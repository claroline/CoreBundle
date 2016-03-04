<?php

namespace Claroline\CoreBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2016/03/03 01:41:50
 */
class Version20160303134148 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_user_administrator 
            DROP PRIMARY KEY
        ");
        $this->addSql("
            ALTER TABLE claro_user_administrator 
            ADD PRIMARY KEY (user_id, organization_id)
        ");
        $this->addSql("
            ALTER TABLE claro_home_tab_config 
            ADD details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)'
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_home_tab_config 
            DROP details
        ");
        $this->addSql("
            ALTER TABLE claro_user_administrator 
            DROP PRIMARY KEY
        ");
        $this->addSql("
            ALTER TABLE claro_user_administrator 
            ADD PRIMARY KEY (organization_id, user_id)
        ");
    }
}