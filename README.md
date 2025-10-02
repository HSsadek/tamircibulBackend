# TamirciBul Backend API

Laravel backend API for the TamirciBul repair service platform.

## Features

- **User Authentication**: Registration, login, logout with role-based access
- **Multi-role System**: Customer, Service Provider, Admin roles
- **Service Management**: Service provider listings, service requests
- **Admin Panel**: User management, service provider approval system
- **Real-time Messaging**: Communication between customers and service providers
- **Review System**: Customer reviews and ratings
- **Location-based Services**: City and district filtering

## API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get authenticated user
- `PUT /api/auth/profile` - Update user profile

### Services (Public)
- `GET /api/services` - Get all active service providers
- `GET /api/services/types` - Get service types
- `GET /api/services/{id}` - Get service provider details

### Services (Customer)
- `POST /api/services/request` - Create service request
- `GET /api/services/my-requests` - Get customer's service requests

### Service Provider Dashboard
- `GET /api/service/dashboard` - Get dashboard data
- `GET /api/service/requests` - Get service requests
- `GET /api/service/stats` - Get statistics
- `POST /api/service/requests/{id}/accept` - Accept service request
- `POST /api/service/requests/{id}/complete` - Complete service request
- `PUT /api/service/profile` - Update service provider profile

### Admin Panel
- `POST /api/admin/login` - Admin login
- `GET /api/admin/dashboard` - Admin dashboard
- `GET /api/admin/users` - Get all users
- `GET /api/admin/service-providers/pending` - Get pending service providers
- `POST /api/admin/service-providers/{id}/approve` - Approve service provider
- `POST /api/admin/service-providers/{id}/reject` - Reject service provider
- `PUT /api/admin/users/{id}/status` - Update user status
- `GET /api/admin/service-requests` - Get all service requests

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js (for frontend)

### Setup Steps

1. **Install Dependencies**
   ```bash
   cd tamircibulb
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   
   Update the `.env` file with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tamircibul
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

4. **Create Database**
   Create a MySQL database named `tamircibul`

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed Database**
   ```bash
   php artisan db:seed
   ```

7. **Start Development Server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000`

## Default Credentials

After seeding the database, you can use these credentials:

### Admin
- Email: `admin@tamircibul.com`
- Password: `admin123`

### Customer
- Email: `customer@example.com`
- Password: `password123`

### Service Provider
- Email: `service@example.com`
- Password: `password123`

## Database Schema

### Users Table
- Multi-role user system (customer, service, admin)
- Email and phone authentication support
- Status management (active, inactive, pending, suspended)

### Service Providers Table
- Company information and service details
- Location data with coordinates
- Rating and review system
- Verification status

### Service Requests Table
- Customer service requests
- Status tracking (pending, accepted, completed, etc.)
- Location and budget information
- Priority levels

### Reviews Table
- Customer reviews for service providers
- Rating system (1-5 stars)
- Image attachments support

### Messages Table
- Real-time messaging between users
- File attachment support
- Read status tracking

## User Roles

### Customer
- Browse and search service providers
- Create service requests
- Communicate with service providers
- Leave reviews and ratings

### Service Provider
- Manage profile and services
- View and accept service requests
- Track job progress
- Communicate with customers

### Admin
- Manage all users
- Approve/reject service provider applications
- Monitor platform activity
- Handle disputes and issues

## API Response Format

All API responses follow this format:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

Error responses:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header:

```
Authorization: Bearer your_token_here
```

## CORS Configuration

CORS is configured to allow requests from the frontend application. Update `config/cors.php` if needed.

## Development

### Adding New Features
1. Create models in `app/Models/`
2. Create migrations in `database/migrations/`
3. Create controllers in `app/Http/Controllers/Api/`
4. Add routes in `routes/api.php`

### Testing
Run the application tests:
```bash
php artisan test
```

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Configure proper database credentials
4. Run `php artisan config:cache`
5. Run `php artisan route:cache`
6. Set up proper web server (Apache/Nginx)

## Support

For issues and questions, please check the documentation or contact the development team.
