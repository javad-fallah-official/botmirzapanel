# Deployment Guide

This document provides comprehensive instructions for deploying the BotMirzaPanel application to various environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Configuration](#environment-configuration)
3. [Local Development Setup](#local-development-setup)
4. [Production Deployment](#production-deployment)
5. [Docker Deployment](#docker-deployment)
6. [Database Setup](#database-setup)
7. [Web Server Configuration](#web-server-configuration)
8. [SSL/TLS Configuration](#ssltls-configuration)
9. [Monitoring and Logging](#monitoring-and-logging)
10. [Backup and Recovery](#backup-and-recovery)
11. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements
- **PHP**: 8.1 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Memory**: Minimum 512MB RAM (2GB+ recommended)
- **Storage**: Minimum 1GB free space
- **SSL Certificate**: Required for production

### Required PHP Extensions
```bash
# Required extensions
php-cli
php-fpm
php-mysql (or php-pgsql)
php-redis
php-curl
php-json
php-mbstring
php-xml
php-zip
php-gd
php-intl
php-bcmath
```

### External Services
- **Redis**: For caching and session storage
- **Payment Gateways**: Stripe, PayPal, etc.
- **Email Service**: SMTP server or service (SendGrid, Mailgun)
- **File Storage**: Local or cloud storage (AWS S3, etc.)

## Environment Configuration

### Environment Variables
Create a `.env` file in the project root:

```bash
# Application
APP_NAME="BotMirzaPanel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:your-32-character-secret-key
APP_TIMEZONE=UTC

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=botmirzapanel
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
REDIS_DATABASE=0

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=bmp_
CACHE_TTL=3600

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Payment Gateways
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=live

# File Storage
FILESYSTEM_DRIVER=local
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_s3_bucket

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/...

# Security
JWT_SECRET=your-jwt-secret-key
ENCRYPTION_KEY=your-32-character-encryption-key
API_RATE_LIMIT=1000
API_RATE_LIMIT_WINDOW=3600

# Bot Configuration
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_WEBHOOK_URL=https://your-domain.com/webhook/telegram

# Monitoring
SENTRY_DSN=https://your-sentry-dsn
NEW_RELIC_LICENSE_KEY=your_new_relic_key
```

### Environment-Specific Configurations

#### Development (.env.local)
```bash
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_HOST=localhost
LOG_LEVEL=debug
```

#### Staging (.env.staging)
```bash
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.your-domain.com
LOG_LEVEL=info
```

#### Production (.env.production)
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=error
```

## Local Development Setup

### Using PHP Built-in Server

1. **Clone the repository**
```bash
git clone https://github.com/your-org/botmirzapanel.git
cd botmirzapanel
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Setup environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Setup database**
```bash
php artisan migrate
php artisan db:seed
```

5. **Build assets**
```bash
npm run build
```

6. **Start development server**
```bash
php artisan serve
```

### Using Docker for Development

1. **Start services**
```bash
docker-compose up -d
```

2. **Install dependencies**
```bash
docker-compose exec app composer install
docker-compose exec app npm install
```

3. **Setup application**
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

## Production Deployment

### Manual Deployment

1. **Server Preparation**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis \
    php8.1-curl php8.1-json php8.1-mbstring php8.1-xml \
    php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

2. **Application Deployment**
```bash
# Clone repository
git clone https://github.com/your-org/botmirzapanel.git /var/www/botmirzapanel
cd /var/www/botmirzapanel

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# Setup environment
cp .env.production .env
php artisan key:generate

# Setup permissions
sudo chown -R www-data:www-data /var/www/botmirzapanel
sudo chmod -R 755 /var/www/botmirzapanel
sudo chmod -R 775 /var/www/botmirzapanel/storage
sudo chmod -R 775 /var/www/botmirzapanel/bootstrap/cache

# Build assets
npm run production

# Setup database
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Automated Deployment with GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
        
    - name: Install Composer dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
        
    - name: Install NPM dependencies
      run: npm ci
      
    - name: Build assets
      run: npm run production
      
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/botmirzapanel
          git pull origin main
          composer install --no-dev --optimize-autoloader
          npm ci --production
          npm run production
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo systemctl reload php8.1-fpm
          sudo systemctl reload nginx
```

## Docker Deployment

### Dockerfile
```dockerfile
FROM php:8.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm ci --production && npm run production

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

EXPOSE 9000

CMD ["php-fpm"]
```

### Docker Compose
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: botmirzapanel-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - botmirzapanel

  nginx:
    image: nginx:alpine
    container_name: botmirzapanel-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/sites/:/etc/nginx/sites-available
      - ./docker/nginx/ssl/:/etc/ssl/certs
    networks:
      - botmirzapanel

  mysql:
    image: mysql:8.0
    container_name: botmirzapanel-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: botmirzapanel
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_USER: botmirzapanel
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - botmirzapanel

  redis:
    image: redis:alpine
    container_name: botmirzapanel-redis
    restart: unless-stopped
    command: redis-server --requirepass redis_password
    networks:
      - botmirzapanel

volumes:
  mysql_data:

networks:
  botmirzapanel:
    driver: bridge
```

## Database Setup

### MySQL Configuration
```sql
-- Create database
CREATE DATABASE botmirzapanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'botmirzapanel'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON botmirzapanel.* TO 'botmirzapanel'@'localhost';
FLUSH PRIVILEGES;
```

### PostgreSQL Configuration
```sql
-- Create database
CREATE DATABASE botmirzapanel;

-- Create user
CREATE USER botmirzapanel WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE botmirzapanel TO botmirzapanel;
```

### Database Migrations
```bash
# Run migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed

# Reset database (development only)
php artisan migrate:fresh --seed
```

## Web Server Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/botmirzapanel/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/your-domain.com.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/botmirzapanel/public

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-domain.com.crt
    SSLCertificateKeyFile /etc/ssl/private/your-domain.com.key
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"

    <Directory /var/www/botmirzapanel/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Cache static assets
    <LocationMatch "\.(css|js|png|jpg|jpeg|gif|ico|woff|woff2)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
    </LocationMatch>
</VirtualHost>
```

## SSL/TLS Configuration

### Let's Encrypt with Certbot
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Manual SSL Certificate
```bash
# Generate private key
openssl genrsa -out your-domain.com.key 2048

# Generate certificate signing request
openssl req -new -key your-domain.com.key -out your-domain.com.csr

# Install certificate files
sudo cp your-domain.com.crt /etc/ssl/certs/
sudo cp your-domain.com.key /etc/ssl/private/
sudo chmod 600 /etc/ssl/private/your-domain.com.key
```

## Monitoring and Logging

### Log Configuration
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        'ignore_exceptions' => false,
    ],
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'BotMirzaPanel',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

### System Monitoring
```bash
# Install monitoring tools
sudo apt install htop iotop nethogs

# Setup log rotation
sudo nano /etc/logrotate.d/botmirzapanel
```

### Health Check Endpoint
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version'),
        'environment' => config('app.env'),
    ]);
});
```

## Backup and Recovery

### Database Backup
```bash
#!/bin/bash
# backup-database.sh

DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/botmirzapanel"
DB_NAME="botmirzapanel"
DB_USER="botmirzapanel"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

# MySQL backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete

echo "Database backup completed: db_backup_$DATE.sql.gz"
```

### File Backup
```bash
#!/bin/bash
# backup-files.sh

DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/botmirzapanel"
APP_DIR="/var/www/botmirzapanel"

mkdir -p $BACKUP_DIR

# Backup application files (excluding vendor and node_modules)
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    --exclude='storage/cache' \
    -C $APP_DIR .

echo "Files backup completed: files_backup_$DATE.tar.gz"
```

### Automated Backup with Cron
```bash
# Add to crontab
sudo crontab -e

# Daily database backup at 2 AM
0 2 * * * /var/scripts/backup-database.sh

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 /var/scripts/backup-files.sh
```

## Troubleshooting

### Common Issues

#### Permission Issues
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/botmirzapanel
sudo chmod -R 755 /var/www/botmirzapanel
sudo chmod -R 775 /var/www/botmirzapanel/storage
sudo chmod -R 775 /var/www/botmirzapanel/bootstrap/cache
```

#### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Database Connection Issues
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check database configuration
php artisan config:show database
```

#### Memory Issues
```bash
# Increase PHP memory limit
sudo nano /etc/php/8.1/fpm/php.ini
# memory_limit = 512M

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Log Analysis
```bash
# View application logs
tail -f /var/www/botmirzapanel/storage/logs/laravel.log

# View web server logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# View PHP-FPM logs
tail -f /var/log/php8.1-fpm.log
```

### Performance Optimization
```bash
# Enable OPcache
sudo nano /etc/php/8.1/fpm/php.ini
# opcache.enable=1
# opcache.memory_consumption=128
# opcache.max_accelerated_files=4000

# Optimize Composer autoloader
composer dump-autoload --optimize

# Enable Redis for sessions and cache
php artisan config:cache
```

### Security Checklist
- [ ] SSL/TLS certificate installed and configured
- [ ] Security headers configured
- [ ] File permissions set correctly
- [ ] Database credentials secured
- [ ] API keys and secrets in environment variables
- [ ] Firewall configured
- [ ] Regular security updates applied
- [ ] Backup system in place
- [ ] Monitoring and alerting configured
- [ ] Error reporting disabled in production

## Support

For deployment support:
- **Documentation**: https://docs.botmirzapanel.com/deployment
- **Support Email**: support@botmirzapanel.com
- **Discord**: https://discord.gg/botmirzapanel
- **GitHub Issues**: https://github.com/botmirzapanel/botmirzapanel/issues