# MStore - Ticketing & Installation Management System

A comprehensive system for managing ISP operations, including customer management, ticketing (complaints/requests), and installation tracking with role-based access control (Admin, NOC, Technician).

## Features

-   **Role-Based Access Control (RBAC)**: Admin, NOC, Technician, Customer roles.
-   **Customer Management**: CRUD operations for customer data.
-   **Ticketing System**: Log, assign, and track support tickets.
-   **Installation Management**: Track new installations from registration to completion.
-   **Dashboards**:
    -   **Admin**: Overview of operations, recent activity, and quick actions.
    -   **Technician**: Mobile-friendly dashboard to view assigned tickets and installations.

## Requirements

-   PHP 8.1 or higher
-   Composer
-   MySQL
-   Node.js & NPM (for frontend assets)

## Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/winetstln-glitch/mstore.git
    cd mstore
    ```

2.  **Install PHP dependencies**
    ```bash
    composer install
    ```

3.  **Install Frontend dependencies**
    ```bash
    npm install
    npm run build
    ```

4.  **Environment Setup**
    Copy the example env file and configure your database credentials:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Update `.env` with your database details:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=mstore
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5.  **Database Migration & Seeding**
    Run migrations and seed the database with initial roles and users:
    ```bash
    php artisan migrate:fresh --seed
    ```

## Usage

### Default Accounts

| Role       | Email               | Password |
| :--------- | :------------------ | :------- |
| Administrator | `admin@mstore.local` | `password` |
| NOC        | `noc@mstore.local`   | `password` |
| Technician | `tech1@mstore.local` | `password` |

### Running the Application

Start the local development server:
```bash
php artisan serve
```

Access the application at `http://localhost:8000`.

## Testing

Run the test suite to ensure everything is working correctly:
```bash
php artisan test
```
