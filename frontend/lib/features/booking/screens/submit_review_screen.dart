import 'package:flutter/material.dart';
import '../widgets/rating_stars.dart';

class SubmitReviewScreen extends StatefulWidget {
  final Map<String, dynamic> booking;

  const SubmitReviewScreen({
    Key? key,
    required this.booking,
  }) : super(key: key);

  @override
  State<SubmitReviewScreen> createState() => _SubmitReviewScreenState();
}

class _SubmitReviewScreenState extends State<SubmitReviewScreen> {
  final _formKey = GlobalKey<FormState>();
  final _commentController = TextEditingController();

  int _overallRating = 0;
  int _professionalismRating = 0;
  int _qualityRating = 0;
  int _valueRating = 0;

  bool _isSubmitting = false;

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submitReview() async {
    if (_formKey.currentState!.validate()) {
      if (_overallRating == 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Veuillez donner une note globale'),
            backgroundColor: Colors.orange,
          ),
        );
        return;
      }

      setState(() {
        _isSubmitting = true;
      });

      try {
        // TODO: Call API to submit review
        final reviewData = {
          'booking_id': widget.booking['id'],
          'rating': _overallRating,
          'professionalism_rating': _professionalismRating > 0 ? _professionalismRating : null,
          'quality_rating': _qualityRating > 0 ? _qualityRating : null,
          'value_rating': _valueRating > 0 ? _valueRating : null,
          'comment': _commentController.text.trim(),
        };

        // Simulate API call
        await Future.delayed(const Duration(seconds: 2));

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Merci pour votre avis!'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.pop(context, true);
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Erreur: $e'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } finally {
        if (mounted) {
          setState(() {
            _isSubmitting = false;
          });
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = widget.booking['provider'] ?? {};
    final service = widget.booking['service'] ?? {};

    return Scaffold(
      appBar: AppBar(
        title: const Text('Donner un avis'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Service info
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(
                        Icons.home_repair_service,
                        size: 40,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              service['name_fr'] ?? 'Service',
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${provider['user']?['first_name']} ${provider['user']?['last_name']}',
                              style: TextStyle(
                                color: Colors.grey[600],
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

              // Overall rating
              Text(
                'Note globale *',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 12),
              Center(
                child: InteractiveRatingStars(
                  rating: _overallRating,
                  onRatingChanged: (rating) {
                    setState(() {
                      _overallRating = rating;
                    });
                  },
                  size: 48,
                ),
              ),

              const SizedBox(height: 32),

              // Detailed ratings
              Text(
                'Évaluations détaillées (optionnel)',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 16),

              _buildDetailedRatingRow(
                'Professionnalisme',
                _professionalismRating,
                (rating) {
                  setState(() {
                    _professionalismRating = rating;
                  });
                },
              ),

              const SizedBox(height: 12),

              _buildDetailedRatingRow(
                'Qualité du travail',
                _qualityRating,
                (rating) {
                  setState(() {
                    _qualityRating = rating;
                  });
                },
              ),

              const SizedBox(height: 12),

              _buildDetailedRatingRow(
                'Rapport qualité/prix',
                _valueRating,
                (rating) {
                  setState(() {
                    _valueRating = rating;
                  });
                },
              ),

              const SizedBox(height: 24),

              // Comment
              Text(
                'Commentaire (optionnel)',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _commentController,
                maxLines: 5,
                maxLength: 1000,
                decoration: const InputDecoration(
                  hintText: 'Partagez votre expérience...',
                  border: OutlineInputBorder(),
                ),
              ),

              const SizedBox(height: 24),

              // Submit button
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _isSubmitting ? null : _submitReview,
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text(
                          'Soumettre l\'avis',
                          style: TextStyle(fontSize: 16),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailedRatingRow(
    String label,
    int rating,
    ValueChanged<int> onChanged,
  ) {
    return Row(
      children: [
        Expanded(
          flex: 2,
          child: Text(
            label,
            style: const TextStyle(fontSize: 14),
          ),
        ),
        Expanded(
          flex: 3,
          child: InteractiveRatingStars(
            rating: rating,
            onRatingChanged: onChanged,
            size: 28,
          ),
        ),
      ],
    );
  }
}
