# 🍲 FoodFusion — Web Application

FoodFusion is a community-driven culinary platform built with **PHP**, **MySQL**, and **vanilla JavaScript**. It features three distinct user experiences: **Visitor**, **Member**, and **Admin**, complete with recipe publishing, culinary education resources, community interaction, and a modern Admin Control Center.

---

## 📋 System Requirements

- **PHP**: 8.0 or higher (with `pdo_mysql` extension enabled)
- **Database**: MySQL 5.7+ or MariaDB 10.4+
- **Web Server**: Apache (via XAMPP) or PHP Built-in Web Server

---

## 🚀 How to Run & Migrate with XAMPP

### Step 1: Place the Project in XAMPP `htdocs`

Move or clone this repository inside your XAMPP `htdocs` directory:

- **Windows**: `C:\xampp\htdocs\foodfusion`
- **macOS**: `/Applications/XAMPP/xamppfiles/htdocs/foodfusion` (or `/Applications/XAMPP/htdocs/foodfusion`)
- **Linux**: `/opt/lampp/htdocs/foodfusion`

---

### Step 2: Start Apache and MySQL in XAMPP

1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Ensure both services show green status indicators.

---

### Step 3: Database Migration (Import SQL)

Choose **Method A** (Browser GUI) or **Method B** (Command Line):

#### Method A: Using phpMyAdmin (Recommended GUI)

1. Open your browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **"New"** in the left sidebar to create a database.
3. Database name: **`foodfusion`**
4. Collation: select **`utf8mb4_unicode_ci`** (or leave default).
5. Click **"Create"**.
6. Select the newly created `foodfusion` database from the left menu.
7. Click the **"Import"** tab at the top.
8. Under **"File to import"**, click **"Choose File"** (or Browse) and select:
   ```
   foodfusion/database/foodfusion.sql
   ```
9. Scroll to the bottom and click **"Import"** (or **"Go"**).
10. You should see a success message indicating all 11 tables and demo records were imported.

#### Method B: Using XAMPP Shell / Terminal (Command Line)

Open the **Shell** from XAMPP Control Panel (or your terminal) and run:

```bash
# Windows (XAMPP Shell) or macOS / Linux Terminal:
mysql -u root < database/foodfusion.sql
```

*(If your MySQL root account has a password, add `-p` and enter your password).*

To verify the migration:
```bash
mysql -u root -e "SHOW TABLES FROM foodfusion;"
```

---

### Step 4: Open the Application in Your Browser

Once the database is imported and Apache is running, visit:

👉 **[http://localhost/foodfusion/](http://localhost/foodfusion/)**

---

## ⚡ Alternative: Run via PHP Built-in Server

If you prefer not using Apache, you can run the site directly via PHP's built-in server with MySQL running in the background:

```bash
# 1. Import database (if not already done)
mysql -u root < database/foodfusion.sql

# 2. Start the local PHP development server from the project directory
php -S localhost:8000
```

👉 Visit: **[http://localhost:8000/](http://localhost:8000/)**

---

## 🔑 Default Test Accounts

The database comes pre-seeded with sample accounts. Passwords are encrypted using PHP's `password_hash()` (BCrypt).

| Role | Email | Password | Access & Capabilities |
|---|---|---|---|
| **Admin** | `admin@foodfusion.test` | `Admin123!` | Full control center, recipe & resource CRUD, post moderation, user directory, feedback inbox |
| **Member** | `member@foodfusion.test` | `Member123!` | Personal dashboard, inline likes/comments, My Wall, image file uploads |
| **Visitor** | *(No login required)* | — | Browse 13 recipes, search, download PDFs, view tutorials, send feedback |

---

## ⚙️ Database Configuration

Configuration settings are stored in `config/database.php` and `config/app.php`. 

Default connection parameters match XAMPP out-of-the-box:
- **Host**: `127.0.0.1` (with fallback to `localhost`)
- **Database**: `foodfusion`
- **Username**: `root`
- **Password**: `""` *(empty string)*

### Custom Environment Overrides (Optional)
If your local MySQL uses custom credentials, you can set environment variables:
- `FOODFUSION_DB_HOST`
- `FOODFUSION_DB_NAME`
- `FOODFUSION_DB_USER`
- `FOODFUSION_DB_PASSWORD`
- `FOODFUSION_DB_SOCKET`

---

## 🌟 Key Application Features

### 1. Visitor Role (Public)
- **Recipe Collection**: 13 recipes including 5 authentic Myanmar specialties (*Mohinga, Shan Noodles, Tofu Nway, Laphet Thoke, Ohn No Khao Swe*) alongside international dishes.
- **Search & Filters**: Search recipes by keyword, cuisine type, dietary preference, and difficulty level.
- **Educational & Culinary Resources**: Downloadable PDF recipe cards and cooking guides (ReportLab formatted) plus embedded cooking videos.
- **Contact Us**: Interactive feedback form with confirmation and admin reply tracking.
- **Unified Auth Modal**: Accessible modal dialog with tab switching between "Join Us (Register)" and "Log In", with a 5-second prompt for visitors.

### 2. Member Role (Logged-In)
- **Member Dashboard (`member.php`)**: Personal welcome, cooking stats, saved recipes, and upcoming events.
- **Community Cookbook**: Browse community recipes, toggle post likes, and write inline comments.
- **My Wall (`my-wall.php`)**: Manage member's own posts with edit/delete controls and local image file uploads (`uploads/posts/`).

### 3. Administrator Role (Control Center)
- **Admin Sidebar Layout**: Dedicated SaaS-style dashboard with persistent navigation and mobile drawer.
- **Real-Time Metrics**: Total members, published recipes, curated resources, review queue, and unreplied messages.
- **Homepage Showcase**: One-click star toggle (`★ Featured on Home`) to manage recipes appearing on the landing page.
- **Cooking Event Scheduler**: Schedule community workshops, dates, and locations.
- **Content Moderation**: Approve or reject pending community posts with status badges.
- **Feedback Inbox**: Review visitor inquiries and submit direct replies.
- **Dedicated Management**:
  - `manage-recipes.php`: Full recipe CRUD with file upload and HTTPS URL support.
  - `manage-resources.php`: Full educational & culinary resource CRUD with PDF/video uploads.

---

## 📂 Project Directory Structure

```text
foodfusion/
├── assets/
│   ├── css/
│   │   ├── style.css         # Main application stylesheet
│   │   └── admin.css         # Admin dashboard and sidebar stylesheet
│   └── js/
│       └── main.js           # Navigation, modals, carousel, and tabs
├── config/
│   ├── app.php               # Core constants and automatic Base URL detection
│   └── database.php          # PDO MySQL database connection
├── database/
│   └── foodfusion.sql        # Full database schema and sample records
├── includes/
│   ├── admin-header.php      # Admin sidebar and topbar layout
│   ├── admin-footer.php      # Admin scripts and closing tags
│   ├── header.php            # Public and member navigation bar
│   ├── footer.php            # Public footer, social links, auth modal
│   └── functions.php         # Sanitization, CSRF, auth helpers
├── output/pdf/               # Culinary guide PDFs and recipe cards
├── uploads/                  # User and admin uploaded media
│   ├── posts/                # Community post images
│   ├── recipes/              # Recipe photos
│   └── resources/            # Downloadable resource files
├── admin.php                 # Admin Control Center overview
├── manage-recipes.php        # Admin Recipe CRUD
├── manage-resources.php      # Admin Resource CRUD
├── member.php                # Member landing dashboard
├── community.php             # Community Cookbook feed
├── my-wall.php               # Member personal post wall
├── recipes.php               # Public recipe catalog
├── recipe.php                # Single recipe detail & comments
├── culinary-resources.php    # Culinary guides and videos
├── educational-resources.php # Food safety and nutrition guides
├── contact.php               # Contact form and feedback viewer
├── login.php                 # Member and admin authentication
├── register.php              # Member registration
└── index.php                 # Public visitor landing page
```

---

## 🔒 Security Practices

- **Password Hashing**: Securely hashed with PHP's native `password_hash()` and verified with `password_verify()`.
- **SQL Injection Prevention**: All queries use PDO prepared statements with parameter binding.
- **XSS Mitigation**: User inputs are sanitized with `htmlspecialchars()` (`ENT_QUOTES, 'UTF-8'`).
- **CSRF Tokens**: All POST forms include cryptographic CSRF token validation.
- **Account Lockout**: 3 failed login attempts trigger an automatic 3-minute temporary account lockout.
- **Session Security**: Session fixation defense with session regeneration on role changes.

---

## ❓ Troubleshooting

| Issue | Solution |
|---|---|
| **"Database connection failed"** | Ensure MySQL is running in XAMPP and `database/foodfusion.sql` has been imported into a database named `foodfusion`. |
| **Port 80 or 443 already in use** | In XAMPP, click **Config** on Apache -> `httpd.conf` -> change `Listen 80` to `Listen 8080`, then access via `http://localhost:8080/foodfusion/`. |
| **Port 3306 already in use** | If you already have another MySQL server installed, stop the existing MySQL service before starting MySQL in XAMPP. |
| **File upload errors** | Ensure write permissions are granted on the `uploads/` folder and its subdirectories (`chmod -R 775 uploads/` on macOS/Linux). |
| **Subdirectory 404 or broken links** | The application automatically detects subdirectories. Ensure the folder name in `htdocs` is `foodfusion` or set `FOODFUSION_BASE_URL` in environment variables. |
