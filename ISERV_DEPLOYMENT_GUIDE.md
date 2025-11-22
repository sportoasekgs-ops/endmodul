# SportOase IServ Module - Deployment Guide

**Complete Instructions for Building and Deploying to IServ Platform**

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Building the Debian Package](#building-the-debian-package)
4. [Installation on IServ](#installation-on-iserv)
5. [Configuration](#configuration)
6. [Database Setup](#database-setup)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## 🔧 Prerequisites

### On Your Build Machine (Debian/Ubuntu)

You need a Debian or Ubuntu system to build the package. Install these tools:

```bash
sudo apt-get update
sudo apt-get install -y \
    build-essential \
    debhelper \
    devscripts \
    dpkg-dev \
    php-cli \
    php8.2 \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-zip \
    composer \
    nodejs \
    npm
```

### On Your IServ Server

- **IServ Version:** 3.0 or higher
- **PHP Version:** 8.0+ (8.2 recommended)
- **PostgreSQL:** Available through IServ
- **Admin Access:** Required for module installation

---

## ✅ Pre-Deployment Checklist

Verify all these files exist in your project:

```bash
# Required Core Files
✓ manifest.xml                    # IServ module manifest
✓ composer.json                   # PHP dependencies
✓ icon.png                        # Module icon
✓ .env.example                    # Configuration template

# Source Code
✓ src/SportOaseBundle.php         # Main bundle class
✓ src/Controller/                 # All controllers
✓ src/Entity/                     # All entities
✓ src/Service/                    # All services
✓ migrations/                     # Database migrations
✓ templates/                      # Twig templates
✓ config/                         # Symfony configuration

# Debian Packaging
✓ debian/changelog                # Package changelog
✓ debian/control                  # Package metadata
✓ debian/install                  # Installation paths
✓ debian/postinst                 # Post-install script
✓ debian/postrm                   # Post-removal script
✓ debian/rules                    # Build rules

# Compiled Assets
✓ public/build/app.css            # Compiled Tailwind CSS
✓ public/build/app.js             # Compiled JavaScript
✓ public/build/runtime.js         # Webpack runtime
✓ public/build/manifest.json      # Asset manifest
```

---

## 📦 Building the Debian Package

### Step 1: Prepare the Source

```bash
# Clone or copy your SportOase module source
cd /path/to/sportoase

# Ensure debian/rules is executable
chmod +x debian/rules

# Clean any previous builds
debian/rules clean
```

### Step 2: Build the Package

```bash
# Build the Debian package
dpkg-buildpackage -us -uc

# This will:
# 1. Install PHP dependencies via composer
# 2. Install Node.js dependencies via npm
# 3. Compile Tailwind CSS and JavaScript assets
# 4. Create the .deb package file
```

**Build Process Details:**

The `debian/rules` file automatically handles:
- ✅ `composer install --no-dev --optimize-autoloader` (production PHP deps)
- ✅ `npm ci --production=false` (install all npm packages for build)
- ✅ `npm run build` (compile assets with Webpack Encore)
- ✅ Verification that all assets were built correctly
- ✅ Cleanup of `node_modules` (not needed at runtime)

### Step 3: Locate Your Package

After successful build, you'll find:

```bash
../iserv-sportoase_1.0.0_all.deb    # The installable package
```

---

## 🚀 Installation on IServ

### Method 1: Via IServ Admin Panel (Recommended)

1. **Upload the Package:**
   - Log in to IServ Admin Panel
   - Navigate to **System → Package Management**
   - Click **Upload Package**
   - Select `iserv-sportoase_1.0.0_all.deb`

2. **Install:**
   - Click **Install** next to the uploaded package
   - Wait for installation to complete

### Method 2: Via Command Line

```bash
# Copy the .deb file to your IServ server
scp ../iserv-sportoase_1.0.0_all.deb admin@your-iserv-server:/tmp/

# SSH into your IServ server
ssh admin@your-iserv-server

# Install the package
sudo aptitude install /tmp/iserv-sportoase_1.0.0_all.deb

# Or use dpkg
sudo dpkg -i /tmp/iserv-sportoase_1.0.0_all.deb
sudo apt-get install -f  # Fix any missing dependencies
```

### Post-Installation

The `postinst` script automatically:
- ✅ Sets correct permissions (`www-data:www-data`)
- ✅ Creates cache and log directories
- ✅ Creates environment template at `/etc/iserv/sportoase.env`
- ✅ Reloads IServ services

---

## ⚙️ Configuration

### 1. Configure Environment Variables

Edit the configuration file:

```bash
sudo nano /etc/iserv/sportoase.env
```

**Required Settings:**

```bash
# IServ OAuth2 Configuration
ISERV_BASE_URL=https://your-school.iserv.de
ISERV_CLIENT_ID=your-client-id-from-iserv
ISERV_CLIENT_SECRET=your-client-secret-from-iserv

# Database (usually auto-configured by IServ)
DATABASE_URL="postgresql://iserv_user:password@localhost:5432/iserv_database"

# Email Notifications
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587
MAILER_FROM_ADDRESS=sportoase@your-school.de
ADMIN_EMAIL=admin@your-school.de

# Module Settings
MAX_STUDENTS_PER_PERIOD=5
BOOKING_ADVANCE_MINUTES=60

# Application
APP_ENV=prod
APP_SECRET=generate-a-random-secret-here
APP_DEBUG=false
```

**Generate a secure APP_SECRET:**

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 2. Set Up OAuth2 in IServ

1. Navigate to **IServ Admin → System → Single Sign-On**
2. Click **Add OAuth2 Client**
3. Configure:
   - **Name:** SportOase
   - **Redirect URI:** `https://your-school.iserv.de/sportoase/auth/callback`
   - **Scopes:** `openid profile email`
4. Copy the **Client ID** and **Client Secret** to your `.env` file

### 3. Set Permissions

```bash
# Ensure correct ownership
sudo chown www-data:www-data /etc/iserv/sportoase.env
sudo chmod 600 /etc/iserv/sportoase.env
```

---

## 🗄️ Database Setup

### Run Migrations

```bash
# Navigate to the module directory
cd /usr/share/iserv/modules/sportoase

# Run database migrations
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# Verify tables were created
sudo -u postgres psql iserv_database -c "\dt sportoase_*"
```

**Expected Tables:**
- `sportoase_users`
- `sportoase_bookings`
- `sportoase_slot_names`
- `sportoase_blocked_slots`
- `sportoase_notifications`
- `sportoase_audit_log`

---

## 🎯 Testing

### 1. Enable the Module

1. Go to **IServ Admin → System → Modules**
2. Find **SportOase** in the list
3. Click **Enable**

### 2. Access the Module

1. Log in as a teacher or admin
2. Navigate to **SportOase** in the main menu
3. You should see the weekly booking schedule

### 3. Test Functionality

**As a Teacher:**
- ✅ View weekly schedule
- ✅ Create a booking with student names
- ✅ Verify email notification is sent
- ✅ Delete own booking

**As an Admin:**
- ✅ Access admin dashboard
- ✅ View all bookings
- ✅ Edit any booking
- ✅ Manage time slots
- ✅ View statistics

### 4. Verify Logs

```bash
# Check application logs
sudo tail -f /var/log/iserv/sportoase/sportoase.log

# Check IServ system logs
sudo tail -f /var/log/iserv/iserv.log
```

---

## 🔍 Troubleshooting

### Module Not Appearing in Menu

**Check:**
```bash
# Verify module is installed
dpkg -l | grep iserv-sportoase

# Verify files are in place
ls -l /usr/share/iserv/modules/sportoase/

# Check IServ service status
sudo systemctl status iserv
```

**Fix:**
```bash
# Reload IServ services
sudo /usr/sbin/iserv-reload

# Clear IServ cache
sudo rm -rf /var/cache/iserv/*
```

### Database Connection Errors

**Check:**
```bash
# Verify PostgreSQL is running
sudo systemctl status postgresql

# Test database connection
sudo -u postgres psql -c "SELECT version();"

# Verify database URL in environment
cat /etc/iserv/sportoase.env | grep DATABASE_URL
```

### OAuth2 / Login Issues

**Check:**
1. Client ID and Secret match IServ configuration
2. Redirect URI is exactly: `https://your-school.iserv.de/sportoase/auth/callback`
3. Scopes include `openid profile email`

**Test:**
```bash
# Check OAuth configuration
sudo -u www-data php /usr/share/iserv/modules/sportoase/bin/console debug:config knpu_oauth2_client
```

### Email Notifications Not Working

**Check:**
```bash
# Verify SMTP settings
cat /etc/iserv/sportoase.env | grep MAILER

# Test email sending
sudo -u www-data php /usr/share/iserv/modules/sportoase/bin/console mailer:test admin@your-school.de
```

### Assets Not Loading / Styling Issues

**Verify:**
```bash
# Check if assets exist
ls -l /usr/share/iserv/modules/sportoase/public/build/

# Should show:
# app.css (compiled Tailwind CSS)
# app.js
# runtime.js
# manifest.json
```

**If missing:**
The build failed. Rebuild the package following the build instructions.

---

## 📊 Monitoring

### Application Health

```bash
# Check if module is responding
curl -I https://your-school.iserv.de/sportoase

# Monitor logs in real-time
sudo tail -f /var/log/iserv/sportoase/sportoase.log
```

### Database Health

```bash
# Count bookings
sudo -u postgres psql iserv_database -c "SELECT COUNT(*) FROM sportoase_bookings;"

# Check recent activity
sudo -u postgres psql iserv_database -c "SELECT * FROM sportoase_audit_log ORDER BY created_at DESC LIMIT 10;"
```

---

## 🔄 Updating the Module

### Install a New Version

```bash
# Build new version (increment version in debian/changelog first)
dpkg-buildpackage -us -uc

# Install update
sudo aptitude install /path/to/iserv-sportoase_1.1.0_all.deb

# Run any new migrations
cd /usr/share/iserv/modules/sportoase
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# Reload services
sudo /usr/sbin/iserv-reload
```

---

## 🗑️ Uninstallation

### Remove the Module

```bash
# Remove package (keeps configuration)
sudo aptitude remove iserv-sportoase

# Purge completely (removes configuration too)
sudo aptitude purge iserv-sportoase
```

**⚠️ Warning:** The purge command does NOT remove database tables automatically.

### Remove Database Tables (Optional)

```bash
sudo -u postgres psql iserv_database << EOF
DROP TABLE IF EXISTS sportoase_notifications CASCADE;
DROP TABLE IF EXISTS sportoase_audit_log CASCADE;
DROP TABLE IF EXISTS sportoase_blocked_slots CASCADE;
DROP TABLE IF EXISTS sportoase_bookings CASCADE;
DROP TABLE IF EXISTS sportoase_slot_names CASCADE;
DROP TABLE IF EXISTS sportoase_users CASCADE;
EOF
```

---

## 📞 Support

- **Documentation:** `/usr/share/doc/iserv-sportoase/`
- **Email:** sportoase.kg@gmail.com
- **Version:** 1.0.0

---

## ✅ Deployment Checklist

**Before Building:**
- [ ] All source files committed to version control
- [ ] Assets compiled (`public/build/` exists)
- [ ] `debian/changelog` updated with version
- [ ] Build machine has all prerequisites installed

**After Building:**
- [ ] `.deb` file created successfully
- [ ] Package size reasonable (should be < 10 MB)
- [ ] No build errors in output

**During Installation:**
- [ ] Package installed without errors
- [ ] OAuth2 client configured in IServ
- [ ] Environment file configured at `/etc/iserv/sportoase.env`
- [ ] Database migrations run successfully

**After Installation:**
- [ ] Module appears in IServ menu
- [ ] Teachers can access booking page
- [ ] Admins can access admin dashboard
- [ ] Email notifications work
- [ ] No errors in logs

---

**🎉 Your SportOase module is now ready for production deployment!**
