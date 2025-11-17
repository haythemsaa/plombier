# Guide d'Installation - ServiceHub Tunisie

## Prérequis

### Backend
- PHP 8.2 ou supérieur
- Composer 2.x
- PostgreSQL 15+
- Redis 7+
- Node.js 18+ (pour la compilation des assets)

### Frontend
- Flutter 3.16+
- Dart SDK 3+
- Android Studio (pour développement Android)
- Xcode (pour développement iOS, macOS uniquement)

## Installation Backend (Laravel)

### 1. Cloner le repository

```bash
git clone https://github.com/haythemsaa/plombier.git
cd plombier/backend
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
```

Éditez le fichier `.env` et configurez :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=servicehub
DB_USERNAME=postgres
DB_PASSWORD=votre_mot_de_passe

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 4. Créer la base de données

```bash
# Créer la base de données PostgreSQL
psql -U postgres
CREATE DATABASE servicehub;
\q
```

### 5. Générer la clé d'application

```bash
php artisan key:generate
```

### 6. Exécuter les migrations

```bash
php artisan migrate
```

### 7. Installer Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 8. (Optionnel) Seeder les données de test

```bash
php artisan db:seed
```

### 9. Lancer le serveur de développement

```bash
php artisan serve
```

Le backend sera accessible sur : `http://localhost:8000`

### 10. Configurer Redis Queue (Optionnel)

Dans un terminal séparé :

```bash
php artisan queue:work
```

## Installation Frontend (Flutter)

### 1. Naviguer vers le dossier frontend

```bash
cd ../frontend
```

### 2. Installer les dépendances

```bash
flutter pub get
```

### 3. Configurer l'API URL

Éditez `lib/core/services/api_service.dart` et modifiez :

```dart
final String baseUrl = 'http://localhost:8000/api'; // Développement local
// ou
final String baseUrl = 'https://api.servicehub.tn/api'; // Production
```

### 4. Configurer Firebase

1. Créez un projet Firebase sur https://console.firebase.google.com
2. Téléchargez `google-services.json` (Android) et placez-le dans `android/app/`
3. Téléchargez `GoogleService-Info.plist` (iOS) et placez-le dans `ios/Runner/`

### 5. Configurer Google Maps

Éditez `android/app/src/main/AndroidManifest.xml` :

```xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="VOTRE_CLE_API_GOOGLE_MAPS"/>
```

Éditez `ios/Runner/AppDelegate.swift` :

```swift
GMSServices.provideAPIKey("VOTRE_CLE_API_GOOGLE_MAPS")
```

### 6. Lancer l'application

#### Android

```bash
flutter run -d android
```

#### iOS

```bash
flutter run -d ios
```

#### Émulateur Web (développement uniquement)

```bash
flutter run -d chrome
```

## Configuration Production

### Backend

#### 1. Optimisations

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 2. Configurer HTTPS et domaine

Éditez `.env` :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://servicehub.tn
```

#### 3. Configurer le serveur web (Nginx)

Exemple de configuration Nginx :

```nginx
server {
    listen 80;
    server_name api.servicehub.tn;
    root /var/www/servicehub/backend/public;

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

#### 4. Configurer le supervisord pour les queues

Créez `/etc/supervisor/conf.d/servicehub-worker.conf` :

```ini
[program:servicehub-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/servicehub/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/servicehub/backend/storage/logs/worker.log
stopwaitsecs=3600
```

Puis :

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start servicehub-worker:*
```

### Frontend

#### 1. Build Android (APK)

```bash
flutter build apk --release
```

APK généré : `build/app/outputs/flutter-apk/app-release.apk`

#### 2. Build Android (App Bundle pour Play Store)

```bash
flutter build appbundle --release
```

AAB généré : `build/app/outputs/bundle/release/app-release.aab`

#### 3. Build iOS

```bash
flutter build ios --release
```

Ouvrez `ios/Runner.xcworkspace` dans Xcode pour archiver et soumettre.

## Docker (Optionnel)

Un `docker-compose.yml` est fourni pour faciliter le développement :

```bash
cd plombier
docker-compose up -d
```

Cela lancera :
- PostgreSQL sur le port 5432
- Redis sur le port 6379
- Laravel backend sur le port 8000

## Dépannage

### Problème : SQLSTATE[08006] Connection refused

Solution : Vérifiez que PostgreSQL est en cours d'exécution et que les identifiants dans `.env` sont corrects.

```bash
sudo service postgresql status
sudo service postgresql start
```

### Problème : Flutter doctor montre des erreurs

Solution : Exécutez et suivez les instructions :

```bash
flutter doctor -v
```

### Problème : Redis connection refused

Solution : Démarrez Redis :

```bash
sudo service redis-server start
```

### Problème : Permission denied sur storage/

Solution :

```bash
cd backend
chmod -R 775 storage bootstrap/cache
```

## Support

Pour toute question ou problème :
- Email : support@servicehub.tn
- Issues GitHub : https://github.com/haythemsaa/plombier/issues

---

**Documentation mise à jour** : Novembre 2025
