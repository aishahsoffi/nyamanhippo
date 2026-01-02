<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    // Get all restaurants with their category information
    $sql = "
        SELECT 
            r.id,
            r.name,
            r.category_id,
            c.Category_Name as category,
            r.link,
            r.logo,
            COALESCE(AVG(i.Price), 15) as avg_price
        FROM restaurants r
        LEFT JOIN category c ON r.category_id = c.Category_ID
        LEFT JOIN item i ON r.id = i.Restaurant_ID
        GROUP BY r.id, r.name, r.category_id, c.Category_Name, r.link, r.logo
        ORDER BY r.id
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $restaurants = [];
    while ($row = $result->fetch_assoc()) {
        $restaurants[] = $row;
    }
    
    // Add hardcoded ratings and distances (you can create tables for these later)
    $ratings = [
        1 => 4.8, 2 => 4.7, 3 => 4.8, 4 => 4.8, 5 => 4.9,
        6 => 4.2, 7 => 4.5, 8 => 4.8, 9 => 4.0, 10 => 4.4,
        11 => 4.6, 12 => 4.7, 13 => 4.5, 14 => 4.6, 15 => 4.7,
        16 => 4.4, 17 => 4.6
    ];
    
    $distances = [
        1 => 2.3, 2 => 1.9, 3 => 1.5, 4 => 2.8, 5 => 1.7,
        6 => 2.5, 7 => 3.2, 8 => 1.8, 9 => 4.1, 10 => 2.0,
        11 => 5.5, 12 => 2.2, 13 => 1.2, 14 => 1.8, 15 => 1.4,
        16 => 2.1, 17 => 1.6
    ];
    
    // Format the data
    foreach ($restaurants as &$restaurant) {
        $restaurant['rating'] = $ratings[$restaurant['id']] ?? 4.5;
        $restaurant['distance'] = $distances[$restaurant['id']] ?? 2.5;
        $restaurant['price'] = round($restaurant['avg_price'], 2);
    }
    
    echo json_encode([
        'success' => true,
        'restaurants' => $restaurants
    ]);
    
    $conn->close();
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>