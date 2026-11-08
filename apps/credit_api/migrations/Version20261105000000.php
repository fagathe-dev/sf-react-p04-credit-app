<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Espace client et back-office : client, advisor, dossier, otp_code (MySQL 8 / MariaDB).
 */
final class Version20261105000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Espace client et back-office conseiller : tables client, advisor, dossier et otp_code.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration prévue pour MySQL / MariaDB.');

        $this->addSql('CREATE TABLE advisor (id BINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_advisor_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE client (id BINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, civility VARCHAR(10) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, birth_date DATE NOT NULL, phone VARCHAR(20) NOT NULL, consent_client_space_created_at DATETIME NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_client_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dossier (id BINARY(16) NOT NULL, status VARCHAR(30) NOT NULL, submitted_at DATETIME NOT NULL, decision VARCHAR(30) NOT NULL, request_snapshot JSON NOT NULL, scoring_summary JSON NOT NULL, quotes JSON NOT NULL, advisor_fallback JSON DEFAULT NULL, rejection JSON DEFAULT NULL, consent_partner_transmission_at DATETIME NOT NULL, consent_advisor_callback_at DATETIME DEFAULT NULL, acquisition JSON DEFAULT NULL, callback_status VARCHAR(30) DEFAULT NULL, advisor_note LONGTEXT DEFAULT NULL, callback_updated_at DATETIME DEFAULT NULL, client_id BINARY(16) NOT NULL, callback_advisor_id BINARY(16) DEFAULT NULL, INDEX idx_dossier_client_status (client_id, status), INDEX idx_dossier_callback (status, consent_advisor_callback_at, decision), INDEX IDX_3D48E03719EB6921 (client_id), INDEX IDX_3D48E0371D59C203 (callback_advisor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE otp_code (id BINARY(16) NOT NULL, code_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, attempts INT NOT NULL, consumed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, client_id BINARY(16) DEFAULT NULL, advisor_id BINARY(16) DEFAULT NULL, dossier_id BINARY(16) DEFAULT NULL, INDEX idx_otp_created_at (created_at), INDEX IDX_93FE231919EB6921 (client_id), INDEX IDX_93FE231966D3AD77 (advisor_id), INDEX IDX_93FE2319611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03719EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0371D59C203 FOREIGN KEY (callback_advisor_id) REFERENCES advisor (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE otp_code ADD CONSTRAINT FK_93FE231919EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE otp_code ADD CONSTRAINT FK_93FE231966D3AD77 FOREIGN KEY (advisor_id) REFERENCES advisor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE otp_code ADD CONSTRAINT FK_93FE2319611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE otp_code DROP FOREIGN KEY FK_93FE2319611C0C56');
        $this->addSql('ALTER TABLE otp_code DROP FOREIGN KEY FK_93FE231966D3AD77');
        $this->addSql('ALTER TABLE otp_code DROP FOREIGN KEY FK_93FE231919EB6921');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E0371D59C203');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E03719EB6921');
        $this->addSql('DROP TABLE otp_code');
        $this->addSql('DROP TABLE dossier');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE advisor');
    }
}
