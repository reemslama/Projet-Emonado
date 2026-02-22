<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add voice journal fields (audio file, input mode, transcription provider)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal ADD audio_file_name VARCHAR(255) DEFAULT NULL, ADD input_mode VARCHAR(20) DEFAULT NULL, ADD transcription_provider VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal DROP audio_file_name, DROP input_mode, DROP transcription_provider');
    }
}
