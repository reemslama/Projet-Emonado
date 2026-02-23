<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add journal audio/transcription/music prescription columns';
    }

    public function up(Schema $schema): void
    {
        // MySQL specific SQL
        $this->addSql("ALTER TABLE journal 
            ADD audio_file_name VARCHAR(255) DEFAULT NULL,
            ADD input_mode VARCHAR(20) DEFAULT NULL,
            ADD transcription_provider VARCHAR(50) DEFAULT NULL,
            ADD psychologue_case_description LONGTEXT DEFAULT NULL,
            ADD psychologue_reviewed_at DATETIME DEFAULT NULL,
            ADD patient_advice_seen_at DATETIME DEFAULT NULL,
            ADD music_prescription_json LONGTEXT DEFAULT NULL,
            ADD music_prescription_source VARCHAR(20) DEFAULT NULL,
            ADD music_prescription_objective VARCHAR(255) DEFAULT NULL,
            ADD music_prescription_generated_at DATETIME DEFAULT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE journal 
            DROP COLUMN audio_file_name,
            DROP COLUMN input_mode,
            DROP COLUMN transcription_provider,
            DROP COLUMN psychologue_case_description,
            DROP COLUMN psychologue_reviewed_at,
            DROP COLUMN patient_advice_seen_at,
            DROP COLUMN music_prescription_json,
            DROP COLUMN music_prescription_source,
            DROP COLUMN music_prescription_objective,
            DROP COLUMN music_prescription_generated_at
        ");
    }
}
