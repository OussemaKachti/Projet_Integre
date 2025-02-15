<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214210509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club ADD president_id INT NOT NULL');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE3872B40A33C7 FOREIGN KEY (president_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B8EE3872B40A33C7 ON club (president_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE3872B40A33C7');
        $this->addSql('DROP INDEX IDX_B8EE3872B40A33C7 ON club');
        $this->addSql('ALTER TABLE club DROP president_id');
    }
}
