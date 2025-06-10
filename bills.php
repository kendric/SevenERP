<?php
include 'includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bills</li>
    </ol>
</nav>

<h2>Manage Bills</h2>
<hr>

<?php
if (isset($_SESSION['message'])) {
    echo "<div class='alert alert-{$_SESSION['message']['type']} alert-dismissible fade show' role='alert'>
            {$_SESSION['message']['text']}
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>×</span>
            </button>
          </div>";
    unset($_SESSION['message']);
}
?>

<a href="bill_form.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Create New Bill</a>

<h4>Bill List</h4>
<table class="table table-striped table-bordered">
    <thead class="thead-dark">
        <tr>
            <th>Bill No</th>
            <th>Bill Date</th>
            <th>Customer Name</th>
            <th>Work Order</th>
            <th>Grand Total</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT b.id, b.bill_no, b.bill_date, c.name as customer_name, b.work_order_details, b.grand_total 
                FROM bills b 
                JOIN customers c ON b.customer_id = c.id 
                ORDER BY b.bill_date DESC, b.id DESC";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . sanitize_output($row['bill_no'] ? $row['bill_no'] : $row['id']) . "</td>";
                echo "<td>" . sanitize_output(date("d-m-Y", strtotime($row['bill_date']))) . "</td>";
                echo "<td>" . sanitize_output($row['customer_name']) . "</td>";
                echo "<td>" . sanitize_output(substr($row['work_order_details'], 0, 50)) . (strlen($row['work_order_details']) > 50 ? '...' : '') . "</td>";
                echo "<td>" . sanitize_output(number_format($row['grand_total'], 2)) . "</td>";
                echo "<td>
                        <a href='invoice_view.php?id=" . $row['id'] . "' class='btn btn-sm btn-info'><i class='fas fa-eye'></i> View</a>
                        <!-- Add edit/delete functionality for bills if needed -->
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center'>No bills found.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>