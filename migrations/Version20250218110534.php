<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250218110534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C9D86650F');
        $this->addSql('DROP INDEX IDX_3472872C9D86650F ON participation_event');
        $this->addSql('ALTER TABLE participation_event DROP user_id_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_event ADD user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C9D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3472872C9D86650F ON participation_event (user_id_id)');
    }
}
