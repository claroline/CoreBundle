<?php

namespace Claroline\CoreBundle\Migrations\pdo_pgsql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2013/10/14 02:09:00
 */
class Version20131014140900 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_workspace_favourite (
                id SERIAL NOT NULL, 
                workspace_id INT NOT NULL, 
                user_id INT NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_711A30B82D40A1F ON claro_workspace_favourite (workspace_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_711A30BA76ED395 ON claro_workspace_favourite (user_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX workspace_favourite_unique_combination ON claro_workspace_favourite (workspace_id, user_id)
        ");
        $this->addSql("
            ALTER TABLE claro_workspace_favourite 
            ADD CONSTRAINT FK_711A30B82D40A1F FOREIGN KEY (workspace_id) 
            REFERENCES claro_workspace (id) 
            ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
        $this->addSql("
            ALTER TABLE claro_workspace_favourite 
            ADD CONSTRAINT FK_711A30BA76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_workspace_favourite
        ");
    }
}