<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250223213106 extends AbstractMigration
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
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDC82EA2E54');
        $this->addSql('ALTER TABLE orderdetails CHANGE produit_id produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('DROP INDEX IDX_6EEAA67DA76ED395 ON commande');
        $this->addSql('ALTER TABLE orderdetails DROP FOREIGN KEY FK_489AFCDC82EA2E54');
        $this->addSql('ALTER TABLE orderdetails CHANGE produit_id produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orderdetails ADD CONSTRAINT FK_489AFCDC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
    }
}
