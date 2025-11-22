<?php

declare(strict_types=1);

namespace SportOase\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version002SystemConfig extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create system_config table and insert default configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sportoase_system_config (
            id SERIAL PRIMARY KEY,
            config_key VARCHAR(100) UNIQUE NOT NULL,
            config_value JSON NOT NULL,
            created_at TIMESTAMP NOT NULL,
            updated_at TIMESTAMP NOT NULL
        )');

        $periods = json_encode([
            1 => ['start' => '07:50', 'end' => '08:35'],
            2 => ['start' => '08:35', 'end' => '09:20'],
            3 => ['start' => '09:40', 'end' => '10:25'],
            4 => ['start' => '10:25', 'end' => '11:20'],
            5 => ['start' => '11:40', 'end' => '12:25'],
            6 => ['start' => '12:25', 'end' => '13:10'],
        ]);

        $fixedOffers = json_encode([
            'Monday' => [
                1 => 'Wochenstart-Aktivierung',
                3 => 'Konflikt-Reset & Deeskalation',
                5 => 'Koordinationszirkel'
            ],
            'Tuesday' => [],
            'Wednesday' => [
                1 => 'Sozialtraining / Gruppenreset',
                3 => 'Aktivierung Mini-Fitness',
                5 => 'Motorik-Parcours'
            ],
            'Thursday' => [
                2 => 'Konflikt-Reset',
                5 => 'Turnen + Balance'
            ],
            'Friday' => [
                2 => 'Atem & Reflexion',
                4 => 'Bodyscan Light',
                5 => 'Ruhezone / Entspannung'
            ]
        ]);

        $freeModules = json_encode([
            'Aktivierung',
            'Regulation / Entspannung',
            'Konflikt-Reset',
            'Egal / flexibel'
        ]);

        $systemSettings = json_encode([
            'max_students_per_period' => 5,
            'booking_advance_minutes' => 60
        ]);

        $now = date('Y-m-d H:i:s');

        $this->addSql("INSERT INTO sportoase_system_config (config_key, config_value, created_at, updated_at) VALUES
            ('periods', '$periods', '$now', '$now'),
            ('fixed_offers', '$fixedOffers', '$now', '$now'),
            ('free_modules', '$freeModules', '$now', '$now'),
            ('system_settings', '$systemSettings', '$now', '$now')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sportoase_system_config');
    }
}
