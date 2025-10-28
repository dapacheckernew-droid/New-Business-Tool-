# New Business Finance Manager

A secure, multi-user business finance management tool designed for shared hosting environments such as Namecheap. The application is fully file-based and deployable via cPanel File Manager or FTP clients like FileZilla.

## Features

- **Admin Portal** at `/access` with dedicated login.
  - Add new users and automatically provision isolated SQLite databases per user.
  - Review existing accounts and permanently remove users and their data files.
- **User Portal** at `/account-panel` with email/password authentication.
  - Comprehensive finance modules: purchases, sales, bank transactions, cash tracking, payables, receivables, parties, vendors, credits, and sales returns.
  - Settings panel for business profile, bank details, fiscal settings, and more.
  - Responsive Bootstrap-based interface ready for desktop and mobile.
- **Security & Isolation**
  - Each account receives an independent SQLite database stored in `data/users/`.
  - Session-based authentication for both admin and user portals.
  - No server-side daemons or dependencies beyond standard PHP extensions.

## Requirements

- PHP 8.0+ with SQLite3 extension enabled (available on most shared hosting providers).
- Ability to upload files via FTP or File Manager.

## Deployment

1. Upload the repository contents to your hosting account (e.g., `public_html`).
2. Ensure the `data/` directory is writable (`755` or `775` permissions depending on host requirements).
3. Update `config.php` with your preferred admin email and password hash if desired.
4. Access the admin portal at `https://your-domain.com/access` to create user accounts.
5. Distribute login credentials to each user. They can access their dashboard at `https://your-domain.com/account-panel`.

## Default Credentials

- Admin email: `admin@mtseotools.com`
- Admin password: `Admin@12345`

> **Important:** Update the admin password hash in `config.php` after deployment.

## Data Storage

- System user index: `data/system.sqlite`
- Per-user ledgers: `data/users/*.sqlite` (automatically generated)

The repository ships with a `.gitignore` entry that prevents SQLite data files from being committed.

## Development

No additional setup is required. The application uses vanilla PHP and SQLite. To run locally, use PHP's built-in server:

```bash
php -S localhost:8080 -t /path/to/project
```

Then visit `http://localhost:8080/access` or `http://localhost:8080/account-panel`.
