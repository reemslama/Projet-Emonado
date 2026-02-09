<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208161613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, sexe VARCHAR(10) DEFAULT NULL, date_naissance DATE DEFAULT NULL, specialite VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE dossier_medical (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, historique_medical LONGTEXT DEFAULT NULL, notes_psychologiques LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_8F2B4F6B6B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consultation (id INT AUTO_INCREMENT NOT NULL, psychologue_id INT NOT NULL, dossier_id INT NOT NULL, date_consultation DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_9646856A6B899279 (psychologue_id), INDEX IDX_9646856A611C0C56 (dossier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_8F2B4F6B6B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_9646856A6B899279 FOREIGN KEY (psychologue_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_9646856A611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_8F2B4F6B6B899279');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_9646856A6B899279');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_9646856A611C0C56');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE dossier_medical');
        $this->addSql('DROP TABLE consultation');
    }
}
