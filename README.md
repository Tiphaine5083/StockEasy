# StockEasy — Stock Management Module

This module handles the complete lifecycle of tire stock management within the **StockEasy** application.  
It is built with a **homemade MVC** structure, using **pure PHP**, no framework, and **ACID-compliant** logic for critical data operations.

---

## Project Tree

Below is the current structure of the project files, organized by functional area :

STOCKEASY/
│
├── public/
│ ├── assets/
│ │ ├── css/
│ │ │ ├── app.css
│ │ │ ├── app.css.map
│ │ ├── fonts/
│ │ │ └── WorkSans-Regular.ttf
│ │ ├── img/
│ │ │ ├── brandsLogos/
│ │ │ └── logos/
│ │ ├── js/
│ │ │ ├── burger.js
│ │ │ ├── generic.js
│ │ │ ├── stock.js
│ │ │ └── user.js
│ │ └── video/
│ ├── .htaccess
│ └── index.php
│
├── scss/
│ ├── back/
│ │ ├── partials/
│ │ │ ├── _accessibility.scss
│ │ │ ├── _base.scss
│ │ │ ├── _components.scss
│ │ │ ├── _forms.scss
│ │ │ ├── _layout.scss
│ │ │ ├── _login.scss
│ │ │ ├── _mixins.scss
│ │ │ ├── _pages.scss
│ │ │ ├── _print.scss
│ │ │ ├── _tables.scss
│ │ │ ├── _utilities.scss
│ │ │ ├── _variables.scss
│ │ └── app.scss
│ ├── front/
│ │ ├── partials/
│ │ │ ├── _layout.scss
│ │ │ ├── _mixins.scss
│ │ │ ├── _variables.scss
│ │ └── style.scss
│
├── src/
│ ├── Controllers/
│ │ ├── AuthController.php
│ │ ├── Error404Controller.php
│ │ ├── PublicController.php
│ │ ├── StockController.php
│ │ └── UserController.php
│ ├── Core/
│ │ ├── AbstractController.php
│ │ ├── AbstractModel.php
│ │ ├── Autoloader.php
│ │ ├── Database.php
│ │ └── Router.php
│ ├── Models/
│ │ ├── RoleModel.php
│ │ ├── StockModel.php
│ │ └── UserModel.php
│ ├── Views/
│ │ ├── partials/
│ │ │ ├── construction.phtml
│ │ │ └── error404.phtml
│ │ ├── stock/
│ │ │ ├── stock-create.phtml
│ │ │ ├── stock-edit.phtml
│ │ │ ├── stock-home.phtml
│ │ │ ├── stock-list.phtml
│ │ │ └── stock-search.phtml
│ │ ├── user/
│ │ │ ├── user-create.phtml
│ │ │ ├── user-edit.phtml
│ │ │ ├── user-home.phtml
│ │ │ ├── user-list.phtml
│ │ │ ├── user-permission.phtml
│ │ │ └── user-role.phtml
│ │ ├── home.phtml
│ │ ├── layout.phtml
│ │ ├── login.phtml
│
├── .env
├── .env.example
├── .gitignore
└── README.md

---

## General Features

StockEasy V2 is a fully ACID-compliant application built in pure PHP with a custom MVC architecture.  
It includes:

- A robust routing system (`index.php` → `Router.php`)
- Clear separation between front and back interfaces
- SCSS modular structure with responsive-first design
- - Flash messages are stored in `$_SESSION` and displayed in the layout with automatic flush
- Full PHPDoc coverage for every class and method
- Role-based access control system (super-admin, admin, secretary, user, intern, guest) — fine-grained permissions planned for V2
- Strong focus on UX for low-tech users (clear labels, modals, keyboard access)
- All sensitive operations are wrapped in transactions (`beginTransaction` / `commit` / `rollback`)

---

## Implemented Modules

### Stock Management

**Create**
- Full form with all `detail_tire` fields
- Inserts in:  
  - `detail_tire`  
  - `catalog` (auto-name & description)  
  - `stock_movement` (type: `entry`, reason: `purchase`)

**Update**
- Unit price & quantity update
- Any change in quantity logs a new `stock_movement`
- Catalog remains in sync

**Delete / Archive**
- Permanent delete (if not invoiced)
- Archive with leftover quantity logs a `stock_movement`
- No orphaned data allowed

**Search**
- Dynamic search with multiple filters
- Highlight current result (`highlight_id`)
- Breadcrumb integration for navigation context

**Duplicate Control**
- Automatic detection before insert
- Modal with 2 options:  
  1. **Increase stock** (adds to quantity + logs movement)  
  2. **Cancel** (keeps form as-is)  
- Fully wrapped in transaction
- Flash messages ensure transparent UX

---

### User Management

**Create / Edit / Delete**
- Full form with role selection and active status
- Password hashed with `password_hash()` (PASSWORD_DEFAULT)
- Email is unique and replaces former "login" field
- Role management is handled via `user_role`. Fine-grained permissions are planned for V2.

**Search & Filter**
- Search by name, email, or role
- Filters for active/inactive status

**Form UX**
- Mobile-first responsive grid
- Real-time JS validation for required fields
- Flash messages shown on success/error

**Security & Architecture**
- All CRUD actions secured with transactions and try/catch blocks
- Access restricted to admin users only


---

## Next Steps

The following functional blocks are planned and will follow the same secure, documented, and modular approach:

### Users 
- Permissions UI planned in `user-permission.phtml`

### Clients
- CRUD with validation and search
- Relation to invoices and appointments
- Logging of modifications
- Fully transactional

### Accounting
- Invoice creation (HT by default)
- Support for credit notes and deposits
- Stock usage linked to invoices
- Generates PDF invoices stored in `/storage/pdf/invoices/`

### Invoices (linked to accounting)
- Secure invoice creation with linked products
- Calculation rules based on company VAT status
- Includes downloadable PDF files for each invoice

### Document Management
- Centralized storage for all generated documents: invoices, stock listings, credits, quotations
- Files organized in `/storage/pdf/...`
- Future export/email integration planned

### Authentication
- Secure login with role system
- Session persistence and protection
- Error feedback and PHPDoc coverage

### Audit & Logging
- Create `system_log` and `modification_log` tables
- Log key user actions (create/update/delete)
- Allow administrators to review recent changes and system events

---

## Planned Features (V2+)

These features are planned for a future release after V2 delivery:

- Fine-grained permissions per action (edit stock, delete user, etc.)
- Multi-rate VAT support depending on product type
- Scheduled stock alerts and appointment reminders (email/SMS)
- Client portal with downloadable invoice history
- Data anonymization and GDPR log tracking

---

## Documentation

All PHP classes and methods include PHPDoc annotations for better readability and maintenance.  
Comments follow professional standards and reflect real application use cases.

---

## Author

This module is maintained by [Tiphaine LE CAM] as part of the **StockEasy** refactoring project.

---
