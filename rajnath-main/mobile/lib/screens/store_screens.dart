import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../api/api_client.dart';
import '../payments/checkout.dart';
import '../state/app_state.dart';
import '../theme/app_theme.dart';
import '../util/validators.dart';
import '../widgets/common.dart';
import 'assistant_screen.dart';
import 'purchase_screens.dart';
import 'routing.dart';

/// Store categories, same as the website (Store::CATEGORIES).
const storeCategories = <String, String>{
  '': 'All',
  'gemstone': 'Gemstones',
  'rudraksha': 'Rudraksha',
  'yantra': 'Yantra',
  'mala': 'Mala & bracelets',
  'kit': 'Puja kits',
  'samagri': 'Samagri',
  'idol': 'Idols',
  'vastu': 'Vastu',
};

/// Store home: night-sky header, category chips and a 2-column product grid (like the website).
class StoreScreen extends StatefulWidget {
  const StoreScreen({super.key, required this.state, this.category = ''});
  final AppState state;
  final String category;
  @override
  State<StoreScreen> createState() => _StoreScreenState();
}

class _StoreScreenState extends State<StoreScreen> {
  late String _cat = widget.category;

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Store'), bottom: const BrandStripe()),
        body: SafeArea(
          child: AsyncView<List<Map<String, dynamic>>>(
            key: ValueKey(_cat),
            load: () async => unwrapList(await widget.state.api.get('/store/products', query: _cat.isEmpty ? null : {'category': _cat})),
            isEmpty: (_) => false,
            builder: (context, list, _) => CustomScrollView(slivers: [
              const SliverToBoxAdapter(
                child: CosmicHeader(
                  eyebrow: 'Store',
                  title: 'Gemstones &',
                  highlight: 'remedies',
                  subtitle: 'Certified gemstones, rudraksha, yantras, malas and puja samagri — energised and shipped across India.',
                ),
              ),
              SliverToBoxAdapter(
                child: SizedBox(
                  height: 54,
                  child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8), children: [
                    for (final e in storeCategories.entries)
                      Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ChoiceChip(label: Text(e.value), selected: _cat == e.key, onSelected: (_) => setState(() => _cat = e.key)),
                      ),
                  ]),
                ),
              ),
              if (list.isEmpty)
                const SliverToBoxAdapter(child: StatePanel(icon: Icons.inventory_2_outlined, message: 'Nothing is listed here right now. Please check back soon.'))
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                  sliver: SliverGrid(
                    gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(maxCrossAxisExtent: 240, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 0.62),
                    delegate: SliverChildBuilderDelegate((_, i) => ProductCard(state: widget.state, product: list[i]), childCount: list.length),
                  ),
                ),
            ]),
          ),
        ),
      );
}

/// Product card used in the store grid and the home "From our store" strip.
class ProductCard extends StatelessWidget {
  const ProductCard({super.key, required this.state, required this.product});
  final AppState state;
  final Map product;

  @override
  Widget build(BuildContext context) {
    final p = product;
    final cat = storeCategories['${p['category']}'] ?? '';
    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductDetailScreen(state: state, slug: '${p['slug']}'))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          AspectRatio(aspectRatio: 1, child: _ProductImage(url: (p['thumb_url'] ?? p['image_url']) as String?)),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                if (cat.isNotEmpty && cat != 'All')
                  Text(cat.toUpperCase(), style: const TextStyle(fontSize: 10, letterSpacing: 1.2, fontWeight: FontWeight.w700, color: ArjColors.gold700)),
                const SizedBox(height: 2),
                Text('${p['name']}', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, height: 1.2)),
                const Spacer(),
                Text(formatPaise((p['price_minor'] as num?)?.toInt()), style: const TextStyle(fontWeight: FontWeight.w800, color: ArjColors.maroon700, fontSize: 15)),
                Text(p['in_stock'] == false ? 'Out of stock' : 'In stock',
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: p['in_stock'] == false ? ArjColors.danger700 : ArjColors.success700)),
              ]),
            ),
          ),
        ]),
      ),
    );
  }
}

class _ProductImage extends StatelessWidget {
  const _ProductImage({required this.url});
  final String? url;
  @override
  Widget build(BuildContext context) =>
      NetImage(url, fallback: Container(color: ArjColors.ivory100, child: const Center(child: Icon(Icons.diamond_outlined, color: ArjColors.gold700, size: 36))));
}

/// Full product page: photo, price, highlights, benefits, how to wear, cautions, specs and FAQs
/// (GET /store/products/{slug} → guide), the same content as the website product page.
class ProductDetailScreen extends StatelessWidget {
  const ProductDetailScreen({super.key, required this.state, required this.slug});
  final AppState state;
  final String slug;

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Product'), bottom: const BrandStripe()),
        body: SafeArea(
          child: AsyncView<Map<String, dynamic>>(
            load: () async => unwrap(await state.api.get('/store/products/$slug')),
            builder: (context, p, _) {
              final g = (p['guide'] as Map?) ?? const {};
              final tt = Theme.of(context).textTheme;
              final canBuy = p['in_stock'] == true && p['price_minor'] != null;
              final credit = g['credit'] as Map?;
              return Column(children: [
                Expanded(
                  child: ListView(padding: const EdgeInsets.all(16), children: [
                    Container(
                      decoration: BoxDecoration(borderRadius: BorderRadius.circular(ArjStyle.radiusCard), border: Border.all(color: ArjColors.gold200), boxShadow: ArjStyle.glowShadow),
                      child: ClipRRect(borderRadius: BorderRadius.circular(ArjStyle.radiusCard), child: AspectRatio(aspectRatio: 1, child: _ProductImage(url: p['image_url'] as String?))),
                    ),
                    if (credit != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text('Photo of this type of item: ${credit['author']}, Wikimedia Commons, ${credit['license']}.', style: const TextStyle(fontSize: 11, color: ArjColors.ink600)),
                      ),
                    const SizedBox(height: 16),
                    if (g['category_label'] != null) Eyebrow('${g['category_label']}'),
                    const SizedBox(height: 6),
                    Text('${p['name']}', style: tt.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
                    if (p['summary'] != null) Padding(padding: const EdgeInsets.only(top: 6), child: Text('${p['summary']}', style: const TextStyle(color: ArjColors.ink600, height: 1.45))),
                    const SizedBox(height: 12),
                    Row(children: [
                      Text(formatPaise((p['price_minor'] as num?)?.toInt()), style: tt.headlineSmall?.copyWith(fontWeight: FontWeight.w800, color: ArjColors.maroon700)),
                      const SizedBox(width: 10),
                      Text(p['in_stock'] == true ? 'In stock' : 'Out of stock',
                          style: TextStyle(fontWeight: FontWeight.w700, color: p['in_stock'] == true ? ArjColors.success700 : ArjColors.danger700)),
                    ]),
                    const Text('Inclusive of all taxes · free insured shipping in India', style: TextStyle(fontSize: 12.5, color: ArjColors.ink600)),
                    const SizedBox(height: 12),
                    for (final h in (g['highlights'] as List?) ?? const [])
                      Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          const Text('✦ ', style: TextStyle(color: ArjColors.gold700)),
                          Expanded(child: Text('$h')),
                        ]),
                      ),
                    if ('${p['description'] ?? ''}'.trim().isNotEmpty) ...[
                      const SectionTitle('About this item'),
                      Text('${p['description']}', style: const TextStyle(height: 1.55)),
                    ],
                    for (final s in ((g['sections'] as List?) ?? const []).whereType<Map>()) _GuideSection(section: s),
                    AskAiCard(state: state, page: 'product:$slug', question: 'Is ${p['name']} right for me? What should I know before buying it?',
                        suggestions: const ['How do I wear it?', 'Any side effects?', 'Is it genuine?']),
                    if (((g['specs'] as List?) ?? const []).isNotEmpty) ...[
                      const SectionTitle('Specifications'),
                      Card(
                        margin: EdgeInsets.zero,
                        child: Column(children: [
                          for (final r in (g['specs'] as List).whereType<List>())
                            ListTile(dense: true, title: Text('${r[0]}', style: const TextStyle(color: ArjColors.ink600, fontSize: 13)), subtitle: Text('${r[1]}', style: const TextStyle(color: ArjColors.ink900, fontSize: 15))),
                        ]),
                      ),
                    ],
                    if (((g['faqs'] as List?) ?? const []).isNotEmpty) ...[
                      const SectionTitle('Questions'),
                      for (final f in (g['faqs'] as List).whereType<Map>())
                        Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ExpansionTile(title: Text('${f['q']}', style: const TextStyle(fontWeight: FontWeight.w600)), childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 14), children: [
                            Align(alignment: Alignment.centerLeft, child: Text('${f['a']}', style: const TextStyle(color: ArjColors.ink600, height: 1.45))),
                          ]),
                        ),
                    ],
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(color: ArjColors.gold50, borderRadius: BorderRadius.circular(14), border: Border.all(color: ArjColors.gold200)),
                      child: const Text('Gemstones, rudraksha and yantras are traditional remedies and do not replace medical, legal or financial advice. Wear a gemstone only after a chart consultation.',
                          style: TextStyle(fontSize: 12.5)),
                    ),
                  ]),
                ),
                // Sticky buy bar, like a shopping app.
                Container(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
                  decoration: const BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Color(0x141B1446), blurRadius: 16, offset: Offset(0, -4))]),
                  child: Row(children: [
                    Expanded(
                      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(formatPaise((p['price_minor'] as num?)?.toInt()), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18, color: ArjColors.ink900)),
                        const Text('Free shipping', style: TextStyle(fontSize: 12, color: ArjColors.success700)),
                      ]),
                    ),
                    GoldButton(
                      label: canBuy ? 'Buy now' : 'Out of stock',
                      icon: canBuy ? Icons.shopping_bag_outlined : null,
                      onPressed: canBuy
                          ? () async {
                              if (!await ensureSignedIn(context, state, reason: 'Sign in to buy and track your order.') || !context.mounted) return;
                              Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductBuyScreen(state: state, product: p)));
                            }
                          : null,
                    ),
                  ]),
                ),
              ]);
            },
          ),
        ),
      );
}

class _GuideSection extends StatelessWidget {
  const _GuideSection({required this.section});
  final Map section;

  @override
  Widget build(BuildContext context) {
    final title = '${section['title']}';
    final warn = title.toLowerCase().contains('caution') || title.toLowerCase().contains('side');
    final items = (section['items'] as List?) ?? const [];
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      SectionTitle(title),
      Card(
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            for (final it in items)
              if (section['kind'] == 'table' && it is List)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('${it[0]}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: ArjColors.gold700)),
                    const SizedBox(height: 2),
                    Text('${it[1]}', style: const TextStyle(height: 1.4)),
                  ]),
                )
              else
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 5),
                  child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Icon(warn ? Icons.warning_amber_rounded : Icons.check_circle_outline, size: 18, color: warn ? ArjColors.danger700 : ArjColors.success700),
                    const SizedBox(width: 8),
                    Expanded(child: Text('$it', style: const TextStyle(height: 1.4))),
                  ]),
                ),
          ]),
        ),
      ),
    ]);
  }
}

class ProductBuyScreen extends StatefulWidget {
  const ProductBuyScreen({super.key, required this.state, required this.product});
  final AppState state;
  final Map product;
  @override
  State<ProductBuyScreen> createState() => _ProductBuyScreenState();
}

class _ProductBuyScreenState extends State<ProductBuyScreen> {
  final _attempt = PurchaseAttempt();
  late final _name = TextEditingController(text: '${widget.state.user?['name'] ?? ''}');
  late final _phone = TextEditingController(text: '${widget.state.user?['phone'] ?? ''}');
  final _line1 = TextEditingController(), _line2 = TextEditingController(), _city = TextEditingController(), _stateName = TextEditingController(), _pincode = TextEditingController();
  int _qty = 1;
  bool _busy = false;
  Map<String, String> _errors = {};
  Map<String, dynamic>? _coupon;

  /// A coupon quote is for one quantity; changing it clears the coupon so it is checked again.
  void _setQty(int q) => setState(() {
        _qty = q;
        _coupon = null;
      });

  int get _max => ((widget.product['max_per_order'] as num?)?.toInt() ?? 1).clamp(1, 99);
  int get _price => (widget.product['price_minor'] as num?)?.toInt() ?? 0;

  @override
  void dispose() {
    for (final c in [_name, _phone, _line1, _line2, _city, _stateName, _pincode]) {
      c.dispose();
    }
    super.dispose();
  }

  Map<String, String> _validate() {
    final e = <String, String>{};
    if (_name.text.trim().isEmpty) e['address.name'] = 'Enter the recipient\'s name.';
    final pe = mobileError(_phone.text);
    if (pe != null) e['address.phone'] = pe;
    if (_line1.text.trim().isEmpty) e['address.line1'] = 'Enter the address.';
    if (_city.text.trim().isEmpty) e['address.city'] = 'Enter the city.';
    if (_stateName.text.trim().isEmpty) e['address.state'] = 'Enter the state.';
    final pin = pincodeError(_pincode.text);
    if (pin != null) e['address.pincode'] = pin;
    return e;
  }

  Future<void> _buy() async {
    final errs = _validate();
    if (errs.isNotEmpty) return setState(() => _errors = errs);
    setState(() { _busy = true; _errors = {}; });
    final address = {
      'name': _name.text.trim(),
      'phone': normalizeIndianMobile(_phone.text),
      'line1': _line1.text.trim(),
      if (_line2.text.trim().isNotEmpty) 'line2': _line2.text.trim(),
      'city': _city.text.trim(),
      'state': _stateName.text.trim(),
      'pincode': _pincode.text.trim(),
    };
    try {
      final ref = '${widget.product['ref']}';
      final coupon = _coupon?['code'] as String?;
      final r = await widget.state.api.post('/checkout/product', {
        'product': ref,
        'quantity': _qty,
        'address': address,
        'coupon': ?coupon,
        'key': _attempt.keyFor('$ref|$_qty|${coupon ?? ''}|${address.values.join('|')}'),
      });
      if (!mounted) return;
      final order = await runCheckout(context, widget.state.api, r);
      if (!mounted) return;
      if (!isPendingPayment(order)) _attempt.reset();
      if (isPaidLike(order)) await afterPurchase(context, widget.state, order!, replace: true);
    } on ApiException catch (e) {
      setState(() => _errors = e.fieldErrors.isEmpty ? {'_': e.message} : e.fieldErrors);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _field(TextEditingController c, String label, String key, {TextInputType? type, List<TextInputFormatter>? fmt, int? maxLength, TextCapitalization cap = TextCapitalization.words}) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(
          controller: c,
          keyboardType: type,
          inputFormatters: fmt,
          maxLength: maxLength,
          textCapitalization: cap,
          decoration: InputDecoration(labelText: label, errorText: _errors[key], counterText: ''),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final p = widget.product;
    return Scaffold(
      appBar: AppBar(title: const Text('Delivery details')),
      body: SafeArea(
        child: ListView(padding: const EdgeInsets.all(16), children: [
          Text('${p['name']}', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          Row(children: [
            const Expanded(child: Text('Quantity')),
            IconButton(tooltip: 'Decrease quantity', onPressed: _qty > 1 ? () => _setQty(_qty - 1) : null, icon: const Icon(Icons.remove_circle_outline)),
            Semantics(label: 'Quantity $_qty', child: ExcludeSemantics(child: Text('$_qty', style: Theme.of(context).textTheme.titleMedium))),
            IconButton(tooltip: 'Increase quantity', onPressed: _qty < _max ? () => _setQty(_qty + 1) : null, icon: const Icon(Icons.add_circle_outline)),
          ]),
          if (_max > 1) Text('Up to $_max per order.', style: const TextStyle(fontSize: 12, color: ArjColors.ink600)),
          FormError(_errors['quantity'] ?? _errors['product']),
          const Divider(height: 32),
          _field(_name, 'Full name', 'address.name'),
          _field(_phone, 'Mobile number', 'address.phone', type: TextInputType.phone, maxLength: 14, fmt: [FilteringTextInputFormatter.allow(RegExp(r'[0-9+ ]'))]),
          _field(_line1, 'House / flat, street', 'address.line1', cap: TextCapitalization.sentences),
          _field(_line2, 'Area, landmark (optional)', 'address.line2', cap: TextCapitalization.sentences),
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(child: _field(_city, 'City', 'address.city')),
            const SizedBox(width: 12),
            Expanded(child: _field(_stateName, 'State', 'address.state')),
          ]),
          _field(_pincode, 'Pincode', 'address.pincode', type: TextInputType.number, maxLength: 6, fmt: [FilteringTextInputFormatter.digitsOnly]),
          const Divider(height: 24),
          CouponField(
            key: ValueKey('coupon-$_qty'),
            api: widget.state.api,
            target: {'product': '${p['ref']}', 'quantity': _qty},
            onChanged: (q) => setState(() => _coupon = q),
          ),
          FormError(_errors['coupon']),
          const SizedBox(height: 12),
          Row(children: [
            const Expanded(child: Text('Total')),
            Text(formatPaise((_coupon?['total_minor'] as num?)?.toInt() ?? _price * _qty), style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600)),
          ]),
          FormError(_errors['_']),
          const SizedBox(height: 16),
          FilledButton(onPressed: _busy ? null : _buy, child: Text(_busy ? 'Please wait…' : 'Pay securely')),
        ]),
      ),
    );
  }
}
