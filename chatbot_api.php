<?php
header('Content-Type: application/json');
require_once 'config/database.php'; // Include database connection
session_start();

$response = ["answer" => "I'm sorry, I don't understand that question. Can you rephrase?"];
$user_message = strtolower(trim($_POST['message'] ?? ''));

// --- Determine if the user is logged in ---
$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$user_id = $is_logged_in ? $_SESSION["user_id"] : null;

if (!empty($user_message)) {
    // -------------------------------------------------------------------------
    // A. GENERAL (FAQ) RESPONSES - Available to all users
    // -------------------------------------------------------------------------
    
    if (str_contains($user_message, 'types') || str_contains($user_message, 'offer')) {
        // Query policy types from the database
        $sql = "SELECT name, base_premium FROM policy_types WHERE status = 'Active'";
        $result = mysqli_query($link, $sql);
        $answer = "We currently offer the following active policies: ";
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $answer .= "{$row['name']} (Base Premium: KES " . number_format($row['base_premium'], 2) . "), ";
            }
            $response["answer"] = trim($answer, ', ');
        } else {
            $response["answer"] = "I apologize, no active policy types are available right now.";
        }
    } 
    // Add more general FAQ rules here (e.g., 'how to apply', 'contact info')
    
    // -------------------------------------------------------------------------
    // B. LOGGED-IN RESPONSES (Requires Session Data)
    // -------------------------------------------------------------------------
    elseif ($is_logged_in && (str_contains($user_message, 'status') || str_contains($user_message, 'application'))) {
        // Query pending applications for the logged-in user
        $sql = "SELECT a.application_id, pt.name 
                FROM applications a 
                JOIN policy_types pt ON a.policy_type_id = pt.policy_type_id 
                WHERE a.user_id = ? AND a.status_id = 1 
                LIMIT 1";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $response["answer"] = "Your application for {$row['name']} (ID: {$row['application_id']}) is currently **Pending Review**.";
            } else {
                $response["answer"] = "You do not have any applications currently marked as Pending.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Add more logged-in rules here (e.g., 'my premium', 'policy number')
}

mysqli_close($link);
echo json_encode($response);
?>