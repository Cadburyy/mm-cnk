# TM

A **Laravel-based project** built for task management and internal operations.

Eng Ver ---

## 🚀 Setup Instructions

To get the **TM** project running on your local machine, follow the steps below. The process includes backend setup, database configuration, and frontend build compilation.

### 1. Initial Setup and Prerequisites

Before starting, ensure the required software is installed:

* **XAMPP**: Start Apache and MySQL to act as your web and database servers.
* **Composer**: PHP package manager for backend dependencies.
* **Node.js & npm**: Required for installing and running frontend assets.

---

### 2. Project Download and Backend Configuration

1. Download the project files as a ZIP from the repository.
2. Extract the ZIP and place the `tm` folder in your preferred location.
3. Open a terminal and navigate to the project directory:

   ```bash
   cd path/to/tm
   ```
4. Install backend dependencies:

   ```bash
   composer install
   ```
5. Create a copy of the `.env` configuration file:

   ```bash
   cp .env.example .env
   ```
6. Generate your Laravel application key:

   ```bash
   php artisan key:generate
   ```
7. Create a new database in **phpMyAdmin**, for example: `tm_db`.
8. Open the `.env` file and update your database configuration:

   ```
   DB_DATABASE=tm_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
---

### 3. Database Migration and Seeding

Once the database is ready, run migrations and seed default data:

1. Run the following command:

   ```bash
   php artisan migrate:fresh --seed
   ```
   ```bash
   php artisan db:seed --class=PermissionTableSeeder
   php artisan db:seed --class=CreateAdminUserSeeder
   ```
2. This will create all required tables and insert initial data.

---

### 4. Frontend Setup and Launch

1. Install frontend dependencies:

   ```bash
   npm install
   ```
2. Build the frontend assets:

   ```bash
   npm run build
   ```
3. Start the Laravel server:

   ```bash
   php artisan serve
   ```
4. Open a **new** terminal and start the Vite development server:

   ```bash
   npm run dev
   ```

Your application is now running and accessible at:

**[http://localhost:8000](http://localhost:8000)**

---

