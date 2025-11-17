import 'package:flutter/material.dart';
import '../widgets/review_card.dart';
import '../widgets/rating_stars.dart';

class ReviewsListScreen extends StatefulWidget {
  final String providerId;
  final Map<String, dynamic>? providerInfo;

  const ReviewsListScreen({
    Key? key,
    required this.providerId,
    this.providerInfo,
  }) : super(key: key);

  @override
  State<ReviewsListScreen> createState() => _ReviewsListScreenState();
}

class _ReviewsListScreenState extends State<ReviewsListScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _reviews = [];
  Map<String, dynamic>? _stats;

  @override
  void initState() {
    super.initState();
    _loadReviews();
  }

  Future<void> _loadReviews() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // TODO: Call API to get reviews
      // final response = await apiService.getProviderReviews(widget.providerId);

      // Simulate API call with mock data
      await Future.delayed(const Duration(seconds: 1));

      final mockStats = {
        'total_reviews': 48,
        'average_rating': 4.7,
        'rating_distribution': {
          '5': 32,
          '4': 12,
          '3': 3,
          '2': 1,
          '1': 0,
        },
        'average_professionalism': 4.8,
        'average_quality': 4.6,
        'average_value': 4.5,
      };

      final mockReviews = List.generate(10, (index) {
        return {
          'id': index + 1,
          'rating': 4 + (index % 2),
          'professionalism_rating': 5,
          'quality_rating': 4,
          'value_rating': 5,
          'comment': 'Excellent service! Très professionnel et ponctuel.',
          'created_at': DateTime.now().subtract(Duration(days: index * 5)).toIso8601String(),
          'is_verified': true,
          'client': {
            'first_name': 'Client',
            'last_name': '${index + 1}',
          },
          'response': index % 3 == 0
              ? 'Merci beaucoup pour votre avis positif!'
              : null,
        };
      });

      setState(() {
        _stats = mockStats;
        _reviews = mockReviews;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Avis'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadReviews,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Stats summary
                    if (_stats != null) _buildStatsSection(),

                    const Divider(height: 1),

                    // Reviews list
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Tous les avis (${_stats?['total_reviews'] ?? 0})',
                            style: Theme.of(context)
                                .textTheme
                                .titleLarge
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 16),
                          if (_reviews.isEmpty)
                            const Center(
                              child: Padding(
                                padding: EdgeInsets.all(32),
                                child: Text('Aucun avis pour le moment'),
                              ),
                            )
                          else
                            ..._reviews.map((review) => ReviewCard(review: review)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildStatsSection() {
    final stats = _stats!;
    final averageRating = (stats['average_rating'] ?? 0).toDouble();
    final totalReviews = stats['total_reviews'] ?? 0;

    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          // Overall rating
          Row(
            children: [
              Column(
                children: [
                  Text(
                    averageRating.toStringAsFixed(1),
                    style: const TextStyle(
                      fontSize: 48,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  RatingStars(
                    rating: averageRating,
                    size: 24,
                    showNumber: false,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '$totalReviews avis',
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 32),
              Expanded(
                child: _buildRatingDistribution(
                  stats['rating_distribution'] as Map<String, dynamic>,
                  totalReviews,
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          // Detailed averages
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildDetailedStat(
                'Professionnalisme',
                (stats['average_professionalism'] ?? 0).toDouble(),
              ),
              _buildDetailedStat(
                'Qualité',
                (stats['average_quality'] ?? 0).toDouble(),
              ),
              _buildDetailedStat(
                'Rapport Q/P',
                (stats['average_value'] ?? 0).toDouble(),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildRatingDistribution(Map<String, dynamic> distribution, int total) {
    return Column(
      children: [5, 4, 3, 2, 1].map((rating) {
        final count = distribution['$rating'] ?? 0;
        final percentage = total > 0 ? (count / total) : 0.0;

        return Padding(
          padding: const EdgeInsets.symmetric(vertical: 2),
          child: Row(
            children: [
              Text(
                '$rating',
                style: const TextStyle(fontSize: 12),
              ),
              const SizedBox(width: 4),
              const Icon(Icons.star, size: 12, color: Colors.amber),
              const SizedBox(width: 8),
              Expanded(
                child: LinearProgressIndicator(
                  value: percentage,
                  backgroundColor: Colors.grey[200],
                  valueColor: AlwaysStoppedAnimation(
                    Theme.of(context).colorScheme.primary,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                width: 30,
                child: Text(
                  '$count',
                  style: const TextStyle(fontSize: 12),
                  textAlign: TextAlign.end,
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildDetailedStat(String label, double rating) {
    return Column(
      children: [
        Text(
          rating.toStringAsFixed(1),
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 4),
        RatingStars(
          rating: rating,
          size: 14,
          showNumber: false,
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey[600],
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }
}
