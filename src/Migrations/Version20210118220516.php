<?php

declare(strict_types=1);

namespace Themes\AbstractApiTheme\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210118220516 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'AbstractApiTheme first initialization';
    }

    public function up(Schema $schema) : void
    {
        $this->skipIf($schema->hasTable('api_applications'), 'AbstractApiTheme has been initialized before Doctrine Migration tool.');
        $this->addSql('CREATE TABLE `api_applications` (id INT AUTO_INCREMENT NOT NULL, app_name VARCHAR(255) NOT NULL, namespace VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, api_key VARCHAR(255) NOT NULL, secret VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, grant_types JSON NOT NULL, referer_regex VARCHAR(255) DEFAULT NULL, redirect_uri VARCHAR(255) DEFAULT NULL, confidential TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_FDA3BF5D5B0D5BA6 (app_name), UNIQUE INDEX UNIQ_FDA3BF5DC912ED9D (api_key), INDEX IDX_FDA3BF5DC912ED9D (api_key), INDEX IDX_FDA3BF5D50F9BB84 (enabled), INDEX IDX_FDA3BF5D52BDE2D6 (confidential), INDEX IDX_FDA3BF5D8B8E8428 (created_at), INDEX IDX_FDA3BF5D43625D9F (updated_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `api_auth_codes` (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, identifier VARCHAR(255) NOT NULL, expiry DATETIME DEFAULT NULL, user_identifier VARCHAR(255) NOT NULL, scopes JSON NOT NULL, revoked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_EE25EC29772E836A (identifier), INDEX IDX_EE25EC2919EB6921 (client_id), INDEX IDX_EE25EC2938B0169B (expiry), INDEX IDX_EE25EC29D0494586 (user_identifier), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `api_auth_codes` ADD CONSTRAINT FK_EE25EC2919EB6921 FOREIGN KEY (client_id) REFERENCES api_applications (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO `roles` (`name`) VALUES ('ROLE_ADMIN_API'),('ROLE_API')');
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
