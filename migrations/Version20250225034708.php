<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250225034708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE3872B40A33C7');
        $this->addSql('DROP INDEX IDX_B8EE3872B40A33C7 ON club');
        $this->addSql('ALTER TABLE club DROP president_id');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7BAF4AE56');
        $this->addSql('DROP INDEX IDX_5FB6DEC7BAF4AE56 ON reponse');
        $this->addSql('ALTER TABLE reponse DROP sondage_id');
        $this->addSql('ALTER TABLE reset_password_request CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sondage DROP FOREIGN KEY FK_7579C89F61190A32');
        $this->addSql('DROP INDEX IDX_7579C89F61190A32 ON sondage');
        $this->addSql('ALTER TABLE sondage DROP club_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club ADD president_id INT NOT NULL');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE3872B40A33C7 FOREIGN KEY (president_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B8EE3872B40A33C7 ON club (president_id)');
        $this->addSql('ALTER TABLE reponse ADD sondage_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7BAF4AE56 FOREIGN KEY (sondage_id) REFERENCES sondage (id)');
        $this->addSql('CREATE INDEX IDX_5FB6DEC7BAF4AE56 ON reponse (sondage_id)');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE sondage ADD club_id INT NOT NULL');
        $this->addSql('ALTER TABLE sondage ADD CONSTRAINT FK_7579C89F61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('CREATE INDEX IDX_7579C89F61190A32 ON sondage (club_id)');
    }
}
