<?php

declare(strict_types=1);

namespace App\Infrastructure\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210101213945 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE wallets (
                id	    VARCHAR(36) NOT NULL PRIMARY KEY,
                balance	INTEGER NOT NULL DEFAULT 0
            );
            SQL);

        $this->addSql(<<<SQL
            CREATE TABLE points (
                id	        INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
                amount	    INTEGER NOT NULL,
                created_at	DATETIME NOT NULL,
                wallet_id	VARCHAR(36) NOT NULL,
                FOREIGN KEY(wallet_id) REFERENCES wallets(id)
            );
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE points');
        $this->addSql('DROP TABLE wallets');
    }
}
