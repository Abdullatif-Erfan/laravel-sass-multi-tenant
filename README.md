# Laravel Multi-Tenant API with PostgreSQL

This project implements a multi-tenant architecture in Laravel using the [stancl/tenancy](https://tenancyforlaravel.com) package. Each tenant gets its **own PostgreSQL database**, and tenant-specific migrations are executed on database creation.


### What is Multi Tenant SaaS?

Multi-tenant SaaS, or Software as a Service, refers to a type of software architecture where a single instance of the software application serves multiple customers, known as tenants. Each tenant shares the same underlying infrastructure and code base, but their data and configurations are kept separate and isolated from one another.


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

### Prerequisites

- PHP 8.2+
- PostgreSQL 12+
- Composer 2.0+
- Laravel CLI



## How to Run

Open your favourite terminal of and follow the instructions below to run the complete project on your machine!

### Step 1:

Clone the github repository by running the following command

```
git clone https://github.com/Abdullatif-Erfan/laravel-sass-multi-tenant.git
```

Navigate to the project directory

```
cd sass-multi-tenancy
```

### Step 2:  Create .env File

```
cp .env.example .env
```

### Step 3: Generate Application Key

Run following command to generate app key.

```
php artisan key:generate
```

### Step 4: Create Central Database

Create a central database and user in PostgreSQL, and assign full privileges to the user.


### Step 5: Configure .env

Update your .env file with the following variables:

-   DB_CONNECTION=central
-   TENANCY_BOOTSTRAP_DB_CONNECTION=tenant
-   DB_HOST=127.0.0.1
-   DB_PORT=5432
-   DB_DATABASE=central_db
-   DB_USERNAME=your_postgres_username
-   DB_PASSWORD=your_postgress_password


### Step 6: Run Migrations for Central Database

```
php artisan migrate --path=database/migrations/central
```

### Step 7: Start the Application

```
php artisan serve
```

Open the project on http://localhost:8000/ link 




### 📡 API Endpoints

🔹 Register a Tenant

   ```
   POST http://localhost:8000/api/v1/tenant/register
   ```
   
   Payload: 
    {
        "name": "New Tenant Name",
        "email": "email@example.com",
        "domain": "domain name",
        "password": "password"
    }


🔹 Register a User for a Tenant

   ```
   POST http://localhost:8000/api/v1/tenant-user-register
   ```
    payload:
    {
        "name": "User Full Name",
        "email": "user@example.com",
        "password": "password",
        "password_confirmation": "password"
    }

    Header:
    ```
    X-Tenant-ID: <tenant-id>
    ```
  
### 📝 TODO
    TODO Task
-   Set up Sanctum authentication for users within each tenant
-   Add endpoint to retrieve user profile data