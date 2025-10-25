<?php
/**
 * Database Migration Script
 * Migrates existing member data to new users/members table structure
 *
 * Run this script once after updating the database schema
 */

require_once 'config/database.php';

echo "Starting database migration...\n";

try {
    // Check if migration has already been run
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        die("Users table doesn't exist. Please run the updated schema.sql first.\n");
    }

    // Check if we have existing members data to migrate
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM members WHERE user_id IS NULL");
    $result = $stmt->fetch();
    $membersToMigrate = $result['count'];

    if ($membersToMigrate == 0) {
        echo "No migration needed - all members already have user accounts.\n";
        exit;
    }

    echo "Found {$membersToMigrate} members to migrate...\n";

    // Start transaction
    $pdo->beginTransaction();

    // Get all existing members
    $stmt = $pdo->query("
        SELECT id, fullname, email, phone, date_of_birth, photo, institution, program,
               year, position, constituency, region, campus_id, password, created_at
        FROM members
        WHERE user_id IS NULL
    ");
    $existingMembers = $stmt->fetchAll();

    $migrated = 0;
    $errors = 0;

    foreach ($existingMembers as $member) {
        try {
            // Create user account
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password, role, status, email_verified, phone_verified, created_at)
                VALUES (?, ?, ?, 'Active', 1, 0, ?)
            ");
            $stmt->execute([
                $member['email'],
                $member['password'],
                $member['position'], // Use position as role (Member, Executive, Patron)
                $member['created_at']
            ]);

            $userId = $pdo->lastInsertId();

            // Update member record to reference the new user
            $stmt = $pdo->prepare("
                UPDATE members SET
                    user_id = ?,
                    membership_status = 'Active'
                WHERE id = ?
            ");
            $stmt->execute([$userId, $member['id']]);

            $migrated++;
            echo "Migrated member: {$member['fullname']} ({$member['email']})\n";

        } catch (PDOException $e) {
            $errors++;
            echo "Error migrating member {$member['fullname']}: " . $e->getMessage() . "\n";
        }
    }

    // Commit transaction
    $pdo->commit();

    echo "\nMigration completed!\n";
    echo "Successfully migrated: {$migrated} members\n";
    echo "Errors: {$errors}\n";

    if ($errors > 0) {
        echo "\nPlease check the errors above and fix any issues before proceeding.\n";
    } else {
        echo "\nDatabase migration successful! You can now use the updated system.\n";
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nNext steps:\n";
echo "1. Test user login with existing accounts\n";
echo "2. Verify member data is correctly linked\n";
echo "3. Test all system features\n";
echo "4. Remove this migration script for security\n";
?>
