import 'package:flutter/material.dart';

class ProviderFilters {
  double? minPrice;
  double? maxPrice;
  double? maxDistance; // in km
  double? minRating;
  List<String> selectedZones;
  bool? isVerified;
  String? sortBy; // 'rating', 'price_asc', 'price_desc', 'distance'

  ProviderFilters({
    this.minPrice,
    this.maxPrice,
    this.maxDistance,
    this.minRating,
    this.selectedZones = const [],
    this.isVerified,
    this.sortBy = 'rating',
  });

  bool get hasActiveFilters {
    return minPrice != null ||
        maxPrice != null ||
        maxDistance != null ||
        minRating != null ||
        selectedZones.isNotEmpty ||
        isVerified != null;
  }

  void reset() {
    minPrice = null;
    maxPrice = null;
    maxDistance = null;
    minRating = null;
    selectedZones = [];
    isVerified = null;
    sortBy = 'rating';
  }

  Map<String, dynamic> toQueryParams() {
    final params = <String, dynamic>{};

    if (minPrice != null) params['min_price'] = minPrice;
    if (maxPrice != null) params['max_price'] = maxPrice;
    if (maxDistance != null) params['max_distance'] = maxDistance;
    if (minRating != null) params['min_rating'] = minRating;
    if (selectedZones.isNotEmpty) params['zones'] = selectedZones.join(',');
    if (isVerified != null) params['is_verified'] = isVerified;
    if (sortBy != null) params['sort_by'] = sortBy;

    return params;
  }
}

class ProviderFiltersSheet extends StatefulWidget {
  final ProviderFilters currentFilters;
  final Function(ProviderFilters) onApply;

  const ProviderFiltersSheet({
    super.key,
    required this.currentFilters,
    required this.onApply,
  });

  @override
  State<ProviderFiltersSheet> createState() => _ProviderFiltersSheetState();
}

class _ProviderFiltersSheetState extends State<ProviderFiltersSheet> {
  late ProviderFilters filters;

  @override
  void initState() {
    super.initState();
    // Create a copy of current filters
    filters = ProviderFilters(
      minPrice: widget.currentFilters.minPrice,
      maxPrice: widget.currentFilters.maxPrice,
      maxDistance: widget.currentFilters.maxDistance,
      minRating: widget.currentFilters.minRating,
      selectedZones: List.from(widget.currentFilters.selectedZones),
      isVerified: widget.currentFilters.isVerified,
      sortBy: widget.currentFilters.sortBy,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Filtres',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Row(
                  children: [
                    TextButton(
                      onPressed: () {
                        setState(() {
                          filters.reset();
                        });
                      },
                      child: const Text('Réinitialiser'),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Price Range
            const Text(
              'Fourchette de prix (TND)',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    decoration: const InputDecoration(
                      labelText: 'Min',
                      border: OutlineInputBorder(),
                    ),
                    keyboardType: TextInputType.number,
                    onChanged: (value) {
                      setState(() {
                        filters.minPrice = double.tryParse(value);
                      });
                    },
                    controller: TextEditingController(
                      text: filters.minPrice?.toString() ?? '',
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextField(
                    decoration: const InputDecoration(
                      labelText: 'Max',
                      border: OutlineInputBorder(),
                    ),
                    keyboardType: TextInputType.number,
                    onChanged: (value) {
                      setState(() {
                        filters.maxPrice = double.tryParse(value);
                      });
                    },
                    controller: TextEditingController(
                      text: filters.maxPrice?.toString() ?? '',
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Distance
            const Text(
              'Distance maximale',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Slider(
              value: filters.maxDistance ?? 50,
              min: 1,
              max: 50,
              divisions: 49,
              label: '${filters.maxDistance?.round() ?? 50} km',
              onChanged: (value) {
                setState(() {
                  filters.maxDistance = value;
                });
              },
            ),
            Text(
              '${filters.maxDistance?.round() ?? 50} km',
              style: TextStyle(color: Colors.grey[600]),
            ),

            const SizedBox(height: 24),

            // Rating
            const Text(
              'Note minimale',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: [1.0, 2.0, 3.0, 4.0, 4.5].map((rating) {
                final isSelected = filters.minRating == rating;
                return ChoiceChip(
                  label: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.star, size: 16),
                      const SizedBox(width: 4),
                      Text('$rating+'),
                    ],
                  ),
                  selected: isSelected,
                  onSelected: (selected) {
                    setState(() {
                      filters.minRating = selected ? rating : null;
                    });
                  },
                );
              }).toList(),
            ),

            const SizedBox(height: 24),

            // Verified only
            CheckboxListTile(
              title: const Text('Prestataires vérifiés uniquement'),
              value: filters.isVerified ?? false,
              onChanged: (value) {
                setState(() {
                  filters.isVerified = value == true ? true : null;
                });
              },
              contentPadding: EdgeInsets.zero,
            ),

            const SizedBox(height: 24),

            // Sort by
            const Text(
              'Trier par',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: [
                {
                  'value': 'rating',
                  'label': 'Meilleure note',
                  'icon': Icons.star,
                },
                {
                  'value': 'price_asc',
                  'label': 'Prix croissant',
                  'icon': Icons.arrow_upward,
                },
                {
                  'value': 'price_desc',
                  'label': 'Prix décroissant',
                  'icon': Icons.arrow_downward,
                },
                {
                  'value': 'distance',
                  'label': 'Distance',
                  'icon': Icons.location_on,
                },
              ].map((option) {
                final isSelected = filters.sortBy == option['value'];
                return ChoiceChip(
                  label: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(option['icon'] as IconData, size: 16),
                      const SizedBox(width: 4),
                      Text(option['label'] as String),
                    ],
                  ),
                  selected: isSelected,
                  onSelected: (selected) {
                    setState(() {
                      filters.sortBy = option['value'] as String;
                    });
                  },
                );
              }).toList(),
            ),

            const SizedBox(height: 32),

            // Apply button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  widget.onApply(filters);
                  Navigator.pop(context);
                },
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: const Text(
                  'Appliquer les filtres',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),

            SizedBox(height: MediaQuery.of(context).padding.bottom),
          ],
        ),
      ),
    );
  }
}
