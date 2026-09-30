# Office-Specific Archive System

## Overview

The faculty clearance system now uses office-specific archive tables to ensure that each clearance office (Academic, Department, Library, Property, Financial, HR) maintains its own isolated archive of approval/denial decisions.

## Archive Tables

Each office has its own archive table:

- `faculty_clearance_archives_academic` - Academic Clearance records
- `faculty_clearance_archives_department` - Department Clearance records
- `faculty_clearance_archives_library` - Library Clearance records
- `faculty_clearance_archives_property` - Property Clearance records
- `faculty_clearance_archives_financial` - Financial Clearance records
- `faculty_clearance_archives_hr` - HR Clearance records

## Office Key Mapping

- `Academic Clearance` → `academic`
- `Department Clearance` → `department`
- `Library Clearance` → `library`
- `Property Clearance` → `property`
- `Financial Clearance` → `financial`
- `HR Clearance` → `hr`

## Functions

### `facultyClearanceCreateOfficeArchiveTables(PDO $db): void`

Creates all six office-specific archive tables if they don't exist.

### `facultyClearanceArchiveOfficeItem(PDO $db, int $clearanceItemId, string $officeKey): void`

Archives a specific clearance item to the appropriate office-specific table when an office approves or denies a requirement.

### `facultyClearanceGetOfficeKey(string $officeName): string`

Maps a clearance office name to its archive table key.

### `facultyClearanceGetOfficeArchives(PDO $db, string $officeKey, array $assignedDepartments = [], bool $canSeeAll = false): array`

Retrieves archived records for a specific office with optional department filtering.

### `facultyClearanceGetOfficeArchiveDetail(PDO $db, string $officeKey, int $archiveId): ?array`

Retrieves a specific archived record from an office's archive table.

## Integration with ClearanceController

The `ClearanceController.php` has been updated to:

1. Import the `clearance_archives.php` helper
2. Call `facultyClearanceArchiveOfficeItem()` after each review-item action
3. Query office-specific archives when the `office` parameter is provided to the `archives` action
4. Query office-specific archive details when the `office` parameter is provided to the `archive-detail` action

## Security

- Each office can only access its own archive table
- Department Heads are restricted to their assigned departments
- HR and Faculty Admin can see all departments
- Server-side validation enforces access restrictions

## API Usage

### Get Office-Specific Archives

```
GET ?action=archives&office=Academic Clearance
```

### Get Office-Specific Archive Detail

```
GET ?action=archive-detail&office=Academic Clearance&archive_id=123
```

### Legacy Combined Archives

The original combined archive table (`faculty_clearance_archives`) still works for backward compatibility when no `office` parameter is provided.

## Testing

Use the test script at `debug/test_office_archives.php` to verify the office-specific archive functionality.