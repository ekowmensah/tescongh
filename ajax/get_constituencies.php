<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Constituency.php';

header('Content-Type: application/json');

if (!isset($_GET['region_id'])) {
    echo json_encode([]);
    exit;
}

$regionId = (int)$_GET['region_id'];

$database = new Database();
$db = $database->getConnection();

$constituency = new Constituency($db);
$constituencies = $constituency->getByRegion($regionId);

echo json_encode($constituencies);
