// get_collected_categories.php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('./../../config.php');

if (isset($_GET['member_id'])) {
    $member_id = $_GET['member_id'];
    $collected_categories = [];

    // Query to find all collected categories for the selected member
    $collected_qry = $conn->query("
        SELECT ci.category_id 
        FROM collection_items ci 
        INNER JOIN collection_list cl ON ci.collection_id = cl.id 
        WHERE cl.member_id = '{$member_id}'
    ");

    while ($row = $collected_qry->fetch_assoc()) {
        $collected_categories[] = $row['category_id'];
    }

    // Return the list of collected categories as JSON
    header('Content-Type: application/json');
    echo json_encode($collected_categories);
    exit;
}
?>