<?php
include 'includes/header.php'; // Should contain db.php and functions.php, and start session
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
    </ol>
</nav>

<h2>Dashboard</h2>
<hr>

<?php
// Display success/error messages from session
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . sanitize_output($_SESSION['success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . sanitize_output($_SESSION['error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>';
    unset($_SESSION['error_message']);
}

// --- PAGINATION LOGIC ---
$items_per_page = 5;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Get total number of bills
$total_bills_sql = "SELECT COUNT(*) as total FROM bills";
$total_bills_result = mysqli_query($conn, $total_bills_sql);
$total_bills_row = mysqli_fetch_assoc($total_bills_result);
$total_items = $total_bills_row['total'] ?? 0;

$total_pages = ceil($total_items / $items_per_page);

if ($current_page > $total_pages && $total_pages > 0) { // Avoid redirect loop if total_pages is 0
    $current_page = $total_pages; // Or redirect to page 1 if preferred
}

$offset = ($current_page - 1) * $items_per_page;
// --- END PAGINATION LOGIC ---

?>

<h4>Recent Bills</h4>
<table class="table table-striped table-bordered">
    <thead class="thead-dark">
        <tr>
            <th>Bill No</th>
            <th>Bill Date</th>
            <th>Customer Name</th>
            <th>Grand Total</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Assuming $conn is your database connection from db.php (included via header.php)
        // Assuming sanitize_output() is defined in functions.php (included via header.php)
        
        // Using prepared statement for security and clarity
        $sql = "SELECT b.id, b.bill_no, b.bill_date, c.name as customer_name, b.grand_total 
                FROM bills b 
                JOIN customers c ON b.customer_id = c.id 
                ORDER BY b.bill_date DESC, b.id DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $items_per_page, $offset);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . sanitize_output($row['bill_no'] ? $row['bill_no'] : $row['id']) . "</td>";
                    echo "<td>" . sanitize_output(date("d-m-Y", strtotime($row['bill_date']))) . "</td>";
                    echo "<td>" . sanitize_output($row['customer_name']) . "</td>";
                    echo "<td>" . sanitize_output(number_format($row['grand_total'], 2)) . "</td>";
                    echo "<td>";
                    echo "<a href='invoice_view.php?id=" . $row['id'] . "' class='btn btn-sm btn-info mr-1 mb-1'><i class='fas fa-eye'></i> View</a>"; // Added mb-1 for better spacing on small screens
                    echo "<a href='includes/delete_bill.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger mb-1' onclick='return confirm(\"Are you sure you want to delete bill #" . sanitize_output($row['bill_no'] ? $row['bill_no'] : $row['id']) . " and all its items? This action cannot be undone.\");'><i class='fas fa-trash-alt'></i> Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No bills found for this page.</td></tr>";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "<tr><td colspan='5' class='text-center'>Error preparing statement: " . mysqli_error($conn) . "</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php if ($current_page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                    <span aria-hidden="true">«</span>
                    <span class="sr-only">Previous</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">«</span>
            </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                    <span aria-hidden="true">»</span>
                    <span class="sr-only">Next</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">»</span>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>


<?php include 'includes/footer.php'; ?>