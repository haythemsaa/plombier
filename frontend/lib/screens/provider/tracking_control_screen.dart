import 'package:flutter/material.dart';
import '../../core/services/tracking_service.dart';

/// Provider tracking control screen
class TrackingControlScreen extends StatefulWidget {
  final int bookingId;
  final String clientName;

  const TrackingControlScreen({
    Key? key,
    required this.bookingId,
    required this.clientName,
  }) : super(key: key);

  @override
  State<TrackingControlScreen> createState() => _TrackingControlScreenState();
}

class _TrackingControlScreenState extends State<TrackingControlScreen> {
  bool _isTracking = false;
  double? _currentSpeed;
  double? _distanceToClient;
  int? _etaMinutes;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Suivi GPS'),
        backgroundColor: Colors.blue[700],
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Client info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 30,
                      backgroundColor: Colors.blue[100],
                      child: Icon(Icons.person, size: 32, color: Colors.blue[700]),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            widget.clientName,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Réservation #${widget.bookingId}',
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
            ),

            const SizedBox(height: 24),

            // Tracking status
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: _isTracking
                      ? [Colors.green[400]!, Colors.green[600]!]
                      : [Colors.grey[300]!, Colors.grey[400]!],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: (_isTracking ? Colors.green : Colors.grey).withOpacity(0.3),
                    spreadRadius: 2,
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Icon(
                    _isTracking ? Icons.my_location : Icons.location_off,
                    size: 64,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _isTracking ? 'Suivi GPS actif' : 'Suivi GPS désactivé',
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  if (_isTracking) ...[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.3),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(
                              color: Colors.white,
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          const Text(
                            'Mise à jour toutes les 10 secondes',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 24),

            // Tracking info
            if (_isTracking) ...[
              Row(
                children: [
                  Expanded(
                    child: _buildStatCard(
                      icon: Icons.speed,
                      label: 'Vitesse',
                      value: _currentSpeed != null ? '${_currentSpeed!.toStringAsFixed(1)} km/h' : '--',
                      color: Colors.blue,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildStatCard(
                      icon: Icons.location_on,
                      label: 'Distance',
                      value: _distanceToClient != null ? '${_distanceToClient!.toStringAsFixed(1)} km' : '--',
                      color: Colors.orange,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _buildStatCard(
                icon: Icons.access_time,
                label: 'Temps estimé d\'arrivée',
                value: _etaMinutes != null ? '$_etaMinutes min' : 'Calcul en cours...',
                color: Colors.green,
              ),
              const SizedBox(height: 24),
            ],

            // Control button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _toggleTracking,
                icon: Icon(
                  _isTracking ? Icons.stop : Icons.play_arrow,
                  size: 28,
                ),
                label: Text(
                  _isTracking ? 'Arrêter le suivi' : 'Démarrer le suivi',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: _isTracking ? Colors.red[600] : Colors.green[600],
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Info card
            Card(
              color: Colors.blue[50],
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.info_outline, color: Colors.blue[700]),
                        const SizedBox(width: 12),
                        const Text(
                          'Informations',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    _buildInfoRow('Le client peut voir votre position en temps réel'),
                    _buildInfoRow('Votre localisation est mise à jour toutes les 10 secondes'),
                    _buildInfoRow('Le suivi s\'arrête automatiquement à la fin du service'),
                    _buildInfoRow('Vos données GPS sont supprimées après 7 jours'),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, size: 28, color: color),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            margin: const EdgeInsets.only(top: 4),
            width: 4,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.blue[700],
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey[700],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _toggleTracking() async {
    if (_isTracking) {
      // Stop tracking
      final confirm = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Arrêter le suivi'),
          content: const Text(
            'Voulez-vous arrêter le suivi GPS ? Le client ne pourra plus voir votre position.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                foregroundColor: Colors.white,
              ),
              child: const Text('Arrêter'),
            ),
          ],
        ),
      );

      if (confirm != true) return;

      // TODO: Call TrackingService.stopTracking()
      setState(() {
        _isTracking = false;
        _currentSpeed = null;
        _distanceToClient = null;
        _etaMinutes = null;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Suivi GPS arrêté'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } else {
      // Start tracking
      // TODO: Call TrackingService.startTracking(widget.bookingId)
      setState(() {
        _isTracking = true;
        _currentSpeed = 45.0;
        _distanceToClient = 3.5;
        _etaMinutes = 12;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Suivi GPS démarré'),
            backgroundColor: Colors.green,
          ),
        );
      }
    }
  }
}
