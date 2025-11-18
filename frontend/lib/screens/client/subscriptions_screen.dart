import 'package:flutter/material.dart';
import '../../core/services/package_service.dart';

/// User subscriptions management screen
class SubscriptionsScreen extends StatefulWidget {
  const SubscriptionsScreen({Key? key}) : super(key: key);

  @override
  State<SubscriptionsScreen> createState() => _SubscriptionsScreenState();
}

class _SubscriptionsScreenState extends State<SubscriptionsScreen> {
  List<PackageSubscription> _subscriptions = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSubscriptions();
  }

  Future<void> _loadSubscriptions() async {
    setState(() => _isLoading = true);

    // TODO: Get PackageService from dependency injection
    // final service = Provider.of<PackageService>(context, listen: false);
    // final subscriptions = await service.getMySubscriptions();

    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      // _subscriptions = subscriptions;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Abonnements'),
        backgroundColor: Colors.blue[700],
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _subscriptions.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _loadSubscriptions,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _subscriptions.length,
                    itemBuilder: (context, index) {
                      return _buildSubscriptionCard(_subscriptions[index]);
                    },
                  ),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.subscriptions_outlined, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Aucun abonnement actif',
            style: TextStyle(fontSize: 18, color: Colors.grey[600]),
          ),
          const SizedBox(height: 8),
          Text(
            'Découvrez nos packages pour économiser',
            style: TextStyle(color: Colors.grey[500]),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: () {
              // Navigate to packages screen
            },
            icon: const Icon(Icons.card_giftcard),
            label: const Text('Voir les packages'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue[700],
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSubscriptionCard(PackageSubscription subscription) {
    final package = subscription.package;
    if (package == null) return const SizedBox.shrink();

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 3,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status badge and menu
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _getStatusColor(subscription.status).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: _getStatusColor(subscription.status)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        _getStatusIcon(subscription.status),
                        size: 16,
                        color: _getStatusColor(subscription.status),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        subscription.statusLabel,
                        style: TextStyle(
                          color: _getStatusColor(subscription.status),
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                const Spacer(),
                PopupMenuButton<String>(
                  onSelected: (value) => _handleMenuAction(value, subscription),
                  itemBuilder: (context) {
                    if (subscription.isActive) {
                      return [
                        const PopupMenuItem(
                          value: 'pause',
                          child: Row(
                            children: [
                              Icon(Icons.pause_circle, size: 20),
                              SizedBox(width: 8),
                              Text('Mettre en pause'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'cancel',
                          child: Row(
                            children: [
                              Icon(Icons.cancel, size: 20, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Annuler', style: TextStyle(color: Colors.red)),
                            ],
                          ),
                        ),
                      ];
                    } else if (subscription.isPaused) {
                      return [
                        const PopupMenuItem(
                          value: 'resume',
                          child: Row(
                            children: [
                              Icon(Icons.play_circle, size: 20, color: Colors.green),
                              SizedBox(width: 8),
                              Text('Reprendre'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'cancel',
                          child: Row(
                            children: [
                              Icon(Icons.cancel, size: 20, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Annuler', style: TextStyle(color: Colors.red)),
                            ],
                          ),
                        ),
                      ];
                    } else if (subscription.isExpired && package.isSubscription) {
                      return [
                        const PopupMenuItem(
                          value: 'renew',
                          child: Row(
                            children: [
                              Icon(Icons.refresh, size: 20, color: Colors.blue),
                              SizedBox(width: 8),
                              Text('Renouveler'),
                            ],
                          ),
                        ),
                      ];
                    }
                    return [];
                  },
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Package name
            Text(
              package.name,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),

            // Package type
            Text(
              package.typeLabel,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 16),

            // Sessions progress
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Sessions utilisées:',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                    Text(
                      '${subscription.sessionsUsed} / ${subscription.totalSessions}',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: LinearProgressIndicator(
                    value: subscription.usagePercentage / 100,
                    minHeight: 10,
                    backgroundColor: Colors.grey[200],
                    color: subscription.usagePercentage > 75
                        ? Colors.orange
                        : Colors.blue[700],
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${subscription.sessionsRemaining} session${subscription.sessionsRemaining > 1 ? "s" : ""} restante${subscription.sessionsRemaining > 1 ? "s" : ""}',
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Dates info
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey[50],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey[300]!),
              ),
              child: Column(
                children: [
                  _buildInfoRow(
                    Icons.calendar_today,
                    'Date de souscription',
                    _formatDate(subscription.startsAt),
                  ),
                  if (subscription.expiresAt != null) ...[
                    const SizedBox(height: 8),
                    _buildInfoRow(
                      Icons.event,
                      'Date d\'expiration',
                      _formatDate(subscription.expiresAt!),
                      isWarning: subscription.isExpiringSoon,
                    ),
                  ],
                  if (subscription.cancelledAt != null) ...[
                    const SizedBox(height: 8),
                    _buildInfoRow(
                      Icons.cancel,
                      'Date d\'annulation',
                      _formatDate(subscription.cancelledAt!),
                      color: Colors.red,
                    ),
                  ],
                ],
              ),
            ),

            // Expiry warning
            if (subscription.isExpiringSoon) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.orange[300]!),
                ),
                child: Row(
                  children: [
                    Icon(Icons.warning_amber, color: Colors.orange[700]),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Expire dans ${subscription.daysUntilExpiry} jour${subscription.daysUntilExpiry! > 1 ? "s" : ""}',
                        style: TextStyle(
                          color: Colors.orange[900],
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            // Renewal info for subscriptions
            if (package.isSubscription && subscription.isActive) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue[50],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.replay, color: Colors.blue[700], size: 20),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Renouvellement automatique le ${subscription.expiresAt != null ? _formatDate(subscription.expiresAt!) : "N/A"}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.blue[900],
                        ),
                      ),
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

  Widget _buildInfoRow(IconData icon, String label, String value, {Color? color, bool isWarning = false}) {
    final effectiveColor = color ?? (isWarning ? Colors.orange[700] : Colors.grey[700]);

    return Row(
      children: [
        Icon(icon, size: 16, color: effectiveColor),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            label,
            style: TextStyle(fontSize: 13, color: Colors.grey[600]),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: effectiveColor,
          ),
        ),
      ],
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'active':
        return Colors.green;
      case 'paused':
        return Colors.orange;
      case 'cancelled':
      case 'expired':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status) {
      case 'active':
        return Icons.check_circle;
      case 'paused':
        return Icons.pause_circle;
      case 'cancelled':
        return Icons.cancel;
      case 'expired':
        return Icons.timer_off;
      default:
        return Icons.help;
    }
  }

  String _formatDate(DateTime date) {
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  Future<void> _handleMenuAction(String action, PackageSubscription subscription) async {
    switch (action) {
      case 'pause':
        await _pauseSubscription(subscription);
        break;
      case 'resume':
        await _resumeSubscription(subscription);
        break;
      case 'cancel':
        await _cancelSubscription(subscription);
        break;
      case 'renew':
        await _renewSubscription(subscription);
        break;
    }
  }

  Future<void> _pauseSubscription(PackageSubscription subscription) async {
    final confirm = await _showConfirmDialog(
      'Mettre en pause',
      'Voulez-vous mettre cet abonnement en pause ? Vous pourrez le reprendre à tout moment.',
    );

    if (confirm != true) return;

    // TODO: Call PackageService
    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Abonnement mis en pause');
      _loadSubscriptions();
    }
  }

  Future<void> _resumeSubscription(PackageSubscription subscription) async {
    // TODO: Call PackageService
    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Abonnement repris');
      _loadSubscriptions();
    }
  }

  Future<void> _cancelSubscription(PackageSubscription subscription) async {
    final confirm = await _showConfirmDialog(
      'Annuler l\'abonnement',
      'Êtes-vous sûr de vouloir annuler cet abonnement ? Cette action est irréversible.',
      isDangerous: true,
    );

    if (confirm != true) return;

    // TODO: Call PackageService
    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Abonnement annulé');
      _loadSubscriptions();
    }
  }

  Future<void> _renewSubscription(PackageSubscription subscription) async {
    final confirm = await _showConfirmDialog(
      'Renouveler l\'abonnement',
      'Voulez-vous renouveler cet abonnement pour ${subscription.package?.price.toStringAsFixed(2)} TND ?',
    );

    if (confirm != true) return;

    // TODO: Call PackageService
    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Abonnement renouvelé avec succès');
      _loadSubscriptions();
    }
  }

  Future<bool?> _showConfirmDialog(String title, String message, {bool isDangerous = false}) {
    return showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: isDangerous ? Colors.red : Colors.blue[700],
              foregroundColor: Colors.white,
            ),
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );
  }

  void _showLoadingDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }
}
