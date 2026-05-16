# EasyDebt Deployment

This project is a plain PHP + MySQL application. It should be deployed to any host that supports:

- PHP 8.0 or newer
- MySQL or MariaDB
- Apache or another web server that serves `index.php`

## Files

- `index.php`: standard web entrypoint
- `index (1).php`: main application UI
- `api.php`: backend API endpoints
- `config.php`: database connection and session bootstrap
- `database.sql`: database schema and default user seed

## Database setup

1. Create a MySQL database.
2. Import `database.sql`.
3. Set your database credentials in one of these ways:

- Preferred: environment variables `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`
- Fallback: edit the defaults in `config.php`

## Shared hosting steps

1. Upload the project files to `public_html` or your domain document root.
2. Import `database.sql` using phpMyAdmin.
3. Update the database credentials.
4. Open the site URL and log in.

## Seeded login

- Username: `gladys`
- The SQL dump includes a password hash, but the original plaintext password could not be verified from the file alone.

## Recommended first changes after deployment

- Move `database.sql` outside the public web root after import if your host allows it.
- Set a password you know for the seeded user:

```sql
UPDATE users
SET password = '$2y$10$GYg.TAV5t9wi8.Tmsr4w3u7R/slpiM18mhwtBQwYcUL6MWj28JRE6'
WHERE username = 'gladys';
```

That hash sets the password to `admin123`.
