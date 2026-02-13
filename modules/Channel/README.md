# Channel Module - Multi-Tenant Validation System

A comprehensive channel-based validation system for Laravel applications that automatically applies `channel_id` constraints to all related models without requiring manual implementation in every Request class.

## 📋 Table of Contents

- [Overview](#overview)
- [Core Components](#core-components)
- [Installation](#installation)
- [Usage Guide](#usage-guide)
- [Practical Examples](#practical-examples)
- [Features](#features)
- [File Structure](#file-structure)
- [Translation Messages](#translation-messages)
- [Quick Start](#quick-start)
- [Important Notes](#important-notes)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

## 🎯 Overview

The Channel module provides a complete solution for multi-tenant validation in Laravel applications. Instead of writing `channel_id` validation logic in every Request class, you can use `BaseRequest` and custom validation rules that automatically apply channel constraints.

### Problem It Solves

**Before using this system**, you had to write code like this in every Request:

```php
'subject_id' => [
    'required',
    'exists:subjects,id',
    function ($attribute, $value, $fail) {
        $subject = Subject::withoutChannelScope()
            ->where('id', $value)
            ->where('channel_id', auth('user')->user()->channel_id)
            ->first();
        
        if (!$subject) {
            $fail('The subject does not belong to the current channel.');
        }
    },
],
```

**After using this system:**

```php
'subject_id' => [
    'required',
    $this->belongsToChannel(Subject::class),
],
```

### Benefits

- ✅ **DRY Principle**: Write once, use everywhere
- ✅ **Consistency**: Uniform validation across all modules
- ✅ **Maintainability**: Centralized channel validation logic
- ✅ **Type Safety**: Strong typing with IDE support
- ✅ **Automatic**: Works seamlessly with `HasChannelScope` trait

## 🏗️ Core Components

### 1. BaseRequest Class

**Location:** `modules/Channel/app/Http/Requests/V1/BaseRequest.php`

A base class that can be extended by any Request class. Provides helper methods for channel validation.

**Available Methods:**

- `getChannelId()`: Get `channel_id` from authenticated user
- `belongsToChannel()`: Validate that a model ID belongs to the current channel
- `uniqueInChannel()`: Validate uniqueness within the current channel

**Example:**

```php
abstract class BaseRequest extends FormRequest
{
    protected function getChannelId(): ?int
    {
        if (auth("user")->check()) {
            return auth('user')->user()?->channel_id;
        }
        return null;
    }

    protected function belongsToChannel(string $modelClass, ?int $channelId = null): BelongsToChannel
    {
        return new BelongsToChannel($modelClass, $channelId ?? $this->getChannelId());
    }

    protected function uniqueInChannel(string $modelClass, array $columns, $ignoreId = null): UniqueInChannel
    {
        return new UniqueInChannel(
            $modelClass,
            $columns,
            $this->getChannelId(),
            $ignoreId
        );
    }
}
```

### 2. BelongsToChannel Rule

**Location:** `modules/Channel/app/Rules/BelongsToChannel.php`

A custom validation rule that verifies any model ID belongs to the current channel.

**Features:**

- ✅ Automatically checks `channel_id`
- ✅ Supports models using `HasChannelScope` trait
- ✅ Works with models that don't use the trait
- ✅ Handles empty values gracefully

**How It Works:**

1. Gets the current channel ID from authenticated user
2. Checks if the model uses `HasChannelScope` trait
3. Uses `withoutChannelScope()` if needed to bypass global scope
4. Validates that the model exists and belongs to the channel

### 3. UniqueInChannel Rule

**Location:** `modules/Channel/app/Rules/UniqueInChannel.php`

A custom validation rule that checks for uniqueness within the current channel.

**Features:**

- ✅ Checks uniqueness only within the channel
- ✅ Supports composite unique constraints (multiple columns)
- ✅ Supports update operations with record exclusion
- ✅ Automatically handles `HasChannelScope` trait

**How It Works:**

1. Gets the current channel ID from authenticated user
2. Builds a query with `channel_id` constraint
3. Adds conditions for each specified column
4. Excludes current record if `ignoreId` is provided (for updates)
5. Validates uniqueness within the channel scope

## 📦 Installation

The Channel module is part of the Teachify project. If you're using it in a new project:

1. **Copy the module** to your `modules/Channel` directory
2. **Register the service provider** in `bootstrap/providers.php`:

```php
return [
    // ... other providers
    Modules\Channel\App\Providers\ChannelServiceProvider::class,
];
```

3. **Publish translations** (optional):

```bash
php artisan vendor:publish --tag=channel-translations
```

## 📖 Usage Guide

### Step 1: Extend BaseRequest

In any Request class, extend `BaseRequest` instead of `FormRequest`:

```php
<?php

namespace Modules\YourModule\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\YourModule\App\Models\YourModel;

class YourRequest extends BaseRequest
{
    public function authorize()
    {
        return true; // Add policies if needed
    }

    public function rules()
    {
        return [
            // Your validation rules here
        ];
    }
}
```

### Step 2: Use belongsToChannel

To validate that a model ID belongs to the current channel:

```php
public function rules()
{
    return [
        'model_id' => [
            'required',
            $this->belongsToChannel(YourModel::class),
        ],
    ];
}
```

**What it does:**
- Checks if the model with the given ID exists
- Verifies it belongs to the current user's channel
- Returns a validation error if not found or doesn't belong

### Step 3: Use uniqueInChannel

To validate uniqueness within the channel:

#### Option A: In rules() directly (single field)

```php
public function rules()
{
    $recordId = $this->route('your_model') ?? null;

    return [
        'name' => [
            'required',
            'string',
            'max:255',
            $this->uniqueInChannel(YourModel::class, ['name'], $recordId),
        ],
    ];
}
```

#### Option B: In withValidator() (multiple fields)

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $field1 = $this->input('field1');
        $field2 = $this->input('field2');
        $recordId = $this->route('your_model') ?? null;

        if ($field1 && $field2) {
            $uniqueRule = $this->uniqueInChannel(
                YourModel::class,
                ['field1', 'field2'],
                $recordId // Exclude current record when updating
            );

            $uniqueRule->validate('field1', $field1, function ($message) use ($validator) {
                $validator->errors()->add('field1', 'This record already exists in this channel.');
            });
        }
    });
}
```

## 💡 Practical Examples

### Example 1: ClassGradeRequest (Complete Implementation)

```php
<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Academic\App\Models\ClassGrade;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class ClassGradeRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'grade_level' => 'required|integer|min:1|max:12',
            'stage' => 'required|in:primary,preparatory,secondary',
            'is_active' => 'sometimes|boolean'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $gradeLevel = $this->input('grade_level');
            $stage = $this->input('stage');
            $classGradeId = $this->route('class_grade') ?? $this->route('id') ?? null;

            // Verify record belongs to channel in update case
            if ($classGradeId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $channelId = $this->getChannelId();
                if ($channelId) {
                    $existingClassGrade = ClassGrade::withoutChannelScope()
                        ->where('id', $classGradeId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingClassGrade) {
                        $validator->errors()->add(
                            'id',
                            trans('channel::app.common.not_found')
                        );
                        return;
                    }
                }
            }

            // Validate uniqueness (works for both create and update)
            if ($gradeLevel && $stage) {
                $uniqueRule = $this->uniqueInChannel(
                    ClassGrade::class,
                    ['grade_level', 'stage'],
                    $classGradeId
                );

                $uniqueRule->validate('grade_level', $gradeLevel, function ($message) use ($validator, $gradeLevel, $stage) {
                    $validator->errors()->add(
                        'grade_level',
                        trans('academic::app.validation.class_grade_duplicate', [
                            'grade_level' => $gradeLevel,
                            'stage' => $stage
                        ])
                    );
                });
            }
        });
    }
}
```

### Example 2: Simple Product Request

```php
<?php

namespace Modules\YourModule\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\YourModule\App\Models\Category;
use Modules\YourModule\App\Models\Product;

class ProductRequest extends BaseRequest
{
    public function rules()
    {
        $productId = $this->route('product') ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $this->uniqueInChannel(Product::class, ['name'], $productId),
            ],
            'category_id' => [
                'required',
                $this->belongsToChannel(Category::class),
            ],
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }
}
```

### Example 3: Request with Composite Unique Constraint

```php
<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Academic\App\Models\Schedule;

class ScheduleRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday',
            'time' => 'required|date_format:H:i',
            'group_id' => [
                'required',
                $this->belongsToChannel(Group::class),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $day = $this->input('day');
            $time = $this->input('time');
            $groupId = $this->input('group_id');
            $scheduleId = $this->route('schedule') ?? null;

            if ($day && $time && $groupId) {
                $uniqueRule = $this->uniqueInChannel(
                    Schedule::class,
                    ['day', 'time', 'group_id'],
                    $scheduleId
                );

                $uniqueRule->validate('day', $day, function ($message) use ($validator) {
                    $validator->errors()->add(
                        'day',
                        'A schedule already exists for this day, time, and group in this channel.'
                    );
                });
            }
        });
    }
}
```

## ✨ Features

### 1. Automatic channel_id Validation

- ✅ No need to write `channel_id` condition in every Request
- ✅ Automatically applied to all channel-related models
- ✅ Works with both `HasChannelScope` and regular models

### 2. HasChannelScope Trait Support

- ✅ Automatically works with models using `HasChannelScope`
- ✅ Uses `withoutChannelScope()` when needed
- ✅ Handles global scope bypassing transparently

### 3. Update Operation Support

- ✅ Can exclude current record from validation when updating
- ✅ Verifies record belongs to channel before update
- ✅ Prevents cross-channel data manipulation

### 4. Custom Error Messages

- ✅ Error messages in Arabic and English
- ✅ Customizable messages per module
- ✅ Uses Laravel translation system

### 5. Composite Unique Constraints

- ✅ Validate uniqueness across multiple columns
- ✅ Perfect for complex business rules
- ✅ Maintains data integrity within channels

## 📁 File Structure

```
modules/Channel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── V1/
│   │   │       ├── BaseController.php       # Base controller with repository pattern
│   │   │       └── ChannelController.php   # Channel management controller
│   │   └── Requests/
│   │       └── V1/
│   │           ├── BaseRequest.php         # Base request class ⭐
│   │           └── RegisterRequest.php    # Registration request
│   ├── Models/
│   │   ├── Channel.php                    # Channel model
│   │   ├── User.php                       # User model
│   │   └── Role.php                       # Role model
│   ├── Rules/
│   │   ├── BelongsToChannel.php           # Channel ownership validation ⭐
│   │   └── UniqueInChannel.php           # Uniqueness validation ⭐
│   ├── Scopes/
│   │   └── ChannelScope.php              # Global scope for channel filtering
│   ├── Traits/
│   │   └── HasChannelScope.php           # Trait for channel-scoped models
│   ├── Events/
│   │   └── UserRegistered.php            # User registration event
│   ├── Jobs/
│   │   └── SendEmailVerificationJob.php  # Email verification job
│   └── Providers/
│       └── ChannelServiceProvider.php    # Service provider
├── database/
│   ├── migrations/                        # Database migrations
│   └── seeders/                          # Database seeders
├── resources/
│   └── lang/
│       ├── ar/
│       │   └── app.php                   # Arabic translations
│       └── en/
│           └── app.php                   # English translations
├── routes/
│   └── api-v1.php                        # API routes
└── README.md                             # This file
```

⭐ = Core validation components

## 🔧 Translation Messages

### Channel Module Messages

**English** (`resources/lang/en/app.php`):

```php
'validation' => [
    'model_not_belongs_to_channel' => 'The selected :model does not belong to the current channel.',
    'unique_in_channel' => 'This record already exists in this channel.',
],

'common' => [
    'operation_failed' => 'Something went wrong! Please try again.',
    'not_found' => 'Resource not found.',
    'show_success' => 'Resource displayed successfully.',
    'list_success' => 'List displayed successfully.',
],
```

**Arabic** (`resources/lang/ar/app.php`):

```php
'validation' => [
    'model_not_belongs_to_channel' => 'الـ :model المحدد غير مرتبط بالقناة الحالية.',
    'unique_in_channel' => 'هذا السجل موجود بالفعل في هذه القناة.',
],

'common' => [
    'operation_failed' => 'حدث خطأ ما! يرجى المحاولة مرة أخرى.',
    'not_found' => 'المورد غير موجود.',
    'show_success' => 'تم عرض المورد بنجاح.',
    'list_success' => 'تم عرض القائمة بنجاح.',
],
```

### Customizing Messages

You can override messages in your module's translation files:

```php
// modules/YourModule/resources/lang/en/app.php
'validation' => [
    'custom_message' => 'Your custom validation message.',
],
```

## 🚀 Quick Start

### 1. Extend BaseRequest in your Request class:

```php
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class YourRequest extends BaseRequest
{
    // Your code here
}
```

### 2. Use belongsToChannel:

```php
'model_id' => [
    'required',
    $this->belongsToChannel(YourModel::class),
],
```

### 3. Use uniqueInChannel:

```php
'field' => [
    'required',
    $this->uniqueInChannel(YourModel::class, ['field']),
],
```

That's it! The channel validation is now automatic.

## 📝 Important Notes

### 1. Authentication Required

The system relies on `auth('user')->user()->channel_id`, so the user must be authenticated. If no user is authenticated, validation will be skipped.

### 2. HasChannelScope Trait

If a model uses the `HasChannelScope` trait, the system will automatically use `withoutChannelScope()` to bypass the global scope when needed.

**Example:**

```php
use Modules\Channel\App\Traits\HasChannelScope;

class Subject extends Model
{
    use HasChannelScope;
    // ...
}
```

### 3. Update Operations

When updating, always pass the `$recordId` to `uniqueInChannel()` to exclude the current record:

```php
$recordId = $this->route('model_name') ?? null;

$this->uniqueInChannel(YourModel::class, ['field'], $recordId)
```

### 4. Route Parameters

Use `$this->route('model_name')` to get the ID from the route. The parameter name should match your route definition:

```php
// Route: PUT /api/v1/models/{model}
$id = $this->route('model'); // ✅ Correct

// Route: PUT /api/v1/models/{id}
$id = $this->route('id'); // ✅ Correct
```

### 5. Multiple Column Validation

For composite unique constraints, use `withValidator()`:

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $uniqueRule = $this->uniqueInChannel(
            YourModel::class,
            ['column1', 'column2', 'column3'],
            $this->route('model')
        );
        
        $uniqueRule->validate('column1', $this->input('column1'), function ($message) use ($validator) {
            $validator->errors()->add('column1', 'Duplicate record.');
        });
    });
}
```

## 🔍 Troubleshooting

### Issue: Validation not working

**Solution:**
- Ensure user is authenticated: `auth('user')->check()`
- Verify `channel_id` exists on the user model
- Check that the model has `channel_id` column

### Issue: "Model not found" even though it exists

**Solution:**
- The model might belong to a different channel
- Check if model uses `HasChannelScope` and scope is interfering
- Use `withoutChannelScope()` in your query if needed

### Issue: Unique validation not excluding current record

**Solution:**
- Make sure you're passing the record ID: `$this->route('model_name')`
- Verify the route parameter name matches your route definition
- Check that the ID is not null in update operations

### Issue: Translation messages not showing

**Solution:**
- Clear config cache: `php artisan config:clear`
- Verify translation files exist in `resources/lang/{locale}/app.php`
- Check namespace: `trans('channel::app.validation.message')`

## 🤝 Contributing

We welcome contributions! To add new features or improvements:

1. **Create an Issue** describing the feature or bug
2. **Fork the repository** and create a feature branch
3. **Make your changes** following PSR-12 coding standards
4. **Add tests** if applicable
5. **Update documentation** (this README)
6. **Create a Pull Request** with a clear description

### Code Style

- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Write meaningful commit messages
- Add PHPDoc comments for public methods

## 📄 License

This module is part of the Teachify project and is licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Authors

- **Teachify Team** - *Initial work and maintenance*

## 🙏 Acknowledgments

- Laravel Framework
- nwidart/laravel-modules
- All contributors and maintainers

## 📞 Support

For support, questions, or feature requests:

- Open an issue in the repository
- Contact the development team
- Check the main project documentation

---

**Built with ❤️ for the Teachify project**

**Last Updated:** 2026
