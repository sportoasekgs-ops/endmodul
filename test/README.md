# SportOase Test Environment

This is a standalone test environment for the SportOase IServ module. Use this environment to test all features without requiring access to IServ.

## 🚀 Quick Start

1. **Access the test environment**: The application is running on port 5000
2. **Login with test credentials**:
   - **Admin**: `admin` / `test123`
   - **Teacher 1**: `lehrer1` / `test123`
   - **Teacher 2**: `lehrer2` / `test123`

## 📋 Features

### For All Users:
- ✅ View weekly booking schedule
- ✅ Create new bookings with up to 5 students
- ✅ Delete own bookings
- ✅ Navigate between weeks
- ✅ See blocked time slots

### For Administrators:
- ✅ **Admin Panel** (`/test/admin.php`)
- ✅ View all bookings and statistics
- ✅ Delete any booking
- ✅ Block/unblock time slots
- ✅ Manage users (activate/deactivate)
- ✅ View real-time statistics

## 🗄️ Database Structure

The test environment uses PostgreSQL with the following tables:

### Tables:
- `sportoase_users` - User accounts (teachers and admins)
- `sportoase_bookings` - All booking records
- `sportoase_slot_names` - Custom slot names
- `sportoase_blocked_slots` - Administratively blocked time slots
- `sportoase_notifications` - System notifications

## 🛠️ Setup

The database has already been initialized. If you need to reset it:

```bash
cd test
php setup_database.php
```

This will:
- Create all necessary tables
- Create test users with password `test123`
- Set up the database schema

## 📱 Test Scenarios

### Scenario 1: Teacher Books a Slot
1. Login as `lehrer1` / `test123`
2. Click "+ Buchen" on any available slot
3. Fill in activity and student details
4. Submit the form
5. Verify booking appears in the schedule

### Scenario 2: Admin Blocks a Slot
1. Login as `admin` / `test123`
2. Go to Admin Panel
3. Navigate to "Slots sperren" tab
4. Select date and period
5. Enter reason (e.g., "Beratung")
6. Submit to block the slot
7. Verify slot appears as blocked in the schedule

### Scenario 3: Admin Manages Bookings
1. Login as `admin` / `test123`
2. Go to Admin Panel
3. View all bookings in "Buchungen verwalten" tab
4. Delete a booking
5. Verify it's removed from the schedule

### Scenario 4: Admin Manages Users
1. Login as `admin` / `test123`
2. Go to Admin Panel
3. Navigate to "Benutzer verwalten" tab
4. Deactivate a teacher account
5. Try logging in as that teacher (should fail)
6. Reactivate the account

## 📊 Features to Test

### Dashboard Features:
- [x] Weekly schedule view
- [x] Create booking modal
- [x] Multiple students per booking (up to 5)
- [x] Delete own bookings
- [x] View blocked slots
- [x] Week navigation (previous/next)

### Admin Features:
- [x] Statistics dashboard
- [x] View all bookings
- [x] Delete any booking
- [x] Block time slots
- [x] Unblock time slots
- [x] View all blocked slots
- [x] User management
- [x] Activate/deactivate users

## 🔒 Security Notes

### Test Environment Only:
- Simple password authentication (no IServ SSO)
- Session-based authentication
- PostgreSQL database with test data

### Not in Test Environment:
- IServ OAuth2/OIDC integration
- Google Calendar integration
- Email notifications
- Real production data

## 📂 File Structure

```
test/
├── README.md              # This file
├── config.php             # Database connection and helper functions
├── setup_database.php     # Database initialization script
├── login.php              # Login page
├── dashboard.php          # Main dashboard (for all users)
└── admin.php              # Admin panel (admin only)
```

## 🐛 Troubleshooting

### Can't login?
- Make sure you're using the correct credentials: `admin` / `test123`, `lehrer1` / `test123`, or `lehrer2` / `test123`
- Check if the user is active (admins can manage user status)

### Database errors?
- Run `php test/setup_database.php` to recreate tables

### Port 5000 not working?
- Check if the workflow "SportOase Test Environment" is running
- Restart the workflow if necessary

## 🎯 Comparison: Test vs Production

| Feature | Test Environment | IServ Production |
|---------|-----------------|------------------|
| Authentication | Simple login | IServ SSO (OAuth2/OIDC) |
| User Management | Manual | Automatic from IServ |
| Email Notifications | Disabled | SMTP enabled |
| Google Calendar | Disabled | Optional integration |
| Database | Replit PostgreSQL | School PostgreSQL |
| URL | `/test/` | `/sportoase/` |

## ✅ What This Tests

This environment allows you to test:
- ✅ Core booking functionality
- ✅ User interface and user experience
- ✅ Admin panel features
- ✅ Slot blocking system
- ✅ User management
- ✅ Week navigation
- ✅ Multi-student bookings
- ✅ Permission system (admin vs teacher)

## ❌ What This Doesn't Test

This environment does NOT test:
- ❌ IServ SSO integration
- ❌ OAuth2/OIDC authentication flow
- ❌ Automatic user provisioning from IServ
- ❌ Email notifications
- ❌ Google Calendar synchronization
- ❌ Production deployment on IServ

## 🚀 Next Steps

After testing here, you'll need to:
1. Configure IServ OAuth2 credentials in `.env`
2. Install the module on IServ
3. Test SSO authentication
4. Configure email and calendar (optional)
5. Deploy to production

See `ISERV_DEPLOYMENT_GUIDE.md` for production deployment instructions.

## 🆘 Need Help?

If you encounter issues:
1. Check the browser console for JavaScript errors
2. Check workflow logs for PHP errors
3. Verify database tables exist: `psql $DATABASE_URL -c "\dt"`
4. Re-run database setup if needed

---

**Happy Testing! 🎉**
