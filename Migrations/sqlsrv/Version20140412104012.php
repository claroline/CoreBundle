<?php

namespace Claroline\CoreBundle\Migrations\sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/04/12 10:40:16
 */
class Version20140412104012 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_user 
            ADD last_uri NVARCHAR(255)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FFE1F029B6 ON claro_resource_node (hash_name) 
            WHERE hash_name IS NOT NULL
        ");
    }

    public function down(Schema $schema)
    {
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
            DROP COLUMN last_uri
        ");
    }
}