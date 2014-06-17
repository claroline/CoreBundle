<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/06/02 04:40:34
 */
class Version20140602164033 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_resource_node 
            ADD COLUMN accessible_from DATETIME DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_resource_node 
            ADD COLUMN accessible_until DATETIME DEFAULT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP INDEX IDX_A76799FF460F904B
        ");
        $this->addSql("
            DROP INDEX IDX_A76799FF98EC6B7B
        ");
        $this->addSql("
            DROP INDEX IDX_A76799FF61220EA6
        ");
        $this->addSql("
            DROP INDEX IDX_A76799FF54B9D732
        ");
        $this->addSql("
            DROP INDEX IDX_A76799FF727ACA70
        ");
        $this->addSql("
            DROP INDEX IDX_A76799FF82D40A1F
        ");
        $this->addSql("
            DROP INDEX UNIQ_A76799FFAA23F6C8
        ");
        $this->addSql("
            DROP INDEX UNIQ_A76799FF2DE62210
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_resource_node AS 
            SELECT id, 
            license_id, 
            resource_type_id, 
            creator_id, 
            icon_id, 
            parent_id, 
            workspace_id, 
            next_id, 
            previous_id, 
            creation_date, 
            modification_date, 
            name, 
            lvl, 
            path, 
            mime_type, 
            class 
            FROM claro_resource_node
        ");
        $this->addSql("
            DROP TABLE claro_resource_node
        ");
        $this->addSql("
            CREATE TABLE claro_resource_node (
                id INTEGER NOT NULL, 
                license_id INTEGER DEFAULT NULL, 
                resource_type_id INTEGER NOT NULL, 
                creator_id INTEGER NOT NULL, 
                icon_id INTEGER DEFAULT NULL, 
                parent_id INTEGER DEFAULT NULL, 
                workspace_id INTEGER NOT NULL, 
                next_id INTEGER DEFAULT NULL, 
                previous_id INTEGER DEFAULT NULL, 
                creation_date DATETIME NOT NULL, 
                modification_date DATETIME NOT NULL, 
                name VARCHAR(255) NOT NULL, 
                lvl INTEGER DEFAULT NULL, 
                path VARCHAR(3000) DEFAULT NULL, 
                mime_type VARCHAR(255) DEFAULT NULL, 
                class VARCHAR(256) NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_A76799FF460F904B FOREIGN KEY (license_id) 
                REFERENCES claro_license (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF98EC6B7B FOREIGN KEY (resource_type_id) 
                REFERENCES claro_resource_type (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF61220EA6 FOREIGN KEY (creator_id) 
                REFERENCES claro_user (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF54B9D732 FOREIGN KEY (icon_id) 
                REFERENCES claro_resource_icon (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF727ACA70 FOREIGN KEY (parent_id) 
                REFERENCES claro_resource_node (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF82D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FFAA23F6C8 FOREIGN KEY (next_id) 
                REFERENCES claro_resource_node (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF2DE62210 FOREIGN KEY (previous_id) 
                REFERENCES claro_resource_node (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_resource_node (
                id, license_id, resource_type_id, 
                creator_id, icon_id, parent_id, workspace_id, 
                next_id, previous_id, creation_date, 
                modification_date, name, lvl, path, 
                mime_type, class
            ) 
            SELECT id, 
            license_id, 
            resource_type_id, 
            creator_id, 
            icon_id, 
            parent_id, 
            workspace_id, 
            next_id, 
            previous_id, 
            creation_date, 
            modification_date, 
            name, 
            lvl, 
            path, 
            mime_type, 
            class 
            FROM __temp__claro_resource_node
        ");
        $this->addSql("
            DROP TABLE __temp__claro_resource_node
        ");
        $this->addSql("
            CREATE INDEX IDX_A76799FF460F904B ON claro_resource_node (license_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_A76799FF98EC6B7B ON claro_resource_node (resource_type_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_A76799FF61220EA6 ON claro_resource_node (creator_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_A76799FF54B9D732 ON claro_resource_node (icon_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_A76799FF727ACA70 ON claro_resource_node (parent_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_A76799FF82D40A1F ON claro_resource_node (workspace_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FFAA23F6C8 ON claro_resource_node (next_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FF2DE62210 ON claro_resource_node (previous_id)
        ");
    }
}