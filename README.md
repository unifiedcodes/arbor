<div align="center">

# Arbor

_A modular yet minimal PHP framework for fast, modern application development._

🌐 [Website](https://unifiedcodes.com/arbor) -
📖 [Docs](https://unifiedcodes.com/arbor/docs) -
💡 [Examples](https://unifiedcodes.com/arbor/examples) -
👤 [About Author](https://unifiedcodes.com/arbor/author)

</div>

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
- [Features](#features)
  - [Bootstrap & Environment Handling](#️-bootstrap--environment-handling)
  - [Dependency Injection & Container](#-dependency-injection--container)
  - [Configuration System](#-configuration-system)
  - [Service Contracts](#-service-contracts)
  - [HTTP Lifecycle](#-http-lifecycle)
  - [Fragment System](#-fragment-system)
  - [Routing System](#-routing-system)
  - [Middleware Pipeline](#-middleware-pipeline)
  - [File Uploads](#-file-uploads)
  - [View & Template System](#️-view--template-system)
  - [Flash Messaging System](#-flash-messaging-system)
  - [Filtering & Pipeline System](#-filtering--pipeline-system)
  - [Validation System](#-validation-system)
  - [Helpers](#-helpers)
  - [Autoloader (legacy)](#-autoloader-legacy-to-be-use-only-when-composer-is-not-used)
  - [Database Layer & ORM](#-database-layer--orm)
  - [Exception Handling](#-exception-handling)
  - [Session Management](#-session-management)
  - [Facade System](#-facade-system)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

## Introduction

Arbor is a lightweight, highly modular PHP framework designed to give you full control over HTTP routing, middleware, dependency injection, configuration and more, while remaining fast and easy to extend.

## Installation

#### Clone the repository

```bash
git clone https://github.com/unifiedcodes/arbor.git
cd arbor
```

#### Or use composer

```bash
composer require unifiedcodes/arbor
cd arbor
```

## Usage

Point your web server document root to the `public/` directory.

```php
use Arbor\bootstrap\App;

require_once '../vendor/autoload.php';

$autoloader = new Autoloader('../src/');

$app = (new App())
    ->withConfig('../configs/')
    ->onEnvironment('development')
    ->boot();

$response = $app->handleHTTP();
$response->send();
```

## Features

### Bootstrap & Environment Handling

- Centralized entry point via `Arbor\bootstrap\App`
- Fluent API to configure app, load environment-specific configs, and boot services
- Isolated app modules (e.g., `admin/`, `web/`) with independent routes, configs, uploads, providers
- URL resolution and environment-aware bootstrapping
- Configuration scope from globals defaults to app specific to environment specific.

### Dependency Injection & Container

- Fast and flexible DI container with `ServiceContainer`
- Attribute injection supported via `ConfigValue` attribute
- Provider system for lazy-loaded services with `Providers` and `Registry`
- Service resolution with `Resolver` and `ServiceBond`
- Contextual resolution support

### Configuration System

- Environment-aware config loading from PHP files via `Configurator`
- Supports app-specific overrides and merged configs
- Attribute-based configuration injection

### Service Contracts

- All critical services (HTTP, Router, Container, etc.) are abstracted with interfaces
- Easy to swap implementations or mock in testing
- Comprehensive contract system covering containers, handlers, HTTP, metadata, sessions, and validation

### HTTP Lifecycle

- Fully-featured HTTP stack inspired by PSR standards
- `Request`, `ServerRequest`, `Response`, `UploadedFile`, `Streams`, `Cookies`, `Headers`
- RequestContext, RequestStack, and SubKernel support for advanced routing scenarios
- HTTP client for external API communication
- Response factory for streamlined response creation

### Fragment System

- Fragment engine for internally calling Controllers with or without a parent HTTP request context

### Routing System

- Efficient trie-based router for dynamic routes
- Route groups, attributes, error routing, and sub-request handling
- URL building and route method management
- Advanced route dispatching and metadata handling

### Middleware Pipeline

- General-purpose pipeline class
- Used in HTTP kernel and route dispatcher for global and route-specific middlewares
- Extensible and reusable for other application pipelines

### File Uploads

- Secure file uploader.
- Pluggable processor system per file type (e.g., `ImageProcessor`)
- Contract-based file processing interface

### View & Template System

- Comprehensive View module
- Templates remain simple `.php` files, staying true to Arbor's minimal‑framework philosophy
- Supports both dumb components (simple includes) and dynamic controller-rendered components

### Flash Messaging System

- Complete flash messaging system with `Flasher`, `Message`, and `View` components
- Session-based message persistence across requests
- Flexible message formatting and display

### Filtering & Pipeline System

- Advanced filtering system with `Filters`, `Registry`, and `StageList`
- Contract-based stage interfaces for extensible filtering
- Multi-stage filtering pipeline support

### Validation System

- Comprehensive validation framework with:
  - `Validator` and `ValidatorFactory` for validation orchestration
  - `Definition` and `Parser` for validation rule definition
  - `Evaluator` for rule execution
  - `ErrorsFormatter` for user-friendly error messages
  - `RuleList` and `Registry` for rule management
- Contract-based rule interface for custom validation rules
- Detailed validation exception handling

### Helpers

- Auto-loaded utility functions to ease development
- URL helpers and common utility functions

### Autoloader (legacy, to be use only when composer is not used)

- PSR-compliant autoloader
- Supports multiple root directories
- Integrated with bootstrap system

### Database Layer & ORM

- **Essential ORM Implementation**:

  - `BaseModel` and `Model` classes for Active Record pattern
  - `ModelQuery` for eloquent-style query building
  - `AttributesTrait` for model attribute management
  - `Pivot` model for many-to-many relationships

- **Relationship Support**:

  - `HasOne` - One-to-one relationships
  - `HasMany` - One-to-many relationships
  - `BelongsTo` - Inverse one-to-many relationships
  - `BelongsToMany` - Many-to-many relationships
  - `MorphOne` - Polymorphic one-to-one relationships
  - `MorphMany` - Polymorphic one-to-many relationships
  - `MorphToMany` - Polymorphic many-to-many relationships

- **Query System**:

  - SQL-dialect agnostic query builder
  - `Grammar` & `Compiler` for MySQL (PostgreSQL & SQLite support coming soon)
  - Safe value bindings with `PlaceholderParser`
  - Connection pool and transformer pipeline
  - Database resolver for multiple connection management

### Exception Handling

- Central exception handler
- Validation-specific exception handling
- Graceful error output and formatting

### Session Management

- Full session handling with `Session` class
- Contract-based session interface for custom implementations
- Integrated with flash messaging and authentication systems

### Facade System

- facades for major components:
  - `Config` - Configuration access
  - `Container` - Dependency injection container
  - `DB` - Database operations
  - `Route` - Routing operations
  - `Session` - Session management
  - `Flash` - Flash messaging
- Simplified static access to framework services

---

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add awesome feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request at https://github.com/unifiedcodes/arbor

Bug reports and improvements are welcome via GitHub [Issues](https://github.com/unifiedcodes/arbor/issues)

## Support

- Email - info.unifiedcodes@gmail.com
- WhatsApp - +91 75 808 908 75

## License

Arbor is licensed under the [Apache License 2.0](LICENSE).
