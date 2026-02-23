<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add patient advice seen timestamp for voice journal advice indicator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal ADD patient_advice_seen_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal DROP patient_advice_seen_at');
    }
}
