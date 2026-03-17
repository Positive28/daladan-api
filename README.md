<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## VPS Connection and Tag Deployment

### 1) Connect to VPS

Use SSH from your terminal:

```bash
ssh root@170.168.6.145
```

After first login, rotate the root password and add an SSH key:

```bash
# local machine
ssh-keygen -t ed25519 -C "kamuranbek1998@gmail.com" -f ~/.ssh/vps_eskiz
ssh-copy-id -i ~/.ssh/vps_eskiz.pub root@170.168.6.145
```

Then connect with key:

```bash
ssh -i ~/.ssh/vps_eskiz root@170.168.6.145
```

### 2) Prepare server once (Ubuntu 22)

This is an API-only Laravel project with PostgreSQL. Install:

```bash
# PHP 8.2 + extensions (add ondrej/php PPA first: sudo add-apt-repository ppa:ondrej/php && sudo apt update)
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-ctype php8.2-fileinfo php8.2-tokenizer php8.2-curl php8.2-zip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# PostgreSQL (use the same password you will put in ENV_CONTENT)
sudo apt install -y postgresql postgresql-contrib
sudo -u postgres psql -c "CREATE USER laravel WITH PASSWORD 'YOUR_DB_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE start_api OWNER laravel;"

# Nginx
sudo apt install -y nginx

# Git
sudo apt install -y git
```

Clone project and set permissions:

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/Positive28/daladan-api.git daladan-api
sudo chown -R www-data:www-data /var/www/daladan-api
sudo chmod -R 775 /var/www/daladan-api/storage /var/www/daladan-api/bootstrap/cache
```

`.env` is created automatically from `ENV_CONTENT` secret during each deploy. Do not create it manually.

Configure Nginx to serve the Laravel app:

```bash
# 1. Copy config to sites-available (required before symlink)
sudo cp /var/www/daladan-api/deploy/nginx-start-api.conf /etc/nginx/sites-available/start-api

# 2. Enable it as default site
sudo ln -sf /etc/nginx/sites-available/start-api /etc/nginx/sites-enabled/default

# 3. Test and reload
sudo nginx -t && sudo systemctl reload nginx
```

**If you get 502 Bad Gateway:** PHP-FPM may not be running or the socket path may differ. On the VPS:

```bash
# Check PHP-FPM status
systemctl status php8.2-fpm

# List available sockets (adjust version if needed)
ls /var/run/php/

# If using a different PHP version, edit the nginx config and change php8.2-fpm.sock
```

### 3) Configure GitHub Secrets and Variables

**Secrets** (Settings → Secrets and variables → Actions → Secrets):

- `VPS_HOST` = `170.168.6.145`
- `VPS_USER` = `root` (or a dedicated deploy user)
- `VPS_SSH_KEY` = private SSH key used by GitHub Actions
- `VPS_APP_DIR` = `/var/www/daladan-api` (optional, default is this path)
- `VPS_PORT` = `22` (optional, default is 22)

**Variables** (Settings → Secrets and variables → Actions → Variables) — values are visible after creation:

- `ENV_CONTENT` = your full `.env` file content (paste as-is)

**Creating `ENV_CONTENT`:**

1. Locally, copy `.env.example` to `.env` and fill in real values (APP_KEY, DB_PASSWORD, APP_URL, etc.).
2. Generate APP_KEY: `php artisan key:generate --show`
3. Copy the entire `.env` file content and paste it into the `ENV_CONTENT` variable.

Use the same `DB_PASSWORD` as in the PostgreSQL `CREATE USER` command from step 2.

**Note:** Variables are not encrypted and can be viewed by anyone with repo admin access. For sensitive data, consider using Secrets instead (values are hidden but not viewable after creation).

### 4) Tag-based deployment

Create and push a version tag:

```bash
git tag v1.0.0
git push origin v1.0.0
```

GitHub Actions triggers deployment and runs:

- `bash scripts/deploy-tag.sh <tag>` on VPS
- `composer install --no-dev`
- `php artisan migrate --force`
- cache refresh (`config:cache`, `route:cache`)

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
