<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/04/11 03:35:14
 */
class Version20140411153510 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_user 
            ADD COLUMN last_uri VARCHAR(255) DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD COLUMN creation_date DATETIME NOT NULL
        ");
        $this->addSql("
            DROP INDEX UNIQ_A76799FFAA23F6C8
        ");
        $this->addSql("
            DROP INDEX UNIQ_A76799FF2DE62210
        ");
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
            CREATE TEMPORARY TABLE __temp__claro_resource_node AS 
            SELECT id, 
            previous_id, 
            license_id, 
            icon_id, 
            creator_id, 
            parent_id, 
            workspace_id, 
            resource_type_id, 
            next_id, 
            creation_date, 
            modification_date, 
            name, 
            lvl, 
            path, 
            mime_type, 
            class, 
            hash_name 
            FROM claro_resource_node
        ");
        $this->addSql("
            DROP TABLE claro_resource_node
        ");
        $this->addSql("
            CREATE TABLE claro_resource_node (
                id INTEGER NOT NULL, 
                previous_id INTEGER DEFAULT NULL, 
                license_id INTEGER DEFAULT NULL, 
                icon_id INTEGER DEFAULT NULL, 
                creator_id INTEGER NOT NULL, 
                parent_id INTEGER DEFAULT NULL, 
                workspace_id INTEGER NOT NULL, 
                resource_type_id INTEGER NOT NULL, 
                next_id INTEGER DEFAULT NULL, 
                creation_date DATETIME NOT NULL, 
                modification_date DATETIME NOT NULL, 
                name VARCHAR(255) NOT NULL, 
                lvl INTEGER DEFAULT NULL, 
                path VARCHAR(3000) DEFAULT NULL, 
                mime_type VARCHAR(255) DEFAULT NULL, 
                class VARCHAR(256) NOT NULL, 
                hash_name VARCHAR(50) NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_A76799FF2DE62210 FOREIGN KEY (previous_id) 
                REFERENCES claro_resource_node (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF460F904B FOREIGN KEY (license_id) 
                REFERENCES claro_license (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF54B9D732 FOREIGN KEY (icon_id) 
                REFERENCES claro_resource_icon (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF61220EA6 FOREIGN KEY (creator_id) 
                REFERENCES claro_user (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF727ACA70 FOREIGN KEY (parent_id) 
                REFERENCES claro_resource_node (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF82D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF98EC6B7B FOREIGN KEY (resource_type_id) 
                REFERENCES claro_resource_type (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FFAA23F6C8 FOREIGN KEY (next_id) 
                REFERENCES claro_resource_node (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_resource_node (
                id, previous_id, license_id, icon_id, 
                creator_id, parent_id, workspace_id, 
                resource_type_id, next_id, creation_date, 
                modification_date, name, lvl, path, 
                mime_type, class, hash_name
            ) 
            SELECT id, 
            previous_id, 
            license_id, 
            icon_id, 
            creator_id, 
            parent_id, 
            workspace_id, 
            resource_type_id, 
            next_id, 
            creation_date, 
            modification_date, 
            name, 
            lvl, 
            path, 
            mime_type, 
            class, 
            hash_name 
            FROM __temp__claro_resource_node
        ");
        $this->addSql("
            DROP TABLE __temp__claro_resource_node
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FFAA23F6C8 ON claro_resource_node (next_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_A76799FF2DE62210 ON claro_resource_node (previous_id)
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
            CREATE UNIQUE INDEX UNIQ_A76799FFE1F029B6 ON claro_resource_node (hash_name)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP INDEX UNIQ_A76799FFE1F029B6
        ");
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
            class, 
            hash_name 
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
                hash_name VARCHAR(50) NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_A76799FF460F904B FOREIGN KEY (license_id) 
                REFERENCES claro_license (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF98EC6B7B FOREIGN KEY (resource_type_id) 
                REFERENCES claro_resource_type (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF61220EA6 FOREIGN KEY (creator_id) 
                REFERENCES claro_user (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A76799FF54B9D732 FOREIGN KEY (icon_id) 
                REFERENCES claro_resource_icon (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
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
                mime_type, class, hash_name
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
            class, 
            hash_name 
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
        $this->addSql("
            DROP INDEX UNIQ_EB8D2852F85E0677
        ");
        $this->addSql("
            DROP INDEX UNIQ_EB8D28525126AC48
        ");
        $this->addSql("
            DROP INDEX UNIQ_EB8D285282D40A1F
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_user AS 
            SELECT id, 
            workspace_id, 
            first_name, 
            last_name, 
            username, 
            password, 
            locale, 
            salt, 
            phone, 
            mail, 
            administrative_code, 
            creation_date, 
            reset_password, 
            hash_time, 
            picture, 
            description, 
            hasAcceptedTerms, 
            is_enabled, 
            is_mail_notified 
            FROM claro_user
        ");
        $this->addSql("
            DROP TABLE claro_user
        ");
        $this->addSql("
            CREATE TABLE claro_user (
                id INTEGER NOT NULL, 
                workspace_id INTEGER DEFAULT NULL, 
                first_name VARCHAR(50) NOT NULL, 
                last_name VARCHAR(50) NOT NULL, 
                username VARCHAR(255) NOT NULL, 
                password VARCHAR(255) NOT NULL, 
                locale VARCHAR(255) DEFAULT NULL, 
                salt VARCHAR(255) NOT NULL, 
                phone VARCHAR(255) DEFAULT NULL, 
                mail VARCHAR(255) NOT NULL, 
                administrative_code VARCHAR(255) DEFAULT NULL, 
                creation_date DATETIME NOT NULL, 
                reset_password VARCHAR(255) DEFAULT NULL, 
                hash_time INTEGER DEFAULT NULL, 
                picture VARCHAR(255) DEFAULT NULL, 
                description CLOB DEFAULT NULL, 
                hasAcceptedTerms BOOLEAN DEFAULT NULL, 
                is_enabled BOOLEAN NOT NULL, 
                is_mail_notified BOOLEAN NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_EB8D285282D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_user (
                id, workspace_id, first_name, last_name, 
                username, password, locale, salt, 
                phone, mail, administrative_code, 
                creation_date, reset_password, hash_time, 
                picture, description, hasAcceptedTerms, 
                is_enabled, is_mail_notified
            ) 
            SELECT id, 
            workspace_id, 
            first_name, 
            last_name, 
            username, 
            password, 
            locale, 
            salt, 
            phone, 
            mail, 
            administrative_code, 
            creation_date, 
            reset_password, 
            hash_time, 
            picture, 
            description, 
            hasAcceptedTerms, 
            is_enabled, 
            is_mail_notified 
            FROM __temp__claro_user
        ");
        $this->addSql("
            DROP TABLE __temp__claro_user
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_EB8D2852F85E0677 ON claro_user (username)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_EB8D28525126AC48 ON claro_user (mail)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_EB8D285282D40A1F ON claro_user (workspace_id)
        ");
    }
}