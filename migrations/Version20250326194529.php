<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250326194529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables: category, product, sale, sale_entry.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT NOT NULL, category_id INT NOT NULL, name VARCHAR(255) NOT NULL, price INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sale (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sale_entry (id INT AUTO_INCREMENT NOT NULL, sale_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_FBFDC5F34A7E4868 (sale_id), INDEX IDX_FBFDC5F34584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE sale_entry ADD CONSTRAINT FK_FBFDC5F34A7E4868 FOREIGN KEY (sale_id) REFERENCES sale (id)');
        $this->addSql('ALTER TABLE sale_entry ADD CONSTRAINT FK_FBFDC5F34584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE sale_entry DROP FOREIGN KEY FK_FBFDC5F34A7E4868');
        $this->addSql('ALTER TABLE sale_entry DROP FOREIGN KEY FK_FBFDC5F34584665A');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE sale');
        $this->addSql('DROP TABLE sale_entry');
    }
}
