<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250301012621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mission_progress (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, competition_id INT NOT NULL, progress INT NOT NULL, is_completed TINYINT(1) NOT NULL, INDEX IDX_2AFDF3FF61190A32 (club_id), INDEX IDX_2AFDF3FF7B39D312 (competition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participation_event (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event_id_id INT NOT NULL, dateparticipation DATETIME NOT NULL, INDEX IDX_3472872CA76ED395 (user_id), INDEX IDX_3472872C3E5F2F7B (event_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF7B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE competition_club DROP FOREIGN KEY FK_B6ADA66061190A32');
        $this->addSql('ALTER TABLE competition_club DROP FOREIGN KEY FK_B6ADA6607B39D312');
        $this->addSql('DROP TABLE competition_club');
        $this->addSql('ALTER TABLE competition ADD points INT NOT NULL, ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD image_description VARCHAR(255) DEFAULT NULL, ADD end_date DATETIME DEFAULT NULL, DROP image_event, CHANGE categorie_id categorie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE saison ADD image VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competition_club (competition_id INT NOT NULL, club_id INT NOT NULL, INDEX IDX_B6ADA6607B39D312 (competition_id), INDEX IDX_B6ADA66061190A32 (club_id), PRIMARY KEY(competition_id, club_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE competition_club ADD CONSTRAINT FK_B6ADA66061190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE competition_club ADD CONSTRAINT FK_B6ADA6607B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF61190A32');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF7B39D312');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C3E5F2F7B');
        $this->addSql('DROP TABLE mission_progress');
        $this->addSql('DROP TABLE participation_event');
        $this->addSql('ALTER TABLE competition DROP points, DROP status');
        $this->addSql('ALTER TABLE evenement ADD image_event VARCHAR(255) NOT NULL, DROP image_description, DROP end_date, CHANGE categorie_id categorie_id INT NOT NULL');
        $this->addSql('ALTER TABLE saison DROP image, DROP updated_at');
    }
}
