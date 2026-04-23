# XAMPP-like Docker Setup

This stack replicates the core XAMPP experience using Docker:

- Apache + PHP (`web`)
- MariaDB (`db`)
- phpMyAdmin (`phpmyadmin`)

## Services and Ports

- Website: `http://localhost`
- phpMyAdmin: `http://localhost:8081`
- MariaDB: `localhost:3306`

## PHP Pages

- `http://localhost/` redirects to `listUsers.php`
- `http://localhost/registration.php` shows the registration form
- `http://localhost/listUsers.php` shows all saved users from MySQL
- `http://localhost/posts.php` shows a simple Posts UI using the REST API
- `http://localhost/viewUser.php?id=...` shows one selected user
- `http://localhost/editUser.php?id=...` updates one selected user

## REST API (Posts Management)

Base URL: `http://localhost/api`

### Auth Endpoint

- `POST /api/auth/login`
  - Body:
    ```json
    {
      "username": "your_username",
      "password": "your_password"
    }
    ```
  - Success response includes JWT token:
    ```json
    {
      "success": true,
      "data": {
        "token": "...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
          "id": "usr_...",
          "username": "your_username"
        }
      },
      "message": "Login successful"
    }
    ```

Use the token in protected requests:

```text
Authorization: Bearer <token>
```

### Required Routes

- `GET /api/posts` (Public)
- `GET /api/posts/{id}` (Public)
- `POST /api/posts` (Protected)
- `PUT /api/posts/{id}` (Protected)
- `DELETE /api/posts/{id}` (Protected)

Post fields:

- `id`
- `title`
- `content`
- `user_id`
- `created_at`
- `updated_at`

### Create/Update Payload

```json
{
  "title": "My first post",
  "content": "Post body text"
}
```

Validation rules:

- `title` is required and must be at least 5 characters.
- `content` is required.

### Response Contract

Success responses:

```json
{
  "success": true,
  "data": {},
  "message": "..."
}
```

Error responses:

```json
{
  "success": false,
  "message": "...",
  "errors": {}
}
```

Status behavior:

- `404 Not Found` for unknown post IDs.
- `422 Unprocessable Entity` for validation errors.
- `401 Unauthorized` for missing/invalid/expired JWT.
- `403 Forbidden` when authenticated users modify posts they do not own.

## Default Database Credentials

- Root user:
  - User: `root`
  - Password: `root`
- App user:
  - Database: `app`
  - User: `app`
  - Password: `app`

## JWT Configuration

`web` service environment variables in `compose.yml`:

- `JWT_SECRET` (change in production)
- `JWT_TTL_SECONDS` (default: `3600`)

## Postman Collection

Import collection file:

- `postman/Posts-Management-API.postman_collection.json`

Quick run order:

1. `Auth - Login`
2. `Posts - Get All (Public)`
3. `Posts - Create (Protected)`
4. `Posts - Get By ID (Public)`
5. `Posts - Update (Protected)`
6. `Posts - Delete (Protected)`

The collection automatically stores:

- `token` from login response
- `postId` from create response

Default collection variables:

- `baseUrl = http://localhost`
- `username = apitest`
- `password = password123`

Additional checks are included:

- `Posts - Validation Error 422`
- `Posts - Unauthorized 401`
- `Posts - Not Found 404`

## Automated Smoke Test (PowerShell)

You can run an end-to-end API smoke test script:

```powershell
./scripts/api-smoke-test.ps1
```

If your host `localhost:80` points to another web server, run requests from inside the container:

```powershell
./scripts/api-smoke-test.ps1 -UseDockerExec
```

Optional parameters:

```powershell
./scripts/api-smoke-test.ps1 -BaseUrl http://localhost -Username apitest -Password password123
```

The script verifies:

- Login returns `200` and token.
- Public list returns `200`.
- Unauthorized create returns `401`.
- Validation error returns `422`.
- Create returns `201`.
- Get/update/delete return `200`.
- Missing/deleted post returns `404`.

## Run

```bash
docker compose up -d --build
```

## MySQL Schema

- Schema file: `docker/mysql/init/01-schema.sql`
- It is applied automatically on first database initialization.
  - If i forgot to add it before the first run 🙂
  - If it doesn't work for some reason, you can apply it manually:
    1. Connect to the database:
       ```bash
       docker compose exec db mysql -u root -p
       ```
       (password: `root`)
    2. Run the schema SQL:
       ```sql
       source /docker-entrypoint-initdb.d/01-schema.sql;
       ```

If your database volume already exists, recreate it to apply init scripts:

```bash
docker compose down -v
docker compose up -d --build
```

## Stop

```bash
docker compose down
```

## Remove Everything (including DB volume)

```bash
docker compose down -v
```

## Project Files

Put your PHP files in `www/` (mapped to `/var/www/html` in the container).
