# Teachify - Educational Management System

<p align="center">
  <img src="https://laravel.com/img/logomark.min.svg" width="100" alt="Laravel Logo">
</p>

<p align="center">
  A comprehensive multi-tenant educational management system built with Laravel 12, featuring modular architecture, channel-based validation, and robust API endpoints.
</p>

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Modules](#modules)
- [API Documentation](#api-documentation)
- [Channel-Based Validation](#channel-based-validation)
- [Development](#development)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## 🎯 About

Teachify is a modern, scalable educational management system designed to handle multiple educational channels (tenants) within a single application. It provides comprehensive features for managing academic years, classes, students, groups, attendance, payments, and more.

### Key Highlights

- ✅ **Multi-Tenant Architecture**: Channel-based system supporting multiple educational institutions
- ✅ **Modular Design**: Clean separation of concerns using Laravel Modules
- ✅ **RESTful API**: Well-structured API endpoints with JWT authentication
- ✅ **Channel-Based Validation**: Automatic channel_id validation for all related models
- ✅ **Repository Pattern**: Clean data access layer using Prettus Repository
- ✅ **Multi-Language Support**: Arabic and English translations

## ✨ Features

### Core Features

- **Channel Management**: Multi-tenant support with isolated data per channel
- **Academic Management**: Academic years, class grades, and stages
- **Student Management**: Student profiles, groups, and attendance tracking
- **Payment System**: Payment months, payments, and financial tracking
- **Session Management**: Class sessions, times, and scheduling
- **User Management**: Role-based access control with JWT authentication
- **Admin Panel**: Administrative interface for system management

### Technical Features

- **Modular Architecture**: Using `nwidart/laravel-modules` for clean code organization
- **Repository Pattern**: Data access abstraction layer
- **Channel Scope**: Automatic data filtering by channel_id
- **Custom Validation Rules**: Reusable validation rules for channel-based checks
- **API Resources**: Consistent API response formatting
- **Event-Driven**: Email verification and notification system

## 🛠️ Technology Stack

### Backend

- **Framework**: Laravel 12
- **PHP**: ^8.2
- **Database**: MySQL/MariaDB (SQLite for development)
- **Authentication**: JWT (tymon/jwt-auth)
- **Modules**: nwidart/laravel-modules ^12.0
- **Repository**: prettus/l5-repository ^3.0

### Development Tools

- **Code Style**: Laravel Pint
- **Testing**: PHPUnit ^11.5.3
- **Logging**: Laravel Pail
- **Queue**: Laravel Queue System

### Frontend (if applicable)

- **Build Tool**: Vite
- **Package Manager**: npm

## 📁 Project Structure

```
teachify/
├── app/                          # Core application code
│   ├── Http/
│   │   ├── Controllers/         # API Controllers
│   │   ├── Requests/            # Form Request Validation
│   │   ├── Resources/           # API Resources
│   │   └── Middleware/          # Custom Middleware
│   ├── Models/                  # Eloquent Models
│   ├── Traits/                  # Reusable Traits
│   └── Rules/                   # Custom Validation Rules
├── modules/                      # Modular components
│   ├── Academic/                # Academic management module
│   │   ├── app/
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   ├── Requests/
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   └── Repositories/
│   │   ├── database/
│   │   ├── routes/
│   │   └── resources/
│   ├── Channel/                 # Channel/tenant management
│   │   ├── app/
│   │   │   ├── Http/
│   │   │   ├── Models/
│   │   │   ├── Rules/           # Channel validation rules
│   │   │   ├── Scopes/          # Channel scope
│   │   │   └── Traits/          # HasChannelScope trait
│   │   └── README.md            # Channel module documentation
│   ├── Admin/                   # Admin panel module
│   └── Core/                    # Core utilities
├── core/                        # Core helpers
│   └── Helpers/                 # Helper functions
├── routes/                       # Route definitions
│   ├── api.php                  # Main API routes
│   └── core/                    # Module-specific routes
├── config/                      # Configuration files
├── database/                    # Migrations and seeders
└── public/                      # Public assets
```

## 🚀 Installation

### Prerequisites

- PHP ^8.2
- Composer
- Node.js & npm (for frontend assets)
- MySQL/MariaDB or SQLite

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-username/teachify.git
cd teachify
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies (if applicable)
npm install
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Database Setup

```bash
# Update .env with your database credentials
# Then run migrations
php artisan migrate

# (Optional) Seed database
php artisan db:seed
```

### Step 5: JWT Configuration

```bash
# Publish JWT config
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# Generate JWT secret
php artisan jwt:secret
```

### Step 6: Module Setup

```bash
# Publish module configuration
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"

# (Optional) Clear cache
php artisan config:clear
php artisan cache:clear
```

## ⚙️ Configuration

### Environment Variables

Key environment variables to configure:

```env
APP_NAME=Teachify
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teachify
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your-jwt-secret-key
JWT_TTL=60
```

### Module Configuration

Modules are configured in `config/modules.php`. Each module has its own configuration file in `modules/{ModuleName}/config/`.

## 📦 Modules

### Academic Module

Manages academic-related entities:

- **Academic Years**: Academic year management
- **Class Grades**: Grade levels and stages (Primary, Preparatory, Secondary)
- **Groups**: Student groups within classes

**Routes**: `/api/v1/academic/`

### Channel Module

Core multi-tenant functionality:

- **Channel Management**: Tenant/channel CRUD operations
- **User Management**: Channel-specific users
- **Channel Scope**: Automatic data isolation
- **Validation Rules**: Channel-based validation

**Key Features**:
- `HasChannelScope` trait for automatic channel filtering
- `BaseRequest` for channel-aware validation
- `BelongsToChannel` validation rule
- `UniqueInChannel` validation rule

**Documentation**: See `modules/Channel/README.md`

### Admin Module

Administrative interface and operations.

### Core Module

Core utilities and shared functionality.

## 📚 API Documentation

### Authentication

All API endpoints (except registration/login) require JWT authentication.

**Headers:**
```
Authorization: Bearer {token}
```

### Base URL

```
http://your-domain.com/api/v1
```

### Endpoints

#### Academic Endpoints

```
GET    /academic/class-grades             # List class grades
POST   /academic/class-grades             # Create class grade
GET    /academic/class-grades/{id}        # Get class grade
PUT    /academic/class-grades/{id}        # Update class grade
DELETE /academic/class-grades/{id}        # Delete class grade
```

#### Channel Endpoints

```
POST   /channel/register                  # Register new channel
POST   /channel/validate-otp              # Validate OTP
```

### Response Format

**Success Response:**
```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": { ... }
}
```

**Error Response:**
```json
{
    "status": "error",
    "message": "Error message",
    "errors": { ... }
}
```

## 🔐 Channel-Based Validation

Teachify includes a powerful channel-based validation system that automatically applies `channel_id` constraints to all related models.

### Quick Start

1. **Extend BaseRequest** in your Request class:

```php
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class YourRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'model_id' => [
                'required',
                $this->belongsToChannel(YourModel::class),
            ],
        ];
    }
}
```

2. **Use belongsToChannel** to validate model ownership:

```php
'subject_id' => [
    'required',
    $this->belongsToChannel(Subject::class),
],
```

3. **Use uniqueInChannel** for uniqueness validation:

```php
'name' => [
    'required',
    $this->uniqueInChannel(YourModel::class, ['name']),
],
```

### Features

- ✅ Automatic `channel_id` validation
- ✅ Supports `HasChannelScope` trait
- ✅ Works with update operations
- ✅ Custom error messages (Arabic & English)

**Full Documentation**: See `modules/Channel/README.md`

## 💻 Development

### Running the Application

```bash
# Start development server
php artisan serve

# Or use the dev script (includes queue, logs, vite)
composer run dev
```

### Code Style

```bash
# Format code using Laravel Pint
./vendor/bin/pint
```

### Module Commands

```bash
# Create new module
php artisan module:make ModuleName

# Generate controller in module
php artisan module:make-controller ControllerName ModuleName

# Generate model in module
php artisan module:make-model ModelName ModuleName
```

### Database Migrations

```bash
# Run migrations for all modules
php artisan migrate

# Run migrations for specific module
php artisan module:migrate ModuleName

# Rollback module migrations
php artisan module:migrate-rollback ModuleName
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Or use composer script
composer run test

# Run tests for specific module
php artisan test --filter ModuleName
```

## 📝 Code Examples

### Creating a Controller with Repository

```php
<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\ClassGradeRepository;

class ClassGradeController extends BaseController
{
    protected ClassGradeRepository $repository;

    public function __construct(ClassGradeRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }
}
```

### Using Channel Scope in Models

```php
<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;

class ClassGrade extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'grade_level',
        'stage',
        'channel_id',
        'academic_year_id',
    ];
}
```

### Custom Validation in Request

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $recordId = $this->route('class_grade') ?? null;

        // Validate uniqueness with channel scope
        $uniqueRule = $this->uniqueInChannel(
            ClassGrade::class,
            ['grade_level', 'stage'],
            $recordId
        );

        $uniqueRule->validate('grade_level', $this->input('grade_level'), function ($message) use ($validator) {
            $validator->errors()->add('grade_level', 'Duplicate record in this channel.');
        });
    });
}
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Authors

- **Teachify Team** - *Initial work*

## 🙏 Acknowledgments

- Laravel Framework
- nwidart/laravel-modules
- prettus/l5-repository
- tymon/jwt-auth
- All contributors and maintainers

## 📞 Support

For support, email support@teachify.com or open an issue in the repository.

---

**Built with ❤️ using Laravel**
