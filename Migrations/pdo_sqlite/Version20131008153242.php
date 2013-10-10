<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2013/10/08 03:32:44
 */
class Version20131008153242 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_badge_rule (
                id INTEGER NOT NULL, 
                badge_id INTEGER NOT NULL, 
                occurrence INTEGER NOT NULL, 
                \"action\" VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_805FCB8FF7A2C2FC ON claro_badge_rule (badge_id)
        ");
        $this->addSql("
            DROP INDEX badge_claim_unique
        ");
        $this->addSql("
            DROP INDEX IDX_487A496AA76ED395
        ");
        $this->addSql("
            DROP INDEX IDX_487A496AF7A2C2FC
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_badge_claim AS 
            SELECT id, 
            user_id, 
            badge_id, 
            claimed_at 
            FROM claro_badge_claim
        ");
        $this->addSql("
            DROP TABLE claro_badge_claim
        ");
        $this->addSql("
            CREATE TABLE claro_badge_claim (
                id INTEGER NOT NULL, 
                user_id INTEGER NOT NULL, 
                badge_id INTEGER NOT NULL, 
                claimed_at DATETIME NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_487A496AA76ED395 FOREIGN KEY (user_id) 
                REFERENCES claro_user (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_487A496AF7A2C2FC FOREIGN KEY (badge_id) 
                REFERENCES claro_badge (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_badge_claim (id, user_id, badge_id, claimed_at) 
            SELECT id, 
            user_id, 
            badge_id, 
            claimed_at 
            FROM __temp__claro_badge_claim
        ");
        $this->addSql("
            DROP TABLE __temp__claro_badge_claim
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_claim_unique ON claro_badge_claim (user_id, badge_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_487A496AA76ED395 ON claro_badge_claim (user_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_487A496AF7A2C2FC ON claro_badge_claim (badge_id)
        ");
        $this->addSql("
            DROP INDEX badge_translation_unique_idx
        ");
        $this->addSql("
            DROP INDEX badge_name_translation_unique_idx
        ");
        $this->addSql("
            DROP INDEX badge_slug_translation_unique_idx
        ");
        $this->addSql("
            DROP INDEX IDX_849BC831F7A2C2FC
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_badge_translation AS 
            SELECT id, 
            badge_id, 
            locale, 
            name, 
            description, 
            slug, 
            criteria 
            FROM claro_badge_translation
        ");
        $this->addSql("
            DROP TABLE claro_badge_translation
        ");
        $this->addSql("
            CREATE TABLE claro_badge_translation (
                id INTEGER NOT NULL, 
                badge_id INTEGER DEFAULT NULL, 
                locale VARCHAR(8) NOT NULL, 
                name VARCHAR(128) NOT NULL, 
                description VARCHAR(128) NOT NULL, 
                slug VARCHAR(128) NOT NULL, 
                criteria CLOB NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_849BC831F7A2C2FC FOREIGN KEY (badge_id) 
                REFERENCES claro_badge (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_badge_translation (
                id, badge_id, locale, name, description, 
                slug, criteria
            ) 
            SELECT id, 
            badge_id, 
            locale, 
            name, 
            description, 
            slug, 
            criteria 
            FROM __temp__claro_badge_translation
        ");
        $this->addSql("
            DROP TABLE __temp__claro_badge_translation
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_translation_unique_idx ON claro_badge_translation (locale, badge_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_name_translation_unique_idx ON claro_badge_translation (name, locale, badge_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_slug_translation_unique_idx ON claro_badge_translation (slug, locale, badge_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_849BC831F7A2C2FC ON claro_badge_translation (badge_id)
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_badge AS 
            SELECT id, 
            version, 
            image, 
            expired_at 
            FROM claro_badge
        ");
        $this->addSql("
            DROP TABLE claro_badge
        ");
        $this->addSql("
            CREATE TABLE claro_badge (
                id INTEGER NOT NULL, 
                workspace_id INTEGER DEFAULT NULL, 
                version INTEGER NOT NULL, 
                image VARCHAR(255) NOT NULL, 
                expired_at DATETIME DEFAULT NULL, 
                automatic_award BOOLEAN DEFAULT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_74F39F0F82D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_badge (id, version, image, expired_at) 
            SELECT id, 
            version, 
            image, 
            expired_at 
            FROM __temp__claro_badge
        ");
        $this->addSql("
            DROP TABLE __temp__claro_badge
        ");
        $this->addSql("
            CREATE INDEX IDX_74F39F0F82D40A1F ON claro_badge (workspace_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_badge_rule
        ");
        $this->addSql("
            DROP INDEX IDX_74F39F0F82D40A1F
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_badge AS 
            SELECT id, 
            version, 
            image, 
            expired_at 
            FROM claro_badge
        ");
        $this->addSql("
            DROP TABLE claro_badge
        ");
        $this->addSql("
            CREATE TABLE claro_badge (
                id INTEGER NOT NULL, 
                version INTEGER NOT NULL, 
                image VARCHAR(255) NOT NULL, 
                expired_at DATETIME DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            INSERT INTO claro_badge (id, version, image, expired_at) 
            SELECT id, 
            version, 
            image, 
            expired_at 
            FROM __temp__claro_badge
        ");
        $this->addSql("
            DROP TABLE __temp__claro_badge
        ");
        $this->addSql("
            DROP INDEX IDX_487A496AA76ED395
        ");
        $this->addSql("
            DROP INDEX IDX_487A496AF7A2C2FC
        ");
        $this->addSql("
            DROP INDEX badge_claim_unique
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_badge_claim AS 
            SELECT id, 
            user_id, 
            badge_id, 
            claimed_at 
            FROM claro_badge_claim
        ");
        $this->addSql("
            DROP TABLE claro_badge_claim
        ");
        $this->addSql("
            CREATE TABLE claro_badge_claim (
                id INTEGER NOT NULL, 
                user_id INTEGER NOT NULL, 
                badge_id INTEGER NOT NULL, 
                claimed_at DATETIME NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            INSERT INTO claro_badge_claim (id, user_id, badge_id, claimed_at) 
            SELECT id, 
            user_id, 
            badge_id, 
            claimed_at 
            FROM __temp__claro_badge_claim
        ");
        $this->addSql("
            DROP TABLE __temp__claro_badge_claim
        ");
        $this->addSql("
            CREATE INDEX IDX_487A496AA76ED395 ON claro_badge_claim (user_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_487A496AF7A2C2FC ON claro_badge_claim (badge_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_claim_unique ON claro_badge_claim (user_id, badge_id)
        ");
        $this->addSql("
            DROP INDEX IDX_849BC831F7A2C2FC
        ");
        $this->addSql("
            DROP INDEX badge_translation_unique_idx
        ");
        $this->addSql("
            DROP INDEX badge_name_translation_unique_idx
        ");
        $this->addSql("
            DROP INDEX badge_slug_translation_unique_idx
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_badge_translation AS 
            SELECT id, 
            badge_id, 
            locale, 
            name, 
            description, 
            slug, 
            criteria 
            FROM claro_badge_translation
        ");
        $this->addSql("
            DROP TABLE claro_badge_translation
        ");
        $this->addSql("
            CREATE TABLE claro_badge_translation (
                id INTEGER NOT NULL, 
                badge_id INTEGER DEFAULT NULL, 
                locale VARCHAR(8) NOT NULL, 
                name VARCHAR(128) NOT NULL, 
                description VARCHAR(128) NOT NULL, 
                slug VARCHAR(128) NOT NULL, 
                criteria CLOB NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            INSERT INTO claro_badge_translation (
                id, badge_id, locale, name, description, 
                slug, criteria
            ) 
            SELECT id, 
            badge_id, 
            locale, 
            name, 
            description, 
            slug, 
            criteria 
            FROM __temp__claro_badge_translation
        ");
        $this->addSql("
            DROP TABLE __temp__claro_badge_translation
        ");
        $this->addSql("
            CREATE INDEX IDX_849BC831F7A2C2FC ON claro_badge_translation (badge_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_translation_unique_idx ON claro_badge_translation (locale, badge_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_name_translation_unique_idx ON claro_badge_translation (name, locale, badge_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX badge_slug_translation_unique_idx ON claro_badge_translation (slug, locale, badge_id)
        ");
    }
}