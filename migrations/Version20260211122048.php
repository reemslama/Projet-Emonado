<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211122048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne couleur à la table type_rendez_vous';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la table existe
        $tables = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'type_rendez_vous'");
        if (empty($tables)) {
            return; // La table sera créée par une migration ultérieure
        }
        // Vérifier si la colonne existe déjà
        $columns = $this->connection->fetchAllAssociative("SHOW COLUMNS FROM type_rendez_vous LIKE 'couleur'");
        if (empty($columns)) {
            $this->addSql('ALTER TABLE type_rendez_vous ADD couleur VARCHAR(7) DEFAULT \'#0d6efd\'');
        }
    }

    public function down(Schema $schema): void
    {
        $tables = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'type_rendez_vous'");
        if (empty($tables)) {
            return;
        }
        $columns = $this->connection->fetchAllAssociative("SHOW COLUMNS FROM type_rendez_vous LIKE 'couleur'");
        if (!empty($columns)) {
            $this->addSql('ALTER TABLE type_rendez_vous DROP couleur');
        }
    }
}