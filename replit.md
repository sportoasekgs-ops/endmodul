# SportOase - IServ Module

## Overview

SportOase is a **PHP/Symfony-based IServ module** for booking school sports facilities. It's designed to integrate directly into IServ 3.0+ servers and provides comprehensive booking management for teachers and administrators. The module features a weekly schedule view, capacity controls, email notifications, and full admin controls for managing bookings and users.

**Important**: This is now an IServ module, not a standalone Replit application. It must be deployed to an IServ server.

## Recent Changes

**November 22, 2025 (Latest)**: 
- **Centralized Configuration System**: Created SystemConfig entity and ConfigService for dynamic configuration management
- **Admin Settings Panel**: Built comprehensive settings interface with 4 tabs (time periods, fixed offers, modules, system settings)
- **Time Period Corrections**: Fixed periods 4-6 (4: 10:25-11:20, 5: 11:40-12:25, 6: 12:25-13:10)
- **Security Hardening**: Added CSRF protection and input validation to all admin configuration forms
- **Dashboard Enhancement**: Fixed offers now displayed in weekly schedule view
- **Test Environment**: Updated database schema and PHP files to match corrected structure

**Previous (November 22, 2025)**: 
- Complete conversion from Flask/Python to PHP/Symfony IServ module structure
- Implemented Phase 2 enhanced features (slot management, user management, audit trail, search/filter, statistics)
- Implemented Phase 3 features (Google Calendar service, CSV/PDF export, PWA support)
- Created comprehensive implementation documentation (PHASE2_PHASE3_IMPLEMENTATION.md)

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

- **sportoase_users** - User accounts linked to IServ accounts (username, email, is_admin boolean, active status)
- **sportoase_bookings** - Booking records (date, period, teacher, students as JSON, offer details)
- **sportoase_slot_names** - Custom labels for fixed time slots
- **sportoase_blocked_slots** - Admin-managed blocked time slots with reasons
- **sportoase_notifications** - Notification history for admins
- **sportoase_audit_logs** - Audit trail for all changes (entity_type, entity_id, action, user, changes, IP)
- **sportoase_system_config** - **NEW**: Centralized configuration storage (config_key, config_value as JSON)

### Authentication & Authorization

- **IServ SSO Integration**: Automatic user sync with IServ accounts
- **Role-based Access**: Two roles defined in User entity:
  - `teacher` - Can create and manage their own bookings
  - `admin` - Full access to all bookings, users, and system settings
- **Session Management**: Symfony security component handles sessions

### Controllers & Routes

1. **DashboardController** (`/sportoase`) - Weekly schedule view for teachers (now displays fixed offers)
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
   - **`/settings`** - **NEW**: Centralized configuration panel (periods, offers, modules, system settings) with CSRF protection

All routes defined in `config/routes.yaml` and registered via `manifest.xml`.

### Services

- **BookingService** - Core booking logic (validation, capacity checks, double-booking prevention) - **Now uses ConfigService for dynamic configuration**
- **ConfigService** - **NEW**: Centralized configuration management (periods, fixed offers, modules, system settings)
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
- **Google Calendar Integration** - Service for syncing bookings to Google Calendar (requires setup)
- **CSV/PDF Export** - Export booking data with search filters (requires PDF library)
- **Progressive Web App** - Manifest and service worker for offline support and installation
- **Modern UI** - Tailwind CSS responsive design throughout admin interfaces

### Known Issues & Future Improvements

#### Integration Required for Production
- **Audit Service Integration**: AuditService created but needs integration into BookingController and AdminController mutation methods
- **Export Dependencies**: Install PDF library (`composer require dompdf/dompdf`) and fix getStudentsJson() handling
- **Google Calendar Setup**: Install Google API client (`composer require google/apiclient`) and configure OAuth credentials
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

### Configuration

**Dynamic Configuration (via Admin Settings Panel `/sportoase/admin/settings`)**:
- **Time Periods** - Start/end times for all 6 periods
- **Fixed Offers** - Predefined activities for specific day/period combinations (e.g., "Fußball" on Monday period 3)
- **Free Modules** - Available options for slots without fixed offers (e.g., "Fußball", "Basketball", "Tischtennis")
- **System Settings**:
  - `max_students_per_period` - Maximum students per slot (default: 5, range: 1-20)
  - `booking_advance_minutes` - Minimum advance time for bookings (default: 60, range: 0-1440)

**Static Environment Variables** (email configuration):
- `ENABLE_NOTIFICATIONS` - Email notification toggle
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` - Email configuration
- `ADMIN_EMAIL` - Notification recipient (sportoase.kg@gmail.com)

### Time Periods

6-period school day (**Now dynamically configured via Admin Settings Panel**):

1. 07:50 - 08:35
2. 08:35 - 09:20
3. 09:40 - 10:25
4. **10:25 - 11:20** (corrected from 10:30-11:15)
5. **11:40 - 12:25** (corrected from 11:20-12:05)
6. **12:25 - 13:10** (corrected from 12:10-12:55)

Admins can modify these times via `/sportoase/admin/settings`.

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
