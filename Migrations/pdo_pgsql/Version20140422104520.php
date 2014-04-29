<?php

namespace Claroline\CoreBundle\Migrations\pdo_pgsql;

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
                id SERIAL NOT NULL, 
                user_id INT NOT NULL, 
                creation_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                userRole_id INT NOT NULL, 
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
            ALTER TABLE claro_user_role_creation 
            ADD CONSTRAINT FK_709FE2E85DFE78E FOREIGN KEY (userRole_id) 
            REFERENCES claro_role (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
        $this->addSql("
            ALTER TABLE claro_user_role_creation 
            ADD CONSTRAINT FK_709FE2EA76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            DROP creation_date
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_user_role_creation
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD creation_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        ");
    }
}