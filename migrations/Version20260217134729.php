<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217134729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, nom_patient VARCHAR(255) NOT NULL, cin VARCHAR(20) NOT NULL, nom_psychologue VARCHAR(255) NOT NULL, date DATETIME DEFAULT NULL, type_id INT NOT NULL, INDEX IDX_65E8AA0AC54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_rendez_vous (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AC54C8C93 FOREIGN KEY (type_id) REFERENCES type_rendez_vous (id)');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY `FK_964685A6611C0C56`');
        $this->addSql('ALTER TABLE consultation ADD date DATE NOT NULL, ADD compte_rendu LONGTEXT NOT NULL, DROP date_consultation, DROP notes');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id)');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE question CHANGE ordre ordre INT DEFAULT NULL, CHANGE type_question type_question VARCHAR(50) DEFAULT NULL, CHANGE categorie categorie VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY `FK_5FB6DEC71E27F6BF`');
        $this->addSql('ALTER TABLE reponse CHANGE ordre ordre INT DEFAULT NULL, CHANGE question_id question_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AC54C8C93');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE type_rendez_vous');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation ADD date_consultation DATETIME NOT NULL, ADD notes LONGTEXT DEFAULT NULL, DROP date, DROP compte_rendu');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT `FK_964685A6611C0C56` FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal DROP FOREIGN KEY FK_C1A7E74DA76ED395');
        $this->addSql('ALTER TABLE question CHANGE ordre ordre INT NOT NULL, CHANGE type_question type_question VARCHAR(50) NOT NULL, CHANGE categorie categorie VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE reponse CHANGE ordre ordre INT NOT NULL, CHANGE question_id question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT `FK_5FB6DEC71E27F6BF` FOREIGN KEY (question_id) REFERENCES question (id)');
    }
}
