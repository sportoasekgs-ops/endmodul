# SportOase - IServ Modul 🏐

<p align="center">
  <img src="icon.png" alt="SportOase Logo" width="200" height="200">
</p>

<p align="center">
  <strong>Modernes Buchungssystem für Schulsportanlagen</strong>
</p>

<p align="center">
  Ein professionelles Symfony-basiertes IServ-Modul für die Verwaltung von Sportanlagen-Buchungen mit modernem Design, Kapazitätsmanagement und umfangreichen Admin-Funktionen.
</p>

---

## 📋 Überblick

**SportOase** ist ein vollständig in IServ 3.0+ integriertes Modul, das Lehrkräften und Administratoren die einfache Verwaltung von Sportanlagen-Buchungen ermöglicht. Das Modul wurde mit Symfony 6.4+ entwickelt und bietet ein modernes, responsives Interface mit deutscher Benutzeroberfläche.

### Für wen ist SportOase?

- **Schulen mit IServ-Server** - Nahtlose Integration in bestehende IServ-Infrastruktur
- **Sportlehrkräfte** - Schnelle und unkomplizierte Buchung von Zeitslots
- **Administratoren** - Volle Kontrolle über Buchungen, Nutzer und Einstellungen

---

## ✨ Features

### 🎨 Modernes Design
- **Tailwind CSS** - Professionelles, responsives Design
- **Progressive Web App (PWA)** - Installierbar auf Smartphones und Tablets
- **Vollständig responsive** - Optimiert für Desktop, Tablet und Mobile
- **Deutsche Benutzeroberfläche** - Alle Texte auf Deutsch

### 🔐 Sicherheit & Authentifizierung
- **IServ Single Sign-On** - OAuth2/OIDC Integration
- **Rollenbasierte Zugriffskontrolle** - Lehrer und Admin-Rollen
- **Sichere Sessionverwaltung** - Symfony Security Component
- **Audit-Trail** - Vollständige Nachvollziehbarkeit aller Änderungen

### 📅 Buchungsverwaltung
- **Wochenansicht** - Übersichtliche Darstellung aller Zeitslots
- **6 Zeitperioden** - Flexibles Zeitraster für den Schulalltag
- **Kapazitätskontrolle** - Maximale Schülerzahl pro Slot (Standard: 5)
- **Doppelbuchungs-Prävention** - Automatische Konfliktprüfung
- **Vorlaufzeit** - Mindestvorlauf für Buchungen (Standard: 60 Minuten)
- **Feste Kursangebote** - Admin-verwaltbare fixe Belegungen

### 👨‍💼 Admin-Funktionen
- **Komplettes Dashboard** - Übersicht über alle Buchungen und Statistiken
- **Nutzerverwaltung** - Aktivieren/Deaktivieren von Nutzerkonten
- **Slot-Management** - Blockieren von Zeitslots mit Begründung
- **Erweiterte Suche** - Filtern nach Lehrer, Klasse, Datum
- **Statistiken** - Visuelle Auswertungen mit Charts
- **Export-Funktionen** - CSV und PDF-Export
- **Audit-Logs** - Nachverfolgung aller Systemänderungen

### 🔔 Benachrichtigungen & Integration
- **E-Mail-Benachrichtigungen** - SMTP-basierte Alerts
- **Google Calendar Sync** (optional) - Automatische Kalender-Synchronisation
- **Webhook-Support** - Erweiterbar für externe Systeme

---

## 🏫 Zeitperioden

Das Modul arbeitet mit 6 festen Zeitperioden pro Schultag:

| Periode | Uhrzeit |
|---------|---------|
| 1 | 07:50 - 08:35 |
| 2 | 08:35 - 09:20 |
| 3 | 09:40 - 10:25 |
| 4 | 10:25 - 11:20 |
| 5 | 11:40 - 12:25 |
| 6 | 12:25 - 13:10 |

*Die Zeitperioden sind zentral im ConfigService definiert und können bei Bedarf angepasst werden.*

---

## 🛠️ Technologie-Stack

- **Framework**: Symfony 6.4+ / 7.0
- **Sprache**: PHP 8.0+
- **Datenbank**: PostgreSQL mit Doctrine ORM
- **Templates**: Twig
- **Frontend**: Tailwind CSS, Webpack Encore
- **Authentifizierung**: OAuth2/OIDC (IServ SSO)
- **Deployment**: Debian-Paket (.deb)

---

## 📦 Installation

### Voraussetzungen

- **IServ Server** Version 3.0 oder höher
- **PHP** 8.0 oder höher (8.2 empfohlen)
- **PostgreSQL** Datenbank
- **Admin-Zugriff** auf den IServ-Server

### Schnellstart

```bash
# 1. Debian-Paket erstellen
dpkg-buildpackage -us -uc

# 2. Paket auf IServ installieren
sudo aptitude install iserv-sportoase_1.0.0_all.deb

# 3. Datenbank-Migrationen ausführen
cd /usr/share/iserv/modules/sportoase
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# 4. Modul in IServ aktivieren
# IServ Admin → System → Module → SportOase aktivieren
```

### Detaillierte Anleitung

📖 **Siehe [INSTALLATION.md](INSTALLATION.md) für die vollständige Deployment-Dokumentation**

Die Installationsanleitung enthält:
- Build-Prozess
- OAuth2-Konfiguration
- Umgebungsvariablen
- Troubleshooting
- Update-Prozess

---

## ⚙️ Konfiguration

Nach der Installation erfolgt die Konfiguration über `/etc/iserv/sportoase.env`:

```bash
# IServ OAuth2
ISERV_BASE_URL=https://ihre-schule.iserv.de
ISERV_CLIENT_ID=ihre-client-id
ISERV_CLIENT_SECRET=ihr-client-secret

# Datenbank
DATABASE_URL="postgresql://user:pass@localhost:5432/iserv_db"

# E-Mail
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587
ADMIN_EMAIL=admin@ihre-schule.de

# Modul-Einstellungen
MAX_STUDENTS_PER_PERIOD=5
BOOKING_ADVANCE_MINUTES=60

# Google Calendar (optional)
GOOGLE_CALENDAR_ID=ihre-calendar-id
GOOGLE_CALENDAR_CREDENTIALS={"type":"service_account",...}
```

---

## 📱 Nutzung

### Für Lehrkräfte

1. **Anmelden** - Über IServ-Login automatisch authentifiziert
2. **Dashboard öffnen** - Menü → SportOase
3. **Wochenansicht nutzen** - Verfügbare und gebuchte Slots sehen
4. **Slot buchen** - Auf freien Slot klicken, Schüler eintragen
5. **Buchung verwalten** - Eigene Buchungen einsehen und löschen

### Für Administratoren

1. **Admin-Dashboard** - Menü → SportOase Admin
2. **Alle Buchungen sehen** - Vollständiger Überblick
3. **Buchungen bearbeiten** - Jede Buchung editieren/löschen
4. **Slots verwalten** - Zeitslots blockieren, Namen anpassen
5. **Nutzer verwalten** - Konten aktivieren/deaktivieren
6. **Statistiken einsehen** - Nutzungsanalyse und Reports
7. **Daten exportieren** - CSV/PDF-Export mit Filteroptionen

---

## 🗄️ Datenbank-Schema

Das Modul verwendet folgende Haupttabellen:

- `sportoase_users` - Nutzerkonten (verknüpft mit IServ)
- `sportoase_bookings` - Buchungsdaten mit Calendar-Event-ID
- `sportoase_slot_names` - Benutzerdefinierte Slot-Bezeichnungen
- `sportoase_blocked_slots` - Gesperrte Zeitslots
- `sportoase_fixed_offer_placements` - Fest platzierte Kursangebote
- `sportoase_fixed_offer_names` - Anzeigenamen für fixe Angebote
- `sportoase_audit_log` - Audit-Trail für alle Änderungen
- `sportoase_notifications` - Benachrichtigungsverlauf
- `sportoase_system_config` - System-Konfiguration

---

## 🔧 Entwicklung

### Lokale Entwicklung

```bash
# Dependencies installieren
composer install
npm install

# Assets kompilieren
npm run build

# Development-Server starten
symfony serve

# Datenbank-Migrationen
php bin/console doctrine:migrations:migrate
```

### Code-Struktur

```
sportoase/
├── src/
│   ├── Controller/        # Request-Handler
│   ├── Entity/            # Doctrine-Entities
│   ├── Service/           # Business-Logik
│   └── Security/          # Authentifizierung
├── templates/             # Twig-Templates
├── migrations/            # DB-Migrationen
├── config/                # Symfony-Config
├── public/                # Public Assets
└── assets/                # Frontend-Quellen
```

---

## 🚀 Roadmap

### Geplante Features

- [ ] **Multi-Sprach-Support** - Englische Oberfläche
- [ ] **Buchungs-Vorlagen** - Häufige Buchungen speichern
- [ ] **Kalender-Ansicht** - Monats-/Wochenkalender
- [ ] **Erweiterte Benachrichtigungen** - Nutzer-Präferenzen
- [ ] **Mobile App** - Native iOS/Android-App
- [ ] **API-Endpunkte** - REST-API für Dritt-Integrationen

---

## 📞 Support & Kontakt

- **E-Mail**: sportoase.kg@gmail.com
- **Kontakt**: Maurizio Morelli (morelli.maurizio@kgs-pattensen.de)
- **Telefon**: 0151 40349764
- **Version**: 1.0.0
- **Letzte Aktualisierung**: November 23, 2025

---

## 📄 Lizenz

MIT License

Copyright (c) 2025 SportOase Team

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

---

## 🏆 Credits

**Entwickelt von**: SportOase Team für die KGS Pattensen

**Built with**:
- [Symfony](https://symfony.com/) - PHP Framework
- [Tailwind CSS](https://tailwindcss.com/) - CSS Framework
- [Doctrine ORM](https://www.doctrine-project.org/) - Database ORM
- [Google Calendar API](https://developers.google.com/calendar) - Calendar Integration

---

<p align="center">
  Made with ❤️ for KGS Pattensen
</p>
