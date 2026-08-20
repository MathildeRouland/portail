<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525195207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE link (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, customer_name VARCHAR(255) NOT NULL, four_random_characters VARCHAR(4) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, customer_phone_number VARCHAR(255) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, status TINYINT(1) NOT NULL, permanent TINYINT(1) NOT NULL, creator_id INT NOT NULL, updater_id INT NOT NULL, INDEX IDX_36AC99F161220EA6 (creator_id), INDEX IDX_36AC99F1E37ECFB0 (updater_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE opening_history (id INT AUTO_INCREMENT NOT NULL, status TINYINT(1) NOT NULL, opening_date DATETIME DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, link_id INT DEFAULT NULL, INDEX IDX_13D0DECBADA40271 (link_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, homepage_text LONGTEXT DEFAULT NULL, background_image VARCHAR(255) DEFAULT NULL, rgpd_file VARCHAR(255) DEFAULT NULL, portal_text LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE link ADD CONSTRAINT FK_36AC99F161220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE link ADD CONSTRAINT FK_36AC99F1E37ECFB0 FOREIGN KEY (updater_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE opening_history ADD CONSTRAINT FK_13D0DECBADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE link DROP FOREIGN KEY FK_36AC99F161220EA6');
        $this->addSql('ALTER TABLE link DROP FOREIGN KEY FK_36AC99F1E37ECFB0');
        $this->addSql('ALTER TABLE opening_history DROP FOREIGN KEY FK_13D0DECBADA40271');
        $this->addSql('DROP TABLE link');
        $this->addSql('DROP TABLE opening_history');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE user');
    }
}
