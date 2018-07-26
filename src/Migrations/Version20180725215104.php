<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180725215104 extends AbstractMigration
{
    public function up(Schema $schema): void
    {

        $this->addSql('INSERT INTO balance (id, "customer_login", "balance") VALUES (1, \'billy-jr\', 2400000);');
        $this->addSql('INSERT INTO balance (id, "customer_login", "balance") VALUES (2, \'billy-dad\', 1000000000);');
        $this->addSql('INSERT INTO balance (id, "customer_login", "balance") VALUES (3, \'billy-mom\', 500000000);');
    }

    public function down(Schema $schema): void
    {
    }
}
