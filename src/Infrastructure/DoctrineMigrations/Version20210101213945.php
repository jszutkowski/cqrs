<?php

declare(strict_types=1);

namespace App\Infrastructure\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210101213945 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE "wallets" (
                "id"	    TEXT NOT NULL,
                "points"	INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY("id")
            );
            SQL);

        $this->addSql(<<<SQL
            CREATE TABLE "points" (
                "id"	        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "amount"	    INTEGER NOT NULL,
                "created_at"	INTEGER NOT NULL,
                "wallet_id"	    TEXT NOT NULL,
                FOREIGN KEY(wallet_id) REFERENCES wallets(id)
            );
            SQL);

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE points');
        $this->addSql('DROP TABLE wallets');
    }
}
