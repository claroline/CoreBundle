<?php

namespace Claroline\CoreBundle\Migrations\pdo_pgsql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/04/21 02:11:31
 */
class Version20140421141125 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_role_creation (
                id SERIAL NOT NULL, 
                creation_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                userRole_id INT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_5005B24585DFE78E ON claro_role_creation (userRole_id)
        ");
        $this->addSql("
            ALTER TABLE claro_role_creation 
            ADD CONSTRAINT FK_5005B24585DFE78E FOREIGN KEY (userRole_id) 
            REFERENCES claro_role (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_role_creation
        ");
    }
}