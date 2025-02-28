<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250226124243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competition_club (competition_id INT NOT NULL, club_id INT NOT NULL, INDEX IDX_B6ADA6607B39D312 (competition_id), INDEX IDX_B6ADA66061190A32 (club_id), PRIMARY KEY(competition_id, club_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE competition_club ADD CONSTRAINT FK_B6ADA6607B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE competition_club ADD CONSTRAINT FK_B6ADA66061190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE choix_sondage DROP FOREIGN KEY FK_53A530E6BAF4AE56');
        $this->addSql('ALTER TABLE choix_sondage ADD CONSTRAINT FK_53A530E6BAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCBAF4AE56');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCBAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE competition ADD saison_id INT NOT NULL');
        $this->addSql('ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1F965414C FOREIGN KEY (saison_id) REFERENCES saison (id)');
        $this->addSql('CREATE INDEX IDX_B50A2CB1F965414C ON competition (saison_id)');
        $this->addSql('ALTER TABLE reponse CHANGE choix_sondage_id choix_sondage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reset_password_request CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competition_club DROP FOREIGN KEY FK_B6ADA6607B39D312');
        $this->addSql('ALTER TABLE competition_club DROP FOREIGN KEY FK_B6ADA66061190A32');
        $this->addSql('DROP TABLE competition_club');
        $this->addSql('ALTER TABLE choix_sondage DROP FOREIGN KEY FK_53A530E6BAF4AE56');
        $this->addSql('ALTER TABLE choix_sondage ADD CONSTRAINT FK_53A530E6BAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCBAF4AE56');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCBAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id)');
        $this->addSql('ALTER TABLE competition DROP FOREIGN KEY FK_B50A2CB1F965414C');
        $this->addSql('DROP INDEX IDX_B50A2CB1F965414C ON competition');
        $this->addSql('ALTER TABLE competition DROP saison_id');
        $this->addSql('ALTER TABLE reponse CHANGE choix_sondage_id choix_sondage_id INT NOT NULL');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE user DROP status');
    }
}
