<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250218110325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participation_event (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, user_id INT NOT NULL, event_id_id INT NOT NULL, dateparticipation DATETIME NOT NULL, INDEX IDX_3472872C9D86650F (user_id_id), INDEX IDX_3472872CA76ED395 (user_id), INDEX IDX_3472872C3E5F2F7B (event_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C9D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES evenement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C9D86650F');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C3E5F2F7B');
        $this->addSql('DROP TABLE participation_event');
    }
}
