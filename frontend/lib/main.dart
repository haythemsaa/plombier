import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'core/services/api_service.dart';
import 'core/services/storage_service.dart';
import 'features/auth/providers/auth_provider.dart';
import 'features/booking/providers/booking_provider.dart';
import 'features/booking/providers/review_provider.dart';
import 'features/services/providers/service_provider.dart';
import 'features/services/providers/provider_provider.dart';
import 'features/address/providers/address_provider.dart';
import 'features/notification/providers/notification_provider.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize services
  final storageService = StorageService();
  await storageService.init();

  final apiService = ApiService();

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => AuthProvider(apiService, storageService),
        ),
        ChangeNotifierProvider(
          create: (_) => BookingProvider(apiService),
        ),
        ChangeNotifierProvider(
          create: (_) => ReviewProvider(apiService),
        ),
        ChangeNotifierProvider(
          create: (_) => ServiceProvider(apiService),
        ),
        ChangeNotifierProvider(
          create: (_) => ProviderProvider(apiService),
        ),
        ChangeNotifierProvider(
          create: (_) => AddressProvider(apiService),
        ),
        ChangeNotifierProvider(
          create: (_) => NotificationProvider(apiService),
        ),
      ],
      child: const ServiceHubApp(),
    ),
  );
}

class ServiceHubApp extends StatelessWidget {
  const ServiceHubApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ServiceHub Tunisie',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2196F3),
          brightness: Brightness.light,
        ),
        useMaterial3: true,
        fontFamily: 'Cairo',
      ),
      darkTheme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2196F3),
          brightness: Brightness.dark,
        ),
        useMaterial3: true,
        fontFamily: 'Cairo',
      ),
      home: const SplashScreen(),
    );
  }
}

class SplashScreen extends StatelessWidget {
  const SplashScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Logo
            Icon(
              Icons.home_repair_service,
              size: 100,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 24),
            Text(
              'ServiceHub Tunisie',
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Tous vos services à portée de main',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 48),
            const CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}
