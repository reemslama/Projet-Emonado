<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223010439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id)');
        $this->addSql('ALTER TABLE journal ADD audio_file_name VARCHAR(255) DEFAULT NULL, ADD input_mode VARCHAR(20) DEFAULT NULL, ADD transcription_provider VARCHAR(50) DEFAULT NULL, ADD psychologue_case_description LONGTEXT DEFAULT NULL, ADD psychologue_reviewed_at DATETIME DEFAULT NULL, ADD patient_advice_seen_at DATETIME DEFAULT NULL, ADD music_prescription_json LONGTEXT DEFAULT NULL, ADD music_prescription_source VARCHAR(20) DEFAULT NULL, ADD music_prescription_objective VARCHAR(255) DEFAULT NULL, ADD music_prescription_generated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD patient_id INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A6B899279 ON rendez_vous (patient_id)');
        $this->addSql('ALTER TABLE type_rendez_vous ADD couleur VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE journal DROP audio_file_name, DROP input_mode, DROP transcription_provider, DROP psychologue_case_description, DROP psychologue_reviewed_at, DROP patient_advice_seen_at, DROP music_prescription_json, DROP music_prescription_source, DROP music_prescription_objective, DROP music_prescription_generated_at');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B899279');
        $this->addSql('DROP INDEX IDX_65E8AA0A6B899279 ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous DROP patient_id');
        $this->addSql('ALTER TABLE type_rendez_vous DROP couleur');
    }
}
