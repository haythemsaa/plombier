import 'package:flutter/material.dart';
import '../../core/services/loyalty_service.dart';

/// Loyalty program screen
class LoyaltyScreen extends StatefulWidget {
  const LoyaltyScreen({Key? key}) : super(key: key);

  @override
  State<LoyaltyScreen> createState() => _LoyaltyScreenState();
}

class _LoyaltyScreenState extends State<LoyaltyScreen> with SingleTickerProviderStateMixin {
  LoyaltyStatus? _status;
  List<LoyaltyTier> _tiers = [];
  bool _isLoading = true;
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    // TODO: Load from LoyaltyService
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      // _status = ...
      // _tiers = ...
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : CustomScrollView(
              slivers: [
                _buildAppBar(),
                SliverToBoxAdapter(child: _buildStatusCard()),
                SliverToBoxAdapter(
                  child: TabBar(
                    controller: _tabController,
                    labelColor: Colors.blue[700],
                    unselectedLabelColor: Colors.grey,
                    indicatorColor: Colors.blue[700],
                    tabs: const [
                      Tab(text: 'Avantages'),
                      Tab(text: 'Tiers'),
                      Tab(text: 'Classement'),
                    ],
                  ),
                ),
                SliverFillRemaining(
                  child: TabBarView(
                    controller: _tabController,
                    children: [
                      _buildBenefitsTab(),
                      _buildTiersTab(),
                      _buildLeaderboardTab(),
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 200,
      pinned: true,
      backgroundColor: _getTierColor(_status?.currentTier ?? 'Bronze'),
      flexibleSpace: FlexibleSpaceBar(
        title: const Text('Programme Fidélité'),
        background: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                _getTierColor(_status?.currentTier ?? 'Bronze'),
                _getTierColor(_status?.currentTier ?? 'Bronze').withOpacity(0.7),
              ],
            ),
          ),
          child: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const SizedBox(height: 40),
                Icon(
                  _getTierIcon(_status?.currentTier ?? 'Bronze'),
                  size: 60,
                  color: Colors.white,
                ),
                const SizedBox(height: 8),
                Text(
                  _status?.currentTier ?? 'Bronze',
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusCard() {
    if (_status == null) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.2),
            spreadRadius: 2,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Points total
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Points totaux',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_status!.totalPoints}',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: _getTierColor(_status!.currentTier),
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: _getTierColor(_status!.currentTier).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${_status!.currentDiscount.toStringAsFixed(0)}% de réduction',
                  style: TextStyle(
                    color: _getTierColor(_status!.currentTier),
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          // Progress to next tier
          if (!_status!.isMaxTier) ...[
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Progression vers ${_status!.nextTierName}',
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                  ),
                ),
                Text(
                  '${_status!.pointsToNextTier} points restants',
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: LinearProgressIndicator(
                value: _status!.progressToNextTier,
                minHeight: 12,
                backgroundColor: Colors.grey[200],
                color: _getTierColor(_status!.nextTierName ?? 'Silver'),
              ),
            ),
          ] else ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.amber[50],
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(Icons.emoji_events, color: Colors.amber[700]),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Vous avez atteint le niveau maximum ! 🎉',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildBenefitsTab() {
    if (_status == null || _status!.benefits.isEmpty) {
      return const Center(child: Text('Aucun avantage disponible'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _status!.benefits.length,
      itemBuilder: (context, index) {
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: _getTierColor(_status!.currentTier).withOpacity(0.1),
              child: Icon(
                Icons.check_circle,
                color: _getTierColor(_status!.currentTier),
              ),
            ),
            title: Text(_status!.benefits[index]),
            subtitle: Text('Tier ${_status!.currentTier}'),
          ),
        );
      },
    );
  }

  Widget _buildTiersTab() {
    final mockTiers = [
      {'name': 'Bronze', 'points': 0, 'discount': 0, 'level': 1},
      {'name': 'Silver', 'points': 500, 'discount': 5, 'level': 2},
      {'name': 'Gold', 'points': 1500, 'discount': 10, 'level': 3},
      {'name': 'Platinum', 'points': 3000, 'discount': 15, 'level': 4},
    ];

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: mockTiers.length,
      itemBuilder: (context, index) {
        final tier = mockTiers[index];
        final isCurrentTier = tier['name'] == _status?.currentTier;
        final tierColor = _getTierColor(tier['name'] as String);

        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          elevation: isCurrentTier ? 8 : 2,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: isCurrentTier
                ? BorderSide(color: tierColor, width: 2)
                : BorderSide.none,
          ),
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              gradient: isCurrentTier
                  ? LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        tierColor.withOpacity(0.1),
                        tierColor.withOpacity(0.05),
                      ],
                    )
                  : null,
            ),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        _getTierIcon(tier['name'] as String),
                        size: 40,
                        color: tierColor,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              tier['name'] as String,
                              style: TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.bold,
                                color: tierColor,
                              ),
                            ),
                            if (isCurrentTier)
                              Container(
                                margin: const EdgeInsets.only(top: 4),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: tierColor,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Text(
                                  'VOTRE NIVEAU',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                      Text(
                        '${tier['discount']}%',
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: tierColor,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildTierDetailRow(
                    Icons.stars,
                    'Points minimum',
                    '${tier['points']} pts',
                    tierColor,
                  ),
                  const SizedBox(height: 8),
                  _buildTierDetailRow(
                    Icons.local_offer,
                    'Réduction',
                    '${tier['discount']}%',
                    tierColor,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Avantages exclusifs:',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: Colors.grey[700],
                    ),
                  ),
                  const SizedBox(height: 8),
                  ..._getMockBenefits(tier['name'] as String)
                      .map((benefit) => Padding(
                            padding: const EdgeInsets.only(bottom: 4),
                            child: Row(
                              children: [
                                Icon(Icons.check, size: 16, color: tierColor),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    benefit,
                                    style: const TextStyle(fontSize: 13),
                                  ),
                                ),
                              ],
                            ),
                          )),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildTierDetailRow(IconData icon, String label, String value, Color color) {
    return Row(
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: 8),
        Text(
          label,
          style: TextStyle(color: Colors.grey[600], fontSize: 14),
        ),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: color,
            fontSize: 14,
          ),
        ),
      ],
    );
  }

  Widget _buildLeaderboardTab() {
    // Mock leaderboard data
    final mockLeaderboard = List.generate(
      10,
      (index) => {
        'rank': index + 1,
        'name': 'Utilisateur ${index + 1}',
        'tier': ['Platinum', 'Gold', 'Silver', 'Bronze'][index % 4],
        'points': 5000 - (index * 400),
        'isMe': index == 4,
      },
    );

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: mockLeaderboard.length,
      itemBuilder: (context, index) {
        final entry = mockLeaderboard[index];
        final isMe = entry['isMe'] as bool;

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          elevation: isMe ? 4 : 1,
          color: isMe ? Colors.blue[50] : null,
          child: ListTile(
            leading: Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: _getRankColor(entry['rank'] as int),
              ),
              child: Center(
                child: Text(
                  '#${entry['rank']}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
            title: Row(
              children: [
                Text(
                  entry['name'] as String,
                  style: TextStyle(
                    fontWeight: isMe ? FontWeight.bold : FontWeight.normal,
                  ),
                ),
                if (isMe) ...[
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.blue[700],
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Text(
                      'VOUS',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ],
            ),
            subtitle: Row(
              children: [
                Icon(
                  _getTierIcon(entry['tier'] as String),
                  size: 14,
                  color: _getTierColor(entry['tier'] as String),
                ),
                const SizedBox(width: 4),
                Text(entry['tier'] as String),
              ],
            ),
            trailing: Text(
              '${entry['points']} pts',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ),
        );
      },
    );
  }

  Color _getTierColor(String tier) {
    switch (tier.toLowerCase()) {
      case 'platinum':
        return Colors.grey[800]!;
      case 'gold':
        return Colors.amber[700]!;
      case 'silver':
        return Colors.grey[400]!;
      case 'bronze':
      default:
        return Colors.brown[600]!;
    }
  }

  IconData _getTierIcon(String tier) {
    switch (tier.toLowerCase()) {
      case 'platinum':
        return Icons.military_tech;
      case 'gold':
        return Icons.workspace_premium;
      case 'silver':
        return Icons.star;
      case 'bronze':
      default:
        return Icons.grade;
    }
  }

  Color _getRankColor(int rank) {
    if (rank == 1) return Colors.amber[700]!;
    if (rank == 2) return Colors.grey[400]!;
    if (rank == 3) return Colors.brown[600]!;
    return Colors.blue[700]!;
  }

  List<String> _getMockBenefits(String tier) {
    switch (tier.toLowerCase()) {
      case 'platinum':
        return [
          '15% de réduction sur tous les services',
          'Manager de compte dédié',
          'Annulation gratuite jusqu\'à 1h avant',
          'Deux services gratuits par an',
          'Badge Platinum sur le profil',
        ];
      case 'gold':
        return [
          '10% de réduction sur tous les services',
          'Support prioritaire',
          'Annulation gratuite jusqu\'à 6h avant',
          'Un service gratuit par an',
          'Badge Gold sur le profil',
        ];
      case 'silver':
        return [
          '5% de réduction sur tous les services',
          'Support prioritaire',
          'Annulation flexible (12h avant)',
          'Points bonus anniversaire',
        ];
      case 'bronze':
      default:
        return [
          'Support standard',
          'Annulation (24h avant)',
        ];
    }
  }
}
