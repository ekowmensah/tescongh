<?php
// Simple test script to check campus data
require_once 'config/config.php';
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Direct Database Query Test</h2>";

// Get all campuses directly
$query = "SELECT * FROM campuses ORDER BY id DESC LIMIT 5";
$stmt = $db->query($query);
$campuses = $stmt->fetchAll();

echo "<h3>Last 5 Campuses (newest first):</h3>";
echo "<pre>";
foreach ($campuses as $campus) {
    echo "ID: {$campus['id']}\n";
    echo "Name: {$campus['name']}\n";
    echo "Institution ID: {$campus['institution_id']}\n";
    echo "Location: {$campus['location']}\n";
    echo "Created: {$campus['created_at']}\n";
    echo "---\n";
}
echo "</pre>";

// Count total
$countQuery = "SELECT COUNT(*) as total FROM campuses";
$countStmt = $db->query($countQuery);
$count = $countStmt->fetch();
echo "<p><strong>Total campuses in database: {$count['total']}</strong></p>";
