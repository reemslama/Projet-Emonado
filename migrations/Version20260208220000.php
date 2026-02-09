<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sync user table with current Entity\User (roles, password column name, telephone, specialite).
 */
final class Version20260208220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update user table to match current User entity (roles, password, telephone, specialite)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD roles JSON DEFAULT NULL, ADD telephone VARCHAR(20) DEFAULT NULL, ADD specialite VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE `user` SET roles = '[]' WHERE roles IS NULL");
        $this->addSql('ALTER TABLE `user` MODIFY roles JSON NOT NULL');
        $this->addSql('ALTER TABLE `user` DROP COLUMN num_tel');
        $this->addSql('ALTER TABLE `user` CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE date_naissance date_naissance DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE sexe sexe VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE mot_de_passe password VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON `user` (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP roles, DROP telephone, DROP specialite');
        $this->addSql('ALTER TABLE `user` ADD num_tel VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE email email VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE date_naissance date_naissance DATE NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE sexe sexe VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE password mot_de_passe VARCHAR(255) NOT NULL');
    }
}
