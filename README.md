# Expense Tracker Backend

Laravel 12 backend and dashboard shell for a personal expense tracker.

This project provides:

- Phone-based user login
- User-scoped CRUD APIs for accounts, categories, transactions, and budgets
- A dashboard summary endpoint with totals, balances, recent activity, and budget progress
- A Blade dashboard shell at the main web routes

## Features

- Login or create a user with a phone number
- Create, update, list, and delete expense data by user
- Fetch dashboard metrics for a single user
- Hard deletes only, no soft delete behavior
- JSON API responses with a consistent `status`, `message`, `data` structure
- Vite-powered frontend shell using Axios and Tailwind CSS

## Tech Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Vite
- Tailwind CSS
- Axios
- PHPUnit 11

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 18 or newer
- MySQL database server
- XAMPP if you want to run the backend locally on Windows

## Local Setup with XAMPP

If you are running this backend locally with XAMPP:

- Open the XAMPP Control Panel
- Start the `Apache` and `MySQL` services
- Make sure your local MySQL database is created before running migrations
- Keep the XAMPP services running whenever you access the API or the dashboard locally

## Installation

Clone the repository and install dependencies:

```bash
composer install
npm install
```

Create your environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Update your database settings in `.env` so they match your XAMPP MySQL configuration, then run the migrations:

```bash
php artisan migrate
```

Seed the sample user if you want test data:

```bash
php artisan db:seed
```

If you want the frontend shell assets available during development, run:

```bash
npm run dev
```

Start the Laravel server:

```bash
php artisan serve
```

## Environment

Set the database credentials that match your local setup. A typical MySQL configuration looks like this:

```env
APP_NAME="Expense Tracker"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expense_tracker
DB_USERNAME=root
DB_PASSWORD=
```

If you prefer SQLite, create an empty database file and point `DB_DATABASE` to it.

## Web Routes

The following web routes all render the dashboard shell:

- `GET /`
- `GET /dashboard`
- `GET /accounts`
- `GET /categories`
- `GET /transactions`
- `GET /budgets`

The shell exposes helper route constants in `resources/js/app.js` and uses Axios with the API base URL.

## API Base

All API routes live under `/api`.

Responses use a consistent format:

```json
{
  "status": 200,
  "message": "Request completed successfully.",
  "data": {}
}
```

## Postman Collection

A ready-to-import Postman collection is included here:

- [postman/expense-tracker-api.postman_collection.json](postman/expense-tracker-api.postman_collection.json)

Collection variables:

- `baseUrl` defaults to `http://127.0.0.1:8000`
- `userId`
- `accountId`
- `categoryId`
- `transactionId`
- `budgetId`

Import the collection into Postman, then update `baseUrl` if your Laravel app is running on a different host or port.

## Authentication

This project does not use passwords or token-based authentication yet.

Login is phone-based:

- If the phone number exists, the matching user is returned
- If the phone number does not exist, a new user is created

### Login

`POST /api/login`

Request body:

```json
{
  "phone": "01700000000",
  "name": "Test User"
}
```

Validation:

- `phone` is required and must be exactly 11 characters
- `name` is optional

## Dashboard

`GET /api/users/{user}/dashboard`

Returns:

- `summary` with income, expense, balance, net change, and savings rate
- `counts` for accounts, categories, budgets, and transactions
- `weekly_flow`
- `accounts`
- `recent_transactions`
- `budgets`

Example:

```bash
GET /api/users/1/dashboard
```

## Resources

All CRUD resources are scoped by user:

- `GET /api/users/{user}/categories`
- `GET /api/users/{user}/accounts`
- `GET /api/users/{user}/transactions`
- `GET /api/users/{user}/budgets`

Each resource supports the standard Laravel resource actions:

- `GET /` for listing
- `POST /` for creating
- `GET /{id}` for showing one item
- `PUT` or `PATCH /{id}` for updating
- `DELETE /{id}` for deleting

### Categories

Fields:

- `name` required
- `type` required, one of `income` or `expense`

### Accounts

Fields:

- `name` required
- `type` required, one of `cash`, `bank`, `card`, `bkash`, `nagad`, `rocket`, or `other`
- `currency` optional, defaults to `BDT`
- `opening_balance` optional, defaults to `0`
- `is_default` optional
- `notes` optional

### Transactions

Fields:

- `account_id` required
- `category_id` optional
- `transaction_type` required, one of `income` or `expense`
- `amount` required
- `transaction_date` required
- `title` required
- `description` optional
- `reference` optional

### Budgets

Fields:

- `category_id` optional
- `amount` required
- `period` required, one of `weekly`, `monthly`, `yearly`, or `custom`
- `start_date` optional
- `end_date` optional

## Data Model

Core tables:

- `users`
- `categories`
- `accounts`
- `transactions`
- `budgets`

Important relationships:

- A user has many categories, accounts, transactions, and budgets
- An account can have many transactions
- A category can have many transactions and budgets
- A budget belongs to a category

Deletes are permanent. Soft delete columns and soft delete model traits are not used in the current version of the app.

## Testing

Run the test suite with:

```bash
php artisan test
```

The test database is configured to use in-memory SQLite.

## Sample Data

The database seeder creates one test user:

- `name`: `Test User`
- `phone`: `01700000000`

Run `php artisan db:seed` after migrating if you want that record available locally.

## Development Notes

- Frontend assets are built with Vite
- Axios is preconfigured to send JSON requests to the API base URL
- The dashboard shell is ready for a Vue or other frontend layer to mount onto
- Route model binding is scoped under `/api/users/{user}` so user-owned data stays isolated per user
