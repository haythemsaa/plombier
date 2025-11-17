import 'package:flutter/material.dart';
import 'package:timeago/timeago.dart' as timeago;
import 'rating_stars.dart';

class ReviewCard extends StatelessWidget {
  final Map<String, dynamic> review;

  const ReviewCard({
    Key? key,
    required this.review,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final client = review['client'] ?? {};
    final response = review['response'];
    final createdAt = DateTime.parse(review['created_at']);

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Client info and rating
            Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundImage: client['avatar_url'] != null
                      ? NetworkImage(client['avatar_url'])
                      : null,
                  child: client['avatar_url'] == null
                      ? Text(
                          (client['first_name']?[0] ?? 'U').toUpperCase(),
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                          ),
                        )
                      : null,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${client['first_name']} ${client['last_name']}',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      Text(
                        timeago.format(createdAt, locale: 'fr'),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                RatingStars(
                  rating: (review['rating'] ?? 0).toDouble(),
                  showNumber: false,
                ),
              ],
            ),

            // Detailed ratings
            if (review['professionalism_rating'] != null ||
                review['quality_rating'] != null ||
                review['value_rating'] != null) ...[
              const SizedBox(height: 12),
              Wrap(
                spacing: 16,
                runSpacing: 8,
                children: [
                  if (review['professionalism_rating'] != null)
                    _buildDetailedRating(
                      'Professionnalisme',
                      review['professionalism_rating'].toDouble(),
                    ),
                  if (review['quality_rating'] != null)
                    _buildDetailedRating(
                      'Qualité',
                      review['quality_rating'].toDouble(),
                    ),
                  if (review['value_rating'] != null)
                    _buildDetailedRating(
                      'Rapport qualité/prix',
                      review['value_rating'].toDouble(),
                    ),
                ],
              ),
            ],

            // Comment
            if (review['comment'] != null && review['comment'].isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                review['comment'],
                style: const TextStyle(fontSize: 14),
              ),
            ],

            // Provider response
            if (response != null && response.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey[100],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.reply,
                          size: 16,
                          color: Colors.grey[700],
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'Réponse du prestataire',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                            color: Colors.grey[700],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      response,
                      style: const TextStyle(fontSize: 14),
                    ),
                  ],
                ),
              ),
            ],

            // Verified badge
            if (review['is_verified'] == true) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.verified,
                    size: 16,
                    color: Colors.green[700],
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Avis vérifié',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.green[700],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildDetailedRating(String label, double rating) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          '$label:',
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(width: 4),
        RatingStars(
          rating: rating,
          size: 14,
          showNumber: false,
        ),
      ],
    );
  }
}
