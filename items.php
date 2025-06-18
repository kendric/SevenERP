<?php
include 'includes/header.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    // Check if item is in any bill (optional: prevent deletion)
    $check_sql = "SELECT COUNT(*) as bill_item_count FROM bill_items WHERE item_id = '$delete_id'";
    $check_result = mysqli_query($conn, $check_sql);
    $bill_item_row = mysqli_fetch_assoc($check_result);
    if ($bill_item_row['bill_item_count'] > 0) {
        $_SESSION['message'] = ['text' => 'Cannot delete item. It is used in existing bills.', 'type' => 'danger'];
    } else {
        $sql = "DELETE FROM items WHERE id = '$delete_id'";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = ['text' => 'Item deleted successfully!', 'type' => 'success'];
        } else {
            $_SESSION['message'] = ['text' => 'Error deleting item: ' . mysqli_error($conn), 'type' => 'danger'];
        }
    }
    redirect('items.php');
}

// Handle Add/Edit
$edit_item = null;
$item_name = $hsn_sac = $price = $tax_percentage = $unit = $stock = "";
$form_action = "items.php";
$button_text = "Add Item";

if (isset($_GET['edit_id'])) {
    $edit_id = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $sql = "SELECT * FROM items WHERE id = '$edit_id'";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $edit_item = $row;
        $item_name = $edit_item['item_name'];
        $hsn_sac = $edit_item['hsn_sac'];
        $price = $edit_item['price'];
        $tax_percentage = $edit_item['tax_percentage'];
        $unit = $edit_item['unit'];
        $stock = $edit_item['stock'];
        $form_action = "items.php?update_id=" . $edit_id;
        $button_text = "Update Item";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $hsn_sac = mysqli_real_escape_string($conn, $_POST['hsn_sac']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $tax_percentage = mysqli_real_escape_string($conn, $_POST['tax_percentage']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);

    if (isset($_GET['update_id'])) { // Update
    $update_id = (int)$_GET['update_id'];
    $new_stock = (int)$stock;

    // Get current stock BEFORE updating
    $current_stock_sql = "SELECT stock FROM items WHERE id = $update_id";
    $current_stock_result = mysqli_query($conn, $current_stock_sql);
    $current_stock_row = mysqli_fetch_assoc($current_stock_result);
    $stock_before = (int)$current_stock_row['stock'];

    $sql = "UPDATE items SET item_name='$item_name', hsn_sac='$hsn_sac', price='$price', tax_percentage='$tax_percentage', unit='$unit', stock='$new_stock' WHERE id='$update_id'";
    
    if (mysqli_query($conn, $sql)) {
        // --- LOG STOCK EDIT ---
        if ($stock_before != $new_stock) {
            $quantity_changed = abs($new_stock - $stock_before);
            $change_type = ($new_stock > $stock_before) ? 'manual_edit_add' : 'manual_edit_remove';
            $log_sql = "INSERT INTO stock_log (item_id, change_type, quantity_changed, stock_before, stock_after, notes) 
                        VALUES ($update_id, '$change_type', $quantity_changed, $stock_before, $new_stock, 'Edited from Items page')";
            mysqli_query($conn, $log_sql);
        }
        // --- END LOG ---
        $_SESSION['message'] = ['text' => 'Item updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'Error updating item: ' . mysqli_error($conn), 'type' => 'danger'];
    }
} else { // Insert
        $sql = "INSERT INTO items (item_name, hsn_sac, price, tax_percentage, unit, stock) VALUES ('$item_name', '$hsn_sac', '$price', '$tax_percentage', '$unit', '$stock')";
        if (mysqli_query($conn, $sql)) {
        $new_item_id = mysqli_insert_id($conn);
        $stock_val = (int)$stock;
        $log_sql = "INSERT INTO stock_log (item_id, change_type, quantity_changed, stock_before, stock_after, notes) 
                    VALUES ($new_item_id, 'initial_stock', $stock_val, 0, $stock_val, 'Item created')";
        mysqli_query($conn, $log_sql);
            $_SESSION['message'] = ['text' => 'Item added successfully!', 'type' => 'success'];
        } else {
            $_SESSION['message'] = ['text' => 'Error adding item: ' . mysqli_error($conn), 'type' => 'danger'];
        }
    }
    redirect('items.php');
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Items</li>
    </ol>
</nav>

<h2>Manage Items</h2>
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

<div class="card mb-4">
    <div class="card-header">
        <?php echo $edit_item ? 'Edit Item' : 'Add New Item'; ?>
    </div>
    <div class="card-body">
        <form action="<?php echo $form_action; ?>" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="item_name">Item Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo sanitize_output($item_name); ?>" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="hsn_sac">HSN/SAC</label>
                    <input type="text" class="form-control" id="hsn_sac" name="hsn_sac" value="<?php echo sanitize_output($hsn_sac); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="price">Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo sanitize_output($price); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="tax_percentage">Tax (%)</label>
                    <input type="number" step="0.01" class="form-control" id="tax_percentage" name="tax_percentage" value="<?php echo sanitize_output($tax_percentage); ?>">
                </div>
                 <div class="form-group col-md-3">
                    <label for="unit">Unit</label>
                    <input type="text" class="form-control" id="unit" name="unit" value="<?php echo sanitize_output($unit ? $unit : 'Nos'); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="stock">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock" value="<?php echo sanitize_output($stock); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $button_text; ?></button>
            <?php if ($edit_item): ?>
                <a href="items.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>


<h4>Item List</h4>
<table class="table table-striped table-bordered">
    <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>Item Name</th>
            <th>HSN/SAC</th>
            <th>Price</th>
            <th>Tax (%)</th>
            <th>Unit</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT * FROM items ORDER BY item_name ASC";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . sanitize_output($row['id']) . "</td>";
                echo "<td>" . sanitize_output($row['item_name']) . "</td>";
                echo "<td>" . sanitize_output($row['hsn_sac']) . "</td>";
                echo "<td>" . sanitize_output(number_format($row['price'], 2)) . "</td>";
                echo "<td>" . sanitize_output(number_format($row['tax_percentage'], 2)) . "</td>";
                echo "<td>" . sanitize_output($row['unit']) . "</td>";
                echo "<td>" . sanitize_output($row['stock']) . "</td>";
                echo "<td>
                        <a href='items.php?edit_id=" . $row['id'] . "' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i> Edit</a>
                        <a href='items.php?delete_id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this item?\")'><i class='fas fa-trash'></i> Delete</a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8' class='text-center'>No items found.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
