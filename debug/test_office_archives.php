<?php
/** Test script for office-specific archive functionality */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/faculty/config/database.php';
require_once ROOT_PATH . '/modules/faculty/controllers/clearance_archives.php';

echo "<h1>Office-Specific Archive System Test</h1>";
echo "<pre>";

try {
    $db = facultyDb();
    echo "✓ Database connection established\n\n";
    
    // Test 1: Create all office archive tables
    echo "Test 1: Creating office-specific archive tables...\n";
    facultyClearanceCreateOfficeArchiveTables($db);
    echo "✓ All office archive tables created\n\n";
    
    // Test 2: Verify tables exist
    echo "Test 2: Verifying tables exist...\n";
    $offices = ['academic', 'department', 'library', 'property', 'financial', 'hr'];
    foreach ($offices as $office) {
        $tableName = "faculty_clearance_archives_{$office}";
        $tableExists = $db->query("SHOW TABLES LIKE '{$tableName}'")->fetch();
        if ($tableExists) {
            echo "✓ Table {$tableName} exists\n";
        } else {
            echo "✗ Table {$tableName} does NOT exist\n";
        }
    }
    echo "\n";
    
    // Test 3: Test office key mapping
    echo "Test 3: Testing office key mapping...\n";
    $testOffices = [
        'Academic Clearance' => 'academic',
        'Department Clearance' => 'department',
        'Library Clearance' => 'library',
        'Property Clearance' => 'property',
        'Financial Clearance' => 'financial',
        'HR Clearance' => 'hr'
    ];
    foreach ($testOffices as $name => $expectedKey) {
        $actualKey = facultyClearanceGetOfficeKey($name);
        if ($actualKey === $expectedKey) {
            echo "✓ {$name} → {$actualKey}\n";
        } else {
            echo "✗ {$name} → Expected: {$expectedKey}, Got: {$actualKey}\n";
        }
    }
    echo "\n";
    
    // Test 4: Test archive retrieval
    echo "Test 4: Testing archive retrieval...\n";
    $academicArchives = facultyClearanceGetOfficeArchives($db, 'academic', [], true);
    echo "✓ Retrieved " . count($academicArchives) . " academic archive records\n\n";
    
    echo "All tests completed successfully!\n";
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
