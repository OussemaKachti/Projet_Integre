<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228223954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mission_progress (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, competition_id INT NOT NULL, progress INT NOT NULL, is_completed TINYINT(1) NOT NULL, INDEX IDX_2AFDF3FF61190A32 (club_id), INDEX IDX_2AFDF3FF7B39D312 (competition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF7B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id)');
        $this->addSql('ALTER TABLE competition_club DROP FOREIGN KEY FK_B6ADA66061190A32');
        $this->addSql('ALTER TABLE competition_club DROP FOREIGN KEY FK_B6ADA6607B39D312');
        $this->addSql('DROP TABLE competition_club');
        $this->addSql('ALTER TABLE competition ADD points INT NOT NULL, ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE saison ADD image VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competition_club (competition_id INT NOT NULL, club_id INT NOT NULL, INDEX IDX_B6ADA66061190A32 (club_id), INDEX IDX_B6ADA6607B39D312 (competition_id), PRIMARY KEY(competition_id, club_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE competition_club ADD CONSTRAINT FK_B6ADA66061190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE competition_club ADD CONSTRAINT FK_B6ADA6607B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF61190A32');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF7B39D312');
        $this->addSql('DROP TABLE mission_progress');
        $this->addSql('ALTER TABLE competition DROP points, DROP status');
        $this->addSql('ALTER TABLE saison DROP image, DROP updated_at');
    }
}
