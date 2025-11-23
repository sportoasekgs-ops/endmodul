<?php

namespace SportOase\Service;

use SportOase\Entity\Booking;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;

class GoogleCalendarService
{
    private ?GoogleCalendar $calendarService = null;
    private bool $enabled = false;

    public function __construct(
        private ConfigService $configService
    ) {
        if ($this->hasCredentials()) {
            try {
                $client = new GoogleClient();
                $client->setApplicationName('SportOase');
                $client->setScopes([GoogleCalendar::CALENDAR]);
                $client->setAuthConfig(json_decode($_ENV['GOOGLE_CALENDAR_CREDENTIALS'] ?? '{}', true));
                
                $this->calendarService = new GoogleCalendar($client);
                $this->enabled = true;
            } catch (\Exception $e) {
                $this->enabled = false;
            }
        }
    }

    private function hasCredentials(): bool
    {
        return !empty($_ENV['GOOGLE_CALENDAR_CREDENTIALS']) && 
               !empty($_ENV['GOOGLE_CALENDAR_ID']);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function createEvent(Booking $booking): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $event = new GoogleEvent([
                'summary' => $booking->getOfferLabel() . ' - ' . $booking->getTeacherName(),
                'description' => sprintf(
                    "Lehrer: %s (%s)\nAngebot: %s\nSchüler: %d",
                    $booking->getTeacherName(),
                    $booking->getTeacherClass(),
                    $booking->getOfferLabel(),
                    count($booking->getStudentsJson())
                ),
                'start' => [
                    'dateTime' => $this->getBookingStartTime($booking)->format(\DateTime::RFC3339),
                    'timeZone' => 'Europe/Berlin',
                ],
                'end' => [
                    'dateTime' => $this->getBookingEndTime($booking)->format(\DateTime::RFC3339),
                    'timeZone' => 'Europe/Berlin',
                ],
            ]);

            $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];
            $createdEvent = $this->calendarService->events->insert($calendarId, $event);
            
            return $createdEvent->getId();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function updateEvent(string $eventId, Booking $booking): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];
            $event = $this->calendarService->events->get($calendarId, $eventId);
            
            $event->setSummary($booking->getOfferLabel() . ' - ' . $booking->getTeacherName());
            $event->setDescription(sprintf(
                "Lehrer: %s (%s)\nAngebot: %s\nSchüler: %d",
                $booking->getTeacherName(),
                $booking->getTeacherClass(),
                $booking->getOfferLabel(),
                count($booking->getStudentsJson())
            ));
            
            $this->calendarService->events->update($calendarId, $eventId, $event);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteEvent(string $eventId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];
            $this->calendarService->events->delete($calendarId, $eventId);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getBookingStartTime(Booking $booking): \DateTime
    {
        $periods = $this->configService->getPeriods();
        $period = $periods[$booking->getPeriod()] ?? null;
        
        $time = $period['start'] ?? '08:00';
        $dateTime = clone $booking->getDate();
        $dateTime->setTime(
            (int) substr($time, 0, 2),
            (int) substr($time, 3, 2)
        );
        
        return $dateTime;
    }

    private function getBookingEndTime(Booking $booking): \DateTime
    {
        $periods = $this->configService->getPeriods();
        $period = $periods[$booking->getPeriod()] ?? null;
        
        $time = $period['end'] ?? '09:00';
        $dateTime = clone $booking->getDate();
        $dateTime->setTime(
            (int) substr($time, 0, 2),
            (int) substr($time, 3, 2)
        );
        
        return $dateTime;
    }
}
