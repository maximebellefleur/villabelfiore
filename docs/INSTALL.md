# Rooted v1 — Installation Guide

## Requirements

- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.5+
- Web server (Apache or Nginx) with URL rewriting
- PHP extensions: `pdo_mysql`, `json`, `mbstring`, `fileinfo`

## Step 1: Deploy Files

Upload or clone the project to your server. The web server **document root must point to the `/public` directory**, not the project root.

Example Nginx config:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/rooted/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Example Apache `.htaccess` (place in `/public`):
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

## Step 2: Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 public/assets/
```

## Step 3: Copy and Configure .env

```bash
cp .env.example .env
```

Edit `.env` and set at minimum:
- `APP_URL` — your public URL
- `APP_KEY` — any random 32-character string
- Database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`)

## Step 4: Run the Installer

Visit `https://yourdomain.com/install` in your browser.

Follow the 5-step installer:
1. Environment check
2. Database setup (creates all tables automatically)
3. Land identity (name, timezone, currency)
4. Storage setup (local filesystem by default)
5. Integrations (optional, can skip)
6. Create admin account

## Step 5: Log In

After installation completes, visit `/login` and sign in with your admin credentials.

## Raspberry Pi / Local Network

The application runs identically on a Raspberry Pi running PHP 8.2 + MySQL. Follow the same steps. Set `APP_URL` to the local IP address (e.g., `http://192.168.1.100`).
