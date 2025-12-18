# User Listings Feature

This feature allows users to view their listings and admins to view listings for any user.

## Files Created/Modified

### New Files:
- `user_listings_api.php` - API endpoint for users to fetch their own listings
- `my_listings.php` - User interface to view their listings with images and status
- `admin_user_listings_api.php` - API endpoint for admins to fetch listings for any user
- `admin_user_listings.php` - Admin interface to view user listings with management options

### Modified Files:
- `dashboard.php` - Added listings summary widget
- `admin.php` - Added "Listings" button for each user
- `includes/user_nav.php` - Added "My Listings" navigation link

## Features

### For Users:
- **My Listings Page**: View all their listings in a grid layout with:
  - Images (first available image from the 3 possible image fields)
  - Title and description
  - Status with color-coded badges
  - Creation date
  - Responsive design
- **Dashboard Summary**: Quick count of total listings
- **Navigation**: Easy access via "My Listings" link in navigation

### For Admins:
- **User Listings Management**: View listings for any user with:
  - Tabular view with listing details
  - Status information
  - Invoice count per listing
  - Direct links to edit listings and manage invoices
- **Quick Access**: "Listings" button for each user in the admin users table
- **User Context**: Shows which user's listings are being viewed

## Status Legend:
- **Status 1 (Yellow)**: Not Finished
- **Status 2 (Red)**: Returned 
- **Status 3 (Blue)**: Finished & Pending Payment
- **Status 4 (Green)**: Completed & Received Payment

## API Endpoints:

### `/user_listings_api.php`
- **Method**: GET
- **Authentication**: User login required
- **Returns**: User's own listings with status text and total count

### `/admin_user_listings_api.php`
- **Method**: GET
- **Parameters**: `user_id` (required)
- **Authentication**: Admin login required
- **Returns**: Specified user's listings with invoice count and user info

## Database Requirements:
- Uses existing `listings` table with columns:
  - `id`, `user_id`, `title`, `description`, `status`
  - `image_path`, `image_path_2`, `image_path_3`
  - `created_at`
- Joins with `invoices` table for invoice counting in admin view
- Requires `users` table for user information

## Error Handling:
- Proper HTTP status codes (400, 401, 404, 500)
- User-friendly error messages
- Graceful fallbacks for loading states
- JSON error responses for API endpoints