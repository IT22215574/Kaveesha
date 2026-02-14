# User Self-Registration System

## Overview
This system allows new users to request accounts on their own, which then require admin approval before becoming active.

## How It Works

### For New Users
1. Visit the login page at `/Kaveesha/login.php`
2. Click on "Request an account" link at the bottom
3. Fill in Name and Mobile Number (10 digits)
4. Submit the registration request
5. Wait for admin approval

### For Administrators
1. Log in to the admin panel
2. Navigate to "Registrations" in the admin menu
3. View all pending registration requests
4. Approve or reject requests:
   - **Approve**: Creates a new user account with the provided details
   - **Reject**: Declines the request (optionally provide a reason)

## Files Created

### Database Migration
- **user_registration_migration.sql**: Creates the `user_registration_requests` table

### User-Facing Pages
- **register_request.php**: Registration request form for new users

### Admin Pages
- **admin_registration_requests.php**: Admin interface to view and process registration requests

### Modified Files
- **login.php**: Added "Request an account" link
- **includes/admin_nav.php**: Added "Registrations" menu item

## Database Schema

### user_registration_requests Table
```sql
- id: Primary key
- username: Requested username
- mobile_number: Requested mobile number (10 digits)
- status: ENUM('pending', 'approved', 'rejected')
- requested_at: Timestamp of request submission
- processed_at: Timestamp when admin processed the request
- processed_by: ID of admin who processed the request
- rejection_reason: Optional reason for rejection
```

## Features

### Security & Validation
- Duplicate mobile number detection (checks existing users and pending requests)
- Duplicate username detection for pending requests
- 10-digit mobile number validation
- Race condition protection when approving requests

### User Experience
- Clean, branded UI matching the existing design
- Clear feedback messages for all actions
- Pending requests highlighted in yellow
- Status badges (Pending/Approved/Rejected)

### Admin Features
- View all requests (pending first, then processed)
- One-click approval
- Rejection with optional reason
- Audit trail (who processed and when)
- Automatic duplicate detection

## Installation

1. Run the database migration:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < /Applications/XAMPP/xamppfiles/htdocs/Kaveesha/user_registration_migration.sql
```

2. Ensure all new files are in place:
   - register_request.php
   - admin_registration_requests.php
   - user_registration_migration.sql

3. The system is now ready to use!

## Usage Flow

1. **User requests account** → Record created in `user_registration_requests` with status='pending'
2. **Admin reviews** → Views all requests in admin panel
3. **Admin approves** → Creates user in `users` table, updates request status to 'approved'
4. **Admin rejects** → Updates request status to 'rejected' with optional reason
5. **User logs in** → Uses mobile number to log in (if approved)

## Notes
- Approved requests create regular user accounts (not admin accounts)
- Users can log in immediately after approval using their mobile number
- The system prevents duplicate registrations during the approval process
- All timestamps are automatically tracked for audit purposes
