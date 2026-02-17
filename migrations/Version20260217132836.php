<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217132836 extends AbstractMigration
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6611C0C56');
        $this->addSql('ALTER TABLE consultation ADD date_consultation DATETIME NOT NULL, ADD notes LONGTEXT DEFAULT NULL, DROP date, DROP compte_rendu');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier_medical (id) ON DELETE CASCADE');
    }
}
