# MES Backend - Manufacturing Execution System API

This is the backend API for the MES application, built with **Laravel 12**. It handles data persistence, business logic, authentication, and integration for the manufacturing platform.

## 🛠 Prerequisites

- **PHP** >= 8.2
- **Composer** (Dependency Manager)
- **PostgreSQL** 15+
- **Redis** 7+ (for caching and queues)
- **Node.js & NPM** (optional, for asset compilation if needed)

## 🚀 Installation & Setup

1.  **Clone the repository** (if you haven't already):
    ```bash
    git clone <repository_url>
    cd mes/backend
    ```

2.  **Install PHP dependencies**:
    ```bash
    composer install
    ```

3.  **Environment Configuration**:
    Copy the example environment file and configure it:
    ```bash
    cp .env.example .env
    ```
    *   Update `DB_*` variables to point to your PostgreSQL database.
    *   Update `REDIS_*` variables for caching/queues.

4.  **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```

5.  **Run Migrations & Seeders**:
    Initialize the database with the required schema and default data:
    ```bash
    php artisan migrate --seed
    ```

6.  **Serve the Application**:
    ```bash
    php artisan serve
    ```
    The API will be available at `http://localhost:8000`.

## 🐳 Docker Setup (Alternative)

If you prefer running via Docker (recommended for consistency), run from the **project root**:

```bash
docker-compose up -d --build
docker-compose exec backend composer install
docker-compose exec backend php artisan migrate --seed
```

## 🧪 Testing

Run the automated test suite to ensure system stability:

```bash
php artisan test
```

## 📂 Key Dependencies

- **Laravel Sanctum**: For API token authentication.
- **Scribe** (optional): For API documentation generation.
- **Pest/PHPUnit**: For automated testing.

## 🏗 Architecture

- **Controllers**: Located in `app/Http/Controllers/Api`. Grouped by module (Auth, Engineering, Inventory, etc.).
- **Models**: Located in `app/Models`.
- **Requests**: FormRequests used for validation in `app/Http/Requests`.
- **Resources**: API Resources for JSON formatting in `app/Http/Resources`.

## 📝 API Documentation

If Scribe is installed, generate docs via:
```bash
php artisan scribe:generate
```
Documentation will be accessible at `/docs`.
