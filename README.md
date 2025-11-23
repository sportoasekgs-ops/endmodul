# SportOase - IServ Module ✨

**Modern Sports Facility Booking System for German Schools**

A beautiful, professional Symfony-based IServ module for booking school sports facilities with modern UI, capacity management, and comprehensive admin controls.

## Overview

SportOase is a **modernized IServ-compatible module** built with Symfony 6.4+ that provides a sleek, intuitive booking management system for school sports facilities. Featuring a professional Tailwind CSS design, responsive layouts, and German-language interface.

## ✨ Features

### **Modern UI & UX**
- 🎨 **Professional Tailwind CSS Design** - Custom blue gradient theme with modern components
- 📱 **Fully Responsive** - Beautiful layouts for desktop, tablet, and mobile (320px+)
- 🇩🇪 **Complete German Localization** - All labels, buttons, messages in German
- ⚡ **Dynamic Forms** - Individual student input fields with add/remove functionality (no JSON!)
- 🎯 **Intuitive Navigation** - Gradient header with icons and smooth animations

### **Core Functionality**
- 🔐 **IServ SSO Integration** - OAuth2/OIDC authentication with IServ accounts
- 👥 **Role-based Access Control** - Teachers book, admins manage everything
- 📅 **Weekly Schedule Management** - Clean weekly view with 6 time periods (7:50-12:55)
- 🎓 **Smart Capacity Management** - Automatic enforcement of student limits (max 5 per slot)
- 🚫 **Double-booking Prevention** - Student conflict detection across all bookings
- ⏰ **Time Restrictions** - 60-minute advance booking, automatic weekend blocking
- 📊 **Admin Dashboard** - Statistics cards, user management, booking overview
- 📧 **Email Notifications** - SMTP-based alerts for new bookings
- 🗓️ **Google Calendar Integration** (Optional) - Automatic event creation

## Requirements

- **IServ** 3.0 or higher
- **PHP** 8.0 or higher
- **PostgreSQL** database
- **Symfony** 6.4 or 7.0

## Installation

**📖 See [INSTALLATION.md](INSTALLATION.md) for complete deployment instructions**

Quick overview:

1. Build Debian package: `dpkg-buildpackage -us -uc`
2. Install on IServ: `aptitude install iserv-sportoase_1.0.0_all.deb`
3. Configure OAuth2 in IServ admin panel
4. Edit `/etc/iserv/sportoase.env`
5. Run migrations: `php bin/console doctrine:migrations:migrate`
6. Enable module in **System → Modules**

## Time Periods

The module uses 6 fixed time periods per day:

1. 07:50 - 08:35
2. 08:35 - 09:20
3. 09:40 - 10:25
4. 10:30 - 11:15
5. 11:20 - 12:05
6. 12:10 - 12:55

## Configuration

All configuration is done via `/etc/iserv/sportoase.env` after installation. See **INSTALLATION.md** for detailed setup instructions

## Usage

### For Teachers

1. Navigate to **SportOase** in the IServ main menu
2. View the weekly schedule with available and booked slots
3. Click on an available slot to create a booking
4. Enter student names and classes
5. Submit the booking

### For Administrators

1. Navigate to **SportOase Admin** in the admin menu
2. View all bookings and users
3. Edit or delete any booking
4. Block specific time slots
5. Manage custom slot names

## License

MIT License

## Credits

**Developed by**: SportOase Team  
**Email**: sportoase.kg@gmail.com  
**Version**: 1.0.0  
**Last Updated**: November 22, 2025
