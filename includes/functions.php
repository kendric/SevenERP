<?php
// NO session_start(); in this file either.
// Session is assumed to be started by the script that includes this (e.g., header.php)

/**
 * Checks if the user is logged in.
 * @return bool True if logged in, false otherwise.
 */
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

/**
 * Redirects to a given URL.
 * @param string $url The URL to redirect to.
 */

/**
 * Sanitizes data for safe HTML output.
 * @param mixed $data The data to sanitize. If null, returns an empty string.
 * @return string The sanitized string.
 */
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// You can add other global utility functions here
// e.g., sanitize_input for form data
// function sanitize_input($data) {
//     $data = trim($data);
//     $data = stripslashes($data); // If magic_quotes_gpc is ON (it shouldn't be)
//     $data = htmlspecialchars($data);
//     return $data;
// }
?>