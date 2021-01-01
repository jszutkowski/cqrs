<?php

declare(strict_types=1);

namespace App\Infrastructure\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210101223050 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE "events_store" (
                "id"	            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "aggregate_class"	TEXT NOT NULL,
                "aggregate_id"	    INTEGER NOT NULL,
                "event_name"	    INTEGER NOT NULL,
                "version"	        INTEGER NOT NULL,
                "payload"	        TEXT NOT NULL,
                "created_at"	        INTEGER NOT NULL,
                UNIQUE (aggregate_class, aggregate_id, version)
            );
            SQL);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE events_store');
    }
}
