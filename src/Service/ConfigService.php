<?php

namespace SportOase\Service;

use SportOase\Entity\SystemConfig;
use Doctrine\ORM\EntityManagerInterface;

class ConfigService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function get(string $key): array
    {
        $config = $this->entityManager
            ->getRepository(SystemConfig::class)
            ->findOneBy(['configKey' => $key]);

        return $config ? $config->getConfigValue() : [];
    }

    public function set(string $key, array $value): void
    {
        $config = $this->entityManager
            ->getRepository(SystemConfig::class)
            ->findOneBy(['configKey' => $key]);

        if (!$config) {
            $config = new SystemConfig();
            $config->setConfigKey($key);
            $this->entityManager->persist($config);
        }

        $config->setConfigValue($value);
        $this->entityManager->flush();
    }

    public function getPeriods(): array
    {
        $periods = $this->get('periods');
        return !empty($periods) ? $periods : [
            1 => ['start' => '07:50', 'end' => '08:35'],
            2 => ['start' => '08:35', 'end' => '09:20'],
            3 => ['start' => '09:40', 'end' => '10:25'],
            4 => ['start' => '10:25', 'end' => '11:20'],
            5 => ['start' => '11:40', 'end' => '12:25'],
            6 => ['start' => '12:25', 'end' => '13:10'],
        ];
    }

    public function getFixedOffers(): array
    {
        $offers = $this->get('fixed_offers');
        return !empty($offers) ? $offers : [
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
        ];
    }

    public function getFreeModules(): array
    {
        $modules = $this->get('free_modules');
        return !empty($modules) ? $modules : [
            'Aktivierung',
            'Regulation / Entspannung',
            'Konflikt-Reset',
            'Egal / flexibel'
        ];
    }

    public function getSystemSettings(): array
    {
        $settings = $this->get('system_settings');
        return !empty($settings) ? $settings : [
            'max_students_per_period' => 5,
            'booking_advance_minutes' => 60
        ];
    }

    public function getMaxStudentsPerPeriod(): int
    {
        $settings = $this->getSystemSettings();
        return $settings['max_students_per_period'] ?? 5;
    }

    public function getBookingAdvanceMinutes(): int
    {
        $settings = $this->getSystemSettings();
        return $settings['booking_advance_minutes'] ?? 60;
    }
}
