<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250305094612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE orderdetails (id INT AUTO_INCREMENT NOT NULL, commande_id INT DEFAULT NULL, produit_id INT NOT NULL, quantity INT NOT NULL, price INT NOT NULL, total DOUBLE PRECISION NOT NULL, INDEX IDX_489AFCDC82EA2E54 (commande_id), INDEX IDX_489AFCDCF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDCF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)');
        $this->addSql('ALTER TABLE competition ADD goal INT NOT NULL, ADD goal_type VARCHAR(50) NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF61190A32');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF61190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872C3E5F2F7B');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('DROP INDEX IDX_3472872C3E5F2F7B ON participation_event');
        $this->addSql('ALTER TABLE participation_event CHANGE event_id_id evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CFD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3472872CFD02F13 ON participation_event (evenement_id)');
        $this->addSql('ALTER TABLE participation_membre DROP FOREIGN KEY FK_8CD17D0C61190A32');
        $this->addSql('ALTER TABLE participation_membre ADD CONSTRAINT FK_8CD17D0C61190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC2761190A32');
        $this->addSql('ALTER TABLE produit ADD quantity VARCHAR(255) DEFAULT NULL, CHANGE img_prod img_prod VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC2761190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD profile_picture VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_login_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDC82EA2E54');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDCF347EFB');
        $this->addSql('DROP TABLE orderdetails');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('DROP INDEX IDX_6EEAA67DA76ED395 ON commande');
        $this->addSql('ALTER TABLE commande DROP user_id');
        $this->addSql('ALTER TABLE competition DROP goal, DROP goal_type, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE mission_progress DROP FOREIGN KEY FK_2AFDF3FF61190A32');
        $this->addSql('ALTER TABLE mission_progress ADD CONSTRAINT FK_2AFDF3FF61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CFD02F13');
        $this->addSql('ALTER TABLE participation_event DROP FOREIGN KEY FK_3472872CA76ED395');
        $this->addSql('DROP INDEX IDX_3472872CFD02F13 ON participation_event');
        $this->addSql('ALTER TABLE participation_event CHANGE evenement_id event_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872C3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE participation_event ADD CONSTRAINT FK_3472872CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3472872C3E5F2F7B ON participation_event (event_id_id)');
        $this->addSql('ALTER TABLE participation_membre DROP FOREIGN KEY FK_8CD17D0C61190A32');
        $this->addSql('ALTER TABLE participation_membre ADD CONSTRAINT FK_8CD17D0C61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC2761190A32');
        $this->addSql('ALTER TABLE produit DROP quantity, CHANGE img_prod img_prod VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC2761190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE user DROP profile_picture, DROP created_at, DROP last_login_at');
    }
}
