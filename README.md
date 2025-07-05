# Arbor

A modular PHP micro‑framework for fast, modern application development.

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Directory Structure](#directory-structure)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

## Introduction

Arbor is a lightweight, highly modular PHP micro‑framework designed to give you full control over HTTP routing, middleware, dependency injection, configuration and more—while remaining fast and easy to extend.

## Features

### ⚙️ Bootstrap & Environment Handling

- Centralized entry point via `Arbor\bootstrap\App`
- Fluent API to configure app, load environment-specific configs, and boot services
- Isolated app modules (e.g., `admin/`, `web/`) with independent routes, configs, uploads, providers

### 🧠 Dependency Injection & Container

- Fast and flexible DI container
- Attribute injection supported
- Provider system for lazy-loaded services
- Will support contextual resolution in the future

### 🔄 Configuration System

- Environment-aware config loading from PHP files
- Supports app-specific overrides and merged configs

### 🔌 Service Contracts

- All critical services (HTTP, Router, Container, etc.) are abstracted with interfaces
- Easy to swap implementations or mock in testing

### 🌐 HTTP Lifecycle

- Fully-featured HTTP stack inspired by PSR standards
- `Request`, `ServerRequest`, `Response`, `UploadedFile`, `Streams`, `Cookies`, `Headers`
- RequestContext, RequestStack, and SubKernel support for advanced routing scenarios

### 🧭 Fragment System

- Fragment engine for internally calling Controllers with or without a parent HTTP request context

### 🧩 Routing System

- Efficient trie-based router for dynamic routes
- Static routes matched via flat arrays for speed
- Route groups, attributes, error routing, and sub-request handling

### 🧵 Middleware Pipeline

- General-purpose pipeline class
- Used in HTTP kernel and route dispatcher for global and route-specific middlewares
- Extensible and reusable for other application pipelines

### 🧱 File Uploads

- Secure file uploader with MIME type checking and extension mapping
- Pluggable processor system per file type

### 🖼️ View & Template System

- Newly added View module consisting of:
  - **Builder**: manages HTML head, metadata, scripts, styles, and body content
  - **Renderer**: renders plain PHP templates, deferred components, and controller-rendered components
  - **ViewFactory**: supports configurable view presets and default setup
- Templates remain simple `.php` files, staying true to Arbor’s micro‑framework philosophy of simplicity and minimal abstraction
- Supports both dumb components (simple includes) and dynamic controller-rendered components
- Developers keep full responsibility for template's structure and cleanliness

### 🧰 Helpers

- Auto-loaded utility functions to ease development

### 📚 Autoloader

- PSR-compliant autoloader
- Supports multiple root directories

### 📦 Database Layer (Implemented)

- SQL-dialect agnostic query builder
- Tree-structured `QueryNode` system
- `Grammar` & `Compiler` for MySQL (PostgreSQL & SQLite support planned)
- Safe value bindings with placeholder parsing
- Connection pool and transformer pipeline

### 🧼 Exception Handling

- Central exception handler
- Graceful error output planned for future versions

### 🔐 Auth & JWT (Under Development)
- Modular Auth system to handle token-based authentication
- Includes:
  - `Auth`: high-level auth manager
  - `JWT`: JSON Web Token encoder/decoder
  - `TokenRefresher`: utility to manage token renewal
  - `SslKeysGenerator`: helper to generate secure SSL keys for signing tokens
- Designed for stateless APIs and easy integration
- Will support middleware integration and user context binding

---

## 🚧 Upcoming Features

These features are planned for upcoming releases of Arbor:

### 📦 ORM & Data Modeling

- Active Record-style base `Model` class
- Relationship types:
  - `HasMany`
  - `BelongsTo`

### 🛠️ Database Migration & Seeder

- Schema versioning
- Migration and seeder tools for development and deployment environments

---

## Installation

```bash
# Clone the repository
git clone https://github.com/unifiedcodes/arbor.git
cd arbor
```

## Usage

Point your web server document root to the `public/` directory.

```php
use Arbor\Autoloader;
use Arbor\bootstrap\App;

require_once '../Arbor/Autoloader.php';
$autoloader = new Autoloader('../');


$app = (new App())
    ->withConfig('../configs/')
    ->onEnvironment('development')
    ->boot();

$response = $app->handleHTTP();
$response->send();
```

## Directory Structure

```
Arbor/                  # Core framework code
├── attributes/         # PHP 8+ attribute handlers (e.g., ConfigValue)
├── bootstrap/          # App bootstrap and environment logic
│   ├── App
│   └── AppConfigScope
├── config/             # Configuration loader
│   └── Config
├── container/          # Dependency Injection Container
│   ├── Container
│   ├── Registry
│   ├── Resolver
│   ├── Providers
│   └── ServiceBond
├── contracts/          # Service and component contracts
│   ├── container/
│   │   ├── ContainerInterface
│   │   └── ServiceProvider
│   ├── metadata/
│   │   └── AttributeInterface
│   ├── handlers/
│   │   ├── ControllerInterface
│   │   └── MiddlewareInterface
│   └── http/
│       ├── RequestStackRO
│       └── RequestStackWR
├── database/           # Database abstraction and ORM
│   ├── connection/
│   │   ├── Connection
│   │   └── ConnectionPool
│   ├── orm/
│   │   ├── Model
│   │   └── relationships/
│   ├── query/
│   │   ├── Builder
│   │   ├── Expression
│   │   ├── Placeholder.php
│   │   ├── grammar/
│   │   │   ├── Grammar
│   │   │   └── MysqlGrammar
│   │   └── helpers/
│   │       ├── WhereTrait
│   │       ├── JoinTrait
│   │       └── HelpersTrait
│   ├── utility/
│   │   ├── GrammarResolver
│   │   └── Placeholders
│   ├── Database
│   ├── PdoDb
│   ├── Migrator
│   └── Seeder
├── facade/             # Facade access layer
│   ├── Facade
│   ├── DB
│   └── Route
│   └── Config
│   └── Container
├── file/               # File upload system
│   └── Uploader
├── fragment/           # Template fragment and view component system
│   └── Fragment
├── http/               # HTTP request/response, context, and kernel
│   ├── components/
│   │   ├── Attributes
│   │   ├── Cookies
│   │   ├── Headers
│   │   ├── Stream
│   │   ├── UploadedFile
│   │   └── Uri
│   ├── traits/
│   │   ├── BodyTrait
│   │   ├── HeaderTrait
│   │   └── ResponseNormalizerTrait
│   ├── context/
│   │   ├── RequestContext
│   │   └── RequestStack
│   ├── HttpKernel
│   ├── HttpSubKernel
│   ├── Request
│   ├── RequestFactory
│   ├── Response
│   └── ServerRequest
├── pipeline/           # Middleware pipeline system
│   ├── Pipeline
│   └── PipelineFactory
├── router/             # Routing system
│   ├── Router
│   ├── Group
│   ├── Meta
│   ├── Node
│   ├── Registry
│   ├── Dispatcher
│   └── URLBuilder
├── support/            # Framework helper utilities
│   ├── Helpers
│   └── helpers/
│       └── common.php
├── view/               # Lightweight View system
│   ├── Builder
│   ├── Renderer
│   └── ViewFactory
├── auth/               # Authentication and JWT system
│   ├── Auth
│   ├── JWT
│   ├── SslKeysGenerator
│   └── TokenRefresher
└── Autoloader.php      # PSR-4-style autoloader

```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add awesome feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request at https://github.com/unifiedcodes/arbor

Bug reports and improvements are welcome via GitHub [Issues](https://github.com/unifiedcodes/arbor/issues)

## Support

- email - info.unifiedcodes@gmail.com
- Whatsapp - +91 - 75 808 908 75

## License

Arbor is licensed under the [Apache License 2.0](LICENSE).
