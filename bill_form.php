<?php
include 'includes/header.php'; // Includes db.php and functions.php

// Fetch ALL customers for the dropdown/search
$customers_sql = "SELECT id, name FROM customers ORDER BY name ASC";
$customers_result = mysqli_query($conn, $customers_sql);
$all_customers_data_for_js = [];
while ($row = mysqli_fetch_assoc($customers_result)) {
    $all_customers_data_for_js[] = $row;
}

// Fetch available items for the item rows
$items_sql = "SELECT id, item_name, hsn_sac, price, tax_percentage, unit, stock FROM items WHERE stock > 0 ORDER BY item_name ASC";
$items_result = mysqli_query($conn, $items_sql);
// We will build the <option> elements directly in the HTML template below.


// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get main bill details from the form
    $bill_date = $_POST['bill_date'];
    $customer_id = $_POST['customer_id'];
    $work_order_details = $_POST['work_order_details']; // Note: We should escape this, but skipping for simplicity
    
    $cgst_rate = 9.00;
    $sgst_rate = 9.00;

    // 2. Insert the main bill record to get a new bill ID
    // We insert it with temporary zeros for totals, and we'll update it later.
    $bill_sql = "INSERT INTO bills (bill_date, customer_id, work_order_details, cgst_rate, sgst_rate, sub_total, cgst_amount, sgst_amount, grand_total)
                 VALUES ('$bill_date', $customer_id, '$work_order_details', $cgst_rate, $sgst_rate, 0, 0, 0, 0)";

    if (mysqli_query($conn, $bill_sql)) {
        // Get the ID of the bill we just created
        $bill_id = mysqli_insert_id($conn);

        // Set the bill_no to be the same as the ID. This is a common and simple approach.
        $update_bill_no_sql = "UPDATE bills SET bill_no = '$bill_id' WHERE id = $bill_id";
        mysqli_query($conn, $update_bill_no_sql);

        // 3. Loop through each item submitted and add it to the 'bill_items' table
        $total_sub_amount = 0;
        $item_ids = $_POST['item_id'];
        $quantities = $_POST['quantity'];

        for ($i = 0; $i < count($item_ids); $i++) {
            $current_item_id = $item_ids[$i];
            $current_quantity = $quantities[$i];

            // Skip if the item or quantity is not valid
            if (empty($current_item_id) || $current_quantity <= 0) {
                continue;
            }

            // Get the item's details (price, name, etc.) at the time of purchase
            $item_detail_sql = "SELECT item_name, hsn_sac, price, tax_percentage, unit FROM items WHERE id = $current_item_id";
            $item_detail_result = mysqli_query($conn, $item_detail_sql);
            $item = mysqli_fetch_assoc($item_detail_result);

            // Calculate amount for this single item
            $amount_for_item = $current_quantity * $item['price'];
            $total_sub_amount += $amount_for_item;

            // Insert this item into the bill_items table, linking it with the bill_id
            $bill_item_sql = "INSERT INTO bill_items (bill_id, item_id, item_name_at_purchase, hsn_sac_at_purchase, quantity, price_at_purchase, tax_percentage_at_purchase, unit_at_purchase, amount)
                              VALUES ($bill_id, $current_item_id, '{$item['item_name']}', '{$item['hsn_sac']}', $current_quantity, {$item['price']}, {$item['tax_percentage']}, '{$item['unit']}', $amount_for_item)";
            
            if (!mysqli_query($conn, $bill_item_sql)) {
                die("Error adding item to bill: " . mysqli_error($conn));
            }

            // Update the stock for the item
            $update_stock_sql = "UPDATE items SET stock = stock - $current_quantity WHERE id = $current_item_id";
            if (!mysqli_query($conn, $update_stock_sql)) {
                 die("Error updating stock: " . mysqli_error($conn));
            }
        }

        // 4. Now that we have the total, calculate taxes and the grand total
        $cgst_amount = ($total_sub_amount * $cgst_rate) / 100;
        $sgst_amount = ($total_sub_amount * $sgst_rate) / 100;
        $grand_total = $total_sub_amount + $cgst_amount + $sgst_amount;

        // 5. Update the main bill record with the final calculated totals
        $update_bill_totals_sql = "UPDATE bills SET sub_total = $total_sub_amount, cgst_amount = $cgst_amount, sgst_amount = $sgst_amount, grand_total = $grand_total WHERE id = $bill_id";

        if (mysqli_query($conn, $update_bill_totals_sql)) {
            // Success! Show a message.
            echo "<div class='alert alert-success'>Bill created successfully! Bill Number: $bill_id</div>";
        } else {
            die("Error updating bill totals: " . mysqli_error($conn));
        }

    } else {
        die("Error creating bill: " . mysqli_error($conn));
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="bills.php">Bills</a></li>
        <li class="breadcrumb-item active" aria-current="page">Create New Bill</li>
    </ol>
</nav>

<h2>Create New Bill/Invoice</h2>
<hr>

<form action="bill_form.php" method="post" id="billForm">
    <!-- Bill Details Card -->
    <div class="card mb-3">
        <div class="card-header">Bill Details</div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="bill_date">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="bill_date" name="bill_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group col-md-8">
                    <label for="customer_search_display">Customer <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="customer_search_display" placeholder="Type to search customer..." autocomplete="off">
                    <input type="hidden" id="customer_id" name="customer_id" required>
                    <div id="customer_suggestions_list" class="list-group position-absolute" style="z-index: 1000; width: calc(100% - 30px); display: none;"></div>
                    <small id="selected_customer_text" class="form-text text-muted"></small>
                </div>
            </div>
             <div class="form-group">
                <label for="work_order_details">Work Order Details</label>
                <textarea class="form-control" id="work_order_details" name="work_order_details" rows="2"></textarea>
            </div>
        </div>
    </div>

    <!-- Items Card -->
    <div class="card mb-3">
        <div class="card-header">Items</div>
        <div class="card-body">
            <table class="table table-bordered" id="itemsTable">
                <thead>
                    <tr>
                        <th>Item <span class="text-danger">*</span></th>
                        <th>HSN/SAC</th>
                        <th>Price</th>
                        <th>Quantity <span class="text-danger">*</span></th>
                        <th>Unit</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="billItemsBody">
                    <!-- Item rows will be added here by JavaScript -->
                </tbody>
            </table>
            <button type="button" class="btn btn-primary" id="addItemRow"><i class="fas fa-plus"></i> Add Item</button>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="card">
        <div class="card-header">Summary</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>Sub Total:</th>
                                <td id="subTotalDisplay" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <th>CGST (9%):</th>
                                <td id="cgstDisplay" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <th>SGST (9%):</th>
                                <td id="sgstDisplay" class="text-right">0.00</td>
                            </tr>
                            <tr>
                                <th>Grand Total:</th>
                                <td id="grandTotalDisplay" class="font-weight-bold text-right">0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Create Bill</button>
        <a href="bills.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<!-- This is an HTML template. It's hidden and used as a blueprint to create new item rows with JavaScript. -->
<template id="itemRowTemplate">
    <tr>
        <td>
            <select class="form-control item-select" name="item_id[]" required>
                <option value="">Select Item</option>
                <?php mysqli_data_seek($items_result, 0); // Reset pointer to loop again ?>
                <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                    <option value="<?php echo $item['id']; ?>"
                            data-price="<?php echo $item['price']; ?>"
                            data-hsn="<?php echo htmlspecialchars($item['hsn_sac']); ?>"
                            data-unit="<?php echo htmlspecialchars($item['unit']); ?>"
                            data-stock="<?php echo $item['stock']; ?>">
                        <?php echo htmlspecialchars($item['item_name']) . " (Stock: ".$item['stock'].")"; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </td>
        <td><input type="text" class="form-control item-hsn" readonly></td>
        <td><input type="number" class="form-control item-price" readonly></td>
        <td><input type="number" class="form-control item-quantity" name="quantity[]" min="1" value="1" required></td>
        <td><input type="text" class="form-control item-unit" readonly></td>
        <td><input type="text" class="form-control item-amount" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm removeItemRow"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<script>
    // Pass the PHP customer data to JavaScript
    const allCustomers = <?php echo json_encode($all_customers_data_for_js); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const billForm = document.getElementById('billForm');
        const addItemButton = document.getElementById('addItemRow');
        const billItemsBody = document.getElementById('billItemsBody');
        const itemRowTemplate = document.getElementById('itemRowTemplate');
        
        // --- Customer Search Logic ---
        const customerSearchInput = document.getElementById('customer_search_display');
        const customerIdInput = document.getElementById('customer_id');
        const customerSuggestionsList = document.getElementById('customer_suggestions_list');
        const selectedCustomerText = document.getElementById('selected_customer_text');
        
        customerSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            customerIdInput.value = '';
            selectedCustomerText.textContent = '';
            customerSuggestionsList.innerHTML = '';

            if (searchTerm.length === 0) {
                customerSuggestionsList.style.display = 'none';
                return;
            }

            const filteredCustomers = allCustomers.filter(c => c.name.toLowerCase().includes(searchTerm));

            if (filteredCustomers.length > 0) {
                filteredCustomers.forEach(customer => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.classList.add('list-group-item', 'list-group-item-action');
                    item.textContent = customer.name;
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        customerSearchInput.value = customer.name;
                        customerIdInput.value = customer.id;
                        selectedCustomerText.textContent = `Selected: ${customer.name}`;
                        customerSuggestionsList.style.display = 'none';
                    });
                    customerSuggestionsList.appendChild(item);
                });
                customerSuggestionsList.style.display = 'block';
            } else {
                customerSuggestionsList.style.display = 'none';
            }
        });
         document.addEventListener('click', function(e) {
            if (!customerSearchInput.contains(e.target)) {
                customerSuggestionsList.style.display = 'none';
            }
        });


        // --- Item Row and Calculation Logic ---
        
        // This function adds a new, empty item row to the table
        function addBillItemRow() {
            // 1. Clone the content of the <template> tag
            const newRow = itemRowTemplate.content.cloneNode(true);
            // 2. Add event listeners to the new row's inputs and buttons
            bindRowEventListeners(newRow.querySelector('tr'));
            // 3. Append the new row to the table body
            billItemsBody.appendChild(newRow);
        }

        // This function is called for every new row to make its inputs work
        function bindRowEventListeners(row) {
            const itemSelect = row.querySelector('.item-select');
            const quantityInput = row.querySelector('.item-quantity');
            const removeButton = row.querySelector('.removeItemRow');

            // When an item is selected from the dropdown
            itemSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                row.querySelector('.item-hsn').value = selectedOption.dataset.hsn || '';
                row.querySelector('.item-price').value = selectedOption.dataset.price || '0.00';
                row.querySelector('.item-unit').value = selectedOption.dataset.unit || '';
                quantityInput.max = selectedOption.dataset.stock; // Set max quantity based on stock
                calculateRowAmount(row);
            });

            // When the quantity is changed
            quantityInput.addEventListener('input', function() {
                const stock = parseInt(itemSelect.options[itemSelect.selectedIndex].dataset.stock);
                if(parseInt(this.value) > stock) {
                    alert('Quantity cannot exceed available stock: ' + stock);
                    this.value = stock;
                }
                calculateRowAmount(row);
            });

            // When the remove button is clicked
            removeButton.addEventListener('click', function() {
                row.remove();
                calculateTotals();
            });
        }

        // Calculates the "Amount" for a single row (Price * Quantity)
        function calculateRowAmount(row) {
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const quantity = parseInt(row.querySelector('.item-quantity').value) || 0;
            const amountInput = row.querySelector('.item-amount');
            amountInput.value = (price * quantity).toFixed(2);
            calculateTotals();
        }

        // Calculates all totals for the summary section
        function calculateTotals() {
            let subTotal = 0;
            billItemsBody.querySelectorAll('.item-amount').forEach(input => {
                subTotal += parseFloat(input.value) || 0;
            });

            const cgst = (subTotal * 0.09);
            const sgst = (subTotal * 0.09);
            const grandTotal = subTotal + cgst + sgst;

            document.getElementById('subTotalDisplay').textContent = subTotal.toFixed(2);
            document.getElementById('cgstDisplay').textContent = cgst.toFixed(2);
            document.getElementById('sgstDisplay').textContent = sgst.toFixed(2);
            document.getElementById('grandTotalDisplay').textContent = grandTotal.toFixed(2);
        }
        
        // --- Initial Setup ---

        // Add the first item row automatically when the page loads
        addBillItemRow();

        // Listen for clicks on the "Add Item" button
        addItemButton.addEventListener('click', addBillItemRow);
        
        // Simple form validation before submitting
        billForm.addEventListener('submit', function(event) {
            if (!customerIdInput.value) {
                alert('Please select a customer.');
                event.preventDefault(); // Stop the form from submitting
            }
            if(billItemsBody.children.length === 0){
                alert('Please add at least one item.');
                event.preventDefault(); // Stop the form from submitting
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>