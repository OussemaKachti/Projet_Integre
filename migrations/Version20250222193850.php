<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250222193850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE orderdetails (id INT AUTO_INCREMENT NOT NULL, commande_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, quantity INT NOT NULL, price INT NOT NULL, INDEX IDX_489AFCDC82EA2E54 (commande_id), INDEX IDX_489AFCDCF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDCF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC2761190A32');
        $this->addSql('ALTER TABLE produit CHANGE img_prod img_prod VARCHAR(255) DEFAULT NULL, CHANGE quantity quantity VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC2761190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDC82EA2E54');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDCF347EFB');
        $this->addSql('DROP TABLE orderdetails');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC2761190A32');
        $this->addSql('ALTER TABLE produit CHANGE img_prod img_prod VARCHAR(255) NOT NULL, CHANGE quantity quantity INT NOT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC2761190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
    }
}
