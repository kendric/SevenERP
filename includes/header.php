<?php
// VERY IMPORTANT: Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files AFTER session_start
require_once "db.php"; // Make sure db.php DOES NOT have session_start()
require_once "functions.php"; // Assuming isLoggedIn, redirect, sanitize_output are here. Make sure this file DOES NOT have session_start()

// Now you can safely use session-dependent functions
if (!isLoggedIn()) {
    redirect("login.php");
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SevenSoft ERP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Ensure this path is correct -->
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 250px; background-color: #343a40; color: #fff; padding-top: 1rem;}
        .sidebar .nav-link { color: #adb5bd; }
        .sidebar .nav-link.active { color: #fff; background-color: #007bff; }
        .sidebar .nav-link:hover { color: #fff; background-color: #495057; }
        .content { flex: 1; padding: 20px; background-color: #f8f9fa; }
        .navbar-custom { background-color: #007bff; color: #fff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: #fff; }
        .breadcrumb {background-color: #e9ecef;}
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <a class="navbar-brand" href="index.php"><i class="fas fa-cubes"></i> SevenSoft ERP</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span> <!-- You might need to style this for visibility on a dark navbar -->
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <?php if (isset($_SESSION["username"])): // Good practice to check if session variable exists ?>
                <li class="nav-item">
                    <span class="navbar-text text-white mr-3">
                        Welcome, <?php echo sanitize_output($_SESSION["username"]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <?php include 'sidebar.php'; // Ensure this path is correct. If header.php is in 'includes', this might be '../sidebar.php' or just 'sidebar.php' if sidebar is also in 'includes' ?>
        <div class="content">
            <!-- The rest of your page content will go in the individual files that include this header -->