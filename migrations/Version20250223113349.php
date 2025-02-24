<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250223113349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDCF347EFB');
        $this->addSql('ALTER TABLE orderdetails CHANGE produit_id produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDCF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('DROP INDEX IDX_6EEAA67DA76ED395 ON commande');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDCF347EFB');
        $this->addSql('ALTER TABLE orderdetails CHANGE produit_id produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDCF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
    }
}
