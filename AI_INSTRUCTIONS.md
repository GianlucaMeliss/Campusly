# 🤖 AI Developer Instructions for PHP MVC Starter Template

## 📌 Project Overview
This is a custom, lightweight, vanilla PHP (8.4+) MVC boilerplate designed for fast deployment of websites, landing pages, and web services. It uses a Front Controller pattern, flat-file JSON data storage (with an optional PDO Singleton), and requires no external dependencies like Composer or Node.js to run the core architecture.

**Your Goal as an AI:** When assisting with this project, you MUST adhere to the existing architectural patterns. Do not suggest adding heavyweight frameworks (like Laravel or Symfony components). Maintain the simplicity, security, and speed of this vanilla PHP structure.

---

## 🏗️ Architecture & Directory Structure

- `/app/` - Contains the core application logic (Namespaced `App\`).
  - `/Controllers/` - Handles incoming requests and calls views/models.
  - `/Core/` - System files (`Router.php`, `View.php`, `Database.php`, `ErrorHandler.php`).
  - `/Models/` - Data retrieval logic.
  - `/logs/` - System logs (e.g., `error.log`, `cookie_consents.csv`).
- `/config/` - Centralized configuration (`app.php`) and routing definitions (`routes.php`).
- `/data/` - Flat-file databases (e.g., `content.json`). Protected from direct web access via `.htaccess`.
- `/public/` - The Web Root. Contains the Front Controller (`index.php`), `.htaccess`, and all public assets (`/assets/`).
- `/views/` - HTML/PHP templates.

---

## ⚙️ Core Mechanics & Strict Rules

### 1. Routing & Boundaries
All requests are routed through `/public/index.php` via `.htaccess`.
- **Strict Boundary:** This template deliberately DOES NOT use Middleware, Route Grouping, or Route Naming. If you feel the need to implement these, stop. The project scope has exceeded this boilerplate.
- **Define new routes** ONLY in `/config/routes.php`.
- **Syntax:** `$router->get('/path', function() { (new Controller())->method(); });`

### 2. Global Error Handling
The application uses a centralized `App\Core\ErrorHandler` initialized in `index.php`.
- **Do not use `die()` or `exit()`** to handle failures (especially in Database operations).
- Always `throw new Exception('message')`. The global handler will catch it, log the technical details securely to `/app/logs/error.log`, and return either a generic 500 HTML page or a 500 JSON response depending on the request type.

### 3. API & Form Security (The Golden Rule)
All endpoints inside `App\Controllers\ApiController.php` (or any POST handlers) MUST adhere to these strict security standards:
- **CSRF Validation:** A global token is generated in `index.php`. EVERY POST request (whether classic HTML form or JS fetch) must include and validate `$_SESSION['csrf_token']`.
- **Rate-Limiting:** Every POST endpoint must implement session-based throttling to prevent abuse.
- **Standardized Responses:** Use `header("Location: ...")` for HTML forms, or strictly formatted JSON with appropriate HTTP status codes (200, 400, 403, 429, 500) for AJAX/Fetch requests.

### 4. Views & Rendering
Views are rendered using `App\Core\View::render('folder/file', $dataArray);`.
- `View::render()` automatically includes `header.php` and `footer.php`.
- Global configurations (from `config/app.php`) are extracted into the views.
- **Rule:** Keep business logic OUT of the views. Use `htmlspecialchars()` when outputting ANY variable to prevent XSS.

### 5. Data Management
- **JSON (Default):** Use flat JSON files inside `/data/` for simple sites. 
- **PDO (Optional):** If the database array in `config/app.php` is filled, `App\Core\Database::getInstance()` returns a singleton PDO connection. Any database-driven model MUST extend `App\Core\Model`. ALWAYS use PDO Prepared Statements for queries.