# Automatic VIP Code Generation System

This document explains the comprehensive automatic VIP code generation system that has been implemented for the WooCommerce VIP Paketi plugin.

## Overview

The system automatically generates and manages VIP codes when users purchase subscriptions, memberships, or VIP products. It provides seamless integration with WooCommerce Subscriptions and Memberships plugins.

## Features

### 1. Automatic Code Generation
- **Format**: `AUTO-YYYY-XXXXX` (e.g., `AUTO-2024-A1B2C`)
- **Unique**: Each code is guaranteed to be unique
- **Year-based**: Includes current year for easy tracking
- **Random suffix**: 5-character alphanumeric code

### 2. Integration Points
- **WooCommerce Subscriptions**: Generates VIP codes when subscriptions become active
- **WooCommerce Memberships**: Generates VIP codes when memberships are activated
- **Direct VIP Products**: Generates codes for one-time VIP product purchases
- **Payment Completion**: Triggers on successful payment completion

### 3. User Management
- **Automatic Role Assignment**: Adds `wvp_vip_member` role to users
- **Metadata Storage**: Stores VIP status in user metadata
- **Expiry Tracking**: Tracks VIP access expiration dates
- **Status Synchronization**: Keeps VIP status in sync with subscription/membership status

### 4. Database Integration
- **Enhanced Schema**: Extended database with new fields for auto-generated codes
- **Comprehensive Tracking**: Tracks creation date, update date, usage count
- **Relationship Mapping**: Links codes to their source (subscription/membership/order)

## How It Works

### 1. Subscription Activation
When a subscription becomes active (`woocommerce_subscription_status_active`):
1. Check if subscription contains VIP products
2. Generate unique VIP code for the user
3. Calculate expiry date based on subscription billing cycle
4. Store code in database with user billing information
5. Assign VIP role to user
6. Send welcome email with VIP code

### 2. Membership Activation
When a membership is activated (`wc_memberships_user_membership_saved`):
1. Generate unique VIP code for the member
2. Calculate expiry date based on membership duration
3. Store code with member information
4. Assign VIP role
5. Send welcome email

### 3. Subscription Lifecycle Management
- **Cancellation**: Removes VIP role and expires codes
- **Expiration**: Automatically expires VIP access
- **On-Hold**: Temporarily suspends VIP access
- **Renewal**: Extends VIP code expiry date

### 4. Email Notifications
Automatic emails are sent in Serbian (latinica) containing:
- Welcome message
- VIP code
- Expiry date
- List of VIP benefits
- Automatic renewal information

## Database Schema

### New Columns Added
- `used_count`: Tracks usage for auto-generated codes
- `created_at`: Timestamp when code was created
- `updated_at`: Timestamp when code was last modified
- `auto_renewal`: Flag indicating if code auto-renews
- `membership_expires_at`: Membership-specific expiry date

### Enhanced Fields
- `user_id`: Links code to WordPress user
- `expires_at`: VIP access expiry date
- `status`: Code status (active/expired/used)
- `max_uses`: Maximum number of uses (999 for auto-generated)

## Configuration

### Required Plugins
- **WooCommerce**: Core e-commerce functionality
- **WooCommerce Subscriptions** (optional): For subscription-based VIP access
- **WooCommerce Memberships** (optional): For membership-based VIP access

### Product Setup
1. **Enable VIP Pricing**: Set `_wvp_enable_vip_pricing` meta to `yes`
2. **VIP Product Flag**: Set `_wvp_is_vip_product` meta to `yes`
3. **Set VIP Price**: Configure `_wvp_vip_price` meta field

## Code Structure

### Main Integration File
`includes/class-wvp-core.php` - Contains the core auto-generation logic:

#### Key Methods
- `setup_subscription_membership_hooks()`: Registers all integration hooks
- `auto_generate_vip_code_for_user()`: Main code generation logic
- `generate_unique_vip_code()`: Creates unique code strings
- `calculate_vip_expiry_date()`: Determines VIP access duration
- `send_auto_generated_vip_code_email()`: Sends welcome emails

#### Hook Handlers
- `handle_subscription_activated()`: Processes active subscriptions
- `handle_membership_saved()`: Processes membership activations
- `handle_subscription_cancelled/expired()`: Removes VIP access
- `handle_vip_product_payment_complete()`: Handles direct purchases

### Database Layer
`includes/database/class-wvp-database.php` - Enhanced with:
- `get_code_by_code()`: Retrieves codes by code string
- Enhanced `insert_code()`: Supports new fields
- Enhanced `update_code()`: Handles timestamps automatically

### Database Updater
`includes/database/class-wvp-database-updater.php` - Updated to version 2.1:
- Adds new columns for auto-generated codes
- Creates indexes for performance
- Handles database migration

## Testing

### Test Script
`includes/test-auto-vip-codes.php` - Comprehensive test suite:
- Database structure verification
- Code generation testing
- Hook registration validation
- Email system testing

### Manual Testing Steps
1. Create subscription/membership product with VIP pricing
2. Purchase as test user
3. Verify VIP code generation in database
4. Check user role assignment
5. Confirm email delivery
6. Test VIP pricing display

## Error Handling

### Database Errors
- All database operations return `WP_Error` objects on failure
- Comprehensive error logging with `error_log()`
- Fallback handling for missing plugins

### Email Failures
- Uses WordPress `wp_mail()` function
- Errors are logged but don't prevent code generation
- Email templates are in Serbian (latinica)

### Plugin Dependencies
- Graceful degradation when required plugins are missing
- Warning messages in admin panel
- Functionality available based on installed plugins

## Security Considerations

### Code Generation
- Uses cryptographically secure random generation
- Codes are checked for uniqueness before insertion
- 5-character suffix provides 60+ million combinations per year

### User Verification
- VIP codes are linked to specific user accounts
- Billing information is populated from WooCommerce data
- User roles are managed through WordPress capabilities

### Data Protection
- User information is stored according to WooCommerce standards
- Email addresses are validated before code assignment
- Automatic cleanup of expired codes

## Maintenance

### Scheduled Tasks
The system integrates with existing WVP scheduled events:
- Code expiry cleanup runs daily
- Membership expiry warnings sent weekly
- Usage statistics collected for admin reports

### Database Maintenance
- Indexes optimize query performance
- Automatic timestamp management
- Foreign key relationships maintained

### Monitoring
- Comprehensive error logging
- Email delivery tracking
- Code usage statistics

## Troubleshooting

### Common Issues
1. **Codes not generating**: Check if WooCommerce Subscriptions/Memberships are active
2. **Emails not sent**: Verify WordPress mail configuration
3. **VIP pricing not applied**: Ensure product has `_wvp_enable_vip_pricing` meta
4. **Database errors**: Run database update through admin panel

### Debug Information
Enable debugging by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs for entries starting with `WVP:` for detailed debugging information.

## Future Enhancements

### Planned Features
- **Bulk Code Management**: Admin tools for managing auto-generated codes
- **Advanced Analytics**: Detailed reporting on VIP code usage
- **Custom Email Templates**: Admin-configurable email templates
- **API Integration**: REST API endpoints for external integrations

### Performance Optimizations
- **Code Caching**: Cache frequently accessed codes
- **Background Processing**: Queue-based code generation for large sites
- **Database Partitioning**: Optimize for sites with many codes

---

*This documentation covers the complete automatic VIP code generation system implemented for the WooCommerce VIP Paketi plugin. The system provides seamless integration with WooCommerce subscriptions and memberships while maintaining security and performance.*