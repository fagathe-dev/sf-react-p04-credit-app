<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Avancement des dossiers (workflow, expiration des offres, souscription) et historique des e-mails (MySQL 8 / MariaDB).
 */
final class Version20261106000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dossier : workflow_status, quotes_expire_at, souscription ; table email_notification.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration prévue pour MySQL / MariaDB.');

        $this->addSql('ALTER TABLE dossier ADD workflow_status VARCHAR(30) DEFAULT NULL, ADD quotes_expire_at DATETIME DEFAULT NULL, ADD subscribed_offer_code VARCHAR(50) DEFAULT NULL, ADD subscribed_at DATETIME DEFAULT NULL, ADD workflow_updated_at DATETIME DEFAULT NULL');
        // Dossiers existants : statut déduit de la décision, offres valables 7 jours après le résultat
        $this->addSql("UPDATE dossier SET workflow_status = CASE decision WHEN 'INSTANT_OFFERS' THEN 'QUOTES_GENERATED' WHEN 'ADVISOR_CALLBACK_NEEDED' THEN 'NO_OFFER_ADVISOR_QUEUE' ELSE 'REJECTED' END, quotes_expire_at = DATE_ADD(submitted_at, INTERVAL 7 DAY)");
        $this->addSql('ALTER TABLE dossier MODIFY workflow_status VARCHAR(30) NOT NULL, MODIFY quotes_expire_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX idx_dossier_workflow ON dossier (status, workflow_status, submitted_at)');

        $this->addSql('CREATE TABLE email_notification (id BINARY(16) NOT NULL, type VARCHAR(40) NOT NULL, recipient VARCHAR(180) NOT NULL, subject VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, sent_at DATETIME NOT NULL, error VARCHAR(255) DEFAULT NULL, dossier_id BINARY(16) NOT NULL, INDEX idx_email_notification_dossier_type (dossier_id, type), INDEX IDX_EA479099611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE email_notification ADD CONSTRAINT FK_EA479099611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_notification DROP FOREIGN KEY FK_EA479099611C0C56');
        $this->addSql('DROP TABLE email_notification');
        $this->addSql('DROP INDEX idx_dossier_workflow ON dossier');
        $this->addSql('ALTER TABLE dossier DROP workflow_status, DROP quotes_expire_at, DROP subscribed_offer_code, DROP subscribed_at, DROP workflow_updated_at');
    }
}
