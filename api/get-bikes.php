<?php
/**
 * API Endpoint: Get Available Bikes
 * Returns JSON array of all bikes for public preview
 */

require_once "../config.php";

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Fetch all bikes with their details
    $sql = "
        SELECT 
            bike_id,
            bike_name,
            status,
            location,
            last_maintained_date
        FROM bikes
        ORDER BY 
            CASE status 
                WHEN 'available' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'maintenance' THEN 3 
                WHEN 'rented' THEN 4 
            END,
            bike_id
    ";

    $bikes = $pdo->query($sql)->fetchAll();

    // Return success response
    echo json_encode([
        'success' => true,
        'count' => count($bikes),
        'bikes' => $bikes
    ]);

} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch bikes'
    ]);
}
