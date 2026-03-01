<?php
session_start();

$username_db = 'z2047119';
$password_db = '2003Dec17';


// Local SQLite Connection 
try {
    // Points to database.db in the exact same folder as this PHP file
    $dsn = "sqlite:" . __DIR__ . "/database.db";
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection to database failed: " . $e->getMessage());
}

//Function checks for employee log in and seperates client side view and employee view
//Some of the log in logic was aided through stack overflow
function check_employee_auth($required_role = null) {
    // Check if logged in at all
    if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
        header("Location: ?page=employee_login&error=Not logged in"); //send user back to log in
        exit;
    }

    // After checking if user is logged in, the code then checks the role of the employee
    if ($required_role !== null) {
        $user_role = $_SESSION['employee_role'];

        //If user role, then owner has access to all permissions
        if ($user_role === 'owner') {
            return true; //
        }

        //If user role is employee
        if ($user_role !== $required_role) {
            // DENIED, log in does not work no clearance
            header("Location: ?page=employee_dashboard&error=Access Denied: Required role '" . htmlspecialchars($required_role) . "'");
            exit;
        }
    }

    //If a page doesnt require a specific clearence, then lets user through
    return true;
}


// Sets current page and defaults to home page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Helps process changes to the website as the website is updated
// Processing, log ins, inventory updates etc.
// DO NOT REMOVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    // This section will be the maincode
    // Program to be done in one file for ease of sharing
    // Switch logic references prior assignment
    switch ($action) {
        case 'process_employee_login':
// SQLite workaround: Hash the password in PHP before querying
            $hashed_password = hash('sha256', $login_password);
            $stmt = $pdo->prepare("SELECT id, username, role FROM employees WHERE username = ? AND password = ?");
            $stmt->execute([$login_username, $hashed_password]);
            //Fool proofing if nothing is entered
            if (empty($login_username) || empty($login_password)) {
                header("Location: ?page=employee_login&error=Username and password required");
                exit;
            }

            //Sets up a database query to find username and password in the database
            //Uses SHA2 for addeed security
            $stmt = $pdo->prepare("SELECT id, username, role FROM employees WHERE username = ? AND password = SHA2(?, 256)");
            $stmt->execute([$login_username, $login_password]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                // LOGIN SUCCESFUL
                $_SESSION['employee_logged_in'] = true;
                $_SESSION['employee_id'] = $employee['id'];
                $_SESSION['employee_username'] = $employee['username'];
                $_SESSION['employee_role'] = $employee['role'];
                // Redirect to employee dashboard
                header("Location: ?page=employee_dashboard");
                exit;
            } else {
                // Login failed
                header("Location: ?page=employee_login&error=Invalid credentials");
                exit;
            }
            break; // TEST

        case 'update_order_status':
            check_employee_auth('employee'); // ONLY EMPLOYEE AND OWNER CAN ACCESS. Reminder that owner IS employee
            $order_id_to_update = $_POST['order_id'] ?? null; //obtains order id
            $new_status = $_POST['new_status'] ?? null;

            if ($order_id_to_update && in_array($new_status, ['Pending', 'Shipped', 'Delivered'])) { //checks if status is matching
                try {
                    $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $update_stmt->execute([$new_status, $order_id_to_update]); //Actually changes status
                    // SUCESSS
                    header("Location: ?page=employee_orders&success=Order " . htmlspecialchars($order_id_to_update) . " status updated");
                    exit;
                } catch (PDOException $e) {
                    // Error handling
                    header("Location: ?page=employee_orders&error=Database error updating status");
                    exit;
                }
            } else {
                // FOOL PROOFING
                header("Location: ?page=employee_orders&error=Invalid data for status update");
                exit;
            }
            break; // TEST

        case 'update_inventory':
            //This code takes references from prior assignments
            check_employee_auth('owner'); // Only Owner can update inventory
            $product_id_to_update = $_POST['product_id'] ?? null; //Gets product id
            $new_quantity = $_POST['quantity'] ?? null;
            // Foolproofing, make sure it is a non negative value
            if ($product_id_to_update && is_numeric($new_quantity) && (int)$new_quantity >= 0) {
                try {
                    // Follows similar logic to update orders
                    $update_inv_stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ?");

                    $update_inv_stmt->execute([(int)$new_quantity, $product_id_to_update]);
                    //Checks to see if rows changed TEST
                    if ($update_inv_stmt->rowCount() > 0) {
                        header("Location: ?page=view_inventory&success=Inventory quantity set for product " . htmlspecialchars($product_id_to_update) . " (across all suppliers)");
                    } else {
                    // Rest of this case tries to identify what the error could be
                        //USE TO CHECK WHAT IS WRONG WITH CODE
                        $check_prod = $pdo->prepare("SELECT 1 FROM products WHERE id = ?");
                        $check_prod->execute([$product_id_to_update]);
                        if ($check_prod->fetch()) {
                            header("Location: ?page=view_inventory&error=Could not update inventory, product ID might not be in inventory, or quantity unchanged.");
                        } else {
                            header("Location: ?page=view_inventory&error=Could not update inventory, product ID does not exist.");
                        }
                    }
                    exit;

                } catch (PDOException $e) {
                    header("Location: ?page=view_inventory&error=Database error updating inventory: " . $e->getMessage());
                    exit;
                }
            } else {
                header("Location: ?page=view_inventory&error=Invalid data for inventory update");
                exit;
            }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YE OLD STORE</title>
    <style>
        /* ALL CSS REFERENCED FROM W3 SCHOOLS */
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 15px;
            background-color: #1c1c1c;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        h1, h2 {
            color: #ff9800;
            border-bottom: 1px solid #ff9800;
            padding-bottom: 5px;
        }
        a {
            color: #b3e5fc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
            color: #ffb74d;
        }
        nav {
            background-color: #263238;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        nav a {
            margin-right: 15px;
            font-weight: bold;
        }
        nav span { /* Style for logged in user info */
            margin-right: 15px;
            color: #ffffff;
            background-color: #ff9800;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        nav a.logout-link { /* Specific style for logout */
            color: #ff6b6b; /* Reddish color for logout */
        }
        nav a.logout-link:hover {
            color: #ff8e8e;
        }

        ul { list-style-type: none; padding: 0; }
        li { margin-bottom: 8px; background-color: #2a2a2a; padding: 5px; border-radius: 3px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #444; /* Darker border */
            padding: 10px;
            text-align: left;
            vertical-align: middle; /* Align content vertically */
        }
        th {
            background-color: #263238;
            color: #ff9800;
        }
        td {
            background-color: #1e1e1e;
        }
        tr:nth-child(even) td { /* Zebra striping for readability */
            background-color: #232323;
        }
        /* Style for forms inside table cells */
        td form {
            margin:0 !important;
            padding:0 !important;
            border:none !important;
            background:none !important;
            display: flex; /* Align items in a row */
            align-items: center; /* Center items vertically */
            gap: 5px; /* Add space between select and button */
        }
        td form select, td form input[type="number"] {
            width: auto; /* Allow select/number input to size naturally */
            flex-grow: 1; /* Allow select/number to take available space */
            margin-top: 0; /* Remove default margin */
        }
        td form input[type="submit"] {
            margin-top: 0; /* Remove default margin */
            padding: 8px 12px; /* Slightly smaller button */
            flex-shrink: 0; /* Prevent button from shrinking */
        }


        form:not(td form) { /* Apply general form styles only if not inside a TD */
            background-color: #1e1e1e;
            padding: 20px;
            border: 1px solid #ff9800;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #e0e0e0;
        }
        label {
            display: block;
            margin-bottom: 12px; /* Increased spacing */
            font-weight: bold;
            color: #b3e5fc;
        }
        input[type="text"],
        input[type="number"]:not(td form input[type="number"]), /* Exclude number inputs in table forms */
        select:not(td form select), /* Exclude selects in table forms */
        input[type="password"] {
            background-color: #263238;
            color: #e0e0e0;
            border: 1px solid #ff9800;
            padding: 8px; /* Increased padding */
            width: calc(100% - 18px); /* Adjust width accounting for padding and border */
            margin-top: 5px;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding and border in element's total width */
        }
        input[type="submit"]:not(td form input[type="submit"]), /* Exclude submit buttons in table forms */
        button {
            background-color: #ff9800;
            color: #121212;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            transition: background-color 0.2s ease;
        }
        input[type="submit"]:not(td form input[type="submit"]):hover, /* Exclude hover effect for table forms */
        button:hover {
            background-color: #ffa726;
        }
        hr {
            border: none;
            height: 1px;
            background-color: #444;
            margin: 20px 0;
        }
        .error-message {
            color: #ff6b6b; /* Red for errors */
            background-color: #4d2a2a;
            padding: 10px;
            border: 1px solid #ff6b6b;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success-message {
            color: #6bff6b; /* Green for success */
            background-color: #2a4d2a;
            padding: 10px;
            border: 1px solid #6bff6b;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <!--HTML Format referenced from prior assignments-->
    <h1>YE OLD STORE</h1>
    <nav>
        <a href="?page=home">Home</a>
        <a href="?page=show_products">View Products</a>
        <a href="?page=view_cart">View Cart</a>
        <a href="?page=checkout">Checkout</a>
        <a href="?page=order_history">Order History</a>
        <a href="?page=order_tracking">Track Order</a>

        <?php // Check employee log in again in order to check unaccesible pages. This method was favored over using JS
        if (isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true) :
            // Links for Owner OR Employee
            if ($_SESSION['employee_role'] === 'owner' || $_SESSION['employee_role'] === 'employee') {
                echo '<a href="?page=employee_orders">View Orders</a>';
            }

            // Links for Owner Only
            if ($_SESSION['employee_role'] === 'owner') {
                echo '<a href="?page=view_inventory">View Inventory</a>';

            }

            // Display logged-in user info and Logout link. HAVE TO CLICK TWICE TO LOG OUT, TRY FIXING
            echo '<span>👤 ' . htmlspecialchars($_SESSION['employee_username']) . ' (' . htmlspecialchars($_SESSION['employee_role']) . ')</span>';
            echo '<a href="?page=logout" class="logout-link">Logout</a>';

        else : // Show Login link if not logged in
            echo '<a href="?page=employee_login">Employee Login</a>';
        endif;
        ?>
    </nav>
    <hr>
    <div>
        <?php
        // Error handling
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        if (isset($_GET['success'])) {
            echo '<p class="success-message">' . htmlspecialchars($_GET['success']) . '</p>';
        }

        // Switch case continued, LARGELY FOR FORMATTING.
        switch ($page) {
            case 'home':
                echo "<h2>Welcome to the Store</h2><p>Explore our magical wares!</p>";
                break;

            case 'employee_login':
                // Error handling for already logged in users
                if (isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true) {
                    echo "<p>You are already logged in as " . htmlspecialchars($_SESSION['employee_username']) . ". <a href='?page=employee_dashboard'>Go to Dashboard</a></p>";
                    break;
                }

                echo "<h2>Employee Login</h2>";
                echo "<form method='post' action='?page=employee_login'>"; // Post back to the same page to trigger the POST handling logic above
                echo "<input type='hidden' name='action' value='process_employee_login'>"; // Hidden field for action
                echo "<label>Username: <input type='text' name='username' required></label>";
                echo "<label>Password: <input type='password' name='password' required></label>";
                echo "<input type='submit' value='Login'>";
                echo "<p>OWNER USERNAME/PASSWORD: guildmaster/dragonheart</p>";
                echo "<p>EMPLOYEE USERNAME/PASSWORD: thorn/shadowblade</p>";
                echo "</form>";
                break;

            case 'employee_dashboard':
                check_employee_auth(); // Enforces log in
                echo "<h2>Employee Dashboard</h2>";
                echo "<p>Welcome, " . htmlspecialchars($_SESSION['employee_username']) . "!</p>";
                echo "<p>Select an option from the navigation bar.</p>";
                echo "<p>To log out, press log out twice.</p>";
                break;

            // Update orders
            case 'employee_orders':

                check_employee_auth('employee'); // Employee or Owner access
                echo "<h2>Manage Customer Orders</h2>";

                // Fetch all orders
                $order_stmt = $pdo->query("SELECT o.id, o.customer_name, o.status, o.created_at, o.shipping_address, o.billing_info, o.total_amount, GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR '<br>') AS items
                                           FROM orders o
                                           LEFT JOIN order_items oi ON o.id = oi.order_id
                                           LEFT JOIN products p ON oi.product_id = p.id
                                           GROUP BY o.id
                                           ORDER BY o.id DESC");
                $all_orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($all_orders) {
                    echo "<table border='1'>";
                    echo "<tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Status</th><th>Shipping</th><th>Billing</th><th>Total</th><th>Items</th><th>Update Status</th></tr>";
                    foreach ($all_orders as $ord) {
                        //Following code is formatting obtained from w3 schools.
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($ord['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($ord['customer_name'] ?? 'N/A') . "</td>";
                        // Format date
                        try { $date = new DateTime($ord['created_at']); echo "<td>" . $date->format('Y-m-d H:i') . "</td>"; } catch (Exception $e) { echo "<td>Invalid Date</td>"; }
                        echo "<td>" . htmlspecialchars($ord['status']) . "</td>";
                        echo "<td>" . htmlspecialchars($ord['shipping_address']) . "</td>";
                        echo "<td>" . htmlspecialchars($ord['billing_info']) . "</td>";
                        echo "<td>$" . htmlspecialchars(number_format((float)($ord['total_amount'] ?? 0), 2)) . "</td>";
                        echo "<td>" . ($ord['items'] ?? 'No items') . "</td>"; // Display concatenated items

                        // Status Update Form
                        echo "<td>";
                        // Form posts back to the same page (?page=employee_orders)
                        // The POST handling block at the top catches the 'update_order_status' action
                        echo "<form method='post' action='?page=employee_orders'>";
                        echo "<input type='hidden' name='action' value='update_order_status'>";
                        echo "<input type='hidden' name='order_id' value='" . htmlspecialchars($ord['id']) . "'>";
                        echo "<select name='new_status'>";
                        foreach (['Pending', 'Shipped', 'Delivered'] as $status_option) {
                            $selected = ($ord['status'] === $status_option) ? 'selected' : '';
                            echo "<option value='" . $status_option . "' " . $selected . ">" . $status_option . "</option>";
                        }
                        echo "</select>";
                        echo "<input type='submit' value='Update'>";
                        echo "</form>";
                        echo "</td>";

                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No orders found.</p>";
                }
                break;

            // --- View/Update Inventory (Owner Only) ---
            case 'view_inventory':
                check_employee_auth('owner'); // Owner access only
                echo "<h2>Manage Store Inventory</h2>";

                // Sets up table view
                $inv_stmt = $pdo->query("SELECT inv.supplier_id, s.name as supplier_name, inv.product_id, p.name as product_name, p.price, inv.quantity
                                         FROM inventory inv
                                         JOIN products p ON inv.product_id = p.id
                                         JOIN suppliers s ON inv.supplier_id = s.id
                                         ORDER BY p.name ASC, s.name ASC");

                $inventory_items = $inv_stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($inventory_items) {
                    echo "<table border='1'>";
                    // Added Supplier column
                    echo "<tr><th>Product ID</th><th>Product Name</th><th>Supplier</th><th>Price</th><th>Current Quantity</th><th>Update Quantity</th></tr>";
                    foreach ($inventory_items as $item) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['product_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['supplier_name']) . "</td>"; // Show supplier
                        echo "<td>$" . htmlspecialchars(number_format((float)($item['price'] ?? 0), 2)) . "</td>";
                        echo "<td>" . htmlspecialchars($item['quantity'] ?? 0) . "</td>"; // Display current quantity

                        // Inventory Update Form - Now needs supplier_id as well
                        echo "<td>";
                        echo "<form method='post' action='?page=view_inventory'>"; // Post back to self
                        echo "<input type='hidden' name='action' value='update_inventory'>";
                        echo "<input type='hidden' name='product_id' value='" . htmlspecialchars($item['product_id']) . "'>";
                        // *** Need to include supplier_id for the update logic to target the correct row ***
                        echo "<input type='hidden' name='supplier_id' value='" . htmlspecialchars($item['supplier_id']) . "'>";
                        echo "<input type='number' name='quantity' value='" . htmlspecialchars($item['quantity'] ?? 0) . "' min='0' required style='width: 80px;'>"; // Small input for quantity
                        echo "<input type='submit' value='Set'>";
                        echo "</form>";
                        echo "</td>";

                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No inventory records found.</p>";
                }
                break;


            // LOGOUT
            case 'logout':
                // CLEAR SESSION VARIABLES
                $_SESSION = array();

                // Destroy the session cookies, idea was provided to us
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }

                // Destroy the session
                session_destroy();

                // Redirect to home page after logout
                header("Location: ?page=home&success=Logged out successfully");
                exit;
                break; // TEST

            // CLIENT SIDE FORMAT
            case 'show_products':
                // Added Price to the product listing
                $products = $pdo->query("SELECT * FROM products ORDER BY name ASC");
                echo "<h2>Our Magical Items</h2>";
                if ($products->rowCount() > 0) {
                    echo "<table border='1'><tr><th>Name</th><th>Color</th><th>Weight</th><th>Price</th><th>Details</th></tr>";
                    foreach ($products as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['color'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['weight'] ?? 'N/A') . " kg</td>";
                        echo "<td>$" . htmlspecialchars(number_format((float)($row['price'] ?? 0), 2)) . "</td>"; // Display price
                        echo "<td><a href='?page=product_details&id=" . htmlspecialchars($row['id']) . "'>View Details</a></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No products currently available.</p>";
                }
                break;

            case 'product_details':
                //Code here gets product ID, queries it, and displays the product details
                $product_id = $_GET['id'] ?? null;
                if (!$product_id) { echo "<p>Product ID missing.</p>"; break; }

                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    echo "<h2>" . htmlspecialchars($product['name']) . "</h2>";
                    echo "<p><strong>Color:</strong> " . htmlspecialchars($product['color'] ?? 'N/A') . "</p>";
                    echo "<p><strong>Weight:</strong> " . htmlspecialchars($product['weight'] ?? 'N/A') . " kg</p>";
                    echo "<p><strong>Price:</strong> $" . htmlspecialchars(number_format((float)($product['price'] ?? 0), 2)) . "</p>";

                    // ADD TO CART
                    echo "<form method='post' action='?page=add_to_cart'>";
                    echo "<label>Quantity: <input type='number' name='quantity' value='1' min='1' required style='width: 60px;'></label>";
                    echo "<input type='hidden' name='product_id' value='" . htmlspecialchars($product['id']) . "'>";
                    echo "<input type='submit' value='Add to Cart'>";
                    echo "</form>";
                } else {
                    echo "<p>Product not found.</p>";
                }
                break;

            case 'add_to_cart': //This add to cart is not entirely format. Code was moved here for simplicity
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $product_id = $_POST['product_id'] ?? null;
                    $quantity = $_POST['quantity'] ?? null;

                    // Checks input
                    if ($product_id && is_numeric($quantity) && $quantity > 0) {
                        $quantity = (int)$quantity; // Ensure integer

                        // Creates a cart if its not already created(has no items)
                        if (!isset($_SESSION['cart'])) {
                            $_SESSION['cart'] = [];
                        }
                        // Adds to cart
                        if (isset($_SESSION['cart'][$product_id])) {
                            $_SESSION['cart'][$product_id] += $quantity;
                        } else {
                            $_SESSION['cart'][$product_id] = $quantity;
                        }
                        header("Location: ?page=view_cart&success=Item added");
                        exit;
                    } else {
                        header("Location: ?page=show_products&error=Invalid item data"); // Error handling
                        exit;
                    }
                } else {
                    header("Location: ?page=show_products");
                    exit;
                }
                break; // TEST

            case 'view_cart':
                echo "<h2>Your Shopping Cart</h2>";
                if (empty($_SESSION['cart'])) {
                    echo "<p>Your cart is empty. <a href='?page=show_products'>Browse products</a>.</p>"; //quality of life
                } else {
                    echo "<table border='1'><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total Price</th><th>Remove</th></tr>";
                    $total_order_price = 0;
                    $cart_product_ids = array_keys($_SESSION['cart']);

                    if (!empty($cart_product_ids)) { //check if cart is empty
                        //Build query that creates the correct amount of placeholders
                        $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));
                        // Fetch id name and price
                        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)"); //gets infro
                        $stmt->execute($cart_product_ids); //exectues query THROUGH ID
                        // Fetch into an array keyed by product ID for easy lookup
                        $products_details = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE); //USE FIRST COLUMN AS KEY
                        foreach ($_SESSION['cart'] as $product_id => $quantity) {
                            if (isset($products_details[$product_id])) {
                                $product = $products_details[$product_id];
                                $unit_price = (float)($product['price'] ?? 0); // Get price from DATABASE
                                $total_item_price = $quantity * $unit_price;
                                $total_order_price += $total_item_price;
                                // Rest of the code is formatting except for the else stmt
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($quantity) . "</td>";
                                echo "<td>$" . htmlspecialchars(number_format($unit_price, 2)) . "</td>";
                                echo "<td>$" . htmlspecialchars(number_format($total_item_price, 2)) . "</td>";
                                echo "<td><a href='?page=remove_from_cart&id=" . htmlspecialchars($product_id) . "' onclick='return confirm(\"Remove this item?\");'>Remove</a></td>";
                                echo "</tr>";
                            } else {
                                // Remove item if it runs out.
                                unset($_SESSION['cart'][$product_id]);
                                echo "<tr><td colspan='5'><em>An item was removed as it's no longer available.</em></td></tr>";
                            }
                        }
                    }

                    echo "<tr><td colspan='3' style='text-align:right; font-weight:bold;'>Order Total:</td><td style='font-weight:bold;'>$ " . htmlspecialchars(number_format($total_order_price, 2)) . "</td><td></td></tr>"; // Display order total
                    echo "</table><p><a href='?page=checkout'>Proceed to Checkout</a></p>";
                }
                break;

            case 'remove_from_cart': //simple remove from cart
                $product_id_to_remove = $_GET['id'] ?? null;
                if ($product_id_to_remove && isset($_SESSION['cart'][$product_id_to_remove])) {
                    unset($_SESSION['cart'][$product_id_to_remove]);
                }
                header("Location: ?page=view_cart&success=Item removed"); // Redirect back to cart page
                exit;
                break; // TEST

            case 'checkout':
                if (empty($_SESSION['cart'])) {
                    echo "<p>Your cart is empty. Cannot checkout. <a href='?page=show_products'>Browse products</a>.</p>";
                    break; // Stop here if cart is empty
                }

                // Display checkout form
                echo "<h2>Checkout</h2>";
                echo "<form method='post' action='?page=process_order'>"; // Action points to process_order
                echo "<label>Customer Name: <input type='text' name='customer_name' required></label>"; // Added customer name
                echo "<label>Shipping Address: <input type='text' name='shipping_address' required></label>";
                echo "<label>Billing Information (Card ending in 1234): <input type='text' name='billing_info' required></label>";
                echo "<input type='submit' value='Place Order'>";
                echo "</form>";
                break;

            case 'process_order':

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    header("Location: ?page=checkout");
                    exit;
                }

                // Return to cart if nothing is there
                if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                    header("Location: ?page=view_cart&error=Cart is empty");
                    exit;
                }
                //set customer information
                $cart = $_SESSION['cart'];
                $shipping_address = trim($_POST['shipping_address'] ?? '');
                $billing_info = trim($_POST['billing_info'] ?? '');
                $customer_name = trim($_POST['customer_name'] ?? 'Anonymous Adventurer'); // If name is empty go anonymous

                // Asks for all fields to be filled
                if (empty($shipping_address) || empty($billing_info) || empty($customer_name)) {
                    header("Location: ?page=checkout&error=Please fill in all required fields");
                    exit;
                }

                $order_total = 0;
                $order_items_details = []; // Store details for insertion

                // Calculate total amount AND validate products exist
                $cart_product_ids = array_keys($cart);
                if (!empty($cart_product_ids)) {
                    $placeholders = implode(',', array_fill(0, count($cart_product_ids), '?'));
                    $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
                    $stmt->execute($cart_product_ids);
                    $products_prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => price
                    //simple total calculation loop
                    foreach ($cart as $product_id => $quantity) {
                        if (isset($products_prices[$product_id])) {
                            $price = (float)$products_prices[$product_id];
                            $order_total += $price * $quantity;
                            $order_items_details[] = ['id' => $product_id, 'quantity' => $quantity];
                        } else {
                            // Product doesn't exist anymore
                            header("Location: ?page=view_cart&error=One or more items in your cart are no longer available.");
                            exit;
                        }
                    }
                } else {
                    // Should not happen if cart check passed, but good failsafe
                    header("Location: ?page=view_cart&error=Cart is empty");
                    exit;
                }

                // TRANSACTION
                try {
                    $pdo->beginTransaction();

                    // Insert into orders table
                    $stmt_order = $pdo->prepare("INSERT INTO orders (customer_name, status, shipping_address, billing_info, total_amount)
                                                VALUES (?, 'Pending', ?, ?, ?)");
                    $stmt_order->execute([$customer_name, $shipping_address, $billing_info, $order_total]);
                    $order_id = $pdo->lastInsertId(); // Get the ID of the order just created

                    // Insert into order_items table
                    $stmt_items = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
                    foreach ($order_items_details as $item) {
                        $stmt_items->execute([$order_id, $item['id'], $item['quantity']]);
                    }


                    $stmt_inv_update = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?");
                    foreach ($order_items_details as $item) {
                        // Find a supplier with enough stock for this product
                        $find_supplier_stmt = $pdo->prepare("SELECT supplier_id FROM inventory WHERE product_id = ? AND quantity >= ? LIMIT 1");
                        $find_supplier_stmt->execute([$item['id'], $item['quantity']]);
                        $supplier_to_decrement = $find_supplier_stmt->fetchColumn();

                        if ($supplier_to_decrement === false) {
                            // Not enough suppliers
                            $pdo->rollBack();
                            $prod_name_stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                            $prod_name_stmt->execute([$item['id']]);
                            $prod_name = $prod_name_stmt->fetchColumn();
                            header("Location: ?page=view_cart&error=Not enough stock for " . htmlspecialchars($prod_name) . ". Order cancelled.");
                            exit;
                        }

                        // DECREMENT STOCK
                        $decrement_stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE product_id = ? AND supplier_id = ?");
                        $decrement_stmt->execute([$item['quantity'], $item['id'], $supplier_to_decrement]);

                        // Double-check row count just in case of race conditions, though less likely with LIMIT 1 select
                        if ($decrement_stmt->rowCount() == 0) {
                            $pdo->rollBack();
                            header("Location: ?page=view_cart&error=Failed to update stock count during order processing. Order cancelled.");
                            exit;
                        }
                    }
                    
                    // Transaction commit
                    $pdo->commit();

                    // Clear the cart ONLY after successful order and commit
                    unset($_SESSION['cart']);

                    // Redirect to a success/thank you page or order tracking
                    header("Location: ?page=order_tracking&order_id=" . $order_id . "&success=Order placed successfully!");
                    exit;

                } catch (PDOException $e) {
                    // DATABASE ERROR HANDLING. Checkign if sql file properly interacts with main
                    $pdo->rollBack();
                    // Debugging
                    error_log("Order processing error: " . $e->getMessage()); // Override
                    header("Location: ?page=checkout&error=Failed to place order due to a database error.");
                    exit;
                }
                break; // TEST


            case 'order_tracking':
                echo "<h2>Order Tracking</h2>";
                $track_order_id = $_GET['order_id'] ?? null;

                if ($track_order_id) {
                    // Fetch order details
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
                    $stmt->execute([$track_order_id]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($order) {
                        echo "<p><strong>Order Number:</strong> " . htmlspecialchars($order['id']) . "</p>";
                        echo "<p><strong>Status:</strong> " . htmlspecialchars($order['status']) . "</p>";
                        echo "<p><strong>Date Placed:</strong> ";
                        try { $date = new DateTime($order['created_at']); echo htmlspecialchars($date->format('Y-m-d H:i:s')); } catch (Exception $e) { echo "Invalid Date"; }
                        echo "</p>";
                        echo "<p><strong>Shipping Address:</strong> " . htmlspecialchars($order['shipping_address']) . "</p>";
                        echo "<p><strong>Billing Info:</strong> " . htmlspecialchars($order['billing_info']) . "</p>";
                        echo "<p><strong>Total Amount:</strong> $" . htmlspecialchars(number_format((float)($order['total_amount'] ?? 0), 2)) . "</p>";

                        // Fetch and display items for this order
                        echo "<h3>Items Ordered:</h3>";
                        $stmt_items = $pdo->prepare("SELECT oi.quantity, p.name, p.price
                                                    FROM order_items oi
                                                    JOIN products p ON oi.product_id = p.id
                                                    WHERE oi.order_id = ?");
                        $stmt_items->execute([$track_order_id]);
                        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                        if ($items) {
                            echo "<table border='1'><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>";
                            foreach ($items as $item) {
                                $item_total = (float)($item['price'] ?? 0) * (int)$item['quantity'];
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                                echo "<td>$" . htmlspecialchars(number_format((float)($item['price'] ?? 0), 2)) . "</td>";
                                echo "<td>$" . htmlspecialchars(number_format($item_total, 2)) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p>No items found for this order.</p>";
                        }
                    } else {
                        echo "<p class='error-message'>Order with ID " . htmlspecialchars($track_order_id) . " not found.</p>";
                        // Show the tracking form again
                        echo "<p>Enter your order number to track your order.</p>";
                        echo "<form method='get' action=''>"; // Submit to current page
                        echo "<input type='hidden' name='page' value='order_tracking'>";
                        echo "<label>Order Number: <input type='text' name='order_id' required></label>";
                        echo "<input type='submit' value='Track Order'>";
                        echo "</form>";
                    }
                } else {
                    // Show the tracking form if no order_id is provided
                    echo "<p>Enter your order number to track your order.</p>";
                    echo "<form method='get' action=''>"; // Submit to current page
                    echo "<input type='hidden' name='page' value='order_tracking'>";
                    echo "<label>Order Number: <input type='text' name='order_id' required></label>";
                    echo "<input type='submit' value='Track Order'>";
                    echo "</form>";
                }
                break;

            case 'order_history':
                echo "<h2>Order History</h2>";

                // Check if user is an employee/owner (they can see all orders)
                $is_employee = isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true;

                if ($is_employee) {
                    // Employees/owners see ALL orders
                    $stmt = $pdo->prepare("SELECT o.id, o.customer_name, o.status, o.created_at, o.shipping_address, o.billing_info, o.total_amount
                              FROM orders o
                              ORDER BY o.id DESC");
                    $stmt->execute();
                } else {
                    // Customers must enter their name to see their orders
                    $customer_name = $_POST['customer_name'] ?? $_GET['customer_name'] ?? null;

                    if (!$customer_name) {
                        // Show a form to enter their name
                        echo "<p>Enter your name to view your order history:</p>";
                        echo "<form method='post' action='?page=order_history'>";
                        echo "<label>Your Name: <input type='text' name='customer_name' required></label>";
                        echo "<input type='submit' value='View Orders'>";
                        echo "</form>";
                        break; // Stop further execution
                    }

                    // Fetch orders only for this customer
                    $stmt = $pdo->prepare("SELECT o.id, o.customer_name, o.status, o.created_at, o.shipping_address, o.billing_info, o.total_amount
                              FROM orders o
                              WHERE o.customer_name = ?
                              ORDER BY o.id DESC");
                    $stmt->execute([$customer_name]);
                }

                $orders_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($orders_list) > 0) {
                    echo "<table border='1'>";
                    echo "<tr><th>Order ID</th><th>Date Placed</th><th>Status</th><th>Shipping Address</th><th>Billing Info</th><th>Total Amount</th><th>Items Ordered</th><th>Details</th></tr>";

                    foreach ($orders_list as $order_row) {
                        $current_order_id = $order_row['id'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($current_order_id) . "</td>";
                        try {
                            $date = new DateTime($order_row['created_at']);
                            echo "<td>" . htmlspecialchars($date->format('Y-m-d H:i:s')) . "</td>";
                        } catch (Exception $e) {
                            echo "<td>Invalid Date</td>";
                        }
                        echo "<td>" . htmlspecialchars($order_row['status'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($order_row['shipping_address'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($order_row['billing_info'] ?? 'N/A') . "</td>";
                        echo "<td>$" . htmlspecialchars(number_format((float)($order_row['total_amount'] ?? 0), 2)) . "</td>";

                        // Fetch and display items for this order
                        echo "<td>";
                        $items_stmt = $pdo->prepare("SELECT oi.quantity, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? ORDER BY p.name ASC");
                        $items_stmt->execute([$current_order_id]);
                        $items_for_this_order = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($items_for_this_order) {
                            echo "<ul>";
                            foreach ($items_for_this_order as $item) {
                                echo "<li>" . htmlspecialchars($item['name']) . " (" . htmlspecialchars($item['quantity']) . ")</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "No items found.";
                        }
                        echo "</td>";

                        echo "<td><a href='?page=order_tracking&order_id=" . htmlspecialchars($current_order_id) . "'>View Details</a></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No orders found.</p>";
                }
                break;


            default:
                // exit; TEST
                echo "<h2>Page Not Found</h2>";
                echo "<p>The requested page '{$page}' does not exist.</p>";
                break;
        }
        ?>
    </div>
</div> </body>
</html>
