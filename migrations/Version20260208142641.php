<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208142641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE analyse_emotionnelle (id INT AUTO_INCREMENT NOT NULL, emotion_principale VARCHAR(255) NOT NULL, niveau_stress INT NOT NULL, score_bien_etre INT NOT NULL, resume_ia LONGTEXT DEFAULT NULL, date_analyse DATETIME NOT NULL, journal_id INT NOT NULL, UNIQUE INDEX UNIQ_DE8A3A10478E8802 (journal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, humeur VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_C1A7E74DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(50) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, num_tel VARCHAR(10) NOT NULL, date_naissance DATE NOT NULL, sexe VARCHAR(10) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE analyse_emotionnelle ADD CONSTRAINT FK_DE8A3A10478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id)');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyse_emotionnelle DROP FOREIGN KEY FK_DE8A3A10478E8802');
        $this->addSql('ALTER TABLE journal DROP FOREIGN KEY FK_C1A7E74DA76ED395');
        $this->addSql('DROP TABLE analyse_emotionnelle');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
