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
│ │ ├── LogController.php
│ │ ├── PartialsController.php
│ │ ├── PublicController.php
│ │ ├── StockController.php
│ │ └── UserController.php
│ ├── Core/
│ │ ├── AbstractController.php
│ │ ├── AbstractModel.php
│ │ ├── Access.php
│ │ ├── Autoloader.php
│ │ ├── Database.php
│ │ ├── permissions.php
│ │ └── Router.php
│ ├── Models/
│ │ ├── LogModel.php
│ │ ├── RoleModel.php
│ │ ├── StockModel.php
│ │ └── UserModel.php
│ ├── Views/
│ │ ├── customer/
│ │ │ ├── customer-create.phtml
│ │ │ ├── customer-edit.phtml
│ │ │ ├── customer-home.phtml
│ │ │ ├── customer-list.phtml
│ │ │ └── customer-search.phtml
│ │ ├── logs/
│ │ │ ├── log-home.phtml
│ │ │ ├── modification.phtml
│ │ │ └── system.phtml
│ │ ├── partials/
│ │ │ ├── construction.phtml
│ │ │ ├── error403.phtml
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
│ │ ├── password-reset.phtml
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
- Flash messages are stored in `$_SESSION` and displayed in the layout with automatic flush
- Full PHPDoc coverage for every class and method
- Role-based access control system (super-admin, admin, secretary, user, intern, guest) — fine-grained permissions planned for V2
- Strong focus on UX for low-tech users (clear labels, modals, keyboard access)
- All sensitive operations are wrapped in transactions (`beginTransaction` / `commit` / `rollback`)

---

## Layout & Navigation

**Structure**
- Shared layout file: `layout.phtml` with modular zones (header, sidebar, content, footer)
- Sidebar rendered on all back-office views
- Header includes logo, breadcrumb, and logout

**Sidebar**
- Mobile-first accordion navigation
- Fully collapsible/expandable via JS (`burger.js`)
- Each main menu has ARIA attributes (`aria-expanded`, `aria-controls`)
- Submenus use `max-height` animation for smooth toggling
- Extra links (logout, home) fixed at bottom

**Accessibility**
- Keyboard navigation and semantic landmarks
- ARIA roles and states implemented across layout
- Breadcrumb supports screen readers via `aria-current` and `aria-label`

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

### Audit & Logging

StockEasy implements a centralized and extensible logging system, ensuring full traceability of both system events and data modifications.

**System Logs**
- Recorded via `logSystem()` in the `LogModel` class
- Covers login attempts, access denials (403), logout events, and transactional errors
- Each log entry includes: type, description, user ID (if available), and timestamp
- Viewable by `super_admin` only via `log/system.phtml`

**Modification Logs**
- Recorded via `logModification()` in the `LogModel` class
- Covers all create, update, delete, archive actions on key entities (e.g., `user`, `stock`)
- Stores old vs. new data to ensure rollback and auditability
- Logs include user ID, target entity, action type, and change payload
- Displayed in `log/modification.phtml` with breadcrumb navigation

**Access Control Logs**
- Denied access attempts are logged through the `denyAccess()` method (via `AbstractController`)
- HTTP 403 status is set and the reason is recorded in the system log
- User is redirected to a custom error403 view (`partials/error403.phtml`)

**Technical Integration**
- All logging methods rely on PDO and are wrapped in `try/catch` blocks
- Logs are stored in a dedicated `log` database table
- Views are isolated by type and follow the MVC architecture
- Logging logic is separated from business logic to improve testability and readability

---

### Authentication

**Login Form**
- The login interface is implemented in `login.phtml` and includes a fully accessible form (`email` and `password` fields).
- Error alerts use semantic HTML (`role="alert"`, `aria-live`) and support keyboard interaction.

**Login Logic**
- The `AuthController::login()` method handles:
  - Form validation and user lookup via `UserModel::findByEmail()`
  - Password verification using `password_verify()`
  - Active status check and conditional redirect to password reset
  - Session initialization (`$_SESSION['user']`) and last login update
  - Redirection using `redirectToRoute()`
- On failure, `LogModel::logSystem()` records the error reason (unknown email, wrong password, inactive account).

**Logout**
- The `AuthController::logout()` method:
  - Logs the logout event (`logSystem`)
  - Clears and destroys the session
  - Starts a new session to store the `success` flash message
  - Redirects the user to the login screen

**Session & Access Control**
- User data is stored in `$_SESSION['user']` upon login
- A centralized `Access` class handles access logic:
  - `isLoggedIn()` checks for a valid session
  - `hasRole()` and `hasOneRole()` validate the current user's role
  - `hasPermission()` checks against a role-permission table in `permissions.php` (planned for fine-grained control in V2)
- These checks are called in controllers before protected actions.
- Routes that require authentication or role validation are not accessible without the appropriate session data.

**Interface Visibility by Role**
- Menus and UI elements in `.phtml` views are conditionally displayed based on `$_SESSION['user']['role']`
- No advanced permissions engine is used yet; visibility is handled directly in the templates for V2
- This ensures that users only see actions they are allowed to perform, even without triggering a controller method

**Flash Messaging**
- Feedback to the user is handled exclusively via the `$_SESSION['error']` and `$_SESSION['success']` keys
- Messages are cleared immediately after being displayed to avoid repetition
- Only authentication-related events are logged to the system log; standard form validation errors are not logged

---

## Next Steps - V2

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

---

## Planned Features (V3+)

These features are planned for a future release after V2 delivery:

- Fine-grained permissions per action (edit stock, delete user, etc.)
- Multi-rate VAT support depending on product type
- Scheduled stock alerts and appointment reminders (email/SMS)
- Client portal with downloadable invoice history
- Data anonymization and GDPR log tracking

---

## Accessibility Policy

StockEasy follows accessibility best practices to ensure usability by all users, including those with visual, motor, or cognitive impairments.
The interface is designed in compliance with WCAG 2.1 – Level AA, and the following key principles have been implemented:

- Responsive layout: mobile-first structure, scalable to tablet and desktop
- Readable typography: sufficient contrast, resizable text, clear fonts
- Keyboard navigation: all interactive elements are accessible via keyboard
- Semantic HTML: headings, landmarks and form elements are correctly structured
- ARIA attributes: used when necessary to support screen readers (e.g., for accordion menus)
- Alternative text: all non-decorative images have meaningful alt attributes
- Avoidance of motion: animations are minimal, non-intrusive, and do not trigger motion sensitivity
- Accessibility will be further reinforced in future versions, especially on the public-facing side (client portal and contact forms).

---

## Documentation

All PHP classes and methods include PHPDoc annotations for better readability and maintenance.  
Comments follow professional standards and reflect real application use cases.

---

## Author

This module is maintained by [Tiphaine LE CAM] as part of the **StockEasy** refactoring project.

---
