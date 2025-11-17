import 'package:flutter/material.dart';
import '../../core/services/analytics_service.dart';

/// Provider analytics dashboard screen
class ProviderAnalyticsScreen extends StatefulWidget {
  const ProviderAnalyticsScreen({Key? key}) : super(key: key);

  @override
  State<ProviderAnalyticsScreen> createState() => _ProviderAnalyticsScreenState();
}

class _ProviderAnalyticsScreenState extends State<ProviderAnalyticsScreen> {
  String _selectedPeriod = 'month';
  ProviderAnalytics? _analytics;
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
    // final analytics = await service.getProviderDashboard(period: _selectedPeriod);

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
        title: const Text('Tableau de Bord'),
        backgroundColor: Colors.blue[700],
        foregroundColor: Colors.white,
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
            'Commencez à accepter des réservations pour voir vos statistiques',
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
          _buildEarningsSection(),
          const SizedBox(height: 16),
          _buildBookingsSection(),
          const SizedBox(height: 16),
          _buildPerformanceSection(),
          const SizedBox(height: 16),
          _buildBenchmarkSection(),
          const SizedBox(height: 16),
          _buildTopServicesSection(),
          const SizedBox(height: 16),
          _buildPeakHoursSection(),
        ],
      ),
    );
  }

  Widget _buildEarningsSection() {
    final earnings = _analytics!.earnings;

    return Card(
      elevation: 4,
      child: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Colors.blue[700]!, Colors.blue[500]!],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: const [
                  Icon(Icons.account_balance_wallet, color: Colors.white, size: 28),
                  SizedBox(width: 12),
                  Text(
                    'Revenus',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Text(
                '${earnings.total.toStringAsFixed(2)} TND',
                style: const TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Revenu net (après commission)',
                style: TextStyle(fontSize: 14, color: Colors.white.withOpacity(0.9)),
              ),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildEarningsStat(
                    'Brut',
                    '${earnings.grossRevenue.toStringAsFixed(2)} TND',
                  ),
                  _buildEarningsStat(
                    'Commission',
                    '${earnings.platformFees.toStringAsFixed(2)} TND',
                  ),
                  _buildEarningsStat(
                    'Par réservation',
                    '${earnings.averagePerBooking.toStringAsFixed(2)} TND',
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEarningsStat(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          value,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        Text(
          label,
          style: TextStyle(fontSize: 12, color: Colors.white.withOpacity(0.8)),
        ),
      ],
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
                _buildBookingStat('Total', bookings.total, Colors.blue),
                _buildBookingStat('Terminées', bookings.completed, Colors.green),
                _buildBookingStat('Annulées', bookings.cancelled, Colors.red),
              ],
            ),
            const SizedBox(height: 16),
            LinearProgressIndicator(
              value: bookings.completionRate / 100,
              backgroundColor: Colors.grey[200],
              color: Colors.green,
              minHeight: 8,
            ),
            const SizedBox(height: 8),
            Text(
              'Taux de complétion: ${bookings.completionRate.toStringAsFixed(1)}%',
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBookingStat(String label, int value, Color color) {
    return Column(
      children: [
        Text(
          value.toString(),
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      ],
    );
  }

  Widget _buildPerformanceSection() {
    final performance = _analytics!.performance;
    final retention = _analytics!.clientRetention;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.star, color: Colors.amber[700]),
                const SizedBox(width: 8),
                const Text(
                  'Performance',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildPerformanceCard(
                    'Note Moyenne',
                    performance.averageRating.toStringAsFixed(1),
                    Icons.star,
                    Colors.amber,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildPerformanceCard(
                    'Avis',
                    performance.totalReviews.toString(),
                    Icons.rate_review,
                    Colors.blue,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildPerformanceCard(
                    'Rétention',
                    '${retention.toStringAsFixed(1)}%',
                    Icons.people,
                    Colors.green,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildPerformanceCard(
                    'Temps de réponse',
                    performance.responseTimeAvg != null
                        ? '${performance.responseTimeAvg}min'
                        : 'N/A',
                    Icons.timer,
                    Colors.purple,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPerformanceCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            label,
            style: const TextStyle(fontSize: 12, color: Colors.grey),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildBenchmarkSection() {
    final benchmark = _analytics!.benchmark;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.compare_arrows, color: Colors.indigo[700]),
                const SizedBox(width: 8),
                const Text(
                  'Comparaison',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildBenchmarkRow(
              'Réservations',
              benchmark.yourBookings,
              benchmark.averageBookings,
            ),
            const SizedBox(height: 12),
            _buildBenchmarkRow(
              'Note moyenne',
              benchmark.yourRating,
              benchmark.averageRating,
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Colors.green[100]!, Colors.green[50]!],
                ),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.emoji_events, color: Colors.green[700], size: 28),
                  const SizedBox(width: 12),
                  Column(
                    children: [
                      Text(
                        'Top ${benchmark.percentile}%',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.green[700],
                        ),
                      ),
                      Text(
                        'Meilleur que ${benchmark.percentile}% des prestataires',
                        style: const TextStyle(fontSize: 12),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBenchmarkRow(String label, num yourValue, double avgValue) {
    final isAboveAverage = yourValue > avgValue;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        Row(
          children: [
            Text(
              'Vous: ${yourValue.toStringAsFixed(yourValue is int ? 0 : 1)}',
              style: TextStyle(
                color: isAboveAverage ? Colors.green : Colors.orange,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(width: 8),
            Text(
              'Moy: ${avgValue.toStringAsFixed(avgValue % 1 == 0 ? 0 : 1)}',
              style: const TextStyle(color: Colors.grey),
            ),
            const SizedBox(width: 4),
            Icon(
              isAboveAverage ? Icons.trending_up : Icons.trending_down,
              color: isAboveAverage ? Colors.green : Colors.orange,
              size: 20,
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildTopServicesSection() {
    final topServices = _analytics!.topServices;

    if (topServices.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.trending_up, color: Colors.teal[700]),
                const SizedBox(width: 8),
                const Text(
                  'Services les plus demandés',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ...topServices.take(5).map((service) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              service.serviceName,
                              style: const TextStyle(fontWeight: FontWeight.w600),
                            ),
                            Text(
                              '${service.bookings} réservation(s)',
                              style: const TextStyle(fontSize: 12, color: Colors.grey),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        '${service.earnings.toStringAsFixed(2)} TND',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.green[700],
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }

  Widget _buildPeakHoursSection() {
    final peakHours = _analytics!.peakHours;

    if (peakHours.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.access_time, color: Colors.purple[700]),
                const SizedBox(width: 8),
                const Text(
                  'Heures de pointe',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: peakHours.map((hour) => Chip(
                    label: Text('${hour.hour} (${hour.bookings})'),
                    backgroundColor: Colors.purple[50],
                    labelStyle: TextStyle(color: Colors.purple[700]),
                  )).toList(),
            ),
            const SizedBox(height: 8),
            Text(
              'Vos créneaux horaires les plus actifs',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }
}
