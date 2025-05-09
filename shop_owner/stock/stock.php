<?php
session_start();
if (!isset($_SESSION['email'])) {
    echo "User is not logged in.";
    exit;
}

require('../navbar/nav.php');
include_once('../../connection.php');

$email = $_SESSION['email']; // Get logged-in user's email

// Handle date filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch batch data for the logged-in user
$batch_data = [];
$query = "
    SELECT b.*, c.category_name 
    FROM batch b 
    LEFT JOIN category c ON b.cat_id = c.id 
    WHERE b.email = ?";
if ($start_date && $end_date) {
    $query .= " AND b.date BETWEEN ? AND ?";
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    exit;
}

if ($start_date && $end_date) {
    $stmt->bind_param("sss", $email, $start_date, $end_date);
} else {
    $stmt->bind_param("s", $email);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $batch_data[] = $row;
}
$stmt->close();

$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stock</title>
    <link rel="stylesheet" href="../navbar/style.css">
    <link rel="stylesheet" href="../stock/style.css">
</head>

<body>
    <div class="main_container">
        <?php if ($message == 'insert'): ?>
            <div class="alert alert-success" id="success-alert">The Batch was added successfully.</div>
        <?php elseif ($message == 'delete'): ?>
            <div class="alert alert-danger" id="success-alert">The Batch was deleted successfully.</div>
        <?php elseif ($message == 'edit'): ?>
            <div class="alert alert-success" id="success-alert">The Batch was updated successfully.</div>
        <?php elseif ($message == 'error'): ?>
            <div class="alert alert-danger" id="success-alert">Batch number is already exist</div>
        <?php endif; ?>

        <div class="title">
            <h1>Manage Stock</h1>
            <br><br>
        </div>

        <!-- Add Batch Form -->
        <form action="add_batch.php" method="POST">
            <div class="form-container">
                <div class="form-row">
                    <div class="form-group">
                        <label for="suplier_name">Supplier Name:</label>
                        <input type="text" id="suplier_name" name="suplier_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="batch_num">Batch Number:</label>
                        <input type="text" id="batch_num" name="batch_num" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="prod_id">Product Name</label>
                        <input type="text" id="prod_id" name="prod_id" required>
                    </div>
                </div>
                <div class="form-row">
    <div class="form-group">
        <label for="cat_id">Category:</label>
        <select id="cat_id" name="cat_id" required>
            <option value="">Select Category</option>
            <?php
            $categories = $conn->query("SELECT id, category_name FROM category");
            while ($category = $categories->fetch_assoc()) {
                echo "<option value='{$category['id']}'>" . htmlspecialchars($category['category_name']) . "</option>";
            }
            ?>
        </select>
    </div>
</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="purchase_price">Purchase Price:</label>
                        <input type="text" id="purchase_price" name="purchase_price" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="avail_qty">Available Quantity:</label>
                        <input type="number" id="avail_qty" name="avail_qty" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>
            </div>
            <br>
            <button type="submit" name="action" value="insert" class="batch view-link">Add Batch</button>
        </form>

        <form method="GET" action="">
    <div class="form-container">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="form-row buttons-group">
            <button id="filter-button" type="submit" class="batch">Filter</button>
            <a id="clear-button" href="stock.php" class="batch">Clear</a>
        </div>
        </div>
    </div>
</form>


        <h2>Batch List</h2>
        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Batch Number</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Purchase Price</th>
                        <th>Available Quantity</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="batch-tbody">
                    <?php if (count($batch_data) > 0): ?>
                        <?php foreach ($batch_data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['suplier_name']) ?></td>
                                <td><?= htmlspecialchars($row['batch_num']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['category_name'] ) ?></td>
                                <td>Rs.<?= htmlspecialchars($row['purchase_price']) ?></td>
                                <td><?= htmlspecialchars($row['avail_qty']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td>
                                    <button class="manage-button view-link"
                                        data-suplier_id="<?= htmlspecialchars($row['suplier_id']) ?>"
                                        data-suplier_name="<?= htmlspecialchars($row['suplier_name']) ?>"
                                        data-batch_num="<?= htmlspecialchars($row['batch_num']) ?>"
                                        data-prod_id="<?= htmlspecialchars($row['product_name']) ?>"
                                        data-cat_id="<?= htmlspecialchars($row['cat_id']) ?>"
                                        data-purchase_price="<?= htmlspecialchars($row['purchase_price']) ?>"
                                        data-avail_qty="<?= htmlspecialchars($row['avail_qty']) ?>"
                                        data-date="<?= htmlspecialchars($row['date']) ?>">
                                        
                                        Manage
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No batches found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Manage Batch Modal -->
        <div id="manageBatchModal" class="modal">
            <div class="modal-content">
                <span id="closeManageBatchModal" class="close">&times;</span>
                <h2>Manage Batch</h2>
                <form id="manageBatchForm" action="manage_batch.php" method="POST">
                    <input type="hidden" id="manage_suplier_id" name="suplier_id">
                    <div class="form-group">
                        <label for="manage_suplier_name">Supplier Name:</label>
                        <input type="text" id="manage_suplier_name" name="suplier_name" required>
                    </div>
                    <div class="form-group">
                        <label for="manage_batch_num">Batch Number:</label>
                        <input type="text" id="manage_batch_num" name="batch_num" required>
                    </div>
                    <div class="form-group">
                        <label for="manage_prod_id">Product Name:</label>
                        <input type="text" id="manage_prod_id" name="prod_id" required>
                    </div>
                    <div class="form-group">
    <label for="manage_cat_id">Category:</label>
    <select id="manage_cat_id" name="cat_id" required>
        <option value="">Select Category</option>
        <?php
        $categories = $conn->query("SELECT id, category_name FROM category");
        while ($category = $categories->fetch_assoc()) {
            echo "<option value='{$category['id']}'>" . htmlspecialchars($category['category_name']) . "</option>";
        }
        ?>
    </select>
</div>

                    <div class="form-group">
                        <label for="manage_purchase_price">Purchase Price:</label>
                        <input type="text" id="manage_purchase_price" name="purchase_price" required>
                    </div>
                    <div class="form-group">
                        <label for="manage_avail_qty">Available Quantity:</label>
                        <input type="number" id="manage_avail_qty" name="avail_qty" required>
                    </div>
                    <div class="form-group">
                        <label for="manage_date">Date:</label>
                        <input type="date" id="manage_date" name="date" required>
                    </div>
                    <br>
                    <button type="submit" name="action" value="edit" class="batch view-link">Edit</button>
                    <button type="submit" name="action" value="delete" class="batch delete-link">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        var manageBatchModal = document.getElementById("manageBatchModal");
        var closeManageBatchModal = document.getElementById("closeManageBatchModal");

        // Close modal when close button is clicked
        closeManageBatchModal.onclick = function() {
            manageBatchModal.style.display = "none";
        }

        // Close modal when user clicks outside of it
        window.onclick = function(event) {
            if (event.target == manageBatchModal) {
                manageBatchModal.style.display = "none";
            }
        }

        // Manage Batch functionality
        document.querySelectorAll('.manage-button').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('manage_suplier_id').value = this.dataset.suplier_id;
                document.getElementById('manage_suplier_name').value = this.dataset.suplier_name;
                document.getElementById('manage_batch_num').value = this.dataset.batch_num;
                document.getElementById('manage_prod_id').value = this.dataset.prod_id;
                document.getElementById('manage_purchase_price').value = this.dataset.purchase_price;
                document.getElementById('manage_avail_qty').value = this.dataset.avail_qty;
                document.getElementById('manage_date').value = this.dataset.date;
                document.getElementById('manage_cat_id').value = this.dataset.cat_id;

                manageBatchModal.style.display = "block";
            });
        });
    </script>


</body>

<script>
    setTimeout(function() {
        var alert = document.getElementById('success-alert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 10000); // 10 seconds
</script>
</html>
