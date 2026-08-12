<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Event stream plus wallet and transfer read models.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
                id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                aggregate_type VARCHAR(50) NOT NULL,
                aggregate_id CHAR(36) NOT NULL,
                version INT NOT NULL,
                event_name VARCHAR(100) NOT NULL,
                payload JSON NOT NULL,
                occurred_at DATETIME(6) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_aggregate_version (aggregate_type, aggregate_id, version)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE wallets (
                id CHAR(36) NOT NULL,
                balance INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE wallet_points (
                id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                wallet_id CHAR(36) NOT NULL,
                amount INT NOT NULL,
                transfer_id CHAR(36) DEFAULT NULL,
                registered_at DATETIME(6) NOT NULL,
                PRIMARY KEY (id),
                KEY idx_wallet_points_wallet (wallet_id, registered_at)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE transfers (
                id CHAR(36) NOT NULL,
                source_wallet_id CHAR(36) NOT NULL,
                target_wallet_id CHAR(36) NOT NULL,
                points INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                failure_reason TEXT DEFAULT NULL,
                initiated_at DATETIME(6) NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE transfers');
        $this->addSql('DROP TABLE wallet_points');
        $this->addSql('DROP TABLE wallets');
        $this->addSql('DROP TABLE events');
    }
}
