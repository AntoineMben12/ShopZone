# ShopZone 🛒

ShopZone is a modern, premium e-commerce web application built with PHP, PDO (MySQL), and a sleek minimal HTML/CSS frontend. It features secure user authentication, shopping carts, dynamic product pagination, role-based dashboard access, and automated email receipts with QR codes.

---

## 🚀 Features

*   **User Roles & Dashboards:** Distinct experiences for Admins (Product/Order Management) and Users (Shopping & Order Tracking).
*   **Modern Shopping Cart:** Asynchronous AJAX "Add to Cart" functionality with live navigation badge updates and stylish toast notifications.
*   **Dynamic Product Listing:** Intelligent SQL-based pagination that preserves search, ordering, and category filters seamlessly.
*   **Automated Email Receipts:** Secure checkout system that triggers customized, storefront-styled HTML email receipts via PHPMailer.
*   **Digital Order Tracking:** Every email receipt intelligently embeds a dynamic API-generated QR Code for instant order tracking.
*   **Environment Configuration:** Sensitive credentials and database configurations safely hidden behind `.env` variables.
*   **Responsive Design:** Fully fluid, mobile-friendly design utilizing clean structural aesthetics with bold typography and custom micro-animations.

---

## 🛠 Prerequisites

*   **PHP:** Version 8.0 or higher.
*   **Database:** MySQL or MariaDB (typically running via XAMPP, WAMP, or MAMP).
*   **Composer:** Required if you plan to enable PHPMailer for the email receipts feature.

---

## ⚙️ Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/e-commerce.git
cd e-commerce
```

### 2. Configure the Database
1. Open phpMyAdmin (or your preferred MySQL client).
2. Create a database named `e_commerce`.
3. Import the provided SQL structure file (if you have an export, or rely on the application to create tables if that logic exists).

### 3. Setup Environment Variables
1. Duplicate the generated `.env` file (or create one at the root of the project).
2. Populate the variables:
```ini
ADMIN_PASSWORD="your-secure-admin-password"

# SMTP Configurations for PHPMailer
SMTP_HOST="smtp.gmail.com"
SMTP_USER="your-email@gmail.com"
SMTP_PASS="your-app-password"
SMTP_PORT="587"
```

### 4. Install Dependencies (For Email Receipts)
The storefront logic safely bypasses email sending if PHPMailer is not found, preventing crashes. To enable email receipts:
```bash
composer require phpmailer/phpmailer
```

### 5. Run the Application
Start your local Apache/MySQL server (e.g., via XAMPP) and navigate to the project directory in your browser:
```text
http://localhost/e-commerce/
```

---

## 📂 Project Structure

```text
e-commerce/
├── .env                # Secure environment variables (Ignored in Git)
├── .gitignore          # Git exclusion rules
├── create_admin.php    # CLI/Web script to securely provision the initial Admin user
├── index.php           # The public storefront entry point
├── database/           # Core database connection logic (PDO)
├── css/                # Global stylesheets and typography defined
├── pages/
│   ├── admin/          # Admin products, dashboard, and inventory management
│   ├── auth/           # Login, Session, and Registration handling
│   ├── cart/           # AJAX addToCart endpoint and Secure Checkout processing
│   ├── includes/       # Shared headers, navBars, footers, & Mailer helpers
│   ├── product/        # Product listing grids and detail pages
│   └── user/           # User profile and order history
└── vendor/             # Composer dependencies (PHPMailer - ignored in Git)
```

---

## 🛡 Security Notes

*   This application uses **PDO Prepared Statements** for all database queries to prevent SQL Injection attacks.
*   Passwords are automatically salted and hashed using PHP's native `password_hash()`.
*   Sensitive configuration is extracted entirely to the `.env` file.
