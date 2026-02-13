<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016193844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE opening_history DROP FOREIGN KEY FK_13D0DECBADA40271');
        $this->addSql('DROP INDEX IDX_13D0DECBADA40271 ON opening_history');
        $this->addSql('ALTER TABLE opening_history ADD url VARCHAR(255) DEFAULT NULL, DROP link_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE opening_history ADD link_id INT DEFAULT NULL, DROP url');
        $this->addSql('ALTER TABLE opening_history ADD CONSTRAINT FK_13D0DECBADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_13D0DECBADA40271 ON opening_history (link_id)');
    }
}
