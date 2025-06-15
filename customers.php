<?php
include 'includes/header.php'; // Assumes $conn, redirect(), sanitize_output() are available

// Handle Delete (remains server-side)
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    // Check for associated bills
    $check_sql = "SELECT COUNT(*) as bill_count FROM bills WHERE customer_id = '$delete_id'";
    $check_result = mysqli_query($conn, $check_sql);
    $bill_count_row = mysqli_fetch_assoc($check_result);

    if ($bill_count_row['bill_count'] > 0) {
        $_SESSION['message'] = ['text' => 'Cannot delete customer. They have existing bills.', 'type' => 'danger'];
    } else {
        $sql = "DELETE FROM customers WHERE id = '$delete_id'";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = ['text' => 'Customer deleted successfully!', 'type' => 'success'];
        } else {
            $_SESSION['message'] = ['text' => 'Error deleting customer: ' . mysqli_error($conn), 'type' => 'danger'];
        }
    }

    // Redirect, preserving search and page context if available
    $redirect_params = [];
    if (isset($_GET['search_context']) && !empty($_GET['search_context'])) {
        $redirect_params['search'] = $_GET['search_context'];
    }
    if (isset($_GET['page_context']) && !empty($_GET['page_context'])) {
        $redirect_params['page'] = $_GET['page_context'];
    }
    $redirect_url = 'customers.php';
    if (!empty($redirect_params)) {
        $redirect_url .= '?' . http_build_query($redirect_params);
    }
    redirect($redirect_url);
}

// Fetch ALL customers data once
$all_customer_records_from_db = [];
$sql_all_customers = "SELECT * FROM customers ORDER BY name ASC";
$result_all_customers = mysqli_query($conn, $sql_all_customers);
if ($result_all_customers) {
    while ($row = mysqli_fetch_assoc($result_all_customers)) {
        $all_customer_records_from_db[] = $row;
    }
}

// Prepare data for JavaScript suggestions (id and name only)
$customers_for_js_suggestions = array_map(function($customer) {
    return ['id' => $customer['id'], 'name' => $customer['name']];
}, $all_customer_records_from_db);

// Determine customers to display based on search
$customers_to_render_in_table = [];
$search_term_display = ""; // For pre-filling the search box

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term_query = strtolower(trim($_GET['search']));
    $search_term_display = trim($_GET['search']); // Keep original case for display

    foreach ($all_customer_records_from_db as $customer) {
        if (strpos(strtolower($customer['name']), $search_term_query) !== false) {
            $customers_to_render_in_table[] = $customer;
        }
    }
} else {
    $customers_to_render_in_table = $all_customer_records_from_db; // No search, display all
}

// --- Pagination Logic ---
$items_per_page = 5;
$total_items = count($customers_to_render_in_table);
$total_pages = ($total_items > 0) ? ceil($total_items / $items_per_page) : 1;

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure current page is at least 1
$current_page = min($current_page, $total_pages); // Ensure current page doesn't exceed total pages

$offset = ($current_page - 1) * $items_per_page;
$paginated_customers = array_slice($customers_to_render_in_table, $offset, $items_per_page);
// --- End Pagination Logic ---

?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Customers</li>
    </ol>
</nav>

<h2>Customer List</h2>
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

<!-- Search Form -->
<form action="customers.php" method="get" class="mb-3" id="customerSearchForm">
    <div class="input-group position-relative"> <!-- position-relative for suggestion box -->
        <input type="text" name="search" id="customerListSearchInput" class="form-control" placeholder="Search by Customer Name..." value="<?php echo sanitize_output($search_term_display); ?>" autocomplete="off">
        <div class="input-group-append">
            <button class="btn btn-primary" type="submit" id="triggerSearchButton"><i class="fas fa-search"></i> Search</button>
            <a href="customers.php" class="btn btn-outline-secondary" title="Clear Search"><i class="fas fa-times"></i> Clear</a>
        </div>
        <!-- Suggestions Dropdown -->
        <div id="customer_suggestions_list_page" class="list-group position-absolute"
             style="z-index: 1000; top: 100%; left: 0; /* Width will be set by JS */ max-height: 200px; overflow-y: auto; display: none; border: 1px solid #ced4da; border-top: none;">
            <!-- Suggestions will be populated by JS -->
        </div>
    </div>
</form>
<!-- End Search Form -->

<div class="mb-3">
    <a href="add_customer.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Customer</a>
</div>

<table class="table table-striped table-bordered">
    <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Mobile</th>
            <th>Email</th>
            <th>GST No</th>
            <th>Address</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="customerTableBody">
        <?php
        if (count($customers_to_render_in_table) > 0) { // Check overall count before pagination
            if (count($paginated_customers) > 0) { // Check if current page has items
                foreach ($paginated_customers as $row) {
                    echo "<tr>";
                    echo "<td>" . sanitize_output($row['id']) . "</td>";
                    echo "<td>" . sanitize_output($row['name']) . "</td>";
                    echo "<td>" . sanitize_output($row['mobile_no']) . "</td>";
                    echo "<td>" . sanitize_output($row['email']) . "</td>";
                    echo "<td>" . sanitize_output($row['gst_no']) . "</td>";
                    echo "<td>" . sanitize_output(nl2br($row['address'])) . "</td>";

                    // Prepare base query parameters for action links context
                    $action_link_context_params = [];
                    if (!empty($search_term_display)) {
                        $action_link_context_params['search_context'] = $search_term_display;
                    }
                    if ($total_pages > 1) {
                        $action_link_context_params['page_context'] = $current_page;
                    }

                    // Delete Link
                    $delete_query_params = ['delete_id' => $row['id']] + $action_link_context_params;
                    $delete_link = "customers.php?" . http_build_query($delete_query_params);

                    // Edit Link (context params are for returning to customers.php from edit page)
                    $edit_query_params = ['edit_id' => $row['id']] + $action_link_context_params;
                    // --- MODIFICATION HERE ---
                    $edit_link = "editcustomer.php?" . http_build_query($edit_query_params);
                    // --- END MODIFICATION ---

                    echo "<td>
                            <a href='" . sanitize_output($edit_link) . "' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i> Edit</a>
                            <a href='" . sanitize_output($delete_link) . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this customer?\")'><i class='fas fa-trash'></i> Delete</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                 echo '<tr><td colspan="7" class="text-center">No customers on this page.</td></tr>';
            }
        } else {
            echo '<tr>';
            echo '  <td colspan="7" class="text-center">';
            if (!empty($search_term_display)) {
                echo 'No customers found matching your search "' . sanitize_output($search_term_display) . '".';
            } else {
                echo 'No customers found. <a href="add_customer.php">Add one?</a>';
            }
            echo '  </td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php
        $pagination_query_params = [];
        if (!empty($search_term_display)) {
            $pagination_query_params['search'] = $search_term_display;
        }

        // Previous Button
        if ($current_page > 1):
            $prev_page_link_params = ['page' => $current_page - 1] + $pagination_query_params;
        ?>
            <li class="page-item">
                <a class="page-link" href="customers.php?<?php echo http_build_query($prev_page_link_params); ?>">Previous</a>
            </li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">Previous</span></li>
        <?php endif; ?>

        <!-- Page Number Links -->
        <?php for ($i = 1; $i <= $total_pages; $i++):
            $page_link_params = ['page' => $i] + $pagination_query_params;
            if ($i == $current_page):
        ?>
            <li class="page-item active" aria-current="page">
                <span class="page-link"><?php echo $i; ?></span>
            </li>
        <?php else: ?>
            <li class="page-item">
                <a class="page-link" href="customers.php?<?php echo http_build_query($page_link_params); ?>"><?php echo $i; ?></a>
            </li>
        <?php endif; ?>
        <?php endfor; ?>

        <!-- Next Button -->
        <?php if ($current_page < $total_pages):
            $next_page_link_params = ['page' => $current_page + 1] + $pagination_query_params;
        ?>
            <li class="page-item">
                <a class="page-link" href="customers.php?<?php echo http_build_query($next_page_link_params); ?>">Next</a>
            </li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">Next</span></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
<!-- End Pagination Controls -->


<script>
    // Customer data passed from PHP to JavaScript for suggestions
    const allCustomersForSuggestions = <?php echo json_encode($customers_for_js_suggestions); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('customerListSearchInput');
        const suggestionsList = document.getElementById('customer_suggestions_list_page');
        const customerSearchForm = document.getElementById('customerSearchForm');

        if (searchInput && suggestionsList && customerSearchForm && allCustomersForSuggestions) {
            function updateSuggestionsListWidth() {
                if (searchInput.offsetWidth > 0) {
                    suggestionsList.style.width = searchInput.offsetWidth + 'px';
                }
            }
            updateSuggestionsListWidth();
            window.addEventListener('resize', updateSuggestionsListWidth);

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                suggestionsList.innerHTML = '';

                if (searchTerm.length === 0) {
                    suggestionsList.style.display = 'none';
                    return;
                }

                const filteredCustomers = allCustomersForSuggestions.filter(customer =>
                    customer.name.toLowerCase().includes(searchTerm)
                );

                if (filteredCustomers.length > 0) {
                    filteredCustomers.slice(0, 10).forEach(customer => {
                        const suggestionItem = document.createElement('a');
                        suggestionItem.href = '#';
                        suggestionItem.classList.add('list-group-item', 'list-group-item-action');
                        suggestionItem.textContent = customer.name;
                        suggestionItem.dataset.name = customer.name;

                        suggestionItem.addEventListener('click', function(e) {
                            e.preventDefault();
                            searchInput.value = this.dataset.name;
                            suggestionsList.innerHTML = '';
                            suggestionsList.style.display = 'none';
                            customerSearchForm.submit();
                        });
                        suggestionsList.appendChild(suggestionItem);
                    });
                    suggestionsList.style.display = 'block';
                } else {
                    const noResultItem = document.createElement('div');
                    noResultItem.classList.add('list-group-item', 'disabled');
                    noResultItem.textContent = 'No customers found';
                    suggestionsList.appendChild(noResultItem);
                    suggestionsList.style.display = 'block';
                }
            });

            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !suggestionsList.contains(event.target)) {
                    suggestionsList.style.display = 'none';
                }
            });

            searchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    if (suggestionsList.style.display === 'block' &&
                        suggestionsList.firstChild &&
                        suggestionsList.firstChild.tagName === 'A') {
                        event.preventDefault();
                        suggestionsList.firstChild.click();
                    } else {
                        suggestionsList.style.display = 'none';
                    }
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
