# Debug Scripts

This directory contains debug and testing scripts for the faculty clearance system.

## Scripts

- `debug_clearance_api.php` - Test clearance API endpoints
- `debug_clearance_hr.php` - Test HR clearance functionality
- `debug_portal_access.php` - Test portal access and permissions
- `debug_roles.php` - Test user roles and permissions
- `debug_users.php` - Test user data and authentication
- `test_session_open.php` - Test session management
- `test_office_archives.php` - Test office-specific archive functionality

## Security

This directory is protected by `.htaccess` with `Deny from all` to prevent web access.

## Usage

These scripts are for development and testing purposes only. They should not be used in production.

To use temporarily:
1. Comment out `Deny from all` in `.htaccess`
2. Access scripts directly via browser
3. Re-enable protection when done
4. Delete this directory when debugging is complete