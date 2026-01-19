# Users & Roles Management Guide

This guide explains how to use the Users and Roles CRUD operations in the Channel module.

## 📋 Overview

The system provides complete CRUD operations for managing users and roles within channels. All operations are automatically scoped to the current channel, ensuring data isolation.

## 🔐 Authentication

All endpoints require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer {your_jwt_token}
```

## 👥 Users Management

### Endpoints

- `GET /api/v1/users` - List all users in current channel
- `POST /api/v1/users` - Create a new user
- `GET /api/v1/users/{id}` - Get user details
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user

### Create User

**Request:**
```json
POST /api/v1/users
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "gender": "male",
    "password": "password123",
    "role_id": 2,
    "status": 1
}
```

**Response:**
```json
{
    "status": "success",
    "message": "User created successfully.",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "1234567890",
        "gender": "male",
        "status": 1,
        "role": {
            "id": 2,
            "name": "teacher",
            "description": "Teacher with access to students and lessons"
        },
        "channel": {
            "id": 1,
            "name": "My Channel",
            "code": "CH001"
        }
    }
}
```

### Update User

**Request:**
```json
PUT /api/v1/users/1
{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "phone": "1234567890",
    "gender": "male",
    "role_id": 2,
    "status": 1
}
```

**Note:** Password is optional on update. If not provided, it won't be changed.

### Delete User

**Request:**
```
DELETE /api/v1/users/1
```

**Note:** You cannot delete your own account.

## 🎭 Roles Management

### Endpoints

- `GET /api/v1/roles` - List all roles
- `POST /api/v1/roles` - Create a new role
- `GET /api/v1/roles/{id}` - Get role details
- `PUT /api/v1/roles/{id}` - Update role
- `DELETE /api/v1/roles/{id}` - Delete role

### Create Role

**Request:**
```json
POST /api/v1/roles
{
    "name": "custom_role",
    "description": "Custom role with specific permissions",
    "permissions": [
        "students.view",
        "students.create",
        "lessons.view"
    ]
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Role created successfully.",
    "data": {
        "id": 5,
        "name": "custom_role",
        "description": "Custom role with specific permissions",
        "permissions": [
            "students.view",
            "students.create",
            "lessons.view"
        ]
    }
}
```

### System Roles

The following roles are system roles and cannot be modified or deleted:

- `owner` - Channel owner with full access
- `teacher` - Teacher with access to students and lessons
- `assistant` - Assistant with limited permissions
- `viewer` - Read-only access

### Permissions Format

Permissions should be an array of strings. Common permissions:

- `students.view`
- `students.create`
- `students.update`
- `students.delete`
- `lessons.view`
- `lessons.create`
- `lessons.update`
- `attendance.view`
- `attendance.manage`
- `reports.view`

For full access, use `"all"` or `["all"]`.

## 🔒 Channel Scoping

All operations are automatically scoped to the current channel:

- Users can only see/manage users in their channel
- Users can only be created in the current channel
- Channel ID is automatically set from the authenticated user

## ✅ Validation Rules

### User Validation

- `name`: Required, string, max 255
- `email`: Required, email, unique in channel
- `phone`: Required, string, max 20, unique in channel
- `gender`: Required, in: male, female
- `password`: Required on create, optional on update, min 6 characters
- `role_id`: Required, must exist in roles table
- `status`: Optional, integer, in: 0, 1

### Role Validation

- `name`: Required, string, max 255, unique
- `description`: Optional, string, max 500
- `permissions`: Required, array of strings

## 🛡️ Security Features

1. **Channel Isolation**: All users are automatically assigned to the current channel
2. **Self-Protection**: Users cannot delete their own accounts
3. **System Role Protection**: System roles cannot be modified or deleted
4. **Role Assignment Check**: Cannot delete roles assigned to users

## 💡 Usage Examples

### Check User Permissions

```php
$user = User::find(1);

// Check single permission
if ($user->hasPermission('students.create')) {
    // User can create students
}

// Check multiple permissions (any)
if ($user->hasAnyPermission(['students.view', 'students.create'])) {
    // User has at least one permission
}

// Check multiple permissions (all)
if ($user->hasAllPermissions(['students.view', 'students.create'])) {
    // User has all permissions
}

// Check if owner
if ($user->isOwner()) {
    // User is channel owner
}
```

### Check Role Permissions

```php
$role = Role::find(1);

// Check permission
if ($role->hasPermission('students.create')) {
    // Role has permission
}

// Check if system role
if ($role->isSystemRole()) {
    // This is a system role
}
```

## 📝 Error Messages

### User Errors

- `User not found` - User doesn't exist or doesn't belong to channel
- `You cannot delete your own account` - Attempting to delete yourself
- `The selected role does not exist` - Invalid role_id

### Role Errors

- `System roles cannot be modified` - Attempting to modify owner role
- `System roles cannot be deleted` - Attempting to delete system role
- `Cannot delete role. It is assigned to X user(s)` - Role is in use
- `Invalid permission format` - Permissions must be array of strings

## 🚀 Quick Start

1. **List Users:**
```bash
GET /api/v1/users
```

2. **Create User:**
```bash
POST /api/v1/users
Content-Type: application/json
Authorization: Bearer {token}

{
    "name": "New User",
    "email": "user@example.com",
    "phone": "1234567890",
    "gender": "male",
    "password": "password123",
    "role_id": 2
}
```

3. **List Roles:**
```bash
GET /api/v1/roles
```

4. **Create Role:**
```bash
POST /api/v1/roles
Content-Type: application/json
Authorization: Bearer {token}

{
    "name": "custom_role",
    "description": "Custom role",
    "permissions": ["students.view", "students.create"]
}
```

---

**For more information, see the main README.md file.**

