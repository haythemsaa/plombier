# ServiceHub Tunisie

## Super Application pour Services du Quotidien - Marché Tunisien

ServiceHub est une plateforme tout-en-un permettant aux utilisateurs d'accéder à une large gamme de services professionnels à domicile via une seule application.

### 🎯 Vision

Devenir la plateforme de référence en Tunisie pour tous les services du quotidien, en digitalisant l'économie informelle et en créant un écosystème de confiance qui profite à tous les acteurs.

### 🚀 Services Couverts (Phase 1)

1. **Services de maintenance**
   - Plomberie, Électricité, Climatisation, Menuiserie

2. **Services de nettoyage**
   - Ménage régulier, Nettoyage approfondi, Nettoyage de vitres

3. **Services de garde et assistance**
   - Babysitting, Garde de personnes âgées, Aide aux devoirs

4. **Services de transport et logistique**
   - Déménagement, Transport de marchandises, Coursier

5. **Services de beauté à domicile**
   - Coiffure, Esthétique, Manucure/Pédicure

## 📁 Structure du Projet

```
plombier/
├── backend/              # Backend Laravel 11 (PHP 8.2+)
│   ├── app/
│   ├── database/
│   ├── routes/
│   └── ...
├── frontend/             # Application mobile Flutter
│   ├── lib/
│   ├── android/
│   ├── ios/
│   └── ...
├── docs/                 # Documentation
│   ├── api/
│   └── specs/
└── README.md
```

## 🛠 Stack Technologique

### Backend
- **Framework**: Laravel 11
- **Language**: PHP 8.2+
- **Database**: PostgreSQL 15+
- **Cache**: Redis 7
- **Search**: Elasticsearch 8
- **Storage**: AWS S3 / MinIO
- **Queue**: Redis Queue

### Frontend Mobile
- **Framework**: Flutter 3.16+
- **Language**: Dart 3+
- **State Management**: Provider / Riverpod
- **Maps**: Google Maps / Mapbox
- **Notifications**: Firebase Cloud Messaging

### Infrastructure
- **Cloud**: AWS / Google Cloud Platform
- **CDN**: CloudFront
- **Payment Gateway**: PayTech (Tunisie), Clictopay, Stripe

## 📦 Installation

### Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- PostgreSQL 15+
- Redis 7+
- Flutter 3.16+
- Docker (optionnel)

### Backend (Laravel)

```bash
cd backend

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer la base de données dans .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=servicehub
# DB_USERNAME=postgres
# DB_PASSWORD=secret

# Exécuter les migrations
php artisan migrate

# Seeder les données initiales
php artisan db:seed

# Lancer le serveur
php artisan serve
```

### Frontend (Flutter)

```bash
cd frontend

# Installer les dépendances
flutter pub get

# Lancer l'application (iOS)
flutter run -d ios

# Lancer l'application (Android)
flutter run -d android
```

## ✅ Fonctionnalités Implémentées

### 1. Authentification & Utilisateurs
- ✅ Inscription client/prestataire avec validation complète
- ✅ Connexion par téléphone + mot de passe
- ✅ JWT tokens avec Laravel Sanctum
- ✅ Gestion de profil (update, change password)
- ✅ Support multi-langue (AR/FR)
- ✅ Statuts utilisateur et soft deletes

### 2. Services & Catégories
- ✅ 5 catégories de services
- ✅ 15+ services seedés (bilingue AR/FR)
- ✅ Filtrage par catégorie et recherche
- ✅ Prix de base et commissions configurables

### 3. Prestataires
- ✅ Profils détaillés avec vérification CIN
- ✅ Zones de couverture géographiques
- ✅ **Algorithme de matching intelligent** (score de pertinence)
- ✅ Statistiques en temps réel (note moyenne, taux de complétion)
- ✅ Abonnements (Starter, Pro, Premium)

### 4. Réservations (Bookings)
- ✅ Workflow complet: pending → confirmed → in_progress → completed
- ✅ **Tarification dynamique** (surcharges urgente, nuit, weekend)
- ✅ **Calcul de commission intelligent** par type de service
- ✅ **Système d'annulation** avec frais progressifs
- ✅ Gestion des photos avant/après
- ✅ 7 endpoints API complets

### 5. Système d'Avis & Notations
- ✅ Notation multi-dimensionnelle (global, professionnalisme, qualité, rapport qualité/prix)
- ✅ Réponses des prestataires
- ✅ MAJ automatique des statistiques prestataire
- ✅ Distribution des notes et avis vedettes
- ✅ 8 endpoints API
- ✅ UI Flutter complète (RatingStars, ReviewCard, SubmitReviewScreen)

### 6. Adresses
- ✅ CRUD complet avec adresses multiples
- ✅ Gestion adresse par défaut automatique
- ✅ Support géocodage (lat/lng)
- ✅ Labels personnalisés

### 7. Système de Paiement
- ✅ **PaymentService** avec intégration multi-gateway
- ✅ PayTech (Tunisie), ClicToPay, Stripe
- ✅ Méthodes: Carte bancaire, D17, Wallet, Espèces
- ✅ Génération URL de paiement et webhooks
- ✅ Système de remboursement

### 8. Système de Notifications
- ✅ Notifications intelligentes (réservation, avis, paiement)
- ✅ Statut lu/non-lu et compteur
- ✅ 4 endpoints API

## 🗄️ Base de Données

**15 tables PostgreSQL implémentées:**

- **users**: Utilisateurs (UUID, types, soft deletes)
- **clients**: Profils clients avec loyalty points
- **providers**: Profils prestataires avec ratings
- **addresses**: Adresses multiples avec géocodage
- **service_categories**: 5 catégories principales
- **services**: 15+ services bilingues
- **provider_services**: Services par prestataire avec tarifs
- **provider_zones**: Zones de couverture
- **provider_documents**: Documents de vérification
- **bookings**: Réservations avec workflow complet
- **booking_photos**: Photos avant/après
- **reviews**: Avis multi-dimensionnels
- **payments**: Paiements multi-gateway
- **payouts**: Versements prestataires
- **notifications**: Notifications système

## 🔐 Sécurité

- Authentification JWT via Laravel Sanctum
- Vérification d'identité (CIN) avec OCR
- Chiffrement des données sensibles
- Conformité RGPD/INPDP
- Protection contre OWASP Top 10

## 💳 Système de Paiement

- **Gateway Principal**: PayTech (Tunisie)
- **Backup**: Clictopay
- **International**: Stripe (touristes/expats)
- **Méthodes**: Carte bancaire, D17, Wallet

### Commissions

- Services maintenance: 18%
- Services nettoyage: 20%
- Services garde: 15%

## 📊 Métriques Clés

### Objectifs Année 1
- 10 000 utilisateurs actifs
- 500 prestataires certifiés
- 30 000 services réalisés
- Taux de satisfaction: >85%
- GMV: 3M TND

### Objectifs Année 3
- 100 000 utilisateurs actifs
- 3 000 prestataires
- 500 000 services annuels
- Expansion dans 20 gouvernorats

## 🚦 Roadmap

### Phase 0: Préparation (Mois -3 à 0)
- ✅ Structuration juridique
- ✅ Équipe core
- ✅ Développement MVP

### Phase 1: Lancement (Mois 1-3)
- 🔄 Soft launch
- 🔄 Recrutement prestataires
- 🔄 Campagne marketing

### Phase 2: Croissance (Mois 4-12)
- 📅 Expansion géographique
- 📅 Nouveaux services
- 📅 Programme fidélité

### Phase 3: Maturité (Année 2-3)
- 📅 Couverture nationale
- 📅 Services B2B
- 📅 Rentabilité

## 🧪 Tests

```bash
# Tests backend
cd backend
php artisan test

# Tests frontend
cd frontend
flutter test
```

## 📊 Métriques de Code

- **Backend**: ~8000+ lignes de code
- **Frontend**: ~3000+ lignes de code
- **Total fichiers**: 100+
- **API Endpoints**: 40+
- **Flutter Screens**: 6
- **Widgets réutilisables**: 10+
- **Services métier**: 5 (BookingService, MatchingService, ReviewService, PaymentService, NotificationService)

## 🧪 Comptes de Test

Après avoir exécuté `php artisan db:seed`, vous pouvez utiliser:

**Client:**
- Email: client@servicehub.tn
- Phone: +216 98 123 456
- Password: password

**Prestataires:**
- plombier@servicehub.tn / password (Plomberie)
- electricien@servicehub.tn / password (Électricité)
- menage@servicehub.tn / password (Nettoyage)
- clim@servicehub.tn / password (Climatisation)
- babysitting@servicehub.tn / password (Babysitting)

## 📝 Documentation

- [Cahier des charges complet](./ServiceHub_Tunisie_Specs_Partie1.md)
- [Spécifications techniques](./ServiceHub_Tunisie_Specs_Partie2.md)
- [Stratégie de croissance](./ServiceHub_Tunisie_Specs_Partie3.md)
- [Installation Guide](./docs/INSTALLATION.md)
- [API Documentation](./docs/API_DOCUMENTATION.md)
- [Features List](./FEATURES.md)

## 👥 Équipe

- **CEO**: Direction générale
- **CTO**: Direction technique
- **CMO**: Direction marketing
- **Operations Manager**: Gestion des opérations

## 📞 Contact

- **Email**: contact@servicehub.tn
- **Téléphone**: +216 XX XXX XXX
- **Web**: www.servicehub.tn (à venir)

## 📄 Licence

Propriétaire - Tous droits réservés

## 🙏 Remerciements

- Communauté Laravel Tunisie
- Communauté Flutter
- Partenaires et early adopters

---

**Version**: 1.2.0
**Date**: Novembre 2025
**Statut**: Production Ready ✅
