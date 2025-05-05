<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505124815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE product_review (id SERIAL NOT NULL, product_id INT NOT NULL, user_id INT NOT NULL, order_id INT DEFAULT NULL, rating SMALLINT NOT NULL, comment TEXT NOT NULL, is_approved BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1B3FC0624584665A ON product_review (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1B3FC062A76ED395 ON product_review (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1B3FC0628D9F6D38 ON product_review (order_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN product_review.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN product_review.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN product_review.approved_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0624584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC062A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0628D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" ADD stripe_session_id VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_review DROP CONSTRAINT FK_1B3FC0624584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_review DROP CONSTRAINT FK_1B3FC062A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_review DROP CONSTRAINT FK_1B3FC0628D9F6D38
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_review
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" DROP stripe_session_id
        SQL);
    }
}
