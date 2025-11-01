<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/VotingConstituency.php';

header('Content-Type: text/html; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

$votingConstituency = new VotingConstituency($db);

$voting_region_id = isset($_GET['region_id']) ? (int)$_GET['region_id'] : 0;

if ($voting_region_id > 0) {
    $constituencies = $votingConstituency->getByVotingRegion($voting_region_id);
    
    echo '<option value="">Select Voting Constituency</option>';
    foreach ($constituencies as $vc) {
        echo '<option value="' . $vc['id'] . '">' . htmlspecialchars($vc['name']) . '</option>';
    }
} else {
    echo '<option value="">Select Voting Region First</option>';
}
