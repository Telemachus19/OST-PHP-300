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
- `http://localhost/listUsers.php` shows all saved users from JSON
- `http://localhost/viewUser.php?id=...` shows one selected user
- `http://localhost/editUser.php?id=...` updates one selected user

## Default Database Credentials

- Root user:
  - User: `root`
  - Password: `root`
- App user:
  - Database: `app`
  - User: `app`
  - Password: `app`

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
