<?php

namespace SportOase\Service;

use SportOase\Entity\Booking;
use SportOase\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConfigService $configService
    ) {
    }

    public function getWeekData(int $weekOffset = 0): array
    {
        $monday = new \DateTime('monday this week');
        $monday->modify('+' . $weekOffset . ' weeks');
        
        $weekDays = [];
        for ($i = 0; $i < 5; $i++) {
            $date = clone $monday;
            $date->modify('+' . $i . ' days');
            $weekDays[] = [
                'date' => $date,
                'weekday' => $date->format('l'),
                'formatted' => $date->format('d.m.Y'),
            ];
        }
        
        return [
            'days' => $weekDays,
            'periods' => $this->configService->getPeriods(),
            'start_date' => $monday,
            'fixed_offers' => $this->configService->getFixedOffers(),
            'free_modules' => $this->configService->getFreeModules(),
        ];
    }

    public function createBooking(User $user, array $data): Booking
    {
        $date = new \DateTime($data['date']);
        $period = (int) $data['period'];
        
        $this->validateBooking($date, $period, $data);
        
        $booking = new Booking();
        $booking->setDate($date);
        $booking->setPeriod($period);
        $booking->setWeekday($date->format('l'));
        $booking->setTeacher($user);
        $booking->setTeacherName($data['teacher_name']);
        $booking->setTeacherClass($data['teacher_class']);
        $booking->setStudentsJson(json_decode($data['students_json'], true) ?? []);
        $booking->setOfferType($data['offer_type']);
        $booking->setOfferLabel($data['offer_label']);
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        
        return $booking;
    }

    public function updateBooking(Booking $booking, array $data): Booking
    {
        if (isset($data['teacher_name'])) {
            $booking->setTeacherName($data['teacher_name']);
        }
        if (isset($data['teacher_class'])) {
            $booking->setTeacherClass($data['teacher_class']);
        }
        if (isset($data['students_json'])) {
            $booking->setStudentsJson(json_decode($data['students_json'], true) ?? []);
        }
        if (isset($data['offer_label'])) {
            $booking->setOfferLabel($data['offer_label']);
        }
        
        $booking->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
        
        return $booking;
    }

    private function validateBooking(\DateTime $date, int $period, array $data): void
    {
        if ($date->format('N') >= 6) {
            throw new \Exception('Wochenenden können nicht gebucht werden.');
        }
        
        $periods = $this->configService->getPeriods();
        $bookingAdvanceMinutes = $this->configService->getBookingAdvanceMinutes();
        $maxStudents = $this->configService->getMaxStudentsPerPeriod();
        
        $now = new \DateTime();
        $periodStart = clone $date;
        $periodStartTime = $periods[$period]['start'];
        $periodStart->setTime(...explode(':', $periodStartTime));
        
        $diffMinutes = ($periodStart->getTimestamp() - $now->getTimestamp()) / 60;
        if ($diffMinutes < $bookingAdvanceMinutes) {
            throw new \Exception('Buchungen müssen mindestens ' . $bookingAdvanceMinutes . ' Minuten im Voraus erfolgen.');
        }
        
        $existing = $this->entityManager
            ->getRepository(Booking::class)
            ->findOneBy(['date' => $date, 'period' => $period]);
        
        if ($existing) {
            throw new \Exception('Dieser Zeitslot ist bereits gebucht.');
        }
        
        $students = json_decode($data['students_json'], true) ?? [];
        if (count($students) > $maxStudents) {
            throw new \Exception('Maximale Anzahl von Schülern überschritten (' . $maxStudents . ').');
        }
        
        $studentNames = array_column($students, 'name');
        $this->checkDoubleBooking($studentNames, $date, $period);
    }

    private function checkDoubleBooking(array $studentNames, \DateTime $date, int $period): void
    {
        $bookings = $this->entityManager
            ->getRepository(Booking::class)
            ->findBy(['date' => $date, 'period' => $period]);
        
        foreach ($bookings as $booking) {
            $bookedStudents = array_column($booking->getStudentsJson(), 'name');
            $duplicates = array_intersect($studentNames, $bookedStudents);
            
            if (!empty($duplicates)) {
                throw new \Exception('Schüler bereits gebucht: ' . implode(', ', $duplicates));
            }
        }
    }
}
