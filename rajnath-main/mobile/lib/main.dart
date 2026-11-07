import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'api/api_client.dart';
import 'push/push_service.dart';
import 'screens/account_screens.dart';
import 'screens/activity_screens.dart';
import 'screens/assistant_screen.dart';
import 'screens/astro_screens.dart';
import 'screens/blog_screens.dart';
import 'screens/content_screens.dart';
import 'screens/info_screens.dart';
import 'screens/purchase_screens.dart';
import 'screens/review_screens.dart';
import 'screens/routing.dart';
import 'screens/services_screens.dart';
import 'screens/showcase_screens.dart';
import 'screens/store_screens.dart';
import 'screens/tool_screens.dart';
import 'state/app_state.dart';
import 'theme/app_theme.dart';
import 'util/validators.dart';
import 'widgets/common.dart';

final navigatorKey = GlobalKey<NavigatorState>();
final messengerKey = GlobalKey<ScaffoldMessengerState>();

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final state = AppState(ApiClient());
  final boot = state.bootstrap();
  runApp(ArjApp(state: state, navigatorKey: navigatorKey, messengerKey: messengerKey));
  final push = PushService(state, navigatorKey, messengerKey: messengerKey, targetBuilder: (kind, ref) => screenForTarget(state, kind, ref));
  // Meta (push config) loads after the session; push failures are swallowed inside start().
  unawaited(boot.then((_) => push.start()).catchError((Object _) {}));
}

class ArjApp extends StatelessWidget {
  const ArjApp({super.key, required this.state, this.navigatorKey, this.messengerKey});
  final AppState state;
  final GlobalKey<NavigatorState>? navigatorKey;
  final GlobalKey<ScaffoldMessengerState>? messengerKey;

  @override
  Widget build(BuildContext context) => MaterialApp(
        navigatorKey: navigatorKey,
        scaffoldMessengerKey: messengerKey,
        title: 'Astro Rajnath Jha',
        debugShowCheckedModeBanner: false,
        theme: buildArjTheme(),
        home: ListenableBuilder(listenable: state, builder: (_, _) => HomeShell(state: state)),
      );
}

class HomeShell extends StatefulWidget {
  const HomeShell({super.key, required this.state});
  final AppState state;
  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeTab(state: widget.state, onOpenServices: () => setState(() => _tab = 1)),
      ServicesScreen(state: widget.state),
      ToolsTab(state: widget.state),
      AccountScreen(state: widget.state),
      MoreScreen(state: widget.state),
    ];
    final state = widget.state;
    const titles = ['Astro Rajnath Jha', 'Our services', 'Tools & guidance', 'My account', 'More'];
    return Scaffold(
      appBar: AppBar(
        titleSpacing: 12,
        title: Row(children: [
          Image.asset('assets/images/logo-mark.png', width: 38, height: 38, excludeFromSemantics: true),
          const SizedBox(width: 10),
          Flexible(child: Text(titles[_tab], overflow: TextOverflow.ellipsis)),
        ]),
        actions: [
          if (state.assistantEnabled) IconButton(tooltip: 'Ask our AI assistant', icon: const Icon(Icons.auto_awesome_outlined), onPressed: () => openAssistant(context, state)),
          if (state.signedIn)
            IconButton(
              tooltip: 'Notifications',
              icon: Badge(isLabelVisible: state.unread > 0, label: Text('${state.unread}'), child: const Icon(Icons.notifications_none_rounded)),
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => NotificationsScreen(state: state))),
            ),
          const SizedBox(width: 4),
        ],
      ),
      floatingActionButton: state.assistantEnabled && _tab <= 1
          ? _AskAiButton(state: state)
          : null,
      body: SafeArea(child: IndexedStack(index: _tab, children: pages)),
      bottomNavigationBar: DecoratedBox(
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: ArjColors.ink100)),
          boxShadow: [BoxShadow(color: Color(0x141B1446), blurRadius: 16, offset: Offset(0, -4))],
        ),
        child: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) {
          HapticFeedback.selectionClick();
          setState(() => _tab = i);
        },
        destinations: [
          const NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
          const NavigationDestination(icon: Icon(Icons.auto_awesome_outlined), selectedIcon: Icon(Icons.auto_awesome), label: 'Services'),
          const NavigationDestination(icon: Icon(Icons.calculate_outlined), selectedIcon: Icon(Icons.calculate), label: 'Tools'),
          NavigationDestination(
            icon: Badge(isLabelVisible: widget.state.unread > 0, label: Text('${widget.state.unread}'), child: const Icon(Icons.person_outline)),
            selectedIcon: Badge(isLabelVisible: widget.state.unread > 0, label: Text('${widget.state.unread}'), child: const Icon(Icons.person)),
            label: 'Account',
            tooltip: widget.state.unread > 0 ? 'Account, ${widget.state.unread} unread notifications' : 'Account',
          ),
          const NavigationDestination(icon: Icon(Icons.menu), label: 'More'),
        ],
        ),
      ),
    );
  }
}

/// Opens the AI assistant. Anyone can ask; booking and ordering through it need a signed-in customer.
Future<void> openAssistant(BuildContext context, AppState state, {String? message}) async {
  Navigator.of(context).push(MaterialPageRoute(builder: (_) => AssistantScreen(state: state, initialMessage: message)));
}

class HomeTab extends StatefulWidget {
  const HomeTab({super.key, required this.state, required this.onOpenServices});
  final AppState state;
  final VoidCallback onOpenServices;
  @override
  State<HomeTab> createState() => _HomeTabState();
}

/// Devanagari initials of the 12 rashis, as on the website.
const _signInitials = ['मे', 'वृ', 'मि', 'क', 'सिं', 'क', 'तु', 'वृ', 'ध', 'म', 'कुं', 'मी'];

/// Floating "Ask AI" button: night-sky pill with a gold ring; the logo glows a few times when it appears.
class _AskAiButton extends StatefulWidget {
  const _AskAiButton({required this.state});
  final AppState state;
  @override
  State<_AskAiButton> createState() => _AskAiButtonState();
}

class _AskAiButtonState extends State<_AskAiButton> with SingleTickerProviderStateMixin {
  late final AnimationController _glow = AnimationController(vsync: this, duration: const Duration(milliseconds: 2400));

  @override
  void initState() {
    super.initState();
    if (!WidgetsBinding.instance.platformDispatcher.accessibilityFeatures.disableAnimations) _glow.repeat(count: 5);
  }

  @override
  void dispose() {
    _glow.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Semantics(
        button: true,
        label: 'Ask AI',
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(40),
            onTap: () => openAssistant(context, widget.state),
            child: Ink(
              padding: const EdgeInsets.fromLTRB(6, 6, 20, 6),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [ArjColors.ink900, ArjColors.ink800, ArjColors.maroon700]),
                borderRadius: BorderRadius.circular(40),
                border: Border.all(color: ArjColors.gold300.withValues(alpha: 0.8), width: 1.5),
                boxShadow: const [BoxShadow(color: Color(0x661B1446), blurRadius: 22, offset: Offset(0, 10))],
              ),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                AnimatedBuilder(
                  animation: _glow,
                  builder: (_, child) => Container(
                    decoration: BoxDecoration(shape: BoxShape.circle, boxShadow: [
                      BoxShadow(color: ArjColors.gold500.withValues(alpha: 0.55 * (1 - _glow.value)), blurRadius: 4, spreadRadius: 14 * _glow.value),
                    ]),
                    child: child,
                  ),
                  child: Image.asset('assets/images/logo-mark-light.png', width: 40, height: 40, excludeFromSemantics: true),
                ),
                const SizedBox(width: 10),
                const Text('Ask AI', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 15)),
              ]),
            ),
          ),
        ),
      );
}

String _or(Object? v, String fallback) {
  final s = v == null ? '' : '$v'.trim();
  return s.isEmpty ? fallback : s;
}

class _HomeTabState extends State<HomeTab> {
  /// GET /profile (hero text, stats, portrait). Until it loads, or if it fails, the built-in text and portrait show.
  Map<String, dynamic>? _profile;
  String? _locale;

  /// Latest 3 from GET /blog; the strip stays hidden while empty or when the request fails.
  List<Map<String, dynamic>> _articles = const [];

  /// Featured services for the "Popular services" strip (hidden until loaded).
  List<Map> _featured = const [];

  /// Today's tithi and nakshatra for the greeting card (Patna), when available.
  String? _today;

  AppState get state => widget.state;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void didUpdateWidget(HomeTab old) {
    super.didUpdateWidget(old);
    if (state.locale != _locale) _loadProfile(); // the profile text is translated
  }

  Future<void> _loadFeatured() async {
    try {
      final list = (await state.api.get('/catalogue/offerings'))['data'] as List;
      final f = list.whereType<Map>().where((o) => o['featured'] == true).take(8).toList();
      if (mounted) setState(() => _featured = f);
    } catch (_) {/* strip stays hidden */}
  }

  Future<void> _loadToday() async {
    try {
      final now = DateTime.now();
      final date = '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
      final d = (await state.api.get('/panchang', query: {'date': date, 'place_id': '1260086'}))['data'] as Map;
      String name(Object? v) => v is Map ? '${v['name'] ?? ''}' : '${v ?? ''}';
      final parts = [name(d['tithi']), name(d['nakshatra'])].where((x) => x.isNotEmpty && x != 'null').toList();
      if (mounted && parts.isNotEmpty) setState(() => _today = parts.join(' · '));
    } catch (_) {/* greeting shows the date only */}
  }

  Future<void> _loadArticles() async {
    try {
      final list = unwrapList(await state.api.get('/blog')).take(3).toList();
      if (mounted) setState(() => _articles = list);
    } catch (_) {/* the strip stays hidden */}
  }

  void _push(Widget Function() page) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => page()));

  /// Services filtered to one family (puja, chadhava), with its own app bar.
  Widget _family(String title, String family) => Scaffold(appBar: AppBar(title: Text(title)), body: SafeArea(child: ServicesScreen(state: state, initialFamily: family)));

  /// The "Everything in one place" tiles: icon, label, screen.
  List<(IconData, String, Widget Function())> get _ecosystem => [
        (Icons.auto_awesome_outlined, 'Free Kundli', () => KundliScreen(state: state)),
        (Icons.favorite_border, 'Kundli matching', () => KundliScreen(state: state, matching: true)),
        (Icons.local_fire_department_outlined, 'Online puja', () => _family('Online puja', 'puja')),
        (Icons.volunteer_activism_outlined, 'Chadhava', () => _family('Chadhava', 'chadhava')),
        (Icons.diamond_outlined, 'Gemstones & samagri', () => StoreScreen(state: state)),
        (Icons.wb_sunny_outlined, 'Panchang', () => PanchangScreen(state: state)),
        (Icons.event_available_outlined, 'Muhurat', () => MuhuratScreen(state: state)),
        (Icons.public_outlined, 'Grah gochar', () => GocharScreen(state: state)),
        (Icons.celebration_outlined, 'Festivals', () => FestivalsScreen(state: state)),
        (Icons.calculate_outlined, 'Calculators', () => Scaffold(appBar: AppBar(title: const Text('Calculators')), body: SafeArea(child: ToolsTab(state: state)))),
        (Icons.menu_book_outlined, 'Articles', () => BlogScreen(state: state)),
        (Icons.school_outlined, 'Courses', () => CoursesScreen(state: state)),
      ];

  Widget _tile(IconData icon, String label, Widget Function() page) => DecoratedBox(
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(ArjStyle.radiusCard), boxShadow: ArjStyle.softShadow),
        child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(ArjStyle.radiusCard),
        child: InkWell(
          borderRadius: BorderRadius.circular(ArjStyle.radiusCard),
          onTap: () => _push(page),
          child: Ink(
            decoration: BoxDecoration(borderRadius: BorderRadius.circular(ArjStyle.radiusCard), border: Border.all(color: ArjColors.ink200, width: 0.8)),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 10),
              child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                Container(
                  padding: const EdgeInsets.all(11),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [ArjColors.gold50, ArjColors.gold200]),
                    border: Border.all(color: ArjColors.gold200),
                  ),
                  child: Icon(icon, color: ArjColors.gold700, size: 24),
                ),
                const SizedBox(height: 8),
                Text(label, textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: ArjColors.ink900, height: 1.2)),
              ]),
            ),
          ),
        ),
        ),
      );

  Future<void> _loadProfile() async {
    _locale = state.locale;
    _loadArticles(); // titles are translated too
    _loadFeatured();
    _loadToday();
    try {
      final p = await loadProfile(state);
      if (mounted) setState(() => _profile = p);
    } catch (_) {/* keep the built-in hero */}
  }

  @override
  Widget build(BuildContext context) {
    final p = _profile ?? const <String, dynamic>{};
    final home = (p['home'] as Map?) ?? const {};
    final name = _or(p['name'], 'Acharya Dr. Rajnath Jha');
    final eyebrow = _or(home['eyebrow'], 'Vedic astrology · Vastu · Remedies').toUpperCase();
    final tt = Theme.of(context).textTheme;
    final intro = Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Container(width: 18, height: 2, color: ArjColors.gold400),
        const SizedBox(width: 8),
        Flexible(child: Text(eyebrow, style: tt.labelMedium?.copyWith(color: ArjColors.gold400, letterSpacing: 1.6, fontWeight: FontWeight.w700))),
      ]),
      const SizedBox(height: 12),
      Text.rich(
        TextSpan(children: [
          const TextSpan(text: 'Clear, practical guidance from '),
          TextSpan(text: name, style: const TextStyle(color: ArjColors.gold300)),
        ]),
        style: tt.headlineSmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, height: 1.25),
      ),
      const SizedBox(height: 10),
      Text(_or(home['subtitle'], 'Ph.D. in Astrology · Director, Jyotirveda Vigyan Sansthan, Patna · 30+ years of practice'),
          style: TextStyle(color: Colors.white.withValues(alpha: 0.78), height: 1.5)),
    ]);
    final portrait = Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(200), bottom: Radius.circular(22)),
        gradient: const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [ArjColors.gold300, ArjColors.saffron500, ArjColors.maroon600]),
        boxShadow: const [BoxShadow(color: Color(0x66000000), blurRadius: 24, offset: Offset(0, 10))],
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(197), bottom: Radius.circular(19)),
        child: AspectRatio(
          aspectRatio: 4 / 5,
          child: NetImage('${(p['photos'] as Map?)?['home'] ?? ''}',
              alignment: Alignment.topCenter, semanticLabel: name, fallback: Image.asset(portraitAsset, fit: BoxFit.cover, alignment: Alignment.topCenter, semanticLabel: name)),
        ),
      ),
    );
    final cta = FilledButton(
      style: FilledButton.styleFrom(
        backgroundColor: ArjColors.gold300,
        foregroundColor: ArjColors.ink950,
        elevation: 0,
        textStyle: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w700, letterSpacing: 0.2),
      ),
      onPressed: widget.onOpenServices,
      child: const Text('Book a consultation'),
    );
    final hero = Container(
      decoration: BoxDecoration(
        gradient: ArjStyle.cosmicGradient,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: ArjColors.gold300.withValues(alpha: 0.35)),
        boxShadow: const [BoxShadow(color: Color(0x401B1446), blurRadius: 30, offset: Offset(0, 14))],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(children: [
          // Soft gold glow in the corner for depth.
          Positioned(
            right: -80,
            top: -80,
            child: IgnorePointer(
              child: Container(
                width: 220,
                height: 220,
                decoration: BoxDecoration(shape: BoxShape.circle, gradient: RadialGradient(colors: [ArjColors.gold400.withValues(alpha: 0.22), Colors.transparent])),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: LayoutBuilder(builder: (context, c) {
              final wide = c.maxWidth >= 620;
              return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                if (wide)
                  Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
                    Expanded(child: intro),
                    const SizedBox(width: 24),
                    SizedBox(width: 280, child: portrait),
                  ])
                else ...[intro, const SizedBox(height: 18), Center(child: FractionallySizedBox(widthFactor: 0.78, child: portrait))],
                if (p['stats'] != null) ...[const SizedBox(height: 16), ProfileStats(p['stats'], onDark: true)],
                const SizedBox(height: 18),
                cta,
              ]);
            }),
          ),
        ]),
      ),
    );
    final first = '${state.user?['name'] ?? ''}'.trim().split(' ').first;
    final now = DateTime.now();
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    final greeting = Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.fromLTRB(16, 14, 12, 14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(ArjStyle.radiusCard), border: Border.all(color: ArjColors.gold200), boxShadow: ArjStyle.softShadow),
      child: Row(children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(first.isEmpty ? 'Namaste 🙏' : 'Namaste, $first 🙏', style: tt.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 4),
            Text('${days[now.weekday - 1]}, ${now.day} ${months[now.month - 1]}${_today == null ? '' : ' · $_today'}', style: const TextStyle(color: ArjColors.ink600, fontSize: 13)),
          ]),
        ),
        IconButton.filledTonal(tooltip: "Today's Panchang", onPressed: () => _push(() => PanchangScreen(state: state)), icon: const Icon(Icons.wb_sunny_outlined, color: ArjColors.gold700)),
      ]),
    );
    final quick = <(IconData, String, VoidCallback)>[
      if (state.assistantEnabled) (Icons.auto_awesome, 'Ask AI', () => openAssistant(context, state)),
      (Icons.videocam_outlined, 'Book a call', () => _push(() => ServiceDetailScreen(state: state, slug: 'consultation-with-acharya-ji'))),
      (Icons.back_hand_outlined, 'Palm reading', () => _push(() => ServiceDetailScreen(state: state, slug: 'palmistry'))),
      (Icons.temple_hindu_outlined, 'Book a puja', () => _push(() => _family('Online puja', 'puja'))),
      (Icons.storefront_outlined, 'Shop', () => _push(() => StoreScreen(state: state))),
      (Icons.home_work_outlined, 'Vastu', () => _push(() => ServiceDetailScreen(state: state, slug: 'vastu-consultation'))),
    ];
    final quickRow = SizedBox(
      height: 92,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: quick.length,
        separatorBuilder: (_, _) => const SizedBox(width: 10),
        itemBuilder: (context, i) {
          final (icon, label, onTap) = quick[i];
          final ai = i == 0 && state.assistantEnabled;
          return SizedBox(
            width: 78,
            child: InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: onTap,
              child: Column(children: [
                Container(
                  width: 58,
                  height: 58,
                  decoration: BoxDecoration(shape: BoxShape.circle, gradient: ai ? ArjStyle.inkGradient : const LinearGradient(colors: [ArjColors.gold50, ArjColors.gold200]), boxShadow: ArjStyle.softShadow),
                  child: Icon(icon, color: ai ? ArjColors.gold200 : ArjColors.gold700),
                ),
                const SizedBox(height: 6),
                Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
              ]),
            ),
          );
        },
      ),
    );
    return ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 96), children: [
      greeting,
      quickRow,
      const SizedBox(height: 10),
      hero,
      const SizedBox(height: 4),
      const SectionTitle('Everything in one place'),
      LayoutBuilder(
        builder: (context, c) => GridView.count(
          crossAxisCount: c.maxWidth >= 600 ? 6 : (c.maxWidth >= 340 ? 3 : 2),
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 0.95,
          children: [for (final (icon, label, page) in _ecosystem) _tile(icon, label, page)],
        ),
      ),
      if (_featured.isNotEmpty) ...[
        const SectionTitle('Popular services'),
        SizedBox(
          height: 216,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: _featured.length,
            separatorBuilder: (_, _) => const SizedBox(width: 12),
            itemBuilder: (context, i) {
              final o = _featured[i];
              return SizedBox(
                width: 210,
                child: Card(
                  margin: EdgeInsets.zero,
                  clipBehavior: Clip.antiAlias,
                  child: InkWell(
                    onTap: () => _push(() => ServiceDetailScreen(state: state, slug: '${o['slug']}')),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      AspectRatio(aspectRatio: 16 / 10, child: NetImage('${o['image_url'] ?? ''}', framed: true, fallback: Container(color: ArjColors.ivory100))),
                      Padding(
                        padding: const EdgeInsets.fromLTRB(12, 8, 12, 0),
                        child: Text('${o['title']}', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, height: 1.25)),
                      ),
                      const Spacer(),
                      Padding(padding: const EdgeInsets.fromLTRB(12, 0, 12, 10), child: PriceChip(o['from_price_minor'] == null ? 'Price on request' : 'From ${formatPaise((o['from_price_minor'] as num).toInt())}')),
                    ]),
                  ),
                ),
              );
            },
          ),
        ),
      ],
      const SectionTitle('Your sign today'),
      Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(gradient: ArjStyle.cosmicGradient, borderRadius: BorderRadius.circular(ArjStyle.radiusCard)),
        child: SizedBox(
          height: 96,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            scrollDirection: Axis.horizontal,
            itemCount: 12,
            separatorBuilder: (_, _) => const SizedBox(width: 10),
            itemBuilder: (context, i) => Semantics(
              button: true,
              label: signNames[i],
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () => _push(() => RashiDetailScreen(state: state, slug: rashiSlugs[i], title: signNames[i])),
                child: SizedBox(
                  width: 72,
                  child: Column(children: [
                    Container(
                      width: 54,
                      height: 54,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [ArjColors.ink800, ArjColors.maroon700]),
                        border: Border.all(color: ArjColors.gold300.withValues(alpha: 0.7), width: 1.5),
                      ),
                      child: Text(_signInitials[i], style: const TextStyle(color: ArjColors.gold200, fontSize: 18, fontWeight: FontWeight.w700)),
                    ),
                    const SizedBox(height: 6),
                    Text(signNames[i].split(' ').first, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                  ]),
                ),
              ),
            ),
          ),
        ),
      ),
      if (_articles.isNotEmpty) ...[
        Row(children: [
          const Expanded(child: SectionTitle('Latest articles')),
          TextButton(onPressed: () => _push(() => BlogScreen(state: state)), child: const Text('See all')),
        ]),
        SizedBox(
          height: 290,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: _articles.length,
            separatorBuilder: (_, _) => const SizedBox(width: 12),
            itemBuilder: (context, i) => SizedBox(width: 250, child: ArticleCard(state: state, article: _articles[i], compact: true)),
          ),
        ),
      ],
      const SectionTitle('More for you'),
      Card(
        margin: EdgeInsets.zero,
        child: Column(children: [
          ListTile(
              leading: const Icon(Icons.brightness_3_outlined),
              title: const Text('Rashifal'),
              subtitle: const Text('Daily, weekly, monthly and yearly'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => _push(() => HoroscopeScreen(state: state))),
          const Divider(height: 1),
          ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Photo gallery'),
              subtitle: const Text('Pujas, events and awards'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => _push(() => GalleryScreen(state: state))),
          if (state.assistantEnabled) ...[
            const Divider(height: 1),
            ListTile(
                leading: const Icon(Icons.chat_bubble_outline),
                title: const Text('Ask our assistant'),
                subtitle: const Text('Answers, prices and bookings in your language'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => openAssistant(context, state)),
          ],
        ]),
      ),
      HomeReviews(state: state),
      const SizedBox(height: 24),
      const Text('Astrological guidance is traditional and interpretive. It does not replace medical, legal or financial advice.', style: TextStyle(fontSize: 12, color: ArjColors.ink600)),
    ]);
  }
}
