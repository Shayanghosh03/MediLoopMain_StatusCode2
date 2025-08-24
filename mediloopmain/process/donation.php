<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include header for CORS and functions
require_once '../includes/header.php';

// Log the request
error_log("Donation request received: " . file_get_contents('php://input'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method.');
}

// Get raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON parsing was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON parse error: " . json_last_error_msg());
    sendResponse(false, 'Invalid JSON data received.');
}

// Validate required fields
$required_fields = [
    'medicationName', 'dosage', 'quantity', 'expiryDate', 
    'condition', 'prescriptionRequired', 'donorName', 
    'donorPhone', 'donorAddress', 'safetyTerms'
];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        error_log("Missing field: $field");
        sendResponse(false, "Missing required field: $field");
    }
}

// Validate safety terms
if ($data['safetyTerms'] !== true && $data['safetyTerms'] !== 'on') {
    error_log("Safety terms not accepted");
    sendResponse(false, "You must accept the safety terms");
}

// Validate and sanitize data
$medicationName = cleanInput($data['medicationName']);
$dosage = cleanInput($data['dosage']);
$quantity = intval($data['quantity']);
$expiryDate = cleanInput($data['expiryDate']);
$medCondition = cleanInput($data['condition']);
$prescriptionRequired = cleanInput($data['prescriptionRequired']);
$donorName = cleanInput($data['donorName']);
$donorPhone = cleanInput($data['donorPhone']);
$donorAddress = cleanInput($data['donorAddress']);

// Additional validation
if ($quantity <= 0) {
    sendResponse(false, "Quantity must be greater than 0");
}

if (strtotime($expiryDate) <= time()) {
    sendResponse(false, "Medication must not be expired");
}

if (!in_array($medCondition, ['excellent', 'good', 'fair'])) {
    sendResponse(false, "Invalid condition specified");
}

if (!in_array($prescriptionRequired, ['yes', 'no'])) {
    sendResponse(false, "Invalid prescription status");
}

// Insert into database
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO donations 
              (medication_name, dosage, quantity, expiry_date, med_condition, 
               prescription_required, donor_name, donor_phone, donor_address) 
              VALUES 
              (:medication_name, :dosage, :quantity, :expiry_date, :med_condition, 
               :prescription_required, :donor_name, :donor_phone, :donor_address)";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':medication_name', $medicationName);
    $stmt->bindParam(':dosage', $dosage);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':expiry_date', $expiryDate);
    $stmt->bindParam(':med_condition', $medCondition);
    $stmt->bindParam(':prescription_required', $prescriptionRequired);
    $stmt->bindParam(':donor_name', $donorName);
    $stmt->bindParam(':donor_phone', $donorPhone);
    $stmt->bindParam(':donor_address', $donorAddress);
    
    if ($stmt->execute()) {
        $lastId = $db->lastInsertId();
        error_log("Donation inserted with ID: $lastId");
        sendResponse(true, 'Thank you! Your donation has been submitted successfully. We will contact you within 24 hours to arrange pickup.', ['id' => $lastId]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Database execute error: " . print_r($errorInfo, true));
        sendResponse(false, 'Unable to process donation. Please try again.');
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    sendResponse(false, 'Database error. Please try again later.');
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again.');
}
?>