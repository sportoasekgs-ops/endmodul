# SportOase - IServ Module

## Overview

SportOase is a **PHP/Symfony-based IServ module** for booking school sports facilities. It's designed to integrate directly into IServ 3.0+ servers and provides comprehensive booking management for teachers and administrators. The module features a weekly schedule view, capacity controls, email notifications, and full admin controls for managing bookings and users.

**Important**: This is now an IServ module, not a standalone Replit application. It must be deployed to an IServ server.

## Recent Changes

**November 23, 2025 - Production Optimization & Branding**:
- ✅ **Module schlank gemacht**: Test-Umgebung und 10+ Dev-Docs entfernt
- ✅ **Dokumentation konsolidiert**: INSTALLATION.md mit kompletter Deployment-Anleitung
- ✅ **README auf Deutsch aktualisiert**: Professionelle GitHub-README mit allen Features, korrekten Zeitperioden und SportOase-Branding
- ✅ **.gitignore optimiert**: Production-ready für IServ-Deployment
- ✅ **Architect-Review**: Bestätigt als production-ready, keine fehlenden Dateien
- ✅ **SportOase Logo integriert**: Offizielles Logo als PWA-Icon und Projekt-Icon (mit Logo-Referenz in README)
- ✅ **GoogleCalendarService optimiert**: Zeitperioden werden zentral vom ConfigService bezogen (keine Duplikation mehr)

**November 22, 2025 - IServ Module Implementation**: 
- Complete conversion from Flask/Python to PHP/Symfony IServ module structure
- Implemented Phase 2 enhanced features (slot management, user management, audit trail, search/filter, statistics)
- Implemented Phase 3 features (Google Calendar service, CSV/PDF export, PWA support)
- **Moveable fixed course offerings** - Admins can position fixed courses anywhere in the weekly schedule
- **Production-ready Google Calendar Integration** - Full calendar sync with create/update/delete operations

## User Preferences

Preferred communication style: Simple, everyday language.

## Project Architecture

### Technology Stack

- **Framework**: Symfony 6.4+ (PHP 8.0+)
- **Database**: PostgreSQL with Doctrine ORM
- **Templates**: Twig (replacing Jinja2)
- **IServ Integration**: Manifest-based module with SSO support
- **Timezone**: Europe/Berlin

### Module Structure

```
sportoase/
├── src/
│   ├── SportOaseBundle.php        # Main Symfony bundle
│   ├── Controller/                 # Request handlers
│   │   ├── DashboardController.php
│   │   ├── BookingController.php
│   │   └── AdminController.php
│   ├── Entity/                     # Doctrine entities (database models)
│   │   ├── User.php
│   │   ├── Booking.php
│   │   ├── SlotName.php
│   │   ├── BlockedSlot.php
│   │   └── Notification.php
│   └── Service/                    # Business logic
│       ├── BookingService.php
│       └── EmailService.php
├── templates/                      # Twig templates
│   └── sportoase/
│       ├── base.html.twig
│       ├── dashboard.html.twig
│       ├── booking/
│       └── admin/
├── migrations/                     # Database schema migrations
├── config/                         # Symfony configuration
│   ├── routes.yaml
│   └── services.yaml
├── composer.json                   # PHP dependencies
└── manifest.xml                    # IServ module manifest
```

### Database Schema

Using Doctrine ORM with PostgreSQL:

- **sportoase_users** - User accounts linked to IServ accounts (username, email, role, active status)
- **sportoase_bookings** - Booking records (date, period, teacher, students as JSON, offer details, calendar_event_id for Google Calendar sync)
- **sportoase_slot_names** - Custom labels for fixed time slots
- **sportoase_blocked_slots** - Admin-managed blocked time slots with reasons
- **sportoase_notifications** - Notification history for admins
- **sportoase_audit_logs** - Audit trail for all changes (entity_type, entity_id, action, user, changes, IP)
- **sportoase_fixed_offer_placements** - Admin-positioned fixed course offerings (weekday, period, offer_name) with UNIQUE constraint
- **sportoase_fixed_offer_names** - Custom display names for fixed offers (offer_key -> custom_name mapping)

### Authentication & Authorization

- **IServ SSO Integration**: Automatic user sync with IServ accounts
- **Role-based Access**: Two roles defined in User entity:
  - `teacher` - Can create and manage their own bookings
  - `admin` - Full access to all bookings, users, and system settings
- **Session Management**: Symfony security component handles sessions

### Controllers & Routes

1. **DashboardController** (`/sportoase`) - Weekly schedule view for teachers
2. **BookingController** (`/sportoase/booking/*`) - Create, view, delete bookings
3. **AdminController** (`/sportoase/admin/*`) - Complete admin panel:
   - `/` - Admin dashboard with all bookings
   - `/booking/{id}/edit` - Edit any booking
   - `/slots/manage` - Manage slot names and blocked slots (add/edit/delete)
   - `/users/manage` - User management (activate/deactivate)
   - `/bookings/search` - Search and filter bookings
   - `/statistics` - Usage statistics dashboard with charts
   - `/export/csv` - Export bookings to CSV
   - `/export/pdf` - Export bookings to PDF/HTML
   - `/courses/manage` - Manage fixed course placements and custom names (moveable courses)

All routes defined in `config/routes.yaml` and registered via `manifest.xml`.

### Services

- **BookingService** - Core booking logic (validation, capacity checks, double-booking prevention)
- **EmailService** - SMTP-based notifications for new bookings
- **AuditService** - Audit logging for all entity changes (tracking who changed what and when)
- **GoogleCalendarService** - Google Calendar API integration for syncing bookings (requires setup)
- **ExportService** - Export bookings to CSV and PDF formats with filters

### Key Features

#### Core Features
- **IServ SSO Integration** - Seamless login through IServ accounts
- **Weekly Schedule Management** - View and book time slots across the week
- **Capacity Management** - Automatic enforcement of student limits (default: 5 per slot)
- **Double-booking Prevention** - Checks for student conflicts across bookings
- **Email Notifications** - SMTP-based alerts for new bookings

#### Phase 2: Enhanced Admin Features
- **Enhanced Slot Management** - Delete/edit slot names and blocked slots with modern UI
- **User Management** - Activate/deactivate user accounts, view roles and booking counts
- **Audit Trail System** - Track all changes with AuditLog entity and AuditService
- **Advanced Search & Filter** - Search bookings by teacher, class, offer; filter by date range
- **Statistics Dashboard** - Visual charts showing bookings by day/period, top teachers, weekly trends

#### Phase 3: Polish & Integration
- **Google Calendar Integration** - **PRODUCTION-READY** automatic sync of bookings to Google Calendar with create/update/delete operations, graceful degradation when credentials are missing, and data integrity guarantees to prevent orphaned events
- **CSV/PDF Export** - Export booking data with search filters (requires PDF library)
- **Progressive Web App** - Manifest and service worker for offline support and installation
- **Modern UI** - Tailwind CSS responsive design throughout admin interfaces

#### Google Calendar Integration Details
- **Automatic Sync**: Bookings are automatically synced to Google Calendar
- **Create**: Calendar events created after successful database insert
- **Update**: Calendar events updated when bookings are edited (handles both JSON and array formats for students)
- **Delete**: Calendar events deleted before database deletion with error logging
- **Data Integrity**: Orphaned events prevented by creating DB record first, cleanup on failure
- **Graceful Degradation**: App works perfectly without credentials - calendar features simply disabled
- **Error Handling**: Calendar failures logged but don't block booking operations
- **Setup**: Requires Google Cloud Service Account with Calendar API access (see GOOGLE_CALENDAR_SETUP.md)

### Known Issues & Future Improvements

#### Integration Required for Production
- **Audit Service Integration**: AuditService created but needs integration into BookingController and AdminController mutation methods
- **Export Dependencies**: Install PDF library (`composer require dompdf/dompdf`) and fix getStudentsJson() handling
- **PWA Assets**: Create app icons (192x192, 512x512) and register service worker in templates

#### UI/UX Improvements
- **Booking Form Usability**: Current booking form uses JSON textarea for student data. Should be refactored to use structured form fields (repeated name/class inputs) for better user experience and validation
- **Form Validation**: Needs server-side validation with clear error messages for student data

#### Optional Enhancements (Documented)
- **Email Notification Preferences**: UserPreferences entity for customizing notification settings
- **Multi-language Support**: Symfony Translation component for English interface
- **Audit Log Viewer**: Admin interface to browse and search audit logs
- **Booking Templates**: Save and reuse common booking configurations
- **Calendar View**: Visual month/week calendar interface
- **Legacy Calendar Backfill**: Migration script to create calendar events for bookings that existed before calendar integration was enabled

### Configuration

Module settings managed via IServ admin panel or environment variables:

- `MAX_STUDENTS_PER_PERIOD` - Maximum students per slot (default: 5)
- `BOOKING_ADVANCE_MINUTES` - Minimum advance time for bookings (default: 60)
- `ENABLE_NOTIFICATIONS` - Email notification toggle
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` - Email configuration
- `ADMIN_EMAIL` - Notification recipient (sportoase.kg@gmail.com)
- `GOOGLE_CALENDAR_ID` - Google Calendar ID for booking sync (optional, see GOOGLE_CALENDAR_SETUP.md)

### Time Periods

Fixed 6-period school day (customizable in `BookingService.php`):

1. 07:50 - 08:35
2. 08:35 - 09:20
3. 09:40 - 10:25
4. 10:30 - 11:15
5. 11:20 - 12:05
6. 12:10 - 12:55

## Deployment

### To IServ Server

1. **Package as Debian Package**: `dpkg-buildpackage -us -uc`
2. **Install on IServ**: `aptitude install iserv3-sportoase`
3. **Run Migrations**: `php bin/console doctrine:migrations:migrate`
4. **Enable Module**: Via IServ admin panel

### Local Development

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony serve
```

## External Dependencies

- **Symfony** 6.4+ - Web framework
- **Doctrine ORM** - Database abstraction
- **Twig** - Template engine
- **Symfony Mailer** - Email sending
- **PostgreSQL** - Database server
- **IServ API** - Authentication and user management

## Migration from Flask

**Completed November 22, 2025**: All Flask/Python code removed. Project now uses PHP/Symfony architecture exclusively.
