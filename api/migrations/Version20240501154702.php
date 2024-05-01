<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240501154702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student ADD grade_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33FE19A1A8 FOREIGN KEY (grade_id) REFERENCES course (id)');
        $this->addSql('CREATE INDEX IDX_B723AF33FE19A1A8 ON student (grade_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33FE19A1A8');
        $this->addSql('DROP INDEX IDX_B723AF33FE19A1A8 ON student');
        $this->addSql('ALTER TABLE student DROP grade_id');
    }
}
