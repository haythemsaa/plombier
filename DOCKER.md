# 🐳 Guide de Déploiement Docker - ServiceHub Tunisie

Ce guide explique comment déployer l'application ServiceHub avec Docker et Docker Compose.

## 📋 Prérequis

- Docker 20.10+
- Docker Compose 2.0+
- 2 GB RAM minimum
- 10 GB d'espace disque

## 🚀 Démarrage Rapide

### 1. Configuration de l'environnement

Copiez le fichier d'environnement exemple :

```bash
cp .env.example .env
```

Modifiez `.env` selon vos besoins :

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_PORT=8000

# Database
DB_DATABASE=servicehub
DB_USERNAME=postgres
DB_PASSWORD=VotreMotDePasseSecurise

# Redis (laissez par défaut)
REDIS_HOST=redis
REDIS_PORT=6379
```

### 2. Lancer les services

```bash
# Build et démarrer tous les services
docker-compose up -d --build

# Vérifier que tous les services sont démarrés
docker-compose ps
```

### 3. Initialiser la base de données

```bash
# Accéder au conteneur backend
docker-compose exec backend sh

# Exécuter les migrations
php artisan migrate

# Seeder les données initiales
php artisan db:seed

# Quitter le conteneur
exit
```

### 4. Accéder à l'application

- **API Backend**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation (à venir)

## 📦 Architecture Docker

L'application utilise 4 services :

1. **postgres** - Base de données PostgreSQL 15
2. **redis** - Cache et gestion de files d'attente
3. **backend** - API Laravel avec Nginx + PHP-FPM
4. **queue** - Worker Laravel pour les jobs asynchrones

## 🔧 Commandes Utiles

### Gestion des conteneurs

```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Redémarrer un service spécifique
docker-compose restart backend

# Voir les logs
docker-compose logs -f

# Voir les logs d'un service spécifique
docker-compose logs -f backend
```

### Administration backend

```bash
# Accéder au shell du backend
docker-compose exec backend sh

# Exécuter une commande Artisan
docker-compose exec backend php artisan <command>

# Vider le cache
docker-compose exec backend php artisan cache:clear

# Lister les routes
docker-compose exec backend php artisan route:list

# Créer un nouvel utilisateur admin
docker-compose exec backend php artisan tinker
```

### Base de données

```bash
# Accéder à PostgreSQL
docker-compose exec postgres psql -U postgres -d servicehub

# Backup de la base de données
docker-compose exec postgres pg_dump -U postgres servicehub > backup.sql

# Restaurer une sauvegarde
docker-compose exec -T postgres psql -U postgres servicehub < backup.sql
```

### Gestion des files d'attente

```bash
# Voir les jobs en queue
docker-compose exec backend php artisan queue:monitor

# Redémarrer le queue worker
docker-compose restart queue

# Vider toutes les queues
docker-compose exec backend php artisan queue:flush
```

## 🔒 Sécurité

### En production

1. **Changez tous les mots de passe par défaut**

```env
DB_PASSWORD=MotDePasseComplexe123!
REDIS_PASSWORD=AutreMotDePasseSecurise456!
```

2. **Désactivez le mode debug**

```env
APP_DEBUG=false
APP_ENV=production
```

3. **Configurez HTTPS avec un reverse proxy** (Nginx, Caddy, Traefik)

4. **Utilisez des secrets Docker** pour les données sensibles

### Backup automatique

Créez un cron job pour sauvegarder automatiquement :

```bash
# Backup quotidien à 2h du matin
0 2 * * * docker-compose exec postgres pg_dump -U postgres servicehub > /backups/servicehub-$(date +\%Y\%m\%d).sql
```

## 🎯 Optimisations Performance

### Cache

```bash
# Cacher les configurations
docker-compose exec backend php artisan config:cache

# Cacher les routes
docker-compose exec backend php artisan route:cache

# Cacher les vues
docker-compose exec backend php artisan view:cache
```

### Augmenter les ressources

Modifiez `docker-compose.yml` pour allouer plus de ressources :

```yaml
backend:
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
```

## 🐛 Dépannage

### Le backend ne démarre pas

```bash
# Vérifier les logs
docker-compose logs backend

# Reconstruire l'image
docker-compose build --no-cache backend
docker-compose up -d backend
```

### Problème de permissions

```bash
# Corriger les permissions
docker-compose exec backend chown -R www-data:www-data /var/www/html
docker-compose exec backend chmod -R 775 /var/www/html/storage
```

### Base de données inaccessible

```bash
# Vérifier que PostgreSQL est démarré
docker-compose ps postgres

# Tester la connexion
docker-compose exec postgres pg_isready -U postgres
```

## 📊 Monitoring

### Vérifier la santé des services

```bash
# Vérifier l'état de tous les conteneurs
docker-compose ps

# Vérifier les ressources utilisées
docker stats
```

### Logs en temps réel

```bash
# Tous les services
docker-compose logs -f

# Service spécifique
docker-compose logs -f backend
docker-compose logs -f postgres
docker-compose logs -f queue
```

## 🔄 Mise à jour

```bash
# 1. Sauvegarder la base de données
docker-compose exec postgres pg_dump -U postgres servicehub > backup_pre_update.sql

# 2. Récupérer les nouvelles modifications
git pull origin main

# 3. Reconstruire et redémarrer
docker-compose down
docker-compose up -d --build

# 4. Exécuter les migrations
docker-compose exec backend php artisan migrate

# 5. Vider les caches
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
```

## 🆘 Support

Pour toute question ou problème :

1. Consultez les logs : `docker-compose logs -f`
2. Vérifiez la documentation Laravel : https://laravel.com/docs
3. Ouvrez une issue sur GitHub

---

**Version**: 1.2.0
**Dernière mise à jour**: Novembre 2025
