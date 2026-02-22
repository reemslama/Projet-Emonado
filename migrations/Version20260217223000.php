<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add music therapy prescription fields to journal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal ADD music_prescription_json LONGTEXT DEFAULT NULL, ADD music_prescription_source VARCHAR(20) DEFAULT NULL, ADD music_prescription_objective VARCHAR(255) DEFAULT NULL, ADD music_prescription_generated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal DROP music_prescription_json, DROP music_prescription_source, DROP music_prescription_objective, DROP music_prescription_generated_at');
    }
}
