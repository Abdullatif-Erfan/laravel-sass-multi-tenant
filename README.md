# Laravel Multi-Tenant API with PostgreSQL

This project implements a multi-tenant architecture in Laravel using the [stancl/tenancy](https://tenancyforlaravel.com) package. Each tenant gets its **own PostgreSQL database**, and tenant-specific migrations are executed on database creation.

## 🔧 Technologies

- **Laravel 12**
- **PostgreSQL**
- **stancl/tenancy** 
- **Sanctum** (optional, for authentication)
- **RESTful API**

---

## 🚀 Features

- Each tenant has a **separate database**.
- Automatic creation of database and migrations when a tenant is registered.
- API-based tenant registration (`POST /api/v1/tenant/register`).
- Option to create a default admin user for each tenant.

---

## 📦 Installation Guide

### 1. Clone the Repository

```bash
1. git clone https://github.com/yourname/your-project.git
cd your-project

2. Install Dependencies
-  composer install

3. Setup .env
- cp .env.example .env
- php artisan key:generate

4.  create two connection variables
-   DB_CONNECTION=central
-   TENANCY_BOOTSTRAP_DB_CONNECTION=tenant
-   DB_HOST=127.0.0.1
-   DB_PORT=5432
-   DB_DATABASE=central_db
-   DB_USERNAME=postgres_user_name
-   DB_PASSWORD=postgress_password

5. Create central database in postgresql and give full priviledges
    
6.  Set Up Database
-   php artisan migrate --path=database/migrations/central

7. Run the application
-  php artisan serve

8. Test end points
-  create a tenant ```POST http://localhost:8000/api/v1/tenant/register```
    payload 
    {
        "name": "New Tenant Name",
        "email": "email@example.com",
        "domain": "domain name",
        "password": "secret123"
    }
-  Register a new tenant for a user
   ```POST http://localhost:8000/api/v1/tenant-user-register```
    payload
    {
        "name": "Test User6",
        "email": "user6@example.com",
        "password": "password",
        "password_confirmation": "password"
    }
    set X-Tenant-ID as a key and set newly created tenant id as a value in the header of the request

    


