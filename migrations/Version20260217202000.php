<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217202000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add psychologue review fields for voice journals';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal ADD psychologue_case_description LONGTEXT DEFAULT NULL, ADD psychologue_reviewed_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal DROP psychologue_case_description, DROP psychologue_reviewed_at');
    }
}

