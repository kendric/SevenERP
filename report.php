<?php
include 'includes/header.php';

// Fetch customers for dropdown
$customers_sql = "SELECT id, name FROM customers ORDER BY name ASC";
$customers_result = mysqli_query($conn, $customers_sql);
$customers = [];
while ($row = mysqli_fetch_assoc($customers_result)) {
    $customers[] = $row;
}

$selected_customer_id = isset($_GET['customer_id']) ? mysqli_real_escape_string($conn, $_GET['customer_id']) : null;
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : null;
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : null;

$report_data = [];
$total_grand_total_sum = 0;

if ($selected_customer_id || ($date_from && $date_to)) {
    $sql = "SELECT b.id, b.bill_no, b.bill_date, c.name as customer_name, c.gst_no as customer_gst, b.grand_total 
            FROM bills b 
            JOIN customers c ON b.customer_id = c.id 
            WHERE 1=1"; // Start with a true condition to append filters

    if ($selected_customer_id) {
        $sql .= " AND b.customer_id = '$selected_customer_id'";
    }
    if ($date_from) {
        $sql .= " AND b.bill_date >= '$date_from'";
    }
    if ($date_to) {
        $sql .= " AND b.bill_date <= '$date_to'";
    }
    $sql .= " ORDER BY b.bill_date DESC, b.id DESC";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $report_data[] = $row;
            $total_grand_total_sum += $row['grand_total'];
        }
    } else {
        $_SESSION['message'] = ['text' => 'Error fetching report: ' . mysqli_error($conn), 'type' => 'danger'];
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Reports</li>
    </ol>
</nav>

<h2>Bill Report</h2>
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

<form method="GET" action="report.php" class="mb-4 card card-body">
    <div class="form-row align-items-end">
        <div class="form-group col-md-4">
            <label for="customer_id_filter">Customer</label>
            <select class="form-control" id="customer_id_filter" name="customer_id">
                <option value="">All Customers</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>" <?php echo ($selected_customer_id == $customer['id']) ? 'selected' : ''; ?>>
                        <?php echo sanitize_output($customer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-3">
            <label for="date_from">Date From</label>
            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo sanitize_output($date_from); ?>">
        </div>
        <div class="form-group col-md-3">
            <label for="date_to">Date To</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo sanitize_output($date_to); ?>">
        </div>
        <div class="form-group col-md-2">
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Search</button>
        </div>
    </div>
</form>

<?php if (!empty($report_data)): ?>
    <h4>Report Results</h4>
    <table class="table table-striped table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Sl.No</th>
                <th>Bill No</th>
                <th>Date</th>
                <th>Name</th>
                <th>GST No</th>
                <th>Grand Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $sl_no = 1; foreach ($report_data as $row): ?>
            <tr>
                <td><?php echo $sl_no++; ?></td>
                <td><?php echo sanitize_output($row['bill_no'] ? $row['bill_no'] : $row['id']); ?></td>
                <td><?php echo sanitize_output(date("d-m-Y", strtotime($row['bill_date']))); ?></td>
                <td><?php echo sanitize_output($row['customer_name']); ?></td>
                <td><?php echo sanitize_output($row['customer_gst']); ?></td>
                <td class="text-right"><?php echo sanitize_output(number_format($row['grand_total'], 2)); ?></td>
                <td><a href='invoice_view.php?id=<?php echo $row['id']; ?>' class='btn btn-sm btn-info'><i class='fas fa-eye'></i> View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-weight-bold">Total:</td>
                <td class="text-right font-weight-bold"><?php echo sanitize_output(number_format($total_grand_total_sum, 2)); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
<?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['customer_id']) || isset($_GET['date_from']))): ?>
    <div class="alert alert-info">No bills found for the selected criteria.</div>
<?php else: ?>
     <div class="alert alert-info">Select criteria above to generate a report.</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>