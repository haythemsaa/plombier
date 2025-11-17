import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/services/analytics_service.dart';

/// Client analytics dashboard screen
class ClientAnalyticsScreen extends StatefulWidget {
  const ClientAnalyticsScreen({Key? key}) : super(key: key);

  @override
  State<ClientAnalyticsScreen> createState() => _ClientAnalyticsScreenState();
}

class _ClientAnalyticsScreenState extends State<ClientAnalyticsScreen> {
  String _selectedPeriod = 'month';
  ClientAnalytics? _analytics;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadAnalytics();
  }

  Future<void> _loadAnalytics() async {
    setState(() => _isLoading = true);

    // TODO: Get AnalyticsService from dependency injection or provider
    // final service = Provider.of<AnalyticsService>(context, listen: false);
    // final analytics = await service.getClientDashboard(period: _selectedPeriod);

    // Simulated for now - replace with actual API call
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      // _analytics = analytics;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Statistiques'),
        actions: [
          PopupMenuButton<String>(
            initialValue: _selectedPeriod,
            onSelected: (period) {
              setState(() => _selectedPeriod = period);
              _loadAnalytics();
            },
            itemBuilder: (context) => [
              const PopupMenuItem(value: 'week', child: Text('Cette semaine')),
              const PopupMenuItem(value: 'month', child: Text('Ce mois')),
              const PopupMenuItem(value: 'quarter', child: Text('Ce trimestre')),
              const PopupMenuItem(value: 'year', child: Text('Cette année')),
            ],
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _analytics == null
              ? _buildEmptyState()
              : _buildAnalyticsDashboard(),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.analytics_outlined, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Aucune donnée disponible',
            style: TextStyle(fontSize: 18, color: Colors.grey[600]),
          ),
          const SizedBox(height: 8),
          Text(
            'Commencez à réserver des services pour voir vos statistiques',
            style: TextStyle(color: Colors.grey[500]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildAnalyticsDashboard() {
    return RefreshIndicator(
      onRefresh: _loadAnalytics,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSpendingSection(),
          const SizedBox(height: 24),
          _buildSavingsSection(),
          const SizedBox(height: 24),
          _buildLoyaltySection(),
          const SizedBox(height: 24),
          _buildBookingsSection(),
          const SizedBox(height: 24),
          _buildFavoritesSection(),
          const SizedBox(height: 24),
          _buildPatternsSection(),
        ],
      ),
    );
  }

  Widget _buildSpendingSection() {
    final spending = _analytics!.spending;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.account_balance_wallet, color: Colors.blue[700]),
                const SizedBox(width: 8),
                const Text(
                  'Dépenses',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildStatCard(
                  'Total',
                  '${spending.total.toStringAsFixed(2)} TND',
                  Colors.blue,
                ),
                _buildStatCard(
                  'Moyenne',
                  '${spending.averagePerBooking.toStringAsFixed(2)} TND',
                  Colors.green,
                ),
              ],
            ),
            if (spending.byCategory.isNotEmpty) ...[
              const SizedBox(height: 16),
              const Text(
                'Par catégorie:',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 8),
              ...spending.byCategory.take(3).map((cat) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(cat.category),
                        Text(
                          '${cat.total.toStringAsFixed(2)} TND',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  )),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSavingsSection() {
    final savings = _analytics!.savings;

    if (savings.totalSaved == 0) return const SizedBox.shrink();

    return Card(
      color: Colors.green[50],
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.savings, color: Colors.green[700]),
                const SizedBox(width: 8),
                const Text(
                  'Économies',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              '${savings.totalSaved.toStringAsFixed(2)} TND économisés',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: Colors.green[700],
              ),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Column(
                  children: [
                    const Text('Packages'),
                    Text(
                      '${savings.fromPackages.toStringAsFixed(2)} TND',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                Column(
                  children: [
                    const Text('Fidélité'),
                    Text(
                      '${savings.fromLoyalty.toStringAsFixed(2)} TND',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLoyaltySection() {
    final loyalty = _analytics!.loyalty;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.star, color: _getTierColor(loyalty.currentTier)),
                const SizedBox(width: 8),
                Text(
                  'Fidélité - ${loyalty.currentTier}',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            LinearProgressIndicator(
              value: loyalty.pointsToNextTier == null
                  ? 1.0
                  : 0.7, // Simplified - calculate actual progress
              backgroundColor: Colors.grey[200],
              color: _getTierColor(loyalty.currentTier),
            ),
            const SizedBox(height: 8),
            Text(
              loyalty.pointsToNextTier == null
                  ? 'Niveau maximum atteint!'
                  : '${loyalty.pointsToNextTier} points pour le niveau suivant',
              style: const TextStyle(fontSize: 12),
            ),
            const SizedBox(height: 16),
            Text(
              'Points totaux: ${loyalty.totalPoints}',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            if (loyalty.tierBenefits.isNotEmpty) ...[
              const SizedBox(height: 12),
              const Text(
                'Avantages:',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              ...loyalty.tierBenefits.take(3).map((benefit) => Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(
                      children: [
                        const Icon(Icons.check_circle, size: 16, color: Colors.green),
                        const SizedBox(width: 8),
                        Expanded(child: Text(benefit, style: const TextStyle(fontSize: 12))),
                      ],
                    ),
                  )),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildBookingsSection() {
    final bookings = _analytics!.bookings;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.event_note, color: Colors.orange[700]),
                const SizedBox(width: 8),
                const Text(
                  'Réservations',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildStatCard('Total', bookings.total.toString(), Colors.blue),
                _buildStatCard('Terminées', bookings.completed.toString(), Colors.green),
                _buildStatCard('Annulées', bookings.cancelled.toString(), Colors.red),
              ],
            ),
            if (bookings.recurringActive > 0) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue[50],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.repeat, color: Colors.blue[700]),
                    const SizedBox(width: 8),
                    Text(
                      '${bookings.recurringActive} réservation(s) récurrente(s) active(s)',
                      style: TextStyle(color: Colors.blue[700]),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildFavoritesSection() {
    final favorites = _analytics!.favorites;

    if (favorites.services.isEmpty && favorites.providers.isEmpty) {
      return const SizedBox.shrink();
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.favorite, color: Colors.pink[700]),
                const SizedBox(width: 8),
                const Text(
                  'Favoris',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            if (favorites.services.isNotEmpty) ...[
              const SizedBox(height: 16),
              const Text('Services préférés:', style: TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              ...favorites.services.take(3).map((service) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(service.serviceName),
                        Text('${service.bookings}x', style: const TextStyle(color: Colors.grey)),
                      ],
                    ),
                  )),
            ],
            if (favorites.providers.isNotEmpty) ...[
              const SizedBox(height: 16),
              const Text('Prestataires préférés:', style: TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              ...favorites.providers.take(3).map((provider) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(provider.providerName),
                        Text('${provider.bookings}x', style: const TextStyle(color: Colors.grey)),
                      ],
                    ),
                  )),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildPatternsSection() {
    final patterns = _analytics!.patterns;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.insights, color: Colors.purple[700]),
                const SizedBox(width: 8),
                const Text(
                  'Habitudes',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildPatternRow('Fréquence', _getFrequencyLabel(patterns.bookingFrequency)),
            const SizedBox(height: 8),
            _buildPatternRow('Tendance dépenses', _getTrendLabel(patterns.spendingTrend)),
            if (patterns.preferredDays.isNotEmpty) ...[
              const SizedBox(height: 8),
              _buildPatternRow('Jours préférés', patterns.preferredDays.join(', ')),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String label, String value, Color color) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 4),
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      ],
    );
  }

  Widget _buildPatternRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
      ],
    );
  }

  Color _getTierColor(String tier) {
    switch (tier.toLowerCase()) {
      case 'platinum':
        return Colors.grey[800]!;
      case 'gold':
        return Colors.amber;
      case 'silver':
        return Colors.grey;
      default:
        return Colors.brown;
    }
  }

  String _getFrequencyLabel(String frequency) {
    switch (frequency) {
      case 'weekly':
        return 'Hebdomadaire';
      case 'bi-weekly':
        return 'Bi-hebdomadaire';
      case 'monthly':
        return 'Mensuelle';
      default:
        return 'Occasionnelle';
    }
  }

  String _getTrendLabel(String trend) {
    switch (trend) {
      case 'increasing':
        return '↑ En hausse';
      case 'decreasing':
        return '↓ En baisse';
      default:
        return '→ Stable';
    }
  }
}
