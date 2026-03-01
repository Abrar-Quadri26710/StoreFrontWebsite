DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS employees;

-- Employees: Swapped ENUM for CHECK
CREATE TABLE employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role TEXT CHECK(role IN ('employee', 'owner')) NOT NULL
);

-- Passwords pre-hashed with SHA-256 for SQLite compatibility
INSERT INTO employees (username, password, role) VALUES
('guildmaster', 'a74330206e2a275bf87ed7e44ba545fc1c52119bdcc794ff2d2ba85e8d5320e8', 'owner'),
('elara', 'f44ba6d5d5bfa7663e00fc1663f7041c2c2a0bf25ca83eddd5bf9ea8d343c68a', 'employee'),
('thorn', '0df2a50785ffb15c1e9581a03423bdfdfdf3df1f04ef05282465eb1a052e42bc', 'employee');

-- Suppliers
CREATE TABLE suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100)
);

INSERT INTO suppliers (name, city) VALUES
('Mystic Forgeworks', 'Emberdeep'),
('Sylvan Scrolls', 'Greenshade'),
('Runeblade Order', 'Virelia'),
('Drakari Alchemists', 'Ashenmoor'),
('Twilight Traders', 'Nocturne Hollow');

-- Products
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20),
    weight INT,
    price DECIMAL(10,2) NOT NULL
);

INSERT INTO products (name, color, weight, price) VALUES
('Healing Potion', 'Crimson', 2, 10.00), ('Mana Vial', 'Azure', 1, 7.50),
('Shadow Dagger', 'Black', 5, 25.00), ('Flameblade', 'Red', 12, 100.00),
('Arcane Rune', 'Violet', 1, 15.00), ('Elven Cloak', 'Forest Green', 3, 60.00),
('Dwarven Hammer', 'Steel Grey', 15, 85.00), ('Scroll of Identify', 'Parchment', 1, 20.00),
('Gryphon Feather Quill', 'White', 1, 12.50), ('Mithril Chainmail', 'Silver', 18, 250.00),
('Orb of Scrying', 'Crystal', 4, 175.00), ('Boots of Speed', 'Brown', 2, 90.00),
('Dragonscale Shield', 'Obsidian', 20, 300.00), ('Phoenix Ash', 'Golden', 1, 50.00),
('Giant Strength Belt', 'Leather', 6, 120.00), ('Invisibility Ring', 'Clear', 1, 200.00),
('Wand of Magic Missiles', 'Oak', 2, 75.00), ('Bag of Holding', 'Canvas', 5, 150.00),
('Sunstone Amulet', 'Yellow', 1, 45.00), ('Troll Blood Salve', 'Murky Green', 3, 30.00);

-- Inventory
CREATE TABLE inventory (
    supplier_id INT,
    product_id INT,
    quantity INT,
    PRIMARY KEY (supplier_id, product_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO inventory (supplier_id, product_id, quantity) VALUES
(1, 1, 150), (1, 4, 80), (1, 6, 60), (2, 2, 200), (2, 5, 120),
(3, 3, 90), (3, 4, 50), (4, 1, 100), (4, 2, 100), (4, 5, 140),
(5, 3, 60), (5, 6, 100);

-- Orders
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name VARCHAR(100),
    status TEXT CHECK(status IN ('Pending', 'Shipped', 'Delivered')) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    shipping_address VARCHAR(255) NOT NULL,
    billing_info VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL
);

-- Order Items
CREATE TABLE order_items (
    order_id INT,
    product_id INT,
    quantity INT,
    PRIMARY KEY (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);