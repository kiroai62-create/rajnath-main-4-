import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'api/api_client.dart';
import 'push/push_service.dart';
import 'screens/account_screens.dart';
import 'screens/activity_screens.dart';
import 'screens/app_control.dart';
import 'screens/assistant_screen.dart';
import 'screens/astro_screens.dart';
import 'screens/blog_screens.dart';
import 'screens/content_screens.dart';
import 'screens/info_screens.dart';
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
  ArjMotion.enabled = true; // slow zodiac-wheel turn (off in tests so pumpAndSettle can finish)
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
    // Admin › Mobile app can put the app into maintenance or require an update.
    if (widget.state.maintenanceMessage != null) return AppGateScreen.maintenance(state: widget.state);
    if (widget.state.updateRequired) return AppGateScreen.update(state: widget.state);
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
        bottom: const BrandStripe(),
        title: Row(children: [
          Image.asset('assets/images/logo-mark.png', width: 38, height: 38, excludeFromSemantics: true),
          const SizedBox(width: 10),
          Flexible(
            child: _tab == 0
                ? ShaderMask(
                    blendMode: BlendMode.srcIn,
                    shaderCallback: (b) => const LinearGradient(colors: [ArjColors.maroon700, ArjColors.maroon500, ArjColors.saffron600]).createShader(b),
                    child: Text(titles[_tab], overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700)),
                  )
                : Text(titles[_tab], overflow: TextOverflow.ellipsis),
          ),
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

  /// The "Everything in one place" tiles: website art, label, screen, and the app feature that hides it.
  List<(String, String, Widget Function(), String?)> get _ecosystem => [
        ('kundli', 'Free Kundli', () => KundliScreen(state: state), null),
        ('kundli-matching', 'Kundli matching', () => KundliScreen(state: state, matching: true), null),
        ('family-puja', 'Online puja', () => _family('Online puja', 'puja'), 'puja'),
        ('family-chadhava', 'Chadhava', () => _family('Chadhava', 'chadhava'), 'puja'),
        ('gems', 'Gemstones & samagri', () => StoreScreen(state: state), 'store'),
        ('panchang', 'Panchang', () => PanchangScreen(state: state), null),
        ('muhurat', 'Muhurat', () => MuhuratScreen(state: state), null),
        ('gochar', 'Grah gochar', () => GocharScreen(state: state), null),
        ('festivals', 'Festivals', () => FestivalsScreen(state: state), null),
        ('calculators', 'Calculators', () => Scaffold(appBar: AppBar(title: const Text('Calculators')), body: SafeArea(child: ToolsTab(state: state))), null),
        ('blog', 'Articles', () => BlogScreen(state: state), 'blog'),
        ('courses', 'Courses', () => CoursesScreen(state: state), 'blog'),
      ].where((t) => t.$4 == null || state.featureOn(t.$4!)).toList();

  /// Website-style card: the same art as the website tile, then the label.
  Widget _tile(String art, String label, Widget Function() page) => DecoratedBox(
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(ArjStyle.radiusCard), boxShadow: ArjStyle.softShadow),
        child: Material(
          color: Colors.white,
          borderRadius: BorderRadius.circular(ArjStyle.radiusCard),
          clipBehavior: Clip.antiAlias,
          child: InkWell(
            onTap: () => _push(page),
            child: Ink(
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(ArjStyle.radiusCard), border: Border.all(color: ArjColors.ink200.withValues(alpha: 0.7), width: 0.8)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(6, 6, 6, 0),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: Image.asset('assets/images/art/$art.jpg', fit: BoxFit.cover, excludeFromSemantics: true,
                          errorBuilder: (_, _, _) => Container(color: ArjColors.gold50, child: const Icon(Icons.auto_awesome, color: ArjColors.gold700))),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(6, 7, 6, 9),
                  child: Text(label, textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: ArjColors.ink900, height: 1.2)),
                ),
              ]),
            ),
          ),
        ),
      );

  /// A few products for the "From our store" strip (hidden until loaded or when the store is off).
  List<Map> _products = const [];

  Future<void> _loadProducts() async {
    if (!state.featureOn('store')) return;
    try {
      final list = unwrapList(await state.api.get('/store/products')).where((p) => p['image_url'] != null).take(10).toList();
      if (mounted) setState(() => _products = list);
    } catch (_) {/* strip stays hidden */}
  }

  bool _hideUpdate = false;

  Future<void> _loadProfile() async {
    _locale = state.locale;
    _loadArticles(); // titles are translated too
    _loadFeatured();
    _loadToday();
    _loadProducts();
    try {
      final p = await loadProfile(state);
      if (mounted) setState(() => _profile = p);
    } catch (_) {/* keep the built-in hero */}
  }

  static const _defaultSections = ['greeting', 'quick', 'hero', 'ecosystem', 'featured', 'signs', 'store', 'articles', 'more', 'reviews'];

  @override
  Widget build(BuildContext context) {
    final p = _profile ?? const <String, dynamic>{};
    final home = (p['home'] as Map?) ?? const {};
    final name = _or(p['name'], 'Acharya Dr. Rajnath Jha');
    final eyebrow = _or(home['eyebrow'], 'Vedic astrology · Vastu · Remedies').toUpperCase();
    final tt = Theme.of(context).textTheme;
    final heading = tt.headlineSmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, height: 1.22, fontSize: 25);
    final intro = Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Container(width: 18, height: 2, color: ArjColors.gold300),
        const SizedBox(width: 8),
        Flexible(child: Text(eyebrow, style: tt.labelMedium?.copyWith(color: ArjColors.gold200, letterSpacing: 1.6, fontWeight: FontWeight.w700))),
      ]),
      const SizedBox(height: 12),
      Text('Clear, practical guidance from', style: heading),
      GoldGradientText(name, style: heading?.copyWith(fontSize: 27)),
      const SizedBox(height: 10),
      Text(_or(home['subtitle'], 'Ph.D. in Astrology · Director, Jyotirveda Vigyan Sansthan, Patna · 30+ years of practice'),
          style: TextStyle(color: Colors.white.withValues(alpha: 0.8), height: 1.5, fontSize: 15)),
    ]);
    final portrait = Stack(alignment: Alignment.center, clipBehavior: Clip.none, children: [
      const Positioned.fill(child: OverflowBox(maxWidth: 420, maxHeight: 420, child: ZodiacWheel(size: 340, opacity: 0.55))),
      Container(
        padding: const EdgeInsets.all(3),
        decoration: BoxDecoration(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(200), bottom: Radius.circular(28)),
          gradient: const LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [ArjColors.gold300, ArjColors.saffron500, ArjColors.maroon600]),
          boxShadow: const [BoxShadow(color: Color(0x66000000), blurRadius: 24, offset: Offset(0, 10))],
        ),
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(197), bottom: Radius.circular(25)),
          child: AspectRatio(
            aspectRatio: 4 / 5,
            child: NetImage('${(p['photos'] as Map?)?['home'] ?? ''}',
                alignment: Alignment.topCenter, semanticLabel: name, fallback: Image.asset(portraitAsset, fit: BoxFit.cover, alignment: Alignment.topCenter, semanticLabel: name)),
          ),
        ),
      ),
      Positioned(
        bottom: -14,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
          decoration: BoxDecoration(color: ArjColors.ink950.withValues(alpha: 0.85), borderRadius: BorderRadius.circular(40), border: Border.all(color: ArjColors.gold300.withValues(alpha: 0.6))),
          child: const Text('Ph.D. in Astrology · Patna and online', style: TextStyle(color: ArjColors.gold200, fontSize: 12, fontWeight: FontWeight.w600)),
        ),
      ),
    ]);
    final ctas = Wrap(spacing: 10, runSpacing: 10, crossAxisAlignment: WrapCrossAlignment.center, children: [
      GoldButton(label: 'Book a consultation', icon: Icons.calendar_month_outlined, onPressed: widget.onOpenServices),
      GlassButton(label: 'Get free Kundli', icon: Icons.auto_awesome, onPressed: () => _push(() => KundliScreen(state: state))),
    ]);
    final hero = Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(26),
        border: Border.all(color: ArjColors.gold300.withValues(alpha: 0.35)),
        boxShadow: const [BoxShadow(color: Color(0x401B1446), blurRadius: 30, offset: Offset(0, 14))],
      ),
      child: CosmicBackground(
        borderRadius: BorderRadius.circular(26),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 22, 20, 22),
          child: LayoutBuilder(builder: (context, c) {
            final wide = c.maxWidth >= 620;
            return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              if (wide)
                Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
                  Expanded(child: intro),
                  const SizedBox(width: 24),
                  SizedBox(width: 280, child: portrait),
                ])
              else ...[intro, const SizedBox(height: 22), Center(child: FractionallySizedBox(widthFactor: 0.74, child: portrait))],
              if (p['stats'] != null) ...[const SizedBox(height: 26), ProfileStats(p['stats'], onDark: true)],
              const SizedBox(height: 20),
              ctas,
            ]);
          }),
        ),
      ),
    );
    final first = '${state.user?['name'] ?? ''}'.trim().split(' ').first;
    final now = DateTime.now();
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    final greeting = Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 12, 14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [Colors.white, ArjColors.gold50]),
        borderRadius: BorderRadius.circular(ArjStyle.radiusCard),
        border: Border.all(color: ArjColors.gold200),
        boxShadow: ArjStyle.softShadow,
      ),
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
      if (state.featureOn('consultation')) (Icons.videocam_outlined, 'Book a call', () => _push(() => ServiceDetailScreen(state: state, slug: 'consultation-with-acharya-ji'))),
      if (state.featureOn('palm')) (Icons.back_hand_outlined, 'Palm reading', () => _push(() => ServiceDetailScreen(state: state, slug: 'palmistry'))),
      if (state.featureOn('puja')) (Icons.temple_hindu_outlined, 'Book a puja', () => _push(() => _family('Online puja', 'puja'))),
      if (state.featureOn('store')) (Icons.storefront_outlined, 'Shop', () => _push(() => StoreScreen(state: state))),
      (Icons.home_work_outlined, 'Vastu', () => _push(() => ServiceDetailScreen(state: state, slug: 'vastu-consultation'))),
    ];
    final quickRow = SizedBox(
      height: 96,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: quick.length,
        separatorBuilder: (_, _) => const SizedBox(width: 10),
        itemBuilder: (context, i) {
          final (icon, label, onTap) = quick[i];
          return SizedBox(
            width: 78,
            child: InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: onTap,
              child: Column(children: [
                IconOrb(size: 58, child: Icon(icon, color: ArjColors.gold300)),
                const SizedBox(height: 7),
                Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
              ]),
            ),
          );
        },
      ),
    );
    final eco = _ecosystem;
    final sections = <String, List<Widget>>{
      'greeting': [greeting],
      'quick': [const SizedBox(height: 14), quickRow],
      'hero': [const SizedBox(height: 10), hero],
      'ecosystem': [
        const SectionTitle('Everything in one place', eyebrow: 'Everything for your spiritual journey'),
        LayoutBuilder(
          builder: (context, c) => GridView.count(
            crossAxisCount: c.maxWidth >= 600 ? 6 : (c.maxWidth >= 340 ? 3 : 2),
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            childAspectRatio: 0.82,
            children: [for (final (art, label, page, _) in eco) _tile(art, label, page)],
          ),
        ),
      ],
      'featured': [
        if (_featured.isNotEmpty) ...[
          const SectionTitle('Popular services', eyebrow: 'Most booked'),
          SizedBox(
            height: 226,
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
                        Padding(
                          padding: const EdgeInsets.fromLTRB(12, 0, 12, 10),
                          child: Row(children: [
                            Expanded(child: PriceChip(o['from_price_minor'] == null ? 'Price on request' : 'From ${formatPaise((o['from_price_minor'] as num).toInt())}')),
                            const Text('View →', style: TextStyle(color: ArjColors.gold700, fontWeight: FontWeight.w700, fontSize: 12.5)),
                          ]),
                        ),
                      ]),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ],
      'signs': [
        const SectionTitle('Your sign today', eyebrow: 'Rashifal'),
        CosmicBackground(
          borderRadius: BorderRadius.circular(ArjStyle.radiusCard),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 14),
            child: SizedBox(
              height: 98,
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
                        IconOrb(child: Text(_signInitials[i], style: const TextStyle(color: ArjColors.gold200, fontSize: 18, fontWeight: FontWeight.w700))),
                        const SizedBox(height: 6),
                        Text(signNames[i].split(' ').first, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                      ]),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
      'store': [
        if (_products.isNotEmpty) ...[
          Row(children: [
            const Expanded(child: SectionTitle('From our store', eyebrow: 'Certified gemstones · Rudraksha · Puja samagri')),
            TextButton(onPressed: () => _push(() => StoreScreen(state: state)), child: const Text('See all')),
          ]),
          SizedBox(
            height: 236,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: _products.length,
              separatorBuilder: (_, _) => const SizedBox(width: 12),
              itemBuilder: (context, i) => SizedBox(width: 168, child: ProductCard(state: state, product: _products[i])),
            ),
          ),
        ],
      ],
      'articles': [
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
      ],
      'more': [
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
            if (state.featureOn('gallery')) ...[
              const Divider(height: 1),
              ListTile(
                  leading: const Icon(Icons.photo_library_outlined),
                  title: const Text('Photo gallery'),
                  subtitle: const Text('Pujas, events and awards'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => _push(() => GalleryScreen(state: state))),
            ],
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
      ],
      'reviews': [HomeReviews(state: state)],
    };
    final banner = state.announcement;
    return ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 96), children: [
      if (banner != null) AnnouncementBanner(state: state, data: banner),
      if (state.updateAvailable && !_hideUpdate) UpdateNote(state: state, onClose: () => setState(() => _hideUpdate = true)),
      for (final key in state.homeSections(_defaultSections)) ...?sections[key],
      const SizedBox(height: 24),
      const Text('Astrological guidance is traditional and interpretive. It does not replace medical, legal or financial advice.', style: TextStyle(fontSize: 12, color: ArjColors.ink600)),
    ]);
  }
}
