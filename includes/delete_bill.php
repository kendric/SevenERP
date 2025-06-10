<?php
// delete_bill.php

// 1. Start session (for flash messages) and include necessary files
//    (header.php usually handles session_start, db.php, and functions.php)
//    For a script that ONLY processes and redirects, we might not need the full HTML header.
//    Let's ensure session is started and include db.php and functions.php directly.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';       // For $conn (database connection)
require_once 'functions.php'; // For redirect() and sanitize_output() if needed for messages

// 2. Check if user is logged in (important for security)
if (!isLoggedIn()) {
    // Optionally, set an error message if you want to inform about permission denied
    // $_SESSION['error_message'] = "You must be logged in to perform this action.";
    redirect('../login.php'); // Redirect to login if not logged in
}

// 3. Get and validate the Bill ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No Bill ID provided for deletion.";
    redirect('../index.php'); // Or your main bills listing page
}

$bill_id = $_GET['id'];

// Basic validation: ensure it's a positive integer
if (!filter_var($bill_id, FILTER_VALIDATE_INT) || $bill_id <= 0) {
    $_SESSION['error_message'] = "Invalid Bill ID format.";
    redirect('../index.php');
}

// 4. Attempt to delete the bill and its items using a transaction
mysqli_begin_transaction($conn);

try {
    // First, delete related items from 'bill_items' table
    // Assuming your bill items table is named 'bill_items' and has a 'bill_id' column
    $sql_delete_items = "DELETE FROM bill_items WHERE bill_id = ?";
    $stmt_items = mysqli_prepare($conn, $sql_delete_items);
    if (!$stmt_items) {
        throw new Exception("Error preparing statement for deleting bill items: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_items, "i", $bill_id);
    if (!mysqli_stmt_execute($stmt_items)) {
        throw new Exception("Error deleting bill items: " . mysqli_stmt_error($stmt_items));
    }
    mysqli_stmt_close($stmt_items);

    // Second, delete the bill from 'bills' table
    $sql_delete_bill = "DELETE FROM bills WHERE id = ?";
    $stmt_bill = mysqli_prepare($conn, $sql_delete_bill);
    if (!$stmt_bill) {
        throw new Exception("Error preparing statement for deleting bill: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_bill, "i", $bill_id);
    if (!mysqli_stmt_execute($stmt_bill)) {
        throw new Exception("Error deleting bill: " . mysqli_stmt_error($stmt_bill));
    }

    // Check if the bill was actually deleted (affected_rows > 0)
    $affected_rows = mysqli_stmt_affected_rows($stmt_bill);
    mysqli_stmt_close($stmt_bill);

    if ($affected_rows > 0) {
        mysqli_commit($conn); // All good, commit the transaction
        $_SESSION['success_message'] = "Bill #{$bill_id} and its associated items have been successfully deleted.";
    } else {
        // Bill was not found, or already deleted.
        // We can still commit if items were deleted, or rollback if we consider this an error.
        // For simplicity, if items might have been deleted but bill not found, let's treat it as "not found".
        mysqli_rollback($conn); // Rollback if bill itself wasn't found, to be safe.
        $_SESSION['error_message'] = "Bill #{$bill_id} not found. No bill was deleted.";
        // If you want to be more nuanced: if items were deleted but bill not, you might commit items
        // and report "Items deleted, but bill main record not found."
    }

} catch (Exception $e) {
    mysqli_rollback($conn); // Something went wrong, rollback any changes
    $_SESSION['error_message'] = "Error deleting bill: " . $e->getMessage();
}

// 5. Redirect back to the bills list page
redirect('../index.php'); // Or bills.php, or wherever your list is

?>