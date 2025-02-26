<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217012040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sondage ADD club_id INT NOT NULL');
        $this->addSql('ALTER TABLE sondage ADD CONSTRAINT FK_7579C89F61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('CREATE INDEX IDX_7579C89F61190A32 ON sondage (club_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sondage DROP FOREIGN KEY FK_7579C89F61190A32');
        $this->addSql('DROP INDEX IDX_7579C89F61190A32 ON sondage');
        $this->addSql('ALTER TABLE sondage DROP club_id');
    }
}
