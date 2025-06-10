<?php
// edit_customer.php
include 'includes/header.php'; // Assumes $conn, redirect(), sanitize_output() are available

// --- Context for returning to customers.php ---
$return_params = [];
// Retrieve context passed from customers.php
if (isset($_GET['search_context']) && !empty(trim($_GET['search_context']))) {
    $return_params['search'] = trim($_GET['search_context']);
}
if (isset($_GET['page_context']) && !empty(trim($_GET['page_context']))) {
    $return_params['page'] = trim($_GET['page_context']);
}
$return_url = 'customers.php';
if (!empty($return_params)) {
    $return_url .= '?' . http_build_query($return_params);
}
// --- End Context ---

$customer_id = null;
$customer = null;

if (isset($_GET['edit_id'])) {
    $customer_id = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $sql_fetch_customer = "SELECT * FROM customers WHERE id = '$customer_id'";
    $result_fetch_customer = mysqli_query($conn, $sql_fetch_customer);
    if ($result_fetch_customer && mysqli_num_rows($result_fetch_customer) > 0) {
        $customer = mysqli_fetch_assoc($result_fetch_customer);
    } else {
        $_SESSION['message'] = ['text' => 'Customer not found.', 'type' => 'danger'];
        redirect($return_url); // Redirect back with context
        exit;
    }
} else {
    $_SESSION['message'] = ['text' => 'No customer ID provided for editing.', 'type' => 'danger'];
    redirect('customers.php'); // Or $return_url if preferred, but less likely to have context if edit_id is missing
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // We get the customer_id from the hidden field for security/validation
    if (isset($_POST['customer_id_hidden']) && $_POST['customer_id_hidden'] == $customer_id) {
        // Validate and sanitize inputs
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile_no']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $gst_no = mysqli_real_escape_string($conn, trim($_POST['gst_no']));
        $address = mysqli_real_escape_string($conn, trim($_POST['address']));

        // Basic validation (add more as needed)
        if (empty($name) || empty($mobile)) {
            $_SESSION['message'] = ['text' => 'Name and Mobile Number are required.', 'type' => 'danger'];
            // To repopulate form with POST data and show error:
            // Set $customer values from $_POST to show them back in the form
            $customer['name'] = $_POST['name'];
            $customer['mobile_no'] = $_POST['mobile_no'];
            $customer['email'] = $_POST['email'];
            $customer['gst_no'] = $_POST['gst_no'];
            $customer['address'] = $_POST['address'];
        } else {
            $update_sql = "UPDATE customers SET 
                            name = '$name', 
                            mobile_no = '$mobile', 
                            email = '$email', 
                            gst_no = '$gst_no', 
                            address = '$address' 
                          WHERE id = '$customer_id'";

            if (mysqli_query($conn, $update_sql)) {
                $_SESSION['message'] = ['text' => 'Customer updated successfully!', 'type' => 'success'];
                redirect($return_url); // Redirect back to customer list with context
                exit;
            } else {
                $_SESSION['message'] = ['text' => 'Error updating customer: ' . mysqli_error($conn), 'type' => 'danger'];
            }
        }
    } else {
        $_SESSION['message'] = ['text' => 'Invalid customer ID for update.', 'type' => 'danger'];
    }
}

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="<?php echo sanitize_output($return_url); ?>">Customers</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Customer (ID: <?php echo sanitize_output($customer_id); ?>)</li>
    </ol>
</nav>

<h2>Edit Customer</h2>
<hr>

<?php
if (isset($_SESSION['message'])) {
    echo "<div class='alert alert-{$_SESSION['message']['type']} alert-dismissible fade show' role='alert'>
            ".sanitize_output($_SESSION['message']['text'])."
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
              <span aria-hidden='true'>×</span>
            </button>
          </div>";
    unset($_SESSION['message']);
}
?>

<?php if ($customer): ?>
<!-- 
  BEST PRACTICE: Use an empty action to submit the form to the current URL.
  This preserves all GET parameters (like edit_id, search_context, page_context) automatically
  and avoids 404 errors from malformed action URLs.
-->
<form action="" method="post">
    <input type="hidden" name="customer_id_hidden" value="<?php echo sanitize_output($customer_id); ?>">
    
    <div class="form-group">
        <label for="name">Name:</label>
        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize_output($customer['name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="mobile_no">Mobile Number:</label>
        <input type="text" class="form-control" id="mobile_no" name="mobile_no" value="<?php echo sanitize_output($customer['mobile_no']); ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize_output($customer['email']); ?>">
    </div>
    <div class="form-group">
        <label for="gst_no">GST No:</label>
        <input type="text" class="form-control" id="gst_no" name="gst_no" value="<?php echo sanitize_output($customer['gst_no']); ?>">
    </div>
    <div class="form-group">
        <label for="address">Address:</label>
        <textarea class="form-control" id="address" name="address" rows="3"><?php echo sanitize_output($customer['address']); ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Customer</button>
    <a href="<?php echo sanitize_output($return_url); ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
</form>
<?php else: ?>
    <div class="alert alert-warning">Customer data could not be loaded.</div>
    <a href="<?php echo sanitize_output($return_url); ?>" class="btn btn-primary">Back to Customers</a>
<?php endif; ?>


<?php include 'includes/footer.php'; ?>