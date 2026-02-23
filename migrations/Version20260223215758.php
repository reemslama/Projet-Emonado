<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223215758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, entity_type VARCHAR(100) DEFAULT NULL, entity_id INT DEFAULT NULL, details LONGTEXT DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_F6E1C0F5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE consultation_document (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, type_fichier VARCHAR(50) NOT NULL, path_or_url VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, consultation_id INT NOT NULL, INDEX IDX_96280AD362FF6CDF (consultation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prescription (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_prescription DATETIME NOT NULL, created_at DATETIME NOT NULL, consultation_id INT NOT NULL, INDEX IDX_1FBFB8D962FF6CDF (consultation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE consultation_document ADD CONSTRAINT FK_96280AD362FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prescription ADD CONSTRAINT FK_1FBFB8D962FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD humeur_patient VARCHAR(100) DEFAULT NULL, ADD sujet_aborde LONGTEXT DEFAULT NULL, ADD observations LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id)');
        $this->addSql('ALTER TABLE dossier_medical ADD diagnostic LONGTEXT DEFAULT NULL, ADD traitement_fond LONGTEXT DEFAULT NULL, ADD objectifs_long_terme LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD notes_prochaine_consultation LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE consultation_document DROP FOREIGN KEY FK_96280AD362FF6CDF');
        $this->addSql('ALTER TABLE prescription DROP FOREIGN KEY FK_1FBFB8D962FF6CDF');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE consultation_document');
        $this->addSql('DROP TABLE prescription');
        $this->addSql('ALTER TABLE dossier_medical DROP diagnostic, DROP traitement_fond, DROP objectifs_long_terme');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation DROP humeur_patient, DROP sujet_aborde, DROP observations');
        $this->addSql('ALTER TABLE `user` DROP notes_prochaine_consultation');
    }
}
