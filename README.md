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
- URL resolution and environment-aware bootstrapping

### 🧠 Dependency Injection & Container

- Fast and flexible DI container with `ServiceContainer`
- Attribute injection supported via `ConfigValue` attribute
- Provider system for lazy-loaded services with `Providers` and `Registry`
- Service resolution with `Resolver` and `ServiceBond`
- Contextual resolution support

### 🔄 Configuration System

- Environment-aware config loading from PHP files via `Configurator`
- Supports app-specific overrides and merged configs
- Attribute-based configuration injection

### 🔌 Service Contracts

- All critical services (HTTP, Router, Container, etc.) are abstracted with interfaces
- Easy to swap implementations or mock in testing
- Comprehensive contract system covering containers, handlers, HTTP, metadata, sessions, and validation

### 🌐 HTTP Lifecycle

- Fully-featured HTTP stack inspired by PSR standards
- `Request`, `ServerRequest`, `Response`, `UploadedFile`, `Streams`, `Cookies`, `Headers`
- RequestContext, RequestStack, and SubKernel support for advanced routing scenarios
- HTTP client for external API communication
- Response factory for streamlined response creation

### 🧭 Fragment System

- Fragment engine for internally calling Controllers with or without a parent HTTP request context

### 🧩 Routing System

- Efficient trie-based router for dynamic routes
- Static routes matched via flat arrays for speed
- Route groups, attributes, error routing, and sub-request handling
- URL building and route method management
- Advanced route dispatching and metadata handling

### 🧵 Middleware Pipeline

- General-purpose pipeline class
- Used in HTTP kernel and route dispatcher for global and route-specific middlewares
- Extensible and reusable for other application pipelines
- Pipeline factory for creating configured pipelines

### 🧱 File Uploads

- Secure file uploader with MIME type checking and extension mapping
- Pluggable processor system per file type (e.g., `ImageProcessor`)
- Contract-based file processing interface

### 🖼️ View & Template System

- Comprehensive View module consisting of:
  - **Builder**: manages HTML head, metadata, scripts, styles, and body content
  - **Renderer**: renders plain PHP templates, deferred components, and controller-rendered components
  - **ViewFactory**: supports configurable view presets and default setup
- Templates remain simple `.php` files, staying true to Arbor's micro‑framework philosophy
- Supports both dumb components (simple includes) and dynamic controller-rendered components

### 💨 Flash Messaging System

- Complete flash messaging system with `Flasher`, `Message`, and `View` components
- Session-based message persistence across requests
- Flexible message formatting and display

### 🔍 Filtering & Pipeline System

- Advanced filtering system with `Filters`, `Registry`, and `StageList`
- Contract-based stage interfaces for extensible filtering
- Multi-stage filtering pipeline support

### ✅ Validation System

- Comprehensive validation framework with:
  - `Validator` and `ValidatorFactory` for validation orchestration
  - `Definition` and `Parser` for validation rule definition
  - `Evaluator` for rule execution
  - `ErrorsFormatter` for user-friendly error messages
  - `RuleList` and `Registry` for rule management
- Contract-based rule interface for custom validation rules
- Detailed validation exception handling

### 🧰 Helpers

- Auto-loaded utility functions to ease development
- URL helpers and common utility functions

### 📚 Autoloader

- PSR-compliant autoloader
- Supports multiple root directories
- Integrated with bootstrap system

### 📦 Database Layer & ORM

- **Complete ORM Implementation**:

  - `BaseModel` and `Model` classes for Active Record pattern
  - `ModelQuery` for eloquent-style query building
  - `AttributesTrait` for model attribute management
  - `Pivot` model for many-to-many relationships

- **Full Relationship Support**:

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

### 🧼 Exception Handling

- Central exception handler
- Validation-specific exception handling
- Graceful error output and formatting

### 🔐 Authentication & JWT

- Complete Auth system with:
  - `Auth`: high-level authentication manager
  - `Guard`: authentication guard system
  - `JWT`: JSON Web Token encoder/decoder
  - `TokenRefresher`: utility to manage token renewal
  - `SslKeysGenerator`: helper to generate secure SSL keys for signing tokens
- Designed for stateless APIs and easy integration
- Middleware integration and user context binding

### 🎭 Session Management

- Full session handling with `Session` class
- Contract-based session interface for custom implementations
- Integrated with flash messaging and authentication systems

### 🛡️ Role-Based Access Control (RBAC)

- Route-based permission mapping with `RoutePermissionMap`
- Integration with authentication system
- Fine-grained access control

### 🎯 Facade System

- facades for major components:
  - `Config` - Configuration access
  - `Container` - Dependency injection container
  - `DB` - Database operations
  - `Route` - Routing operations
  - `Session` - Session management
  - `Flash` - Flash messaging
- Simplified static access to framework services

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

## Directory Structure

```
src/                    # Core framework code
├── attributes/         # PHP 8+ attribute handlers
│   └── ConfigValue.php
├── auth/              # Authentication and JWT system
│   ├── Auth.php
│   ├── Guard.php
│   ├── JWT.php
│   ├── SslKeysGenerator.php
│   └── TokenRefresher.php
├── bootstrap/         # App bootstrap and environment logic
│   ├── App.php
│   ├── AppConfigScope.php
│   ├── Autoloader.php
│   └── URLResolver.php
├── config/           # Configuration system
│   └── Configurator.php
├── container/        # Dependency Injection Container
│   ├── Providers.php
│   ├── Registry.php
│   ├── Resolver.php
│   ├── ServiceBond.php
│   └── ServiceContainer.php
├── contracts/        # Service and component contracts
│   ├── container/
│   │   ├── ContainerInterface.php
│   │   └── ServiceProvider.php
│   ├── file/
│   │   └── FileProcessorInterface.php
│   ├── filters/
│   │   ├── StageInterface.php
│   │   └── StageListInterface.php
│   ├── handlers/
│   │   ├── Controller.php
│   │   ├── ControllerInterface.php
│   │   └── MiddlewareInterface.php
│   ├── http/
│   │   ├── RequestStackRO.php
│   │   └── RequestStackWR.php
│   ├── metadata/
│   │   └── AttributeInterface.php
│   ├── session/
│   │   └── SessionInterface.php
│   └── validation/
│       ├── RuleInterface.php
│       └── RuleListInterface.php
├── database/         # Database abstraction and ORM
│   ├── connection/
│   │   ├── Connection.php
│   │   └── ConnectionPool.php
│   ├── orm/
│   │   ├── AttributesTrait.php
│   │   ├── BaseModel.php
│   │   ├── Model.php
│   │   ├── ModelQuery.php
│   │   ├── Pivot.php
│   │   └── relations/
│   │       ├── BelongsTo.php
│   │       ├── BelongsToMany.php
│   │       ├── HasMany.php
│   │       ├── HasOne.php
│   │       ├── MorphMany.php
│   │       ├── MorphOne.php
│   │       ├── MorphToMany.php
│   │       └── Relationship.php
│   ├── query/
│   │   ├── Builder.php
│   │   ├── Expression.php
│   │   ├── Placeholder.php
│   │   ├── grammar/
│   │   │   ├── Grammar.php
│   │   │   └── MysqlGrammar.php
│   │   └── helpers/
│   │       ├── HelpersTrait.php
│   │       ├── JoinTrait.php
│   │       └── WhereTrait.php
│   ├── schema/
│   ├── utility/
│   │   ├── GrammarResolver.php
│   │   └── PlaceholderParser.php
│   ├── Database.php
│   ├── DatabaseResolver.php
│   ├── PdoDb.php
│   └── QueryBuilder.php
├── facades/          # Facade access layer
│   ├── Config.php
│   ├── Container.php
│   ├── DB.php
│   ├── Facade.php
│   ├── Flash.php
│   ├── Route.php
│   └── Session.php
├── file/             # File upload system
│   ├── processors/
│   │   └── ImageProcessor.php
│   └── Uploader.php
├── filters/          # Advanced filtering system
│   ├── Filters.php
│   ├── Registry.php
│   └── StageList.php
├── flash/           # Flash messaging system
│   ├── Flasher.php
│   ├── Message.php
│   └── View.php
├── fragment/        # Template fragment system
│   └── Fragment.php
├── http/           # HTTP request/response and kernel
│   ├── client/
│   │   └── Client.php
│   ├── components/
│   │   ├── Attributes.php
│   │   ├── Cookies.php
│   │   ├── Headers.php
│   │   ├── Stream.php
│   │   ├── UploadedFile.php
│   │   └── Uri.php
│   ├── context/
│   │   ├── RequestContext.php
│   │   └── RequestStack.php
│   ├── traits/
│   │   ├── BodyTrait.php
│   │   ├── HeaderTrait.php
│   │   └── ResponseNormalizerTrait.php
│   ├── HttpKernel.php
│   ├── HttpSubKernel.php
│   ├── Request.php
│   ├── RequestFactory.php
│   ├── Response.php
│   ├── ResponseFactory.php
│   └── ServerRequest.php
├── pipeline/        # Middleware pipeline system
│   ├── Pipeline.php
│   └── PipelineFactory.php
├── rbac/           # Role-Based Access Control
│   └── RoutePermissionMap.php
├── router/         # Routing system
│   ├── Dispatcher.php
│   ├── Group.php
│   ├── Meta.php
│   ├── Node.php
│   ├── Registry.php
│   ├── RouteMethods.php
│   ├── Router.php
│   └── URLBuilder.php
├── session/        # Session management
│   └── Session.php
├── support/        # Framework helper utilities
│   ├── helpers/
│   │   ├── common.php
│   │   └── url.php
│   └── Helpers.php
├── validation/     # Comprehensive validation system
│   ├── Definition.php
│   ├── ErrorsFormatter.php
│   ├── Evaluator.php
│   ├── Parser.php
│   ├── Registry.php
│   ├── RuleList.php
│   ├── ValidationException.php
│   ├── Validator.php
│   └── ValidatorFactory.php
└── view/          # View and template system
    ├── Builder.php
    ├── Renderer.php
    └── ViewFactory.php
```

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
