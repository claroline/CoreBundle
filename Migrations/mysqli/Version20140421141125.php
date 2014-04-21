<?php

namespace Claroline\CoreBundle\Migrations\mysqli;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/04/21 02:11:32
 */
class Version20140421141125 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_role_creation (
                id INT AUTO_INCREMENT NOT NULL, 
                creation_date DATETIME NOT NULL, 
                userRole_id INT DEFAULT NULL, 
                INDEX IDX_5005B24585DFE78E (userRole_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE claro_role_creation 
            ADD CONSTRAINT FK_5005B24585DFE78E FOREIGN KEY (userRole_id) 
            REFERENCES claro_role (id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_role_creation
        ");
    }
}