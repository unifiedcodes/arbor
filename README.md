Below is a comprehensive **README.md** for the Arbor framework, reflecting its current structure and usage. Citations point to the GitHub repository and relevant code sections.

---

A modular PHP micro‑framework for fast, modern application development. citeturn0view0

## Table of Contents
- [Introduction](#introduction)  
- [Features](#features)  
- [Installation](#installation)  
- [Usage](#usage)  
- [Directory Structure](#directory-structure)  
- [Contributing](#contributing)  
- [License](#license)  

## Introduction
Arbor is a lightweight, highly modular PHP micro‑framework designed to give you full control over HTTP routing, middleware, dependency injection, configuration and more—while remaining fast and easy to extend. citeturn0view0

## Features
- **Bootstrap & environment handling** through `Arbor\bootstrap\App`, enabling multi‑environment configuration (development, production) citeturn10view0  
- **Configuration management** loaded from PHP files (`configs/`), supporting environment‑specific overrides citeturn10view0  
- **Dependency Injection Container** for service registration and resolution (`Arbor\container`) citeturn10view0  
- **Service Contracts** defining interfaces and abstractions (`Arbor\contracts`) citeturn10view0  
- **Fragment system** for template fragments and reusable view components (`Arbor\fragment`) citeturn10view0  
- **HTTP layer** with PSR‑7‑style requests/responses and context (`Arbor\http`) citeturn10view0  
- **Middleware pipeline** to intercept and process requests (`Arbor\pipeline`) citeturn10view0  
- **Routing system** supporting group files, error pages, and dynamic URIs (`Arbor\router`) citeturn10view0  
- **Autoloader** for easy class loading (`Arbor/Autoloader.php`) citeturn10view0  
- **Database abstraction & QueryBuilder** for SQL‑dialect‑agnostic queries (`Arbor\database`) citeturn10view0

## Installation
```bash
# Clone the repository
git clone https://github.com/unifiedcodes/arbor.git
cd arbor

# Point your web server document root to the `public/` directory.
```
citeturn0view0

## Usage
In `public/index.php`:
```php
use Arbor\Autoloader;
use Arbor\bootstrap\App;

require_once '../Arbor/Autoloader.php';

$autoloader = new Autoloader('../');

$app = (new App())
    ->withConfig('../configs/')
    ->onEnvironment('development')
    ->boot();

// Handle incoming HTTP request
$response = $app->handleHTTP();
$response->send();
```
This bootstraps Arbor, loads configuration from `configs/`, sets the environment, registers services and middleware, then dispatches routes to controllers. citeturn14view0

## Directory Structure
```
arbor/                  # Core framework code
├── attributes/         # PHP 8+ attribute handlers
├── bootstrap/          # App bootstrap & environment
├── config/             # Config file loader
├── container/          # DI container implementation
├── contracts/          # Service interfaces
├── database/           # PDO wrapper & QueryBuilder
├── fragment/           # Template fragment system
├── http/               # Request/Response, context
├── pipeline/           # Middleware pipeline
├── router/             # Routing engine
└── Autoloader.php      # PSR‑4‑style autoloader

app/                    # Sample application controllers
configs/                # Configuration files (app, db, dirs, etc.)
middlewares/            # Global middleware classes
providers/              # Service provider registrations
public/                 # Public web root (index.php)
routes/                 # Route definitions (app.php, errorPages.php)
.htaccess               # Apache rewrite rules
index.php               # Silent root stub
```
citeturn10view0turn0view0


## 🚧 Upcoming Features

Arbor is under active development, and the following features and enhancements are planned for future releases:

---

### 📦 Database Module (In Progress)

A powerful, extensible, and SQL-dialect-agnostic database layer:

- **Query System**
  - `QueryBuilder`: Programmatically build SQL queries
  - `QueryNode`: Represent query components as nodes

- **Compiler**
  - `Compiler`: Compile QueryNodes into executable SQL
  - `Adapters/MySql`: Dialect-specific SQL compilation for MySQL

- **ORM (Object-Relational Mapping)**
  - `Model`: Active Record-like base class
  - `Relations`:
    - `HasMany`
    - `BelongsTo`

- **Migration System**
  - Schema versioning and migrations (TBD)

---

### 📁 File Uploader

- Simple and secure file uploading utility with:
  - MIME/type validation
  - File size limits
  - Custom storage drivers (local, cloud-ready)

---

### 🔐 Authentication Module

- Core authentication utilities:
  - Login/logout
  - Session & token-based auth
  - User provider integration

---

### 🧩 Templating Engine

- A lightweight, extendable HTML templating engine with:
  - Layout support
  - Custom directives
  - Secure escaping

---

### 🌐 HTTP Client

- Fluent, PSR-compatible HTTP client for making outbound requests.
- Will support:
  - JSON payloads
  - File uploads
  - Response transformation pipelines

---

### 🛡️ Global Middlewares

Planned global middlewares to enhance security and DX:

- ✅ Middleware assignment and execution testing
- ⚠️ Error Handling & Logging (middleware or dedicated class TBD)
- 🛡️ CSRF Protection
- 🧼 Input Sanitization
- 🧷 Security Headers (CSP, X-Content-Type-Options, etc.)
- 🌍 CORS (Cross-Origin Resource Sharing)
- 🚦 Rate Limiting

---

## Contributing
1. Fork the repository  
2. Create your feature branch (`git checkout -b feature/YourFeature`)  
3. Commit your changes (`git commit -m 'Add awesome feature'`)  
4. Push to the branch (`git push origin feature/YourFeature`)  
5. Open a Pull Request at https://github.com/unifiedcodes/arbor

Bug reports and improvements are welcome via GitHub [Issues](https://github.com/unifiedcodes/arbor/issues). citeturn0view0
