<?php
header('Content-Type: application/json');
include 'db_connection.php'; 

try {
    // We filter by link to exclude "Coming Soon" pages
    $query = "
        SELECT r.name, r.logo, r.link, COUNT(o.Order_ID) as total_orders
        FROM restaurants r
        JOIN item i ON r.id = i.Restaurant_ID
        JOIN cart c ON i.Item_ID = c.Item_ID
        JOIN `order` o ON c.Cart_ID = o.Cart_ID
        WHERE r.link NOT LIKE '%comingsoon.html%' 
        GROUP BY r.id
        ORDER BY total_orders DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->query($query);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'brands' => $brands]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>