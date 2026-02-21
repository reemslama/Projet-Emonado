<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221130205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE test_adaptatif (id INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(50) NOT NULL, questions_reponses JSON NOT NULL, score_actuel INT NOT NULL, nombre_questions INT NOT NULL, termine TINYINT(1) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, analyse LONGTEXT DEFAULT NULL, profil_patient JSON DEFAULT NULL, patient_id INT DEFAULT NULL, INDEX IDX_9110F0346B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE test_adaptatif ADD CONSTRAINT FK_9110F0346B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation ADD date DATE NOT NULL, ADD compte_rendu LONGTEXT NOT NULL, DROP date_consultation, DROP notes');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id)');
        $this->addSql('ALTER TABLE question CHANGE ordre ordre INT DEFAULT NULL, CHANGE type_question type_question VARCHAR(50) DEFAULT NULL, CHANGE categorie categorie VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE reponse CHANGE ordre ordre INT DEFAULT NULL, CHANGE question_id question_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE type_rendez_vous DROP couleur');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE test_adaptatif DROP FOREIGN KEY FK_9110F0346B899279');
        $this->addSql('DROP TABLE test_adaptatif');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation ADD date_consultation DATETIME NOT NULL, ADD notes LONGTEXT DEFAULT NULL, DROP date, DROP compte_rendu');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question CHANGE ordre ordre INT NOT NULL, CHANGE type_question type_question VARCHAR(50) NOT NULL, CHANGE categorie categorie VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE reponse CHANGE ordre ordre INT NOT NULL, CHANGE question_id question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE type_rendez_vous ADD couleur VARCHAR(7) DEFAULT \'#0d6efd\'');
    }
}
