<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508191645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE analyse_emotionnelle (id INT AUTO_INCREMENT NOT NULL, journal_id INT NOT NULL, emotion_principale VARCHAR(255) DEFAULT NULL, niveau_stress INT DEFAULT NULL, score_bien_etre INT DEFAULT NULL, resume_ia LONGTEXT DEFAULT NULL, date_analyse DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_DE8A3A10478E8802 (journal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, patient_id INT NOT NULL, nom_patient VARCHAR(255) DEFAULT NULL, cin VARCHAR(20) DEFAULT NULL, nom_psychologue VARCHAR(255) DEFAULT NULL, date DATETIME DEFAULT NULL, INDEX IDX_FE38F844C54C8C93 (type_id), INDEX IDX_FE38F8446B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment_type (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, couleur VARCHAR(7) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, action VARCHAR(50) DEFAULT NULL, entity_type VARCHAR(100) DEFAULT NULL, entity_id VARCHAR(100) DEFAULT NULL, details LONGTEXT DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F6E1C0F5A76ED395 (user_id), INDEX IDX_F6E1C0F5B03A8386 (created_by_id), INDEX IDX_F6E1C0F5896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consultation (id INT AUTO_INCREMENT NOT NULL, dossier_id INT NOT NULL, psychologue_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, date DATE DEFAULT NULL, compte_rendu LONGTEXT DEFAULT NULL, humeur_patient VARCHAR(100) DEFAULT NULL, sujet_aborde LONGTEXT DEFAULT NULL, observations LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_964685A6611C0C56 (dossier_id), INDEX IDX_964685A6465459D3 (psychologue_id), INDEX IDX_964685A6B03A8386 (created_by_id), INDEX IDX_964685A6896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consultation_document (id INT AUTO_INCREMENT NOT NULL, consultation_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, type_fichier VARCHAR(50) DEFAULT NULL, path_or_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_96280AD362FF6CDF (consultation_id), INDEX IDX_96280AD3B03A8386 (created_by_id), INDEX IDX_96280AD3896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dossier_medical (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, historique_medical LONGTEXT DEFAULT NULL, notes_psychologiques LONGTEXT DEFAULT NULL, diagnostic LONGTEXT DEFAULT NULL, traitement_fond LONGTEXT DEFAULT NULL, objectifs_long_terme LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3581EE626B899279 (patient_id), INDEX IDX_3581EE62B03A8386 (created_by_id), INDEX IDX_3581EE62896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, contenu LONGTEXT DEFAULT NULL, humeur VARCHAR(255) DEFAULT NULL, date_creation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', audio_file_name VARCHAR(255) DEFAULT NULL, input_mode VARCHAR(20) DEFAULT NULL, transcription_provider VARCHAR(50) DEFAULT NULL, psychologue_case_description LONGTEXT DEFAULT NULL, psychologue_reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', patient_advice_seen_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', music_prescription_json LONGTEXT DEFAULT NULL, music_prescription_source VARCHAR(20) DEFAULT NULL, music_prescription_objective VARCHAR(255) DEFAULT NULL, music_prescription_generated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C1A7E74DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B6BD307FF624B39D (sender_id), INDEX IDX_B6BD307FCD53EDB6 (receiver_id), INDEX IDX_B6BD307FB03A8386 (created_by_id), INDEX IDX_B6BD307F896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE prescription (id INT AUTO_INCREMENT NOT NULL, consultation_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, date_prescription DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1FBFB8D962FF6CDF (consultation_id), INDEX IDX_1FBFB8D9B03A8386 (created_by_id), INDEX IDX_1FBFB8D9896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, texte VARCHAR(255) DEFAULT NULL, ordre INT DEFAULT NULL, type_question VARCHAR(50) DEFAULT NULL, categorie VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, texte VARCHAR(255) DEFAULT NULL, valeur INT NOT NULL, ordre INT DEFAULT NULL, INDEX IDX_5FB6DEC71E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE test_adaptatif (id INT AUTO_INCREMENT NOT NULL, patient_id INT DEFAULT NULL, categorie VARCHAR(50) DEFAULT NULL, questions_reponses JSON NOT NULL, score_actuel INT DEFAULT NULL, nombre_questions INT DEFAULT NULL, termine TINYINT(1) DEFAULT NULL, date_debut DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', analyse LONGTEXT DEFAULT NULL, profil_patient JSON DEFAULT NULL, INDEX IDX_9110F0346B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, psychologue_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, sexe VARCHAR(10) DEFAULT NULL, date_naissance DATE DEFAULT NULL, specialite VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, face_id_image_path VARCHAR(255) DEFAULT NULL, has_child TINYINT(1) DEFAULT NULL, reset_password_token VARCHAR(64) DEFAULT NULL, reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649465459D3 (psychologue_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE analyse_emotionnelle ADD CONSTRAINT FK_DE8A3A10478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844C54C8C93 FOREIGN KEY (type_id) REFERENCES appointment_type (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446B899279 FOREIGN KEY (patient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6465459D3 FOREIGN KEY (psychologue_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE consultation_document ADD CONSTRAINT FK_96280AD362FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation_document ADD CONSTRAINT FK_96280AD3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE consultation_document ADD CONSTRAINT FK_96280AD3896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE626B899279 FOREIGN KEY (patient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE62B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE dossier_medical ADD CONSTRAINT FK_3581EE62896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE prescription ADD CONSTRAINT FK_1FBFB8D962FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prescription ADD CONSTRAINT FK_1FBFB8D9B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE prescription ADD CONSTRAINT FK_1FBFB8D9896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE test_adaptatif ADD CONSTRAINT FK_9110F0346B899279 FOREIGN KEY (patient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649465459D3 FOREIGN KEY (psychologue_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyse_emotionnelle DROP FOREIGN KEY FK_DE8A3A10478E8802');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844C54C8C93');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8446B899279');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5B03A8386');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5896DBBDE');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6465459D3');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6B03A8386');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6896DBBDE');
        $this->addSql('ALTER TABLE consultation_document DROP FOREIGN KEY FK_96280AD362FF6CDF');
        $this->addSql('ALTER TABLE consultation_document DROP FOREIGN KEY FK_96280AD3B03A8386');
        $this->addSql('ALTER TABLE consultation_document DROP FOREIGN KEY FK_96280AD3896DBBDE');
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_3581EE626B899279');
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_3581EE62B03A8386');
        $this->addSql('ALTER TABLE dossier_medical DROP FOREIGN KEY FK_3581EE62896DBBDE');
        $this->addSql('ALTER TABLE journal DROP FOREIGN KEY FK_C1A7E74DA76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCD53EDB6');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FB03A8386');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F896DBBDE');
        $this->addSql('ALTER TABLE prescription DROP FOREIGN KEY FK_1FBFB8D962FF6CDF');
        $this->addSql('ALTER TABLE prescription DROP FOREIGN KEY FK_1FBFB8D9B03A8386');
        $this->addSql('ALTER TABLE prescription DROP FOREIGN KEY FK_1FBFB8D9896DBBDE');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE test_adaptatif DROP FOREIGN KEY FK_9110F0346B899279');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649465459D3');
        $this->addSql('DROP TABLE analyse_emotionnelle');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE appointment_type');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE consultation');
        $this->addSql('DROP TABLE consultation_document');
        $this->addSql('DROP TABLE dossier_medical');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE prescription');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE test_adaptatif');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
