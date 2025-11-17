# Invoice Feature Documentation

## Overview
The invoice feature allows admins to create and manage invoices for listings. When a listing is created, an admin can generate an invoice for the customer, track payment status, and send invoices.

## New Features Added

### 1. Invoice Button in Listings
- Added "Invoice" button next to Edit/Delete buttons in listing display
- Button redirects to invoice creation page for that specific listing
- Only visible to admin users

### 2. Invoice Creation Page (`create_invoice.php`)
- Allows admins to create new invoices or edit existing ones
- Features:
  - Auto-generated invoice numbers
  - Multiple line items with quantities and prices
  - Tax calculation
  - Notes section
  - Due date management
  - Status tracking (Draft, Sent, Paid, Overdue)

### 3. Invoice Viewing Page (`view_invoice.php`)
- Professional invoice display
- Print functionality
- Status update capabilities
- Send to customer functionality (simulation)
- Edit invoice link

### 4. Invoice Management Page (`invoices.php`)
- Lists all invoices with search and filter capabilities
- Statistics dashboard showing:
  - Total invoices
  - Count by status
  - Total and paid amounts
- Sortable and searchable invoice list

### 5. Database Tables
Two new tables were added:
- `invoices` - Stores invoice header information
- `invoice_items` - Stores individual line items for each invoice

### 6. Navigation Updates
- Added "Invoices" link to admin navigation
- Added "Listings" link to admin navigation for easy access

## Usage Instructions

1. **Creating an Invoice:**
   - Go to Listings page
   - Find the desired listing
   - Click the "Invoice" button
   - Fill in invoice details and items
   - Click "Create Invoice"

2. **Managing Invoices:**
   - Navigate to "Invoices" from admin menu
   - View all invoices with status and customer information
   - Use search to find specific invoices
   - Filter by status (Draft, Sent, Paid, Overdue)

3. **Invoice Workflow:**
   - Create invoice (Status: Draft)
   - Send to customer (Status: Sent)
   - Mark as paid when payment received (Status: Paid)
   - Mark as overdue if past due date

## Files Created/Modified

### New Files:
- `create_invoice.php` - Invoice creation and editing
- `view_invoice.php` - Invoice display and printing
- `invoices.php` - Invoice management dashboard
- `create_invoice_tables.sql` - Database table creation script

### Modified Files:
- `add_listing.php` - Added Invoice button
- `includes/admin_nav.php` - Added navigation links
- `setup.sql` - Added invoice table definitions

## Database Schema

### invoices table:
- `id` - Primary key
- `listing_id` - Foreign key to listings table
- `user_id` - Foreign key to users table (customer)
- `invoice_number` - Unique invoice identifier
- `invoice_date` - Date invoice was created
- `due_date` - Payment due date
- `subtotal` - Total before tax
- `tax_amount` - Tax amount
- `total_amount` - Final total
- `notes` - Additional notes
- `status` - Invoice status (draft/sent/paid/overdue)
- `created_at` - Creation timestamp
- `updated_at` - Last modified timestamp

### invoice_items table:
- `id` - Primary key
- `invoice_id` - Foreign key to invoices table
- `description` - Item description
- `quantity` - Item quantity
- `unit_price` - Price per unit
- `total_price` - Line total (quantity × unit_price)

## Security Features
- Admin authentication required for all invoice operations
- CSRF protection through existing token system
- Input validation and sanitization
- SQL injection prevention through prepared statements