import 'package:flutter/material.dart';
import 'package:timeago/timeago.dart' as timeago;
import '../widgets/rating_stars.dart';
import 'submit_review_screen.dart';

class BookingDetailsScreen extends StatefulWidget {
  final String bookingId;

  const BookingDetailsScreen({
    Key? key,
    required this.bookingId,
  }) : super(key: key);

  @override
  State<BookingDetailsScreen> createState() => _BookingDetailsScreenState();
}

class _BookingDetailsScreenState extends State<BookingDetailsScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _booking;

  @override
  void initState() {
    super.initState();
    _loadBookingDetails();
  }

  Future<void> _loadBookingDetails() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // TODO: Call API to get booking details
      await Future.delayed(const Duration(seconds: 1));

      // Mock data
      final mockBooking = {
        'id': widget.bookingId,
        'booking_number': 'BOOK-ABC12345',
        'status': 'completed',
        'scheduled_at': DateTime.now().subtract(const Duration(days: 2)).toIso8601String(),
        'completed_at': DateTime.now().subtract(const Duration(days: 2, hours: 2)).toIso8601String(),
        'price': 50.00,
        'total': 50.00,
        'commission': 9.00,
        'payment_status': 'paid',
        'payment_method': 'card',
        'service': {
          'id': 1,
          'name_fr': 'Plomberie',
          'icon': '🚰',
        },
        'provider': {
          'id': 1,
          'user': {
            'first_name': 'Mohamed',
            'last_name': 'Trabelsi',
            'phone': '+216 98 123 456',
            'avatar_url': null,
          },
          'provider': {
            'rating_average': 4.7,
            'rating_count': 48,
          },
        },
        'address': {
          'label': 'Maison',
          'street': '123 Rue de la République',
          'city': 'Tunis',
          'governorate': 'Tunis',
        },
        'review': null,
      };

      setState(() {
        _booking = mockBooking;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_booking?['booking_number'] ?? 'Réservation'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _booking == null
              ? const Center(child: Text('Réservation introuvable'))
              : RefreshIndicator(
                  onRefresh: _loadBookingDetails,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Status card
                        _buildStatusCard(),
                        const SizedBox(height: 16),

                        // Service info
                        _buildServiceCard(),
                        const SizedBox(height: 16),

                        // Provider info
                        _buildProviderCard(),
                        const SizedBox(height: 16),

                        // Address
                        _buildAddressCard(),
                        const SizedBox(height: 16),

                        // Payment info
                        _buildPaymentCard(),
                        const SizedBox(height: 16),

                        // Review section
                        if (_booking!['status'] == 'completed')
                          _buildReviewSection(),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildStatusCard() {
    final status = _booking!['status'];
    final statusConfig = _getStatusConfig(status);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: statusConfig['color'].withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(
                statusConfig['icon'],
                color: statusConfig['color'],
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    statusConfig['label'],
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Réservation #${_booking!['booking_number']}',
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildServiceCard() {
    final service = _booking!['service'];
    final scheduledAt = DateTime.parse(_booking!['scheduled_at']);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Service',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Text(
                  service['icon'],
                  style: const TextStyle(fontSize: 32),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        service['name_fr'],
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.calendar_today, size: 14, color: Colors.grey[600]),
                          const SizedBox(width: 4),
                          Text(
                            '${scheduledAt.day}/${scheduledAt.month}/${scheduledAt.year} à ${scheduledAt.hour}:${scheduledAt.minute.toString().padLeft(2, '0')}',
                            style: TextStyle(
                              color: Colors.grey[600],
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProviderCard() {
    final provider = _booking!['provider'];
    final user = provider['user'];
    final providerData = provider['provider'];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Prestataire',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                CircleAvatar(
                  radius: 30,
                  child: Text(
                    user['first_name'][0].toUpperCase(),
                    style: const TextStyle(fontSize: 24),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${user['first_name']} ${user['last_name']}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      RatingStars(
                        rating: providerData['rating_average'].toDouble(),
                        size: 14,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        user['phone'],
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.phone),
                  onPressed: () {
                    // Call provider
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAddressCard() {
    final address = _booking!['address'];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Adresse',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.location_on, color: Theme.of(context).colorScheme.primary),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        address['label'],
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(address['street']),
                      Text('${address['city']}, ${address['governorate']}'),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Paiement',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Prix du service'),
                Text(
                  '${_booking!['price']} TND',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Total'),
                Text(
                  '${_booking!['total']} TND',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Row(
              children: [
                Icon(
                  _getPaymentIcon(_booking!['payment_method']),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Text(_getPaymentLabel(_booking!['payment_method'])),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: _booking!['payment_status'] == 'paid'
                        ? Colors.green.withOpacity(0.1)
                        : Colors.orange.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    _booking!['payment_status'] == 'paid' ? 'Payé' : 'En attente',
                    style: TextStyle(
                      color: _booking!['payment_status'] == 'paid'
                          ? Colors.green[700]
                          : Colors.orange[700],
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReviewSection() {
    final hasReview = _booking!['review'] != null;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Avis',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 12),
            if (hasReview)
              const Text('Vous avez déjà laissé un avis')
            else
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => SubmitReviewScreen(
                          booking: _booking!,
                        ),
                      ),
                    ).then((value) {
                      if (value == true) {
                        _loadBookingDetails();
                      }
                    });
                  },
                  icon: const Icon(Icons.star_outline),
                  label: const Text('Laisser un avis'),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Map<String, dynamic> _getStatusConfig(String status) {
    switch (status) {
      case 'pending':
        return {
          'label': 'En attente',
          'icon': Icons.hourglass_empty,
          'color': Colors.orange,
        };
      case 'confirmed':
        return {
          'label': 'Confirmée',
          'icon': Icons.check_circle_outline,
          'color': Colors.blue,
        };
      case 'in_progress':
        return {
          'label': 'En cours',
          'icon': Icons.settings,
          'color': Colors.purple,
        };
      case 'completed':
        return {
          'label': 'Terminée',
          'icon': Icons.check_circle,
          'color': Colors.green,
        };
      case 'cancelled':
        return {
          'label': 'Annulée',
          'icon': Icons.cancel,
          'color': Colors.red,
        };
      default:
        return {
          'label': status,
          'icon': Icons.info,
          'color': Colors.grey,
        };
    }
  }

  IconData _getPaymentIcon(String method) {
    switch (method) {
      case 'card':
        return Icons.credit_card;
      case 'cash':
        return Icons.money;
      case 'd17':
        return Icons.account_balance;
      case 'wallet':
        return Icons.account_balance_wallet;
      default:
        return Icons.payment;
    }
  }

  String _getPaymentLabel(String method) {
    switch (method) {
      case 'card':
        return 'Carte bancaire';
      case 'cash':
        return 'Espèces';
      case 'd17':
        return 'D17';
      case 'wallet':
        return 'Wallet';
      default:
        return method;
    }
  }
}
