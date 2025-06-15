<nav class="sidebar">
    <!-- The link now points to logo_settings.php -->
    <!-- The 'active' class is now also checked for logo_settings.php -->
    <a class="sidebar-brand <?php echo ($current_page == 'index.php' || $current_page == 'logo.php') ? 'active' : ''; ?>" href="logo.php">
        <?php
        $logo_path = 'uploads/logo.png';
        if (file_exists($logo_path)) {
            // If the logo exists, display it. The 't=' query string is to prevent browser caching.
            echo '<img src="' . $logo_path . '?t=' . time() . '" alt="Company Logo" style="max-height: 40px; margin-right: 10px;">';
        } else {
            // Fallback to the original icon and text if no logo is uploaded
            echo '<i class="fas fa-cubes"></i> SevenSoft ERP';
        }
        ?>
    </a>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'bills.php' || $current_page == 'bill_form.php' || $current_page == 'invoice_view.php') ? 'active' : ''; ?>" href="bill_form.php">
                <i class="fas fa-file-invoice"></i> Bill
            </a>
        </li>
        <!-- ... rest of your nav items ... -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'items.php') ? 'active' : ''; ?>" href="items.php">
                <i class="fas fa-box-open"></i> Item
            </a>
        </li>
	    <li class="nav-item"> 
            <a class="nav-link <?php echo ($current_page == 'add_customer.php') ? 'active' : ''; ?>" href="add_customer.php">
                <i class="fas fa-users"></i> Add Customer
            </a>
	    </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>" href="customers.php">
                <i class="fas fa-users"></i> Customer
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>" href="report.php">
                <i class="fas fa-chart-bar"></i> Report
            </a>
        </li>
    </ul>
</nav>
