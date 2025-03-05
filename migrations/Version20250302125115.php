<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302125115 extends AbstractMigration
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
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF7B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE choix_sondage DROP FOREIGN KEY FK_53A530E6BAF4AE56');
        $this->addSql('ALTER TABLE choix_sondage ADD CONSTRAINT FK_53A530E6BAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCBAF4AE56');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCBAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE competition ADD saison_id INT NOT NULL, ADD points INT NOT NULL, ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1F965414C FOREIGN KEY (saison_id) REFERENCES saison (id)');
        $this->addSql('CREATE INDEX IDX_B50A2CB1F965414C ON competition (saison_id)');
        $this->addSql('ALTER TABLE evenement ADD end_date DATETIME DEFAULT NULL, CHANGE categorie_id categorie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDC82EA2E54');
        $this->addSql('ALTER TABLE orderdetails CHANGE produit_id produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE reponse ADD sondage_id INT NOT NULL, CHANGE choix_sondage_id choix_sondage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7BAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id)');
        $this->addSql('CREATE INDEX IDX_5FB6DEC7BAF4AE56 ON reponse (sondage_id)');
        $this->addSql('ALTER TABLE saison ADD image VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user ADD status VARCHAR(20) NOT NULL, ADD is_verified TINYINT(1) NOT NULL, ADD confirmation_token VARCHAR(64) DEFAULT NULL, ADD confirmation_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF61190A32');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF7B39D312');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C3E5F2F7B');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE mission_progress');
        $this->addSql('DROP TABLE participation_event');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('ALTER TABLE choix_sondage DROP FOREIGN KEY FK_53A530E6BAF4AE56');
        $this->addSql('ALTER TABLE choix_sondage ADD CONSTRAINT FK_53A530E6BAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id)');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('DROP INDEX IDX_6EEAA67DA76ED395 ON commande');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCBAF4AE56');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCBAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id)');
        $this->addSql('ALTER TABLE competition DROP FOREIGN KEY FK_B50A2CB1F965414C');
        $this->addSql('DROP INDEX IDX_B50A2CB1F965414C ON competition');
        $this->addSql('ALTER TABLE competition DROP saison_id, DROP points, DROP status');
        $this->addSql('ALTER TABLE evenement DROP end_date, CHANGE categorie_id categorie_id INT NOT NULL');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDC82EA2E54');
        $this->addSql('ALTER TABLE orderdetails CHANGE produit_id produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7BAF4AE56');
        $this->addSql('DROP INDEX IDX_5FB6DEC7BAF4AE56 ON reponse');
        $this->addSql('ALTER TABLE reponse DROP sondage_id, CHANGE choix_sondage_id choix_sondage_id INT NOT NULL');
        $this->addSql('ALTER TABLE saison DROP image, DROP updated_at');
        $this->addSql('ALTER TABLE user DROP status, DROP is_verified, DROP confirmation_token, DROP confirmation_token_expires_at');
    }
}
