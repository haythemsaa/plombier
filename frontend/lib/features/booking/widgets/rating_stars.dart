import 'package:flutter/material.dart';

class RatingStars extends StatelessWidget {
  final double rating;
  final double size;
  final Color? color;
  final bool showNumber;

  const RatingStars({
    Key? key,
    required this.rating,
    this.size = 20,
    this.color,
    this.showNumber = true,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final starColor = color ?? Colors.amber;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        ...List.generate(5, (index) {
          final starValue = index + 1;
          IconData iconData;

          if (rating >= starValue) {
            iconData = Icons.star;
          } else if (rating >= starValue - 0.5) {
            iconData = Icons.star_half;
          } else {
            iconData = Icons.star_border;
          }

          return Icon(
            iconData,
            size: size,
            color: starColor,
          );
        }),
        if (showNumber) ...[
          const SizedBox(width: 4),
          Text(
            rating.toStringAsFixed(1),
            style: TextStyle(
              fontSize: size * 0.7,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ],
    );
  }
}

class InteractiveRatingStars extends StatefulWidget {
  final int rating;
  final ValueChanged<int> onRatingChanged;
  final double size;

  const InteractiveRatingStars({
    Key? key,
    required this.rating,
    required this.onRatingChanged,
    this.size = 32,
  }) : super(key: key);

  @override
  State<InteractiveRatingStars> createState() => _InteractiveRatingStarsState();
}

class _InteractiveRatingStarsState extends State<InteractiveRatingStars> {
  late int _currentRating;

  @override
  void initState() {
    super.initState();
    _currentRating = widget.rating;
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (index) {
        final starValue = index + 1;

        return GestureDetector(
          onTap: () {
            setState(() {
              _currentRating = starValue;
            });
            widget.onRatingChanged(starValue);
          },
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: Icon(
              starValue <= _currentRating ? Icons.star : Icons.star_border,
              size: widget.size,
              color: Colors.amber,
            ),
          ),
        );
      }),
    );
  }
}
