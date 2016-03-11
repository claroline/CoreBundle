<?php

namespace Claroline\CoreBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2016/03/11 02:23:37
 */
class Version20160311142335 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_home_tab_config 
            ADD details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)'
        ");
        $this->addSql("
            ALTER TABLE claro_widget_instance 
            ADD template VARCHAR(255) DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_widget_display_config 
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
            ALTER TABLE claro_widget_display_config 
            DROP details
        ");
        $this->addSql("
            ALTER TABLE claro_widget_instance 
            DROP template
        ");
    }
}