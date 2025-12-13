# Security Guidelines

## Authentication & Authorization
- Always use Laravel's built-in authentication features
- Implement proper authorization with Gates and Policies
- Use Sanctum for API authentication
- Never store passwords in plain text
- Implement rate limiting on authentication endpoints

## Input Validation & Sanitization
- Always validate user input using Form Requests
- Use Laravel's built-in validation rules
- Sanitize data before database operations
- Implement CSRF protection on all forms
- Use prepared statements (Eloquent handles this)

## Database Security
- Never use raw SQL queries with user input
- Use Eloquent ORM for database operations
- Implement proper database permissions
- Use environment variables for database credentials
- Enable query logging only in development

## API Security
- Implement proper API versioning
- Use authentication tokens with expiration
- Validate all API inputs
- Implement rate limiting
- Use HTTPS in production
- Return consistent error responses

## File Upload Security
- Validate file types and sizes
- Store uploads outside web root
- Scan uploaded files for malware
- Use unique filenames to prevent conflicts
- Implement proper file permissions

## Environment & Configuration
- Never commit .env files
- Use strong encryption keys
- Rotate secrets regularly
- Implement proper logging without sensitive data
- Use secure session configuration

## Headers & CORS
- Implement proper CORS policies
- Use security headers (CSP, HSTS, etc.)
- Configure proper cookie settings
- Implement X-Frame-Options
- Use secure referrer policies