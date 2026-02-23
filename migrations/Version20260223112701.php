<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223112701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise (id UUID NOT NULL, name VARCHAR(255) NOT NULL, muscle_group VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, difficulty VARCHAR(20) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE meal (id UUID NOT NULL, date DATE NOT NULL, name VARCHAR(255) NOT NULL, calories INT NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9EF68E9CA76ED395 ON meal (user_id)');
        $this->addSql('CREATE TABLE profile (id UUID NOT NULL, full_name VARCHAR(255) DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, height DOUBLE PRECISION DEFAULT NULL, age INT DEFAULT NULL, goal VARCHAR(20) DEFAULT NULL, activity_level INT DEFAULT NULL, daily_calorie_target INT DEFAULT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0FA76ED395 ON profile (user_id)');
        $this->addSql('CREATE TABLE program (id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, content JSON NOT NULL, duration_weeks INT NOT NULL, sessions_per_week INT NOT NULL, generated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_92ED7784A76ED395 ON program (user_id)');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE workout (id UUID NOT NULL, date DATE NOT NULL, completed BOOLEAN NOT NULL, notes TEXT DEFAULT NULL, user_id UUID NOT NULL, program_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_649FFB72A76ED395 ON workout (user_id)');
        $this->addSql('CREATE INDEX IDX_649FFB723EB8070A ON workout (program_id)');
        $this->addSql('CREATE TABLE workout_exercise (id UUID NOT NULL, sets INT NOT NULL, reps VARCHAR(20) NOT NULL, weight DOUBLE PRECISION DEFAULT NULL, workout_id UUID NOT NULL, exercise_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_76AB38AAA6CCCFC9 ON workout_exercise (workout_id)');
        $this->addSql('CREATE INDEX IDX_76AB38AAE934951A ON workout_exercise (exercise_id)');
        $this->addSql('ALTER TABLE meal ADD CONSTRAINT FK_9EF68E9CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE program ADD CONSTRAINT FK_92ED7784A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB72A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB723EB8070A FOREIGN KEY (program_id) REFERENCES program (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workout_exercise ADD CONSTRAINT FK_76AB38AAA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workout_exercise ADD CONSTRAINT FK_76AB38AAE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meal DROP CONSTRAINT FK_9EF68E9CA76ED395');
        $this->addSql('ALTER TABLE profile DROP CONSTRAINT FK_8157AA0FA76ED395');
        $this->addSql('ALTER TABLE program DROP CONSTRAINT FK_92ED7784A76ED395');
        $this->addSql('ALTER TABLE workout DROP CONSTRAINT FK_649FFB72A76ED395');
        $this->addSql('ALTER TABLE workout DROP CONSTRAINT FK_649FFB723EB8070A');
        $this->addSql('ALTER TABLE workout_exercise DROP CONSTRAINT FK_76AB38AAA6CCCFC9');
        $this->addSql('ALTER TABLE workout_exercise DROP CONSTRAINT FK_76AB38AAE934951A');
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE meal');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE program');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE workout');
        $this->addSql('DROP TABLE workout_exercise');
    }
}
