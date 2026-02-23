<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214153208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Si les tables principales (par exemple `journal`) existent déjà,
        // on considère que le schéma initial a été créé en dehors de cette migration
        // (ancien dump SQL, ancien jeu de migrations, etc.)
        // Pour ne pas casser le travail existant, on rend cette migration idempotente.
        $schemaManager = method_exists($this->connection, 'createSchemaManager')
            ? $this->connection->createSchemaManager()
            : $this->connection->getSchemaManager();

        if ($schemaManager->tablesExist(['journal'])) {
            // Le schéma de base (dont la table `journal`) existe déjà :
            // on ne refait pas les CREATE TABLE / FOREIGN KEY.
            return;
        }

        // Schéma initial sur une base neuve
        $this->addSql('CREATE TABLE analyse_emotionnelle (id INT AUTO_INCREMENT NOT NULL, emotion_principale VARCHAR(255) NOT NULL, niveau_stress INT NOT NULL, score_bien_etre INT NOT NULL, resume_ia LONGTEXT DEFAULT NULL, date_analyse DATETIME NOT NULL, journal_id INT NOT NULL, UNIQUE INDEX UNIQ_DE8A3A10478E8802 (journal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE consultation (id INT AUTO_INCREMENT NOT NULL, date_consultation DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, psychologue_id INT DEFAULT NULL, dossier_id INT NOT NULL, INDEX IDX_964685A6465459D3 (psychologue_id), INDEX IDX_964685A6611C0C56 (dossier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dossier_medical (id INT AUTO_INCREMENT NOT NULL, historique_medical LONGTEXT DEFAULT NULL, notes_psychologiques LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, patient_id INT NOT NULL, INDEX IDX_3581EE626B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, humeur VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_C1A7E74DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_rendez_vous (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, couleur VARCHAR(7) DEFAULT \'#0d6efd\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, nom_patient VARCHAR(255) NOT NULL, cin VARCHAR(20) NOT NULL, nom_psychologue VARCHAR(255) NOT NULL, date DATETIME DEFAULT NULL, INDEX IDX_65E8AA0AC54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, texte VARCHAR(255) NOT NULL, ordre INT NOT NULL, type_question VARCHAR(50) NOT NULL, categorie VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, texte VARCHAR(255) NOT NULL, valeur INT NOT NULL, ordre INT NOT NULL, question_id INT DEFAULT NULL, INDEX IDX_5FB6DEC71E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, sexe VARCHAR(10) DEFAULT NULL, date_naissance DATE DEFAULT NULL, specialite VARCHAR(255) DEFAULT NULL, reset_password_token VARCHAR(64) DEFAULT NULL, reset_password_token_expires_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE analyse_emotionnelle ADD CONSTRAINT FK_DE8A3A10478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6465459D3 FOREIGN KEY (psychologue_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE626B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AC54C8C93 FOREIGN KEY (type_id) REFERENCES type_rendez_vous (id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyse_emotionnelle DROP FOREIGN KEY FK_DE8A3A10478E8802');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6465459D3');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_3581EE626B899279');
        $this->addSql('ALTER TABLE journal DROP FOREIGN KEY FK_C1A7E74DA76ED395');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AC54C8C93');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('DROP TABLE analyse_emotionnelle');
        $this->addSql('DROP TABLE consultation');
        $this->addSql('DROP TABLE dossier_medical');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE type_rendez_vous');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
