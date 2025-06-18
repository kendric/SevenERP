<?php
include 'includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = ['text' => 'Invalid Bill ID.', 'type' => 'danger'];
    redirect('bills.php');
}

$bill_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch Bill Details
// The `b.*` already includes the `image_path` column. No change to the query is needed.
$bill_sql = "SELECT b.*, c.name as customer_name, c.address as customer_address, c.mobile_no as customer_mobile, c.email as customer_email, c.gst_no as customer_gst 
             FROM bills b 
             JOIN customers c ON b.customer_id = c.id 
             WHERE b.id = ?";
$stmt_bill = mysqli_prepare($conn, $bill_sql);
mysqli_stmt_bind_param($stmt_bill, "i", $bill_id);
mysqli_stmt_execute($stmt_bill);
$bill_result = mysqli_stmt_get_result($stmt_bill);
$bill = mysqli_fetch_assoc($bill_result);
mysqli_stmt_close($stmt_bill);

if (!$bill) {
    $_SESSION['message'] = ['text' => 'Bill not found.', 'type' => 'danger'];
    redirect('bills.php');
}

// Fetch Bill Items
$bill_items_sql = "SELECT * FROM bill_items WHERE bill_id = ?";
$stmt_items = mysqli_prepare($conn, $bill_items_sql);
mysqli_stmt_bind_param($stmt_items, "i", $bill_id);
mysqli_stmt_execute($stmt_items);
$bill_items_result = mysqli_stmt_get_result($stmt_items);
$bill_items = [];
while ($item_row = mysqli_fetch_assoc($bill_items_result)) {
    $bill_items[] = $item_row;
}
mysqli_stmt_close($stmt_items);

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="bills.php">Bills</a></li>
        <li class="breadcrumb-item active" aria-current="page">Invoice #<?php echo sanitize_output($bill['bill_no'] ? $bill['bill_no'] : $bill['id']); ?></li>
    </ol>
</nav>

<div class="invoice-box card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Invoice #<?php echo sanitize_output($bill['bill_no'] ? $bill['bill_no'] : $bill['id']); ?></h4>
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Invoice</button>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h5>From:</h5>
                <address>
                    <strong>SevenSoft ERP Solutions</strong><br>
                    123 ERP Street, Tech City<br>
                    Imaginary State, 98765<br>
                    Phone: (123) 456-7890<br>
                    Email: info@sevensoft.erp
                </address>
            </div>
            <div class="col-sm-6 text-sm-right">
                <h5>To:</h5>
                <address>
                    <strong><?php echo sanitize_output($bill['customer_name']); ?></strong><br>
                    <?php echo nl2br(sanitize_output($bill['customer_address'])); ?><br>
                    Phone: <?php echo sanitize_output($bill['customer_mobile']); ?><br>
                    Email: <?php echo sanitize_output($bill['customer_email']); ?><br>
                    GSTIN: <?php echo sanitize_output($bill['customer_gst']); ?>
                </address>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <strong>Bill Date:</strong> <?php echo sanitize_output(date("F j, Y", strtotime($bill['bill_date']))); ?><br>
                <?php if (!empty($bill['work_order_details'])): ?>
                    <strong>Work Order:</strong> <?php echo sanitize_output($bill['work_order_details']); ?><br>
                <?php endif; ?>

                <!-- START: Display Uploaded Image -->
                <?php if (!empty($bill['image_path']) && file_exists($bill['image_path'])): ?>
                <div class="mt-3">
                    <strong>Attached Image:</strong><br>
                    <a href="<?php echo sanitize_output($bill['image_path']); ?>" target="_blank" title="Click to view full image">
                        <img src="<?php echo sanitize_output($bill['image_path']); ?>" alt="Attached Bill Image" class="img-thumbnail" style="max-width: 200px; max-height: 200px; margin-top: 5px;">
                    </a>
                </div>
                <?php endif; ?>
                <!-- END: Display Uploaded Image -->

            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>HSN/SAC</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $item_no = 1; foreach ($bill_items as $item): ?>
                    <tr>
                        <td><?php echo $item_no++; ?></td>
                        <td><?php echo sanitize_output($item['item_name_at_purchase']); ?></td>
                        <td><?php echo sanitize_output($item['hsn_sac_at_purchase']); ?></td>
                        <td><?php echo sanitize_output($item['quantity']); ?></td>
                        <td><?php echo sanitize_output($item['unit_at_purchase']); ?></td>
                        <td class="text-right"><?php echo sanitize_output(number_format($item['price_at_purchase'], 2)); ?></td>
                        <td class="text-right"><?php echo sanitize_output(number_format($item['amount'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-lg-7 col-md-6">
                <p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
                    Thank you for your business!
                </p>
            </div>
            <div class="col-lg-5 col-md-6">
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width:50%">Subtotal:</th>
                            <td class="text-right"><?php echo sanitize_output(number_format($bill['sub_total'], 2)); ?></td>
                        </tr>
                        <tr>
                            <th>CGST (<?php echo sanitize_output($bill['cgst_rate']); ?>%):</th>
                            <td class="text-right"><?php echo sanitize_output(number_format($bill['cgst_amount'], 2)); ?></td>
                        </tr>
                        <tr>
                            <th>SGST (<?php echo sanitize_output($bill['sgst_rate']); ?>%):</th>
                            <td class="text-right"><?php echo sanitize_output(number_format($bill['sgst_amount'], 2)); ?></td>
                        </tr>
                        <tr>
                            <th>Grand Total:</th>
                            <td class="text-right"><strong><?php echo sanitize_output(number_format($bill['grand_total'], 2)); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Basic print styling */
    @media print {
        body * { visibility: hidden; }
        .invoice-box, .invoice-box * { visibility: visible; }
        .invoice-box {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            border: none;
            box-shadow: none;
        }
        .breadcrumb, .card-header button, .footer, .sidebar, .navbar { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .img-thumbnail { border: 1px solid #ddd !important; padding: 4px; }
        a[href]:after { content: none !important; } /* Prevents link URL from being printed */
    }
</style>

<?php include 'includes/footer.php'; ?>
