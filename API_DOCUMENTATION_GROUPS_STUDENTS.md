# Groups & Students API Documentation

## Overview

This document provides comprehensive API documentation for Groups and Students management endpoints. These endpoints allow you to create, read, update, and delete groups and students, with support for linking students to groups and vice versa.

## Base URL

```
http://your-domain/api/v1
```

## Authentication

All endpoints require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer {your-token}
```

---

## Groups API

### 1. Get Groups Metadata

Get metadata required for creating/updating groups (class grades, subjects, students).

**Endpoint:** `GET /api/v1/academic/groups-metadata`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "message": "Metadata retrieved successfully",
  "data": {
    "class_grades": [
      {
        "id": 1,
        "name": "Grade 1 - Primary",
        "grade_level": 1,
        "stage": "primary"
      }
    ],
    "subjects": [
      {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH-001"
      }
    ],
    "students": [
      {
        "id": 1,
        "name": "Ahmed Ali",
        "code": "STU-000001"
      }
    ]
  }
}
```

### 2. List Groups

Get a paginated list of groups with optional filters.

**Endpoint:** `GET /api/v1/academic/groups`

**Query Parameters:**
- `class_grade_id` (optional): Filter by class grade ID
- `subject_id` (optional): Filter by subject ID
- `is_active` (optional): Filter by active status (true/false)
- `per_page` (optional): Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "message": "Groups list retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Math Group A",
      "code": "GRP-0001",
      "class_grade_id": 1,
      "class_grade": {
        "id": 1,
        "grade_level": 1,
        "stage": "primary"
      },
      "subject_id": 1,
      "subject": {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH-001"
      },
      "capacity": 30,
      "price": 500.00,
      "is_active": true,
      "sessions_count": 5,
      "students_count": 25,
      "students": [
        {
          "id": 1,
          "name": "Ahmed Ali",
          "code": "STU-000001"
        }
      ],
      "created_at": "2024-01-01 00:00:00",
      "updated_at": "2024-01-01 00:00:00",
      "channel_id": 1
    }
  ]
}
```

### 3. Create Group

Create a new group with optional students.

**Endpoint:** `POST /api/v1/academic/groups`

**Request Body:**
```json
{
  "name": "Math Group A",
  "class_grade_id": 1,
  "subject_id": 1,
  "capacity": 30,
  "price": 500.00,
  "is_active": true,
  "code": "GRP-0001",
  "student_ids": [1, 2, 3],
  "session_times": [
    {
      "day": "monday",
      "start_time": "09:00",
      "end_time": "10:30",
      "is_active": true
    },
    {
      "day": "wednesday",
      "start_time": "09:00",
      "end_time": "10:30",
      "is_active": true
    }
  ]
}
```

**Field Descriptions:**
- `name` (required): Group name
- `class_grade_id` (required): ID of the class grade
- `subject_id` (required): ID of the subject
- `capacity` (required): Maximum number of students (1-100)
- `price` (optional): Group price
- `is_active` (optional): Active status (default: true)
- `code` (optional): Group code (auto-generated if not provided)
- `student_ids` (optional): Array of student IDs to add to the group
- `session_times` (optional): Array of session time objects:
  - `day` (required): Day of week (saturday, sunday, monday, tuesday, wednesday, thursday, friday)
  - `start_time` (required): Start time in format HH:mm (e.g., "09:00")
  - `end_time` (required): End time in format HH:mm, must be after start_time
  - `is_active` (optional): Active status (default: true)

**Session Time Conflict Validation:**
- The system automatically checks for time conflicts
- If a session time overlaps with another session on the same day, validation will fail
- Conflicts are checked both within the same request and against existing sessions in the database

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Group created successfully",
  "data": {
    "id": 1,
    "name": "Math Group A",
    "code": "GRP-0001",
    "class_grade_id": 1,
    "subject_id": 1,
    "capacity": 30,
    "price": 500.00,
    "is_active": true,
      "students": [
        {
          "id": 1,
          "name": "Ahmed Ali",
          "code": "STU-000001"
        }
      ],
      "sessions": [
        {
          "id": 1,
          "day": "monday",
          "start_time": "09:00",
          "end_time": "10:30",
          "is_active": true
        }
      ],
      "sessions_count": 2,
      "students_count": 3,
    "created_at": "2024-01-01 00:00:00",
    "updated_at": "2024-01-01 00:00:00",
    "channel_id": 1
  }
}
```

### 4. Get Group Details

Get detailed information about a specific group.

**Endpoint:** `GET /api/v1/academic/groups/{id}`

**Response:**
```json
{
  "status": "success",
  "message": "Group retrieved successfully",
  "data": {
    "id": 1,
    "name": "Math Group A",
    "code": "GRP-0001",
    "class_grade_id": 1,
    "subject_id": 1,
    "capacity": 30,
    "price": 500.00,
    "is_active": true,
      "students": [
        {
          "id": 1,
          "name": "Ahmed Ali",
          "code": "STU-000001"
        }
      ],
      "sessions": [
        {
          "id": 1,
          "day": "monday",
          "start_time": "09:00",
          "end_time": "10:30",
          "is_active": true
        }
      ],
      "sessions_count": 5,
      "students_count": 25,
    "created_at": "2024-01-01 00:00:00",
    "updated_at": "2024-01-01 00:00:00",
    "channel_id": 1
  }
}
```

### 5. Update Group

Update an existing group, including students.

**Endpoint:** `PUT /api/v1/academic/groups/{id}`

**Request Body:**
```json
{
  "name": "Math Group A - Updated",
  "capacity": 35,
  "price": 600.00,
  "is_active": true,
  "student_ids": [1, 2, 3, 4, 5],
  "session_times": [
    {
      "day": "monday",
      "start_time": "10:00",
      "end_time": "11:30",
      "is_active": true
    }
  ]
}
```

**Note:** All fields are optional. Only provided fields will be updated. 
- To update students, include `student_ids` array
- To update session times, include `session_times` array (will replace all existing session times)

**Response:**
```json
{
  "status": "success",
  "message": "Group updated successfully",
  "data": {
    "id": 1,
    "name": "Math Group A - Updated",
    "capacity": 35,
    "price": 600.00,
    "students": [
      {
        "id": 1,
        "name": "Ahmed Ali",
        "code": "STU-000001"
      }
    ],
    "students_count": 5
  }
}
```

### 6. Delete Group

Delete a group.

**Endpoint:** `DELETE /api/v1/academic/groups/{id}`

**Response:**
```json
{
  "status": "success",
  "message": "Group deleted successfully",
  "data": null
}
```

---

## Students API

### 1. Get Students Metadata

Get metadata required for creating/updating students (groups, genders, statuses).

**Endpoint:** `GET /api/v1/students/metadata`

**Response:**
```json
{
  "status": "success",
  "message": "Metadata retrieved successfully",
  "data": {
    "groups": [
      {
        "id": 1,
        "name": "Math Group A",
        "code": "GRP-0001",
        "class_grade": {
          "id": 1,
          "grade_level": 1,
          "stage": "primary"
        },
        "subject": {
          "id": 1,
          "name": "Mathematics",
          "code": "MATH-001"
        }
      }
    ],
    "genders": [
      {
        "value": "male",
        "label": "Male"
      },
      {
        "value": "female",
        "label": "Female"
      }
    ],
    "statuses": [
      {
        "value": 1,
        "label": "Active"
      },
      {
        "value": 0,
        "label": "Inactive"
      }
    ]
  }
}
```

### 2. List Students

Get a paginated list of students with optional filters.

**Endpoint:** `GET /api/v1/students`

**Query Parameters:**
- `group_id` (optional): Filter by group ID
- `status` (optional): Filter by status (0 or 1)
- `gender` (optional): Filter by gender (male/female)
- `search` (optional): Search by name, code, email, or phone
- `per_page` (optional): Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "message": "Students list retrieved successfully",
  "data": [
    {
      "id": 1,
      "code": "STU-000001",
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "phone": "+1234567890",
      "gender": "male",
      "status": 1,
      "is_active": true,
      "image": null,
      "email_verified_at": "2024-01-01 00:00:00",
      "channel_id": 1,
      "groups": [
        {
          "id": 1,
          "name": "Math Group A",
          "code": "GRP-0001"
        }
      ],
      "groups_count": 1,
      "attendances_count": 10,
      "payments_count": 5,
      "created_at": "2024-01-01 00:00:00",
      "updated_at": "2024-01-01 00:00:00"
    }
  ]
}
```

### 3. Create Student

Create a new student with optional groups.

**Endpoint:** `POST /api/v1/students`

**Request Body:**
```json
{
  "name": "Ahmed Ali",
  "email": "ahmed@example.com",
  "phone": "+1234567890",
  "gender": "male",
  "password": "Password123!",
  "status": 1,
  "image": "path/to/image.jpg",
  "group_ids": [1, 2]
}
```

**Field Descriptions:**
- `name` (required): Student full name
- `email` (optional): Email address (must be unique within channel)
- `phone` (optional): Phone number (must be unique within channel)
- `gender` (required): Gender (male/female)
- `password` (optional): Password (min 6 characters, auto-hashed)
- `status` (optional): Status (0=inactive, 1=active, default: 1)
- `image` (optional): Image path/URL
- `group_ids` (optional): Array of group IDs to add the student to

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Student created successfully",
  "data": {
    "id": 1,
    "code": "STU-000001",
    "name": "Ahmed Ali",
    "email": "ahmed@example.com",
    "phone": "+1234567890",
    "gender": "male",
    "status": 1,
    "is_active": true,
    "groups": [
      {
        "id": 1,
        "name": "Math Group A",
        "code": "GRP-0001"
      }
    ],
    "groups_count": 2,
    "created_at": "2024-01-01 00:00:00",
    "updated_at": "2024-01-01 00:00:00",
    "channel_id": 1
  }
}
```

### 4. Get Student Details

Get detailed information about a specific student.

**Endpoint:** `GET /api/v1/students/{id}`

**Response:**
```json
{
  "status": "success",
  "message": "Student retrieved successfully",
  "data": {
    "id": 1,
    "code": "STU-000001",
    "name": "Ahmed Ali",
    "email": "ahmed@example.com",
    "phone": "+1234567890",
    "gender": "male",
    "status": 1,
    "is_active": true,
    "groups": [
      {
        "id": 1,
        "name": "Math Group A",
        "code": "GRP-0001"
      }
    ],
    "groups_count": 2,
    "attendances_count": 10,
    "payments_count": 5,
    "created_at": "2024-01-01 00:00:00",
    "updated_at": "2024-01-01 00:00:00",
    "channel_id": 1
  }
}
```

### 5. Update Student

Update an existing student, including groups.

**Endpoint:** `PUT /api/v1/students/{id}`

**Request Body:**
```json
{
  "name": "Ahmed Ali Updated",
  "email": "ahmed.updated@example.com",
  "phone": "+1234567891",
  "status": 1,
  "group_ids": [1, 2, 3]
}
```

**Note:** All fields are optional. Only provided fields will be updated. To update groups, include `group_ids` array. To update password, include `password` field.

**Response:**
```json
{
  "status": "success",
  "message": "Student updated successfully",
  "data": {
    "id": 1,
    "name": "Ahmed Ali Updated",
    "email": "ahmed.updated@example.com",
    "groups": [
      {
        "id": 1,
        "name": "Math Group A",
        "code": "GRP-0001"
      }
    ],
    "groups_count": 3
  }
}
```

### 6. Delete Student

Delete a student.

**Endpoint:** `DELETE /api/v1/students/{id}`

**Response:**
```json
{
  "status": "success",
  "message": "Student deleted successfully",
  "data": null
}
```

---

## Error Responses

All endpoints may return the following error responses:

### 400 Bad Request
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "status": "error",
  "message": "Validation error message",
  "errors": {
    "field_name": ["Specific validation error"]
  }
}
```

---

## Common Use Cases

### Use Case 1: Create Group with Students and Session Times

1. Get metadata: `GET /api/v1/academic/groups-metadata`
2. Create group with students and session times:
```json
POST /api/v1/academic/groups
{
  "name": "Math Group A",
  "class_grade_id": 1,
  "subject_id": 1,
  "capacity": 30,
  "student_ids": [1, 2, 3],
  "session_times": [
    {
      "day": "monday",
      "start_time": "09:00",
      "end_time": "10:30",
      "is_active": true
    },
    {
      "day": "wednesday",
      "start_time": "09:00",
      "end_time": "10:30",
      "is_active": true
    }
  ]
}
```

**Note:** The system will automatically validate that session times don't conflict with existing sessions.

### Use Case 2: Create Student with Groups

1. Get metadata: `GET /api/v1/students/metadata`
2. Create student with groups:
```json
POST /api/v1/students
{
  "name": "Ahmed Ali",
  "email": "ahmed@example.com",
  "gender": "male",
  "group_ids": [1, 2]
}
```

### Use Case 3: Update Group Students and Session Times

```json
PUT /api/v1/academic/groups/1
{
  "student_ids": [1, 2, 3, 4, 5],
  "session_times": [
    {
      "day": "monday",
      "start_time": "10:00",
      "end_time": "11:30",
      "is_active": true
    }
  ]
}
```

**Note:** Providing `session_times` will replace all existing session times for the group.

### Use Case 4: Update Student Groups

```json
PUT /api/v1/students/1
{
  "group_ids": [1, 2, 3]
}
```

---

## Notes

1. **Channel Isolation**: All operations are automatically scoped to the authenticated user's channel.

2. **Auto-Generated Codes**: 
   - Group codes are auto-generated if not provided (format: GRP-0001)
   - Student codes are auto-generated if not provided (format: STU-000001)

3. **Relationships**:
   - Groups and Students have a many-to-many relationship
   - You can add students to groups when creating/updating groups
   - You can add groups to students when creating/updating students

4. **Validation**:
   - All IDs (class_grade_id, subject_id, student_ids, group_ids) must belong to the current channel
   - Email and phone must be unique within the channel
   - Group name must be unique within the same class_grade and subject
   - Session times are validated for conflicts:
     - No two sessions can overlap on the same day
     - End time must be after start time
     - Conflicts are checked within the same request and against existing sessions

5. **Session Times**:
   - Session times can be added when creating or updating a group
   - When updating, providing `session_times` will replace all existing session times
   - The system automatically detects and prevents time conflicts
   - Day values: saturday, sunday, monday, tuesday, wednesday, thursday, friday
   - Time format: HH:mm (24-hour format, e.g., "09:00", "14:30")

6. **Metadata Endpoints**: Always call metadata endpoints before create/update forms to populate dropdowns.

7. **Repository Pattern**: All database queries use Repository pattern instead of direct Model access for better code organization and maintainability.

---

## Support

For questions or issues, please contact the development team or refer to the main API documentation at `/api-docs/view`.

