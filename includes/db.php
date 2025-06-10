<?php
// includes/db.php

// REMOVE session_start(); FROM THIS FILE (as per your instruction)

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');   // Default XAMPP password
define('DB_NAME', 'sevensoft_erp');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($conn === false){
    // For production, log this error instead of die() or show a generic user message.
    error_log("ERROR: Could not connect to database. " . mysqli_connect_error());
    // It's generally better not to die() in a library file like db.php
    // Let the calling script handle the connection failure if needed.
    // For now, we'll keep a similar behavior but you might want to revise this.
    // For example, the calling script could check if $conn is false.
    die("An error occurred while trying to connect to the service. Please try again later.");
}
mysqli_set_charset($conn, "utf8mb4"); // Good practice for character encoding

// --- ADDED HELPER FUNCTIONS ---

/**
 * Checks if the user is logged in.
 * IMPORTANT: session_start() must have been called before this function is used.
 * @return bool True if the user is logged in, false otherwise.
 */
function isLoggedIn() {
    // Check if the session variable is set and is true
    // Assumes session_status() == PHP_SESSION_ACTIVE
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
        return true;
    }
    return false;
}

/**
 * Redirects to a new page.
 * IMPORTANT: This function calls exit() and will terminate script execution.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit; // Always call exit after a header redirect
}

// --- END OF ADDED HELPER FUNCTIONS ---

?>