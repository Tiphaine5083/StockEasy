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
│ │ │ └── stock.js
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
│ │ │ ├── user-role.phtml
│ │ │ └── user-permission.phtml
│ │ ├── home.phtml
│ │ ├── layout.phtml
│ │ ├── login.phtml
│
├── .env
├── .env.example
├── .gitignore
└── README.md

---

## Implemented Features

- **Create Stock**
    - Form with all required fields (`detail_tire`)
    - Insert into `detail_tire`
    - Insert into `catalog` with auto name & description
    - Insert into `stock_movement` for initial entry

- **Update Stock**
    - Full update with unit price & quantity
    - Automatic stock movement logging for any quantity change
    - Sync with `catalog`

- **Delete / Archive Stock**
    - Archive for invoiced tires
    - Permanent deletion for non-invoiced tires
    - Always logs a `stock_movement` when archiving with leftover quantity

- **Search Stock**
    - Dynamic search form with multiple filters
    - Results with `highlight_id` for context
    - Integrated breadcrumb navigation

- **Flash Messages — Uniform Session-Based System**
    - All success and error messages are now handled via `$_SESSION`, not `GET` parameters.
    - The layout (`layout.phtml`) includes a reusable message overlay block that displays:
        - Success messages (`$_SESSION['success']`)
        - Error messages (`$_SESSION['error']`)
    - Messages are automatically flushed (`unset`) after being displayed.
    - Controllers set messages like this:
        ```php
        $_SESSION['success'] = 'Item created successfully';
        $_SESSION['error'] = 'An error occurred';
        $this->redirectToRoute('some-route');
        ```
    - This approach ensures consistency, security, and compatibility with form repopulation via `$_SESSION['form_data']`.
    - Example integration at the top of `layout.phtml`:
        ```php
        <?php
            $success = $_SESSION['success'] ?? null;
            $error = $_SESSION['error'] ?? null;
            unset($_SESSION['success'], $_SESSION['error']);

            if ($success || $error): ?>
            <div class="message">
                <div class="message__modal <?= $success ? 'message__modal--success' : 'message__modal--error' ?>" role="alert" aria-live="assertive">
                    <button class="message__close" aria-label="Fermer le message">&times;</button>
                    <p><?= htmlspecialchars($success ?? $error) ?></p>
                </div>
            </div>
        <?php endif; ?>
        ```

- **ACID Compliance**
    - Critical operations (`createFullStock`, `updateFullStock`, `archiveTire`, `deleteTireCompletely`) use `beginTransaction` and `commit/rollback`
    - Any error automatically triggers a `rollback` to ensure no partial write occurs.
    - All multi-table changes are atomic

---

## Duplicate Control — V1 (Final)

This version implements full duplicate control for tire creation.  
When adding a new tire, the system automatically:

1. **Detects any duplicate** using `StockModel::existsDuplicate()`.  
2. **Redirects** the user back to the creation page with a `duplicate_id` if a match is found.  
3. **Displays a confirmation modal**, pre-filled with the duplicate ID.  
4. Lets the user decide:
    - **Increment stock** → triggers `StockController::stockIncrement()` with proper `stock_movement` logging (`entrée`, `reason: achat`).
    - **Cancel** → closes the modal, keeps the form untouched.
    - **Reset manually** → handled by the separate reset button on the main form.

### Technical Flow

- **Backend**
    - `StockController::stockCreate()` checks for duplicates **before inserting**.
    - If duplicate ➜ redirects with `duplicate_id` in GET.
    - `StockController::stockIncrement()` updates `quantity_available` and creates a new `stock_movement` record.
    - All changes are wrapped in a transaction (`updateFullStock`).

- **Frontend**
    - `app.js` reads `duplicate_id` from URL.
    - Opens the duplicate modal automatically if found.
    - The modal uses a simple hidden `<form>` to POST the `product_id` + `added_quantity` back to `stock-increment` route.
    - No AJAX here — full page reload ensures consistent state and shows the flash message overlay.

- **UX**
    - Users always see a clear success/error message through the existing modal overlay (`success` or `error` from GET).
    - No unexpected silent insert or overwrite.

---

**Duplicate control** is now fully ACID-compliant, user-friendly, and tested for real-world scenarios.

---

## Next Steps

The following functional blocks will be completed to finalize StockEasy V2:

- **Authentication**
    - Secure user login with role management (admin, employee).
    - Session handling, restricted areas.
    - Full PHPDoc coverage.

- **Client Management**
    - CRUD for client database.
    - Validation rules and search filters.
    - ACID-compliant operations with transaction support.
    - Complete documentation and error handling.

- **Accounting**
    - Basic invoicing logic.
    - Stock usage tracking linked to invoices.
    - Consistent stock movements for all financial operations.
    - Prepared for multi-table updates with rollback on failure.

- **Document Management**
    - Generation and download of stock reports (PDF).
    - Potential future integration with invoicing.
    - File consistency checks.

**All blocks will:**
    - Use the same MVC base (`Controller`, `Model`, `View`).
    - Be documented with clear PHPDoc blocks.
    - Rely on ACID transactions for critical multi-table logic.
    - Be tested with real scenarios.
    - Be presented in slides during the final demonstration.

---

## Documentation

All PHP files are fully commented using PHPDoc blocks.  
Each class, method, parameter, and return type is described for clarity and maintenance.

---

## Author

This module is maintained by [Tiphaine LE CAM] as part of the **StockEasy V2** refactoring.

---
