<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Only admins and executives can export reports
if (!hasAnyRole(['Admin', 'Executive'])) {
    die('Unauthorized access');
}

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$selected_region = isset($_GET['region_id']) ? (int)$_GET['region_id'] : null;
$selected_constituency = isset($_GET['constituency_id']) ? (int)$_GET['constituency_id'] : null;

// Build query with filters
$query = "SELECT 
            m.fullname,
            m.student_id,
            m.phone,
            m.gender,
            m.institution,
            m.department,
            m.program,
            m.year,
            m.position,
            m.membership_status,
            m.npp_position,
            vr.name as voting_region,
            vc.name as voting_constituency,
            m.region as current_region,
            m.constituency as current_constituency,
            c.name as campus_name
          FROM members m
          LEFT JOIN voting_regions vr ON m.voting_region_id = vr.id
          LEFT JOIN voting_constituencies vc ON m.voting_constituency_id = vc.id
          LEFT JOIN campuses c ON m.campus_id = c.id
          WHERE 1=1";

$params = [];

if ($selected_region) {
    $query .= " AND m.voting_region_id = :region_id";
    $params[':region_id'] = $selected_region;
}

if ($selected_constituency) {
    $query .= " AND m.voting_constituency_id = :constituency_id";
    $params[':constituency_id'] = $selected_constituency;
}

$query .= " ORDER BY vr.name ASC, vc.name ASC, m.fullname ASC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$members = $stmt->fetchAll();

// Set headers for CSV download
$filename = 'voting_regions_report_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add header row
fputcsv($output, [
    'Full Name',
    'Student ID',
    'Phone Number',
    'Gender',
    'Voting Region',
    'Voting Constituency',
    'Current Region',
    'Current Constituency',
    'Institution',
    'Campus',
    'Department',
    'Program',
    'Year',
    'Position',
    'NPP Position',
    'Membership Status'
]);

// Add data rows
foreach ($members as $member) {
    fputcsv($output, [
        $member['fullname'],
        $member['student_id'],
        $member['phone'],
        $member['gender'] ?? '',
        $member['voting_region'] ?? 'Not Specified',
        $member['voting_constituency'] ?? 'Not Specified',
        $member['current_region'] ?? '',
        $member['current_constituency'] ?? '',
        $member['institution'] ?? '',
        $member['campus_name'] ?? '',
        $member['department'] ?? '',
        $member['program'] ?? '',
        $member['year'] ?? '',
        $member['position'] ?? '',
        $member['npp_position'] ?? '',
        $member['membership_status'] ?? ''
    ]);
}

fclose($output);
exit;
?>
