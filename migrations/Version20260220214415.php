<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220214415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation ADD date DATE NOT NULL, ADD compte_rendu LONGTEXT NOT NULL, DROP date_consultation, DROP notes');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id)');
        $this->addSql('ALTER TABLE question CHANGE ordre ordre INT DEFAULT NULL, CHANGE type_question type_question VARCHAR(50) DEFAULT NULL, CHANGE categorie categorie VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD patient_id INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A6B899279 ON rendez_vous (patient_id)');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE reponse CHANGE ordre ordre INT DEFAULT NULL, CHANGE question_id question_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE type_rendez_vous DROP couleur');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation ADD date_consultation DATETIME NOT NULL, ADD notes LONGTEXT DEFAULT NULL, DROP date, DROP compte_rendu');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question CHANGE ordre ordre INT NOT NULL, CHANGE type_question type_question VARCHAR(50) NOT NULL, CHANGE categorie categorie VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B899279');
        $this->addSql('DROP INDEX IDX_65E8AA0A6B899279 ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous DROP patient_id');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE reponse CHANGE ordre ordre INT NOT NULL, CHANGE question_id question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE type_rendez_vous ADD couleur VARCHAR(7) DEFAULT \'#0d6efd\'');
    }
}
