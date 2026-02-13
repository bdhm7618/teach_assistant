# Teachify API Documentation

## How to Access the Documentation

### 1. View Documentation in Browser (Swagger UI)

Open your browser and navigate to:
```
http://your-domain/api-docs/view
```

This page displays the documentation interactively using Swagger UI, where you can:
- Browse all endpoints
- See required parameters
- Test endpoints directly
- Enter JWT token for protected endpoints

### 2. Get JSON File

To get the JSON file directly:
```
http://your-domain/api-docs
```

Or via curl:
```bash
curl http://your-domain/api-docs
```

### 3. Use Documentation in Postman

1. Open Postman
2. Click **Import**
3. Select **Link**
4. Enter the URL: `http://your-domain/api-docs`
5. All endpoints will be imported automatically

### 4. Use Documentation in Swagger Editor

1. Open [Swagger Editor](https://editor.swagger.io/)
2. Click **File** > **Import URL**
3. Enter the URL: `http://your-domain/api-docs`
4. The documentation will be loaded and you can edit it

### 5. Update Documentation

When adding new endpoints, run the following command to update the documentation:

```bash
php artisan api:docs:generate
```

## Authentication

Most endpoints require a JWT token. To get a token:

1. Login via: `POST /api/v1/channel/user/login`
2. Use the returned token in the header:
   ```
   Authorization: Bearer {your-token}
   ```

In Swagger UI, you can enter the token in the "Authorize" button at the top of the page.

## Response Structure

### Success Response
```json
{
    "status": "success",
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response
```json
{
    "status": "error",
    "message": "Error message",
    "errors": { ... }
}
```

## Available Endpoints

### Channel Endpoints
- `POST /api/v1/channel/register` - Register a new channel and user
- `POST /api/v1/channel/user/verify-email` - Verify email with OTP
- `POST /api/v1/channel/user/login` - User login
- `POST /api/v1/channel/user/forget-password` - Request password reset OTP
- `POST /api/v1/channel/user/reset-password` - Reset password with OTP

### Academic Endpoints
- `GET /api/v1/academic/class-grades` - List class grades
- `POST /api/v1/academic/class-grades` - Create class grade
- `GET /api/v1/academic/class-grades/{id}` - Get class grade details
- `PUT /api/v1/academic/class-grades/{id}` - Update class grade
- `DELETE /api/v1/academic/class-grades/{id}` - Delete class grade
- `GET /api/v1/academic/groups-metadata` - Get metadata for groups (class grades, subjects, students)
- `GET /api/v1/academic/groups` - List groups
- `POST /api/v1/academic/groups` - Create group (with optional students)
- `GET /api/v1/academic/groups/{id}` - Get group details
- `PUT /api/v1/academic/groups/{id}` - Update group (with optional students)
- `DELETE /api/v1/academic/groups/{id}` - Delete group

### Student Endpoints
- `GET /api/v1/students/metadata` - Get metadata for students (groups, genders, statuses)
- `GET /api/v1/students` - List students
- `POST /api/v1/students` - Create student (with optional groups)
- `GET /api/v1/students/{id}` - Get student details
- `PUT /api/v1/students/{id}` - Update student (with optional groups)
- `DELETE /api/v1/students/{id}` - Delete student

## Important Notes

1. All endpoints that require authentication need a JWT token in the header
2. Base URL is: `http://your-domain`
3. All responses are in JSON format
4. When adding new endpoints, you must update the documentation using the command mentioned above

## Groups & Students Documentation

For detailed documentation on Groups and Students API, including:
- Creating groups with students
- Creating students with groups
- Metadata endpoints
- Complete request/response examples

See: [API_DOCUMENTATION_GROUPS_STUDENTS.md](./API_DOCUMENTATION_GROUPS_STUDENTS.md)

## Base URL

Replace `your-domain` with your actual domain:
- Development: `http://localhost` or `http://teachify.127.0.0.1.io`
- Production: `https://your-production-domain.com`

## Testing Endpoints

### Using Swagger UI
1. Navigate to `/api-docs/view`
2. Click on any endpoint to expand it
3. Click "Try it out"
4. Fill in the required parameters
5. Click "Execute" to test the endpoint

### Using Postman
1. Import the API documentation from `/api-docs`
2. Set the base URL in Postman environment
3. For authenticated endpoints, add the JWT token in the Authorization header

### Using cURL
```bash
# Example: Login
curl -X POST http://your-domain/api/v1/channel/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Example: Get class grades (with token)
curl -X GET http://your-domain/api/v1/academic/class-grades \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```
