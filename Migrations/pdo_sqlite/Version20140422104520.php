<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/04/22 10:45:24
 */
class Version20140422104520 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_user_role_creation (
                id INTEGER NOT NULL, 
                user_id INTEGER NOT NULL, 
                creation_date DATETIME NOT NULL, 
                userRole_id INTEGER NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_709FE2E85DFE78E ON claro_user_role_creation (userRole_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_709FE2EA76ED395 ON claro_user_role_creation (user_id)
        ");
        $this->addSql("
            DROP INDEX UNIQ_317774715E237E06
        ");
        $this->addSql("
            DROP INDEX IDX_3177747182D40A1F
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_role AS 
            SELECT id, 
            workspace_id, 
            name, 
            translation_key, 
            is_read_only, 
            type 
            FROM claro_role
        ");
        $this->addSql("
            DROP TABLE claro_role
        ");
        $this->addSql("
            CREATE TABLE claro_role (
                id INTEGER NOT NULL, 
                workspace_id INTEGER DEFAULT NULL, 
                name VARCHAR(255) NOT NULL, 
                translation_key VARCHAR(255) NOT NULL, 
                is_read_only BOOLEAN NOT NULL, 
                type INTEGER NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_3177747182D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_role (
                id, workspace_id, name, translation_key, 
                is_read_only, type
            ) 
            SELECT id, 
            workspace_id, 
            name, 
            translation_key, 
            is_read_only, 
            type 
            FROM __temp__claro_role
        ");
        $this->addSql("
            DROP TABLE __temp__claro_role
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_317774715E237E06 ON claro_role (name)
        ");
        $this->addSql("
            CREATE INDEX IDX_3177747182D40A1F ON claro_role (workspace_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_user_role_creation
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD COLUMN creation_date DATETIME NOT NULL
        ");
    }
}