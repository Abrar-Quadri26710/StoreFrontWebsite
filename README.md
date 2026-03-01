# Storefront E-commerce Website

A beginner project into understanding databases and trying my hand at combining both front end and back end development. 

## Features

* **Role-Based Permissions:** Distinct authorization levels for 'Owners' (full access to inventory and orders) and 'Employees' (order management only).
* **Secure Authentication:** Implements manual SHA-256 password hashing for secure login credential verification.
* **Shopping Cart & Checkout:** Session-based cart system allowing users to add items, calculate totals dynamically, and process simulated transactions.
* **Order Management:** Customers can track their order history, while employees can update order statuses (Pending, Shipped, Delivered).
* **Inventory Tracking:** Real-time inventory deduction upon checkout, with owner-level controls to restock items across multiple suppliers.



## Technical Stack

* **Backend:** PHP 8.x
* **Database:** SQLite (Migrated from MySQL/PDO for localized usage)
* **Frontend:** HTML5, CSS3 (Vanilla)
* **Security:** Prepared statements (PDO) to prevent SQL injection, SHA-256 hashing.

## Local Execution Protocol

1. Clone this repository.
2. Ensure PHP is installed and added to your system environment variables.
3. Enable the SQLite PDO extension in your `php.ini` file (`extension=pdo_sqlite`).
4. Initialize the database by running the setup script in your terminal:
   ```bash
   php init_db.php