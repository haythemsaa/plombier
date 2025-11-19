# 📱 ServiceHub Mobile - Flutter App

Application mobile complète pour ServiceHub Tunisie - Plateforme de services à domicile.

## ✨ Fonctionnalités (40 features)

### V1.0-V1.5 (29 features)
- ✅ Authentification complète (Login, Register, JWT)
- ✅ Recherche et filtres avancés de services
- ✅ Réservations avec workflow complet
- ✅ Système d'avis et notations
- ✅ Gestion d'adresses multiples
- ✅ Paiements multi-gateway (PayTech, Stripe)
- ✅ Notifications push Firebase
- ✅ Dashboard prestataire
- ✅ Multi-langue (FR, AR, EN)
- ✅ Dark mode
- ✅ Et 20+ autres features...

### V2.0 (6 features)
- ✅ Chat temps réel (WebSocket)
- ✅ Calendrier disponibilité providers
- ✅ Réservation instantanée
- ✅ Pricing dynamique
- ✅ Programme parrainage
- ✅ Wallet utilisateur

### V2.1 (4 features)
- ✅ Packages & Abonnements (MRR)
- ✅ Programme fidélité 4 tiers
- ✅ Réservations récurrentes
- ✅ Tracking GPS temps réel

### V2.2 (1 feature)
- ✅ Analytics Dashboard complet

## 🚀 Installation Rapide (2 minutes)

```bash
# 1. Installer les dépendances
flutter pub get

# 2. Configurer l'environment
echo "API_BASE_URL=http://localhost:8000" > .env

# 3. Lancer l'app
flutter run

# 4. Build pour production
flutter build apk --release  # Android
flutter build ios --release  # iOS (nécessite Mac)
```

## 📦 Dépendances Principales

```yaml
# State Management
provider: ^6.1.1

# Navigation
go_router: ^13.0.0

# Networking
http: ^1.2.0
dio: ^5.4.0
socket_io_client: ^2.0.3+1

# Firebase
firebase_core: ^2.24.2
firebase_messaging: ^14.7.10
firebase_analytics: ^10.8.0

# Maps & Location
google_maps_flutter: ^2.5.3
geolocator: ^10.1.0

# UI Components
cached_network_image: ^3.3.1
flutter_svg: ^2.0.9
shimmer: ^3.0.0
fl_chart: ^0.65.0

# Et 20+ autres...
```

## 📱 Structure du Projet

```
frontend/lib/
├── core/
│   ├── config/          # Configuration (API, constantes, etc.)
│   ├── network/         # API Client avec interceptors
│   ├── routes/          # Routes de navigation
│   ├── services/        # Services backend integration
│   └── utils/           # Utilitaires (storage, etc.)
├── screens/
│   ├── client/          # Écrans client
│   │   ├── packages_screen.dart
│   │   ├── subscriptions_screen.dart
│   │   ├── loyalty_screen.dart
│   │   ├── recurring_bookings_screen.dart
│   │   ├── live_tracking_screen.dart
│   │   └── analytics_screen.dart
│   └── provider/        # Écrans prestataire
│       ├── tracking_control_screen.dart
│       └── analytics_screen.dart
└── main.dart           # Point d'entrée
```

## 🔧 Configuration

### 1. Firebase

Placez vos fichiers de configuration Firebase:
- Android: `android/app/google-services.json`
- iOS: `ios/Runner/GoogleService-Info.plist`

### 2. Google Maps

Dans `android/app/src/main/AndroidManifest.xml`:
```xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="YOUR_GOOGLE_MAPS_API_KEY"/>
```

Dans `ios/Runner/AppDelegate.swift`:
```swift
GMSServices.provideAPIKey("YOUR_GOOGLE_MAPS_API_KEY")
```

### 3. Environment Variables

Créer `.env` à la racine de `frontend/`:
```env
API_BASE_URL=https://api.servicehub.tn
SOCKET_URL=wss://api.servicehub.tn
ENVIRONMENT=production
```

## 🎨 Thèmes

L'app supporte:
- ✅ Light Mode (par défaut)
- ✅ Dark Mode
- ✅ Système Auto

Les couleurs principales:
- Primary: Blue 700 (#1976D2)
- Secondary: Orange 500 (#FF9800)
- Success: Green 500 (#4CAF50)
- Error: Red 500 (#F44336)

## 🌍 Langues Support

- 🇫🇷 Français (par défaut)
- 🇸🇦 Arabe (RTL)
- 🇬🇧 Anglais

## 📱 Écrans Disponibles

### Client (9+ écrans)
1. **Home** - Recherche services
2. **Packages** - Liste packages & abonnements
3. **Subscriptions** - Gestion abonnements
4. **Loyalty** - Programme fidélité
5. **Recurring Bookings** - Réservations récurrentes
6. **Live Tracking** - Suivi GPS provider
7. **Analytics** - Dashboard dépenses & économies
8. **Chat** - Messages temps réel
9. **Profile** - Gestion compte

### Provider (3+ écrans)
1. **Dashboard** - Vue d'ensemble
2. **Tracking Control** - Contrôle GPS
3. **Analytics** - Dashboard revenus & performance

## 🧪 Tests

```bash
# Tests unitaires
flutter test

# Tests d'intégration
flutter test integration_test/

# Analyse de code
flutter analyze
```

## 📦 Build Release

### Android

```bash
# APK
flutter build apk --release

# App Bundle (pour Play Store)
flutter build appbundle --release

# Fichier: build/app/outputs/bundle/release/app-release.aab
```

### iOS

```bash
# Build
flutter build ios --release

# Ensuite ouvrir Xcode et archiver
open ios/Runner.xcworkspace
```

## 🔐 Permissions

### Android (`AndroidManifest.xml`)
```xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION"/>
<uses-permission android:name="android.permission.CAMERA"/>
```

### iOS (`Info.plist`)
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Nous avons besoin de votre localisation pour trouver des services près de vous</string>
<key>NSCameraUsageDescription</key>
<string>Nous avons besoin d'accès à votre caméra pour prendre des photos</string>
```

## 📊 Performance

- Temps de démarrage: <3s
- Taille APK: ~20MB
- Taille IPA: ~30MB
- Cible: Flutter 3.16+, Dart 3.0+

## 🐛 Debug

```bash
# Logs en temps réel
flutter logs

# Mode debug avec hot reload
flutter run

# Profiler performance
flutter run --profile
```

## 📚 Documentation API

Voir: https://api.servicehub.tn/api/documentation

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/amazing`)
3. Commit (`git commit -m 'Add amazing feature'`)
4. Push (`git push origin feature/amazing`)
5. Pull Request

## 📞 Support

- Email: support@servicehub.tn
- Docs: https://docs.servicehub.tn

---

**Version**: 2.2.0 (Build 220)
**Dernière mise à jour**: Novembre 2025
**Statut**: Production Ready ✅
**Plateformes**: iOS 12+ | Android 7.0+ (API 24+)
