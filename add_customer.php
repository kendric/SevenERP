<?php
// add_customer.php

include 'includes/header.php'; // Assumes $conn, redirect(), sanitize_output() are available

// Initialize variables for form fields and errors
$name = '';
$mobile_no = '';
$email = '';
$gst_no = '';
$address = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim POST data
    $name = trim($_POST['name'] ?? '');
    $mobile_no = trim($_POST['mobile_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gst_no = trim($_POST['gst_no'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // --- Validation ---
    // Validate Name (Required)
    if (empty($name)) {
        $errors['name'] = "Customer name is required.";
    }

    // Validate Email (Optional, but if provided, must be a valid email format)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }
    
    // (Optional) Validate Mobile Number (e.g., basic check for digits, length can be more specific)
    // if (!empty($mobile_no) && !preg_match('/^[0-9\s\-\+\(\)]{7,15}$/', $mobile_no)) {
    //     $errors['mobile_no'] = "Please enter a valid mobile number.";
    // }

    // If there are no validation errors, proceed with database operations
    if (empty($errors)) {
        // Sanitize data for database insertion
        $name_db = mysqli_real_escape_string($conn, $name);
        $mobile_db = mysqli_real_escape_string($conn, $mobile_no);
        $email_db = mysqli_real_escape_string($conn, $email);
        $gst_db = mysqli_real_escape_string($conn, $gst_no);
        $address_db = mysqli_real_escape_string($conn, $address);

        // Check for duplicate customer name 
        // Consider a case-insensitive check if appropriate: WHERE LOWER(name) = LOWER('$name_db')
        $check_sql = "SELECT id FROM customers WHERE name = '$name_db'";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            // Set a session message for duplicate name. The form will re-render with this message.
            $_SESSION['message'] = ['text' => 'A customer with this name already exists. Please use a different name.', 'type' => 'danger'];
        } else {
            // Insert new customer into the database
            // Assuming created_at and updated_at columns exist and should be set
            $insert_sql = "INSERT INTO customers (name, mobile_no, email, gst_no, address) 
                           VALUES ('$name_db', '$mobile_db', '$email_db', '$gst_db', '$address_db')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $_SESSION['message'] = ['text' => 'Customer added successfully!', 'type' => 'success'];
                redirect('customers.php'); // Redirect to the customer list page
            } else {
                // Set a session message for database error. The form will re-render with this message.
                $_SESSION['message'] = ['text' => 'Error adding customer: ' . mysqli_error($conn), 'type' => 'danger'];
            }
        }
    } else {
        // Validation errors occurred.
        // The $errors array will be used to display inline error messages next to form fields.
        // Optionally, set a general session message:
        // $_SESSION['message'] = ['text' => 'Please correct the errors highlighted below.', 'type' => 'warning'];
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
        <li class="breadcrumb-item active" aria-current="page">Add New Customer</li>
    </ol>
</nav>

<h2>Add New Customer</h2>
<hr>

<?php
// Display any session messages (e.g., for duplicate name, DB error, or general validation summary if set)
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

<form action="add_customer.php" method="post" novalidate>
    <div class="form-group">
        <label for="name">Customer Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
               id="name" name="name" value="<?php echo sanitize_output($name); ?>" required>
        <?php if (isset($errors['name'])): ?>
            <div class="invalid-feedback"><?php echo sanitize_output($errors['name']); ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="mobile_no">Mobile Number</label>
        <input type="tel" class="form-control <?php echo isset($errors['mobile_no']) ? 'is-invalid' : ''; ?>" 
               id="mobile_no" name="mobile_no" value="<?php echo sanitize_output($mobile_no); ?>">
        <?php if (isset($errors['mobile_no'])): ?>
            <div class="invalid-feedback"><?php echo sanitize_output($errors['mobile_no']); ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
               id="email" name="email" value="<?php echo sanitize_output($email); ?>">
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?php echo sanitize_output($errors['email']); ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="gst_no">GST Number</label>
        <input type="text" class="form-control" id="gst_no" name="gst_no" 
               value="<?php echo sanitize_output($gst_no); ?>">
        <!-- Add validation for GST if needed -->
    </div>

    <div class="form-group">
        <label for="address">Address</label>
        <textarea class="form-control" id="address" name="address" rows="3"><?php echo sanitize_output($address); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Customer</button>
    <a href="customers.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
</form>

<?php include 'includes/footer.php'; ?>
