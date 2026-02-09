<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Align user and messenger_messages columns with entity mapping (nullable, types).
 */
final class Version20260208230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync user and messenger_messages column definitions with mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE sexe sexe VARCHAR(10) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE specialite specialite VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE date_naissance date_naissance DATE NOT NULL, CHANGE sexe sexe VARCHAR(10) NOT NULL, CHANGE roles roles JSON NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE specialite specialite VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME NOT NULL');
    }
}
