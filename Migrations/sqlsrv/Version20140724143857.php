<?php

namespace Claroline\CoreBundle\Migrations\sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/07/24 02:39:16
 */
class Version20140724143857 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_user 
            ADD exchange_token NVARCHAR(255) NOT NULL
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_EB8D28524100ED73 ON claro_user (exchange_token) 
            WHERE exchange_token IS NOT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_resource_node 
            ADD hash_name NVARCHAR(50) NOT NULL
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FFE1F029B6 ON claro_resource_node (hash_name) 
            WHERE hash_name IS NOT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_resource_node 
            DROP COLUMN hash_name
        ");
        $this->addSql("
            IF EXISTS (
                SELECT * 
                FROM sysobjects 
                WHERE name = 'UNIQ_A76799FFE1F029B6'
            ) 
            ALTER TABLE claro_resource_node 
            DROP CONSTRAINT UNIQ_A76799FFE1F029B6 ELSE 
            DROP INDEX UNIQ_A76799FFE1F029B6 ON claro_resource_node
        ");
        $this->addSql("
            ALTER TABLE claro_user 
            DROP COLUMN exchange_token
        ");
        $this->addSql("
            IF EXISTS (
                SELECT * 
                FROM sysobjects 
                WHERE name = 'UNIQ_EB8D28524100ED73'
            ) 
            ALTER TABLE claro_user 
            DROP CONSTRAINT UNIQ_EB8D28524100ED73 ELSE 
            DROP INDEX UNIQ_EB8D28524100ED73 ON claro_user
        ");
    }
}