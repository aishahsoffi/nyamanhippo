<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $sql = "SELECT Category_ID, Category_Name FROM category ORDER BY Category_Name";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
    $conn->close();
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>