<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/04/21 09:53:08
 */
class Version20140421215303 extends AbstractMigration
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
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_user_role_creation
        ");
    }
}