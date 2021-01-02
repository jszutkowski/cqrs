<?php

declare(strict_types=1);

namespace App\Infrastructure\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210101223050 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE event_store (
                id	            INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                aggregate_id	VARCHAR(255) NOT NULL,
                event_name	    VARCHAR(255) NOT NULL,
                version	        INTEGER NOT NULL,
                payload	        TEXT NOT NULL,
                created_at	    DATETIME NOT NULL,
                UNIQUE (aggregate_id, version)
            );
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_store');
    }
}
