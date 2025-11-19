# 🚀 Guide de Déploiement - ServiceHub Tunisie

## Guide Rapide de Production (5 minutes)

### ⚡ Déploiement Backend (Laravel)

```bash
# 1. Clone le repository
cd backend

# 2. Installer les dépendances
composer install --optimize-autoloader --no-dev

# 3. Configuration
cp .env.example .env
# Éditer .env avec vos configurations production

# 4. Générer la clé
php artisan key:generate

# 5. Exécuter les migrations
php artisan migrate --force

# 6. Seeder les données initiales
php artisan db:seed --class=LoyaltyTiersSeeder

# 7. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Lancer le serveur (production)
php artisan serve --host=0.0.0.0 --port=8000
# OU utiliser Nginx/Apache

# 9. Lancer Queue Worker
php artisan queue:work --tries=3 --timeout=90

# 10. Lancer WebSocket Server
php artisan websockets:serve
```

### 📱 Déploiement Mobile (Flutter)

```bash
# 1. Aller dans le dossier frontend
cd frontend

# 2. Installer les dépendances
flutter pub get

# 3. Configuration environment
# Créer frontend/.env
echo "API_BASE_URL=https://api.servicehub.tn" > .env
echo "SOCKET_URL=wss://api.servicehub.tn" >> .env

# 4. Build Android (APK)
flutter build apk --release

# 5. Build Android (App Bundle pour Play Store)
flutter build appbundle --release

# 6. Build iOS (nécessite Mac)
flutter build ios --release

# Les fichiers sont dans:
# Android APK: build/app/outputs/flutter-apk/app-release.apk
# Android AAB: build/app/outputs/bundle/release/app-release.aab
# iOS: build/ios/iphoneos/Runner.app
```

---

## 🐳 Déploiement Docker (Recommandé)

### Backend Docker Compose

```bash
# Démarrer tous les services
docker-compose up -d --build

# Vérifier les logs
docker-compose logs -f backend

# Exécuter migrations
docker-compose exec backend php artisan migrate --force

# Seeder
docker-compose exec backend php artisan db:seed

# Services disponibles:
# - Backend API: http://localhost:8000
# - PostgreSQL: localhost:5432
# - Redis: localhost:6379
```

---

## ☁️ Déploiement Cloud

### Option 1: Heroku (Backend)

```bash
# 1. Login Heroku
heroku login

# 2. Créer app
heroku create servicehub-api

# 3. Add-ons
heroku addons:create heroku-postgresql:hobby-dev
heroku addons:create heroku-redis:hobby-dev

# 4. Config vars
heroku config:set APP_ENV=production
heroku config:set APP_KEY=base64:...
heroku config:set APP_DEBUG=false
heroku config:set APP_URL=https://servicehub-api.heroku.com

# 5. Deploy
git push heroku main

# 6. Run migrations
heroku run php artisan migrate --force
heroku run php artisan db:seed
```

### Option 2: AWS (Production)

**Backend:**
- EC2 pour l'API Laravel
- RDS PostgreSQL
- ElastiCache Redis
- S3 pour les fichiers
- CloudFront pour CDN
- Route53 pour DNS

**Mobile:**
- App Store Connect (iOS)
- Google Play Console (Android)
- Firebase pour notifications

### Option 3: DigitalOcean (Simple & Économique)

```bash
# 1. Créer Droplet Ubuntu 22.04
# 2. SSH dans le serveur
ssh root@your-server-ip

# 3. Install stack
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-pgsql postgresql redis-server

# 4. Clone & setup
git clone https://github.com/your-repo/plombier
cd plombier/backend
composer install --optimize-autoloader --no-dev

# 5. Configure Nginx
# (voir config nginx ci-dessous)

# 6. SSL avec Let's Encrypt
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.servicehub.tn
```

---

## 📋 Configuration Nginx (Production)

```nginx
server {
    listen 80;
    server_name api.servicehub.tn;
    root /var/www/plombier/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🔐 Variables d'Environment

### Backend (.env)

```env
# App
APP_NAME="ServiceHub Tunisie"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://api.servicehub.tn

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=servicehub
DB_USERNAME=servicehub_user
DB_PASSWORD=VOTRE_MOT_DE_PASSE

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@servicehub.tn
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@servicehub.tn
MAIL_FROM_NAME="${APP_NAME}"

# Firebase
FIREBASE_CREDENTIALS=path/to/firebase-credentials.json

# Sentry
SENTRY_LARAVEL_DSN=https://...@sentry.io/...

# PayTech
PAYTECH_API_KEY=...
PAYTECH_API_SECRET=...
```

### Mobile (.env)

```env
API_BASE_URL=https://api.servicehub.tn
SOCKET_URL=wss://api.servicehub.tn
ENVIRONMENT=production
FIREBASE_ANDROID_APP_ID=...
FIREBASE_IOS_APP_ID=...
```

---

## 📱 Publication App Stores

### Google Play Store

1. **Préparer APK/AAB**
   ```bash
   flutter build appbundle --release
   ```

2. **Console Google Play**
   - https://play.google.com/console
   - Créer une nouvelle app
   - Upload AAB
   - Remplir les infos (description, screenshots, etc.)
   - Soumettre pour review

3. **Screenshots requis**
   - Phone: 1080x1920 (min 2, max 8)
   - Tablet 7": 1200x1920
   - Tablet 10": 1600x2560

### Apple App Store

1. **Préparer IPA**
   ```bash
   flutter build ios --release
   # Puis ouvrir Xcode et archiver
   ```

2. **App Store Connect**
   - https://appstoreconnect.apple.com
   - Créer nouvelle app
   - Upload via Xcode Organizer
   - Remplir les infos
   - Soumettre pour review

3. **Screenshots requis**
   - iPhone 6.7": 1290x2796
   - iPhone 6.5": 1242x2688
   - iPad Pro 12.9": 2048x2732

---

## ✅ Checklist de Production

### Backend
- [ ] Variables d'environment configurées
- [ ] Base de données créée et migrations executées
- [ ] Seeders exécutés (LoyaltyTiers, etc.)
- [ ] Cache Laravel configuré
- [ ] Queue worker en cours d'exécution
- [ ] WebSocket server actif
- [ ] SSL/HTTPS configuré
- [ ] Firewall configuré
- [ ] Backups automatiques configurés
- [ ] Monitoring (Sentry, etc.) actif
- [ ] Redis configuré
- [ ] Emails testés

### Mobile
- [ ] API_BASE_URL pointe vers production
- [ ] Firebase configuré (iOS + Android)
- [ ] Google Maps API key configurée
- [ ] App icons générés
- [ ] Splash screen configuré
- [ ] Version code/name mis à jour
- [ ] Build release sans erreurs
- [ ] Test sur devices réels (iOS + Android)
- [ ] Permissions vérifiées
- [ ] Deep links testés
- [ ] Push notifications testées

### App Stores
- [ ] Compte développeur Google Play (25$ one-time)
- [ ] Compte développeur Apple (99$/an)
- [ ] App description rédigée (FR + AR + EN)
- [ ] Screenshots créés (tous les formats)
- [ ] Privacy policy URL active
- [ ] Terms of service URL active
- [ ] App icon 512x512
- [ ] Feature graphic 1024x500
- [ ] Video promo (optionnel)

---

## 🎯 URLs de Production

```
Backend API: https://api.servicehub.tn
WebSocket: wss://api.servicehub.tn
Admin Panel: https://admin.servicehub.tn
Website: https://servicehub.tn

Android App: https://play.google.com/store/apps/details?id=tn.servicehub.mobile
iOS App: https://apps.apple.com/app/servicehub/id...
```

---

## 📊 Monitoring

### Sentry
- Error tracking
- Performance monitoring
- https://sentry.io

### Firebase Analytics
- User analytics
- Crash reporting
- https://console.firebase.google.com

### Laravel Telescope (Dev only)
- Query monitoring
- Request/Response logs
- http://localhost/telescope

---

## 🔄 Mise à jour

### Backend
```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan queue:restart
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

### Mobile
```bash
# Mettre à jour version dans pubspec.yaml
# version: 2.3.0+230

flutter pub get
flutter build appbundle --release  # Android
flutter build ios --release  # iOS

# Upload nouvelle version sur stores
```

---

## 💡 Astuces Production

1. **Performance**
   - Activer Redis cache
   - Utiliser CDN pour assets
   - Optimiser images
   - Activer Gzip

2. **Sécurité**
   - HTTPS uniquement
   - Rate limiting actif
   - CORS configuré
   - Headers sécurité

3. **Scalabilité**
   - Load balancer
   - Multiple workers
   - Database replicas
   - Queue workers distribués

4. **Backups**
   - DB backup quotidien
   - Files backup hebdomadaire
   - Retention 30 jours

---

## 📞 Support

Email: support@servicehub.tn
Docs: https://docs.servicehub.tn
Status: https://status.servicehub.tn

---

**Version**: 2.2.0
**Date**: Novembre 2025
**Statut**: Production Ready ✅
