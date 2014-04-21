<?php

namespace Claroline\CoreBundle\Migrations\oci8;

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
                id NUMBER(10) NOT NULL, 
                user_id NUMBER(10) NOT NULL, 
                creation_date TIMESTAMP(0) NOT NULL, 
                userRole_id NUMBER(10) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'CLARO_USER_ROLE_CREATION' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE CLARO_USER_ROLE_CREATION ADD CONSTRAINT CLARO_USER_ROLE_CREATION_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE CLARO_USER_ROLE_CREATION_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER CLARO_USER_ROLE_CREATION_AI_PK BEFORE INSERT ON CLARO_USER_ROLE_CREATION FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT CLARO_USER_ROLE_CREATION_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT CLARO_USER_ROLE_CREATION_ID_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'CLARO_USER_ROLE_CREATION_ID_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT CLARO_USER_ROLE_CREATION_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
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
            REFERENCES claro_role (id)
        ");
        $this->addSql("
            ALTER TABLE claro_user_role_creation 
            ADD CONSTRAINT FK_709FE2EA76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_user_role_creation
        ");
    }
}