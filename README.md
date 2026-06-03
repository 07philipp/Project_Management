# Project & Customer Management System

A bespoke, high-performance web platform tailored for managing projects, clients, time tracking, and automated invoicing. Built with native PHP and a relational SQL database using PDO, this application has undergone extensive security hardening and architectural refactoring to meet modern production and enterprise standards.

---

## Architecture & Directory Layout

The application enforces a strict separation between the publicly accessible web root, core system configuration, document templates, and backend dependencies:


```

.
├── htdocs/                         # Public web directory (Frontend & Backend)
│   ├── admin/                      # Administrative dashboards, KPIs and system overview
│   ├── backup/                     # Backup management and restore interface
│   ├── change_password/            # User self-service password management
│   ├── change_password_admin/      # Administrative password reset functions
│   ├── clients/                    # Client management views and handlers
│   ├── css/                        # Application stylesheets and UI components
│   ├── docx/                       # Protected document templates for invoice exports
│   ├── download/                   # Generated file download endpoints
│   ├── edit_client/                # Client data modification forms
│   ├── edit_project/               # Project editing and assignment management
│   ├── edit_time/                  # Time log corrections and adjustments
│   ├── js/                         # JavaScript components (AJAX, validation, UI helpers)
│   ├── log/                        # Audit logs and system activity monitoring
│   ├── login/                      # Authentication gateway and session creation
│   ├── projects/                   # Project overview, planning and uploads
│   │   └── uploads/                # Project-related file attachments
│   ├── register/                   # User registration and permission assignment
│   ├── settings/                   # Application settings and configuration panels
│   ├── users/                      # User administration and role management
│   ├── write_bill/                 # Invoice generation and billing preparation
│   ├── add_client.php              # Create new client records
│   ├── add_project.php             # Create new projects
│   ├── add_time.php                # Create new time entries
│   ├── backup.php                  # Manual backup execution
│   ├── change_pw.php               # Password update processing
│   ├── change_pw_admin.php         # Administrative password update processing
│   ├── delete_client.php           # Client deletion handler
│   ├── delete_project.php          # Project deletion handler
│   ├── delete_time.php             # Time entry deletion handler
│   ├── delete_user.php             # User removal handler
│   ├── edit_amount.php             # Billing amount adjustments
│   ├── edit_checklist.php          # Project checklist management
│   ├── edit_client_process.php     # Client update processing logic
│   ├── edit_order.php              # Order and billing sequence updates
│   ├── edit_project_client.php     # Client assignment updates for projects
│   ├── edit_project_process.php    # Project update processing logic
│   ├── edit_project_user.php       # User assignment management for projects
│   ├── edit_time.php               # Time entry update processing
│   ├── export.php                  # Data export functionality
│   ├── generate_word.php           # DOCX invoice/document generation
│   ├── get_amount.php              # Dynamic billing amount retrieval
│   ├── get_client.php              # AJAX client information endpoint
│   ├── mysql.php                   # Database abstraction and helper functions
│   ├── session_loguot.php          # Automatic session timeout handling
│   ├── settings.php                # Settings persistence controller
│   ├── setup.php                   # Web-based installation and initialization
│   ├── update_filter.php           # Dashboard filter updates
│   ├── update_permission.php       # User permission management
│   ├── update_project_status.php   # Project workflow status updates
│   ├── upload.php                  # File upload processing
│   ├── index.php                   # Primary dashboard and application entry point
│   └── logout.php                  # Secure session termination
│
├── backups/                        # Automated database backup storage
│   └── temp_restore/               # Temporary restore workspace
│
├── libraries/                      # Custom helper libraries and utilities
├── vendor/                         # Third-party PHP dependencies (Composer)
├── README.md                       # Project documentation and setup guide
├── backup_cron.php                 # Scheduled backup automation script
├── config.php                      # Central environment and database configuration
└── sql_backup.sql                  # Database schema and backup snapshot

```

---

## Key Features

* **Client & Project Lifecycle Management:** Dedicated modules for onboarding clients, tracking ongoing project statuses, and evaluating administrative KPIs.
* **Granular Time Tracking:** Integrated work-hour logging with administrative tools for record correction, verification, and compliance checking.
* **Automated Billing Pipeline:** Aggregated data previews that dynamically pull logged hours and client rates to generate comprehensive billing previews and document exports.
* **Multi-Tenant User Management:** Secure login interfaces, user registration routes, and permission/role allocation levels.

---

## Security Hardening & Architectural Refactoring

The core application logic was significantly upgraded during a comprehensive refactoring phase to neutralize common web application vulnerabilities:

* **SQL Injection (SQLi) Elimination:** Converted over 40 raw, string-interpolated query paths across critical entities (Projects, Clients, Time Logs, and Upload fields) into safe, parameterized **PDO Prepared Statements**. This strictly separates user input from database execution logic.
* **Cross-Site Scripting (XSS) Mitigation:** Implemented strict context-aware output sanitization across more than 17 view templates using a native escaping helper `h()`. Safe variable injection into JavaScript scopes is handled via dynamic JSON script serialization (`pm_json_script()`).
* **Architectural Clean-up & Modularization (DRY Principle):** Eliminated structural redundancies by extracting repetitive checks (such as role permissions and site configurations) into efficient, centralized helper functions inside `mysql.php`.
* **Privilege Separation:** Refactored the logging ecosystem (`log.php`) to isolate operational libraries from controller execution paths. This strictly prevents non-privileged sessions from triggering sensitive API calls while protecting administrative interfaces using strict query-table whitelists.
* **Stability Fixes:** Corrected logical validation errors in the project creation workflow (`add_project.php`) and fixed pluralization parsing routines in the file exporter modules (`generate_word.php`).

---

## Configuration

### Database and Admin Configuration (config.php)

The `config.php` file handles essential environment variables for database connectivity and master administrator authentication. Before running the application, ensure these variables match your local or production environment setup:

```php
<?php
$host = "localhost";       // Database server hostname (e.g., localhost or an IP address)
$user = "root";            // Database username
$password = "";            // Database password
$database = "PM_System";   // Relational database name
$high_admin_pw = "admin";  // Master administrator password for high-privilege operations
$high_admin_name = "admin";// Master administrator username
?>

```

### Document Templates (docx folder)

The system includes a `/docx` directory containing pre-configured file templates used for exports and reporting.

* These templates must be adapted to fit your specific design or reporting standards before deployment.
* **Note:** While you can edit these files manually in the file system, the platform provides a built-in management option, allowing administrators to modify and update these templates directly through the web user interface.

---

## Deployment & Installation Guide

### 1. General System Requirements

* **PHP Runtime:** Version 7.0 or higher
* **Web Server:** Apache HTTP Server (with `mod_rewrite` enabled)
* **Database Engine:** MySQL or MariaDB instance

### 2. Local Setup on Windows (via XAMPP)

1. Launch the **XAMPP Control Panel** and start both the **Apache** and **MySQL** modules.
2. Copy the contents of the `/htdocs` folder into your local root directory: `C:\xampp\htdocs`.
3. Move the `/vendor` and `/docx` directories, along with `backup_cron.php`, `config.php`, and `setup.php`, into the same root folder (`C:\xampp\htdocs`).
4. Proceed to the Automated First-Time Setup instructions below.

### 3. Server Deployment on Linux (Debian / Ubuntu)

1. Install the baseline environment packages via your system package manager:

```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql

```

2. Deploy the core web directories to your server document root:

* Map the contents of `/htdocs` to `/var/www/html`
* Map the `/vendor` dependencies folder to `/var/www/vendor`
* Ensure `backup_cron.php`, `config.php`, and `setup.php` sit inside `/var/www/html`

3. Provision proper system ownership and file permissions to permit the webserver user thread to execute the application cleanly:

```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

```

### 4. NAS Deployment on Synology NAS

1. Open the Synology **Package Center** and install **Web Station**, **PHP (v7.x or higher)**, and **MariaDB**.
2. Upload the files using File Station or SFTP:

* Drop all files from the `/htdocs` directory directly into the `/web` root share directory.
* Upload the `/vendor` folder directly to `/web/vendor`.
* Place `backup_cron.php`, `config.php`, and `setup.php` into the root `/web` directory.

---

## Automated First-Time Setup

The environment initializes its relational schema tables and configurations through a built-in graphical installer wizard:

1. Open your web browser and target your deployment's setup script path:

* **Local Environment:** `http://localhost/setup.php`
* **Server / NAS Instance:** `http://<YOUR-SERVER-IP>/setup.php`

2. Follow the prompt instructions in the graphical installer interface. The system will automatically build out the relational constraints, compile the tables, and serialize your live database environment variables into `config.php`.
3. **CRITICAL SECURITY NOTE:** Once the installer announces a successful environment configuration, **immediately remove the deployment script from your server** to prevent unauthorized configuration re-initialization exploits:

* **Windows Environment:** Delete `setup.php` via File Explorer or command line.
* **Linux Environment:** Execute `sudo rm /var/www/html/setup.php`

