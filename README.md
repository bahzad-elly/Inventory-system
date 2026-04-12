# Inventory Pro - Premium Management System

Inventory Pro is a modern, bilingual (English & Kurdish), and fully responsive inventory management solution designed for efficiency and ease of use. This system allows businesses to manage products, track warehouse levels, process sales, and monitor supplier restock orders in a premium, high-performance environment.

---

## 🚀 Key Features

- **Bilingual Interface**: Native support for **English (LTR)** and **Kurdish (RTL)** with instant switching.
- **Dynamic Theming**: Premium **Dark** and **Light** modes tailored for long-term usage.
- **Real-time Performance Dashboard**: Visualized KPIs including total revenue, active customers, and stock reorder alerts.
- **Modular Data Management**: Dedicated modules for Products, Suppliers, Customers, Sales, and Staff.
- **Enterprise-Grade UI**: Built with a "Mobile-First" approach using modern glassmorphism and sticky navigation.
- **Database Security**: Integrated one-click SQL backup system.

---

## 📂 Project Structure

The project follows a modular PHP architecture for maximum maintainability:

- **/assets**: Contains CSS styling (`style.css`) and JavaScript logic for language/theming (`script.js`).
- **/includes**: Reusable components such as `header.php`, `sidebar.php`, and `footer.php`.
- **Core Modules**:
  - `dashboard.php`: The central command hub.
  - `products.php`: Catalog management.
  - `sales_orders.php`: Transaction and stock deduction logic.
  - `reports.php`: Business analytics and data backup.
  - `index.php`: Secure login portal.

---

## 🛠️ Installation & Setup

To run this project locally, follow these steps:

1. **Prerequisites**: Ensure you have **XAMPP** installed (or any PHP/MySQL stack).
2. **Setup Folder**: 
   - Open your `htdocs` directory.
   - Create a folder named `inventory system` and place all files inside.
3. **Database Import**:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a new database named `inventory_db`.
   - Select the database and use the **Import** tab to upload the `inventorysystem.sql` file provided.
4. **Accessing the App**:
   - Start the Apache and MySQL modules in XAMPP.
   - Open your browser and navigate to `http://localhost/inventory%20system/`.
5. **Default Credentials**:
   - **Username**: (Check the `User` table in your DB for the admin user) - Typically `admin` or the user created during setup.

---

## 🏗️ Technical Implementation

- **Backend**: PHP 8.x with PDO (PHP Data Objects) for secure, prepared SQL interactions.
- **Database**: MySQL/MariaDB with relational integrity and foreign key constraints.
- **Frontend**: 
  - Vanilla CSS3 with advanced variables for theming.
  - FontAwesome 6 for iconography.
  - Google Fonts (Poppins) for modern typography.
  - JavaScript (ES6) for the theme and language state engine.

---

### Developed with ❤️ for Modern Business Excellence.
