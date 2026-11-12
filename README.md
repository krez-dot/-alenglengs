# Aling Leng POS

Aling Leng is a web-based point-of-sale and sales-management system for a small sari-sari store. It manages products, categories, suppliers, inventory, and sales using Laravel, Eloquent, and MySQL. SQLite is configured for automated tests.

## Setup

1. Install PHP 8.3+ and Composer, then install dependencies with `composer install`.
2. Create `.env` from `.env.example`, configure the database, and run `php artisan key:generate`.
3. Run `php artisan migrate --seed`.
4. Create the first administrator with `php artisan app:create-user --role=admin`. The command prompts for a name, email, and password. Create cashier accounts with `php artisan app:create-user --role=cashier`.
5. Start the server with `php artisan serve` and sign in at `/login`.

There is no public registration route. Administrators can manage products, categories, and suppliers. Admins and Cashiers can check out sales and view sale history.

## REST API

Send JSON requests to `/api` with `Accept: application/json`. Obtain a personal access token:

```http
POST /api/auth/token
Content-Type: application/json

{"email":"cashier@example.com","password":"your-password","token_name":"Postman"}
```

Use the returned token as `Authorization: Bearer <access-token>`. Revoke the current token with `DELETE /api/auth/token`.

| Method | Endpoint | Access |
|---|---|---|
| `GET` | `/api/user` | Admin or Cashier |
| `GET` | `/api/products?q=&category_id=&supplier_id=&per_page=` | Admin or Cashier |
| `GET` | `/api/products/{id}` | Admin or Cashier |
| `POST` | `/api/products` | Admin |
| `PUT`, `PATCH` | `/api/products/{id}` | Admin |
| `DELETE` | `/api/products/{id}` | Admin |
| `GET` | `/api/sales?date_from=&date_to=&per_page=` | Admin or Cashier |
| `GET` | `/api/sales/{id}` | Admin or Cashier |
| `POST` | `/api/sales` | Admin or Cashier |

Product creation requires `sku`, `name`, `category_id`, `cost_price`, `selling_price`, `stock_quantity`, and `reorder_level`; `supplier_id` is optional. Sales require `items` (each with `product_id` and `quantity`) and `amount_paid`. A `senior` or `pwd` discount also requires `discount_confirmed: true` and a non-empty `discount_id_number`. Product prices and sale totals are calculated server-side, and each sale is attributed to the authenticated user.

Validation errors return `422`, unauthenticated requests return `401`, and role/ability violations return `403`. Insufficient stock or attempted deletion of a product used in a sale returns `409`.

## Tests

Run the automated test suite with:

```sh
php artisan test
```
