<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration déjà exécutée sur certaines bases (enregistrement orphelin).
 * Ne fait rien pour éviter les conflits avec les migrations existantes.
 */
final class Version20260208161613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: migration déjà appliquée (enregistrement orphelin)';
    }

    public function up(Schema $schema): void
    {
        // Déjà appliquée sur cette base ; rien à faire.
    }

    public function down(Schema $schema): void
    {
        // Rien à annuler.
    }
}
