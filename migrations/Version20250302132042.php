<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302132042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C3E5F2F7B');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('DROP INDEX IDX_3472872C3E5F2F7B ON participation_event');
        $this->addSql('ALTER TABLE participation_event CHANGE event_id_id evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CFD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3472872CFD02F13 ON participation_event (evenement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CFD02F13');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('DROP INDEX IDX_3472872CFD02F13 ON participation_event');
        $this->addSql('ALTER TABLE participation_event CHANGE evenement_id event_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3472872C3E5F2F7B ON participation_event (event_id_id)');
    }
}
