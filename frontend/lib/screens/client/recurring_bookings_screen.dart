import 'package:flutter/material.dart';
import '../../core/services/recurring_booking_service.dart';

/// Recurring bookings management screen
class RecurringBookingsScreen extends StatefulWidget {
  const RecurringBookingsScreen({Key? key}) : super(key: key);

  @override
  State<RecurringBookingsScreen> createState() => _RecurringBookingsScreenState();
}

class _RecurringBookingsScreenState extends State<RecurringBookingsScreen> {
  List<RecurringBooking> _recurringBookings = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadRecurringBookings();
  }

  Future<void> _loadRecurringBookings() async {
    setState(() => _isLoading = true);

    // TODO: Load from RecurringBookingService
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Réservations Récurrentes'),
        backgroundColor: Colors.blue[700],
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () {
              // Navigate to create recurring booking
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _recurringBookings.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _loadRecurringBookings,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _recurringBookings.length,
                    itemBuilder: (context, index) {
                      return _buildRecurringBookingCard(_recurringBookings[index]);
                    },
                  ),
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // Navigate to create recurring booking
        },
        icon: const Icon(Icons.add),
        label: const Text('Nouvelle récurrence'),
        backgroundColor: Colors.blue[700],
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.repeat, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Aucune réservation récurrente',
            style: TextStyle(fontSize: 18, color: Colors.grey[600]),
          ),
          const SizedBox(height: 8),
          Text(
            'Planifiez vos services réguliers',
            style: TextStyle(color: Colors.grey[500]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildRecurringBookingCard(RecurringBooking booking) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 3,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _getStatusColor(booking.status).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: _getStatusColor(booking.status)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        booking.isActive ? Icons.check_circle : Icons.pause_circle,
                        size: 16,
                        color: _getStatusColor(booking.status),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        booking.statusLabel,
                        style: TextStyle(
                          color: _getStatusColor(booking.status),
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                const Spacer(),
                PopupMenuButton<String>(
                  onSelected: (value) => _handleMenuAction(value, booking),
                  itemBuilder: (context) {
                    if (booking.isActive) {
                      return [
                        const PopupMenuItem(
                          value: 'pause',
                          child: Row(
                            children: [
                              Icon(Icons.pause, size: 20),
                              SizedBox(width: 8),
                              Text('Mettre en pause'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'edit',
                          child: Row(
                            children: [
                              Icon(Icons.edit, size: 20),
                              SizedBox(width: 8),
                              Text('Modifier'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'cancel',
                          child: Row(
                            children: [
                              Icon(Icons.delete, size: 20, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Supprimer', style: TextStyle(color: Colors.red)),
                            ],
                          ),
                        ),
                      ];
                    } else if (booking.isPaused) {
                      return [
                        const PopupMenuItem(
                          value: 'resume',
                          child: Row(
                            children: [
                              Icon(Icons.play_arrow, size: 20, color: Colors.green),
                              SizedBox(width: 8),
                              Text('Reprendre'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'cancel',
                          child: Row(
                            children: [
                              Icon(Icons.delete, size: 20, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Supprimer', style: TextStyle(color: Colors.red)),
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
            Text(
              booking.serviceName ?? 'Service',
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.repeat, size: 18, color: Colors.blue[700]),
                const SizedBox(width: 8),
                Text(
                  '${booking.frequencyLabel} - ${booking.dayOfWeek}',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.access_time, size: 18, color: Colors.orange[700]),
                const SizedBox(width: 8),
                Text(
                  '${booking.startTime} (${booking.durationMinutes}min)',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ],
            ),
            if (booking.nextOccurrenceDate != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.event, size: 18, color: Colors.green[700]),
                  const SizedBox(width: 8),
                  Text(
                    'Prochaine: ${_formatDate(booking.nextOccurrenceDate!)}',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ],
            if (booking.providerName != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.person, size: 18, color: Colors.purple[700]),
                  const SizedBox(width: 8),
                  Text(
                    'Prestataire préféré: ${booking.providerName}',
                    style: const TextStyle(fontSize: 13),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'active':
        return Colors.green;
      case 'paused':
        return Colors.orange;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatDate(DateTime date) {
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  Future<void> _handleMenuAction(String action, RecurringBooking booking) async {
    switch (action) {
      case 'pause':
        await _pauseBooking(booking);
        break;
      case 'resume':
        await _resumeBooking(booking);
        break;
      case 'edit':
        // Navigate to edit screen
        break;
      case 'cancel':
        await _cancelBooking(booking);
        break;
    }
  }

  Future<void> _pauseBooking(RecurringBooking booking) async {
    final confirm = await _showConfirmDialog(
      'Mettre en pause',
      'Voulez-vous mettre cette réservation récurrente en pause ?',
    );

    if (confirm != true) return;

    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Réservation mise en pause');
      _loadRecurringBookings();
    }
  }

  Future<void> _resumeBooking(RecurringBooking booking) async {
    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Réservation reprise');
      _loadRecurringBookings();
    }
  }

  Future<void> _cancelBooking(RecurringBooking booking) async {
    final confirm = await _showConfirmDialog(
      'Supprimer',
      'Êtes-vous sûr de vouloir supprimer cette réservation récurrente ? Cette action est irréversible.',
      isDangerous: true,
    );

    if (confirm != true) return;

    _showLoadingDialog();
    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      Navigator.pop(context);
      _showSuccessSnackBar('Réservation supprimée');
      _loadRecurringBookings();
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
