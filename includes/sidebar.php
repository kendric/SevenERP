<nav class="sidebar">
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
        <!-- <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-cogs"></i> Work Order (N/A)
            </a>
        </li> -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'items.php') ? 'active' : ''; ?>" href="items.php">
                <i class="fas fa-box-open"></i> Item
            </a>
        </li>
        <!-- <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-boxes"></i> Stock (N/A)
            </a>
        </li> -->
	<li clas="nav-item">
		<a class="nav-link" <?php echo ($current_page == 'add_customer.php') ? 'active' : ''; ?>" href="add_customer.php">
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
        <!-- <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-database"></i> DB Backup (N/A)
            </a>
        </li> -->
    </ul>
</nav>