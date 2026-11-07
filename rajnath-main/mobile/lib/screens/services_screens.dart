import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api/api_client.dart';
import '../models/slots.dart';
import '../state/app_state.dart';
import '../theme/app_theme.dart';
import '../widgets/common.dart';
import 'assistant_screen.dart';
import 'purchase_screens.dart';
import 'store_screens.dart';

class ServicesScreen extends StatefulWidget {
  const ServicesScreen({super.key, required this.state, this.initialFamily});
  final AppState state;

  /// Family slug to pre-select (e.g. 'puja', 'chadhava' from the home tiles).
  final String? initialFamily;
  @override
  State<ServicesScreen> createState() => _ServicesScreenState();
}

class _ServicesScreenState extends State<ServicesScreen> {
  String _q = '';
  late String? _family = widget.initialFamily;
  String? _mode;
  final _key = GlobalKey();

  Future<Map<String, dynamic>> _load() async {
    final fams = await widget.state.api.get('/catalogue/families');
    final list = await widget.state.api.get('/catalogue/offerings', query: {if (_q.isNotEmpty) 'q': _q, if (_family != null) 'family': _family, if (_mode != null) 'mode': _mode});
    return {'families': fams['data'], 'modes': (fams['meta'] as Map?)?['modes'] ?? const [], 'items': list['data']};
  }

  void _open(Map o) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ServiceDetailScreen(state: widget.state, slug: '${o['slug']}')));

  /// Image card in a two-column grid: picture, title, short summary and starting price.
  Widget _card(Map o) => Card(
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => _open(o),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            AspectRatio(
              aspectRatio: 4 / 3,
              child: Stack(fit: StackFit.expand, children: [
                o['image_url'] != null
                    ? NetImage('${o['image_url']}', framed: true, fallback: Container(color: ArjColors.ivory100))
                    : Container(decoration: const BoxDecoration(gradient: ArjStyle.inkGradient), child: Center(child: Image.asset('assets/images/logo-mark-light.png', width: 64, excludeFromSemantics: true))),
                if (o['featured'] == true)
                  Positioned(
                    left: 8, top: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(color: ArjColors.gold400, borderRadius: BorderRadius.circular(20)),
                      child: const Text('Popular', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: ArjColors.ink900)),
                    ),
                  ),
              ]),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('${o['title']}', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5, height: 1.25)),
                  const SizedBox(height: 4),
                  Expanded(child: Text('${o['summary'] ?? ''}', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: ArjColors.ink600, fontSize: 12.5, height: 1.35))),
                  const SizedBox(height: 6),
                  PriceChip(o['from_price_minor'] == null ? 'Price on request' : 'From ${formatPaise((o['from_price_minor'] as num).toInt())}'),
                ]),
              ),
            ),
          ]),
        ),
      );

  @override
  Widget build(BuildContext context) {
    return AsyncView<Map<String, dynamic>>(
      key: ValueKey('$_q|$_family|$_mode|$_key'),
      load: _load,
      builder: (context, d, reload) {
        final items = (d['items'] as List).cast<Map>();
        final fams = (d['families'] as List).cast<Map>();
        final modes = (d['modes'] as List).whereType<Map>().toList();
        return CustomScrollView(slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            sliver: SliverList.list(children: [
              TextField(
                decoration: const InputDecoration(labelText: 'Search services', prefixIcon: Icon(Icons.search), hintText: 'e.g. marriage, Shani, Vastu, court case'),
                textInputAction: TextInputAction.search,
                onSubmitted: (v) => setState(() => _q = v.trim()),
              ),
              const SizedBox(height: 12),
              SizedBox(
                height: 40,
                child: ListView(scrollDirection: Axis.horizontal, children: [
                  ChoiceChip(label: const Text('All'), selected: _family == null, onSelected: (_) => setState(() => _family = null)),
                  for (final f in fams) Padding(padding: const EdgeInsets.only(left: 8), child: ChoiceChip(label: Text('${f['name']}'), selected: _family == f['slug'], onSelected: (_) => setState(() => _family = '${f['slug']}'))),
                ]),
              ),
              if (modes.isNotEmpty) ...[
                const SizedBox(height: 10),
                const Text('How would you like it?', style: TextStyle(color: ArjColors.ink600)),
                const SizedBox(height: 6),
                SizedBox(
                  height: 40,
                  child: ListView(scrollDirection: Axis.horizontal, children: [
                    ChoiceChip(label: const Text('Any mode'), selected: _mode == null, onSelected: (_) => setState(() => _mode = null)),
                    for (final m in modes) Padding(padding: const EdgeInsets.only(left: 8), child: ChoiceChip(label: Text('${m['label']}'), selected: _mode == m['key'], onSelected: (_) => setState(() => _mode = '${m['key']}'))),
                  ]),
                ),
              ],
              const SizedBox(height: 12),
              Card(
                margin: const EdgeInsets.only(bottom: 12),
                color: ArjColors.gold50,
                child: ListTile(
                  leading: const Icon(Icons.diamond_outlined, color: ArjColors.gold700),
                  title: const Text('Gemstone store'),
                  subtitle: const Text('Certified gemstones, rudraksha, yantras and puja kits'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => StoreScreen(state: widget.state))),
                ),
              ),
              if (items.isEmpty) const StatePanel(icon: Icons.search_off_rounded, message: 'No services match.'),
            ]),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
            sliver: SliverLayoutBuilder(builder: (context, c) {
              final cols = c.crossAxisExtent >= 900 ? 4 : (c.crossAxisExtent >= 600 ? 3 : 2);
              return SliverGrid.builder(
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: cols, mainAxisSpacing: 12, crossAxisSpacing: 12, mainAxisExtent: (c.crossAxisExtent / cols) * 0.75 + 128),
                itemCount: items.length,
                itemBuilder: (context, i) => _card(items[i]),
              );
            }),
          ),
        ]);
      },
    );
  }
}

class ServiceDetailScreen extends StatefulWidget {
  const ServiceDetailScreen({super.key, required this.state, required this.slug});
  final AppState state;
  final String slug;
  @override
  State<ServiceDetailScreen> createState() => _ServiceDetailScreenState();
}

class _ServiceDetailScreenState extends State<ServiceDetailScreen> {
  final _optionsKey = GlobalKey();
  late final Future<Map<String, dynamic>> _future = _fetch();
  AppState get state => widget.state;
  String get slug => widget.slug;

  Future<Map<String, dynamic>> _fetch() async => (await state.api.get('/catalogue/offerings/$slug'))['data'] as Map<String, dynamic>;

  void _toOptions() {
    final ctx = _optionsKey.currentContext;
    if (ctx != null) Scrollable.ensureVisible(ctx, duration: const Duration(milliseconds: 400), curve: Curves.easeOutCubic);
  }

  Widget _steps(List items, {bool numbered = true}) => Column(children: [
        for (final (i, s) in items.indexed)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Container(
                width: 28, height: 28, alignment: Alignment.center,
                decoration: BoxDecoration(shape: BoxShape.circle, color: ArjColors.gold50, border: Border.all(color: ArjColors.gold200)),
                child: numbered ? Text('${i + 1}', style: const TextStyle(fontWeight: FontWeight.w700, color: ArjColors.gold700, fontSize: 13)) : const Icon(Icons.check, size: 16, color: ArjColors.gold700),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: s is Map
                    ? Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('${s['title']}', style: const TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 2),
                        Text('${s['text']}', style: const TextStyle(color: ArjColors.ink600, height: 1.4)),
                      ])
                    : Padding(padding: const EdgeInsets.only(top: 4), child: Text('$s', style: const TextStyle(height: 1.45))),
              ),
            ]),
          ),
      ]);

  @override
  Widget build(BuildContext context) {
    final tt = Theme.of(context).textTheme;
    return Scaffold(
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return Scaffold(appBar: AppBar(title: const Text('Service')), body: const Center(child: CircularProgressIndicator()));
          }
          if (snap.hasError) {
            return Scaffold(
              appBar: AppBar(title: const Text('Service')),
              body: ErrorPanel(message: snap.error is ApiException ? (snap.error as ApiException).message : 'Something went wrong.', offline: snap.error is ApiException && (snap.error as ApiException).isOffline,
                  onRetry: () => Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => ServiceDetailScreen(state: state, slug: slug)))),
            );
          }
          final o = snap.data!;
          final variants = (o['variants'] as List).cast<Map>();
          final process = (o['process'] as List?) ?? const [];
          final benefits = (o['benefits'] as List?) ?? const [];
          final faqs = ((o['faqs'] as List?) ?? const []).whereType<Map>().toList();
          final steps = ((o['steps'] as List?) ?? const []).whereType<Map>().toList();
          final priced = variants.any((v) => v['price_minor'] != null);
          return Column(children: [
            Expanded(
              child: CustomScrollView(slivers: [
                SliverAppBar(
                  pinned: true,
                  expandedHeight: o['image_url'] != null ? 240 : null,
                  title: const Text('Service'),
                  flexibleSpace: o['image_url'] == null
                      ? null
                      : FlexibleSpaceBar(
                          background: Stack(fit: StackFit.expand, children: [
                            NetImage('${o['image_url']}', framed: true, semanticLabel: '${o['title']}', fallback: Container(color: ArjColors.ivory100)),
                            const DecoratedBox(decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0x66000000), Color(0x00000000), Color(0x22000000)]))),
                          ]),
                        ),
                ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                  sliver: SliverList.list(children: [
                    if (o['family_name'] != null) Padding(padding: const EdgeInsets.only(bottom: 6), child: Text('${o['family_name']}'.toUpperCase(), style: tt.labelMedium?.copyWith(color: ArjColors.gold700, letterSpacing: 1.2))),
                    Text('${o['title']}', style: tt.headlineSmall?.copyWith(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 6),
                    Text('${o['summary']}', style: const TextStyle(color: ArjColors.ink600, height: 1.45)),
                    if (o['body'] != null) ...[
                      const SectionTitle('About'),
                      for (final para in '${o['body']}'.split(RegExp(r'\n\s*\n'))) Padding(padding: const EdgeInsets.only(bottom: 10), child: Text(para.trim(), style: const TextStyle(height: 1.55))),
                    ],
                    if (process.isNotEmpty) ...[const SectionTitle('How it is done'), _steps(process)],
                    if (benefits.isNotEmpty) ...[
                      const SectionTitle('Benefits'),
                      Wrap(spacing: 8, runSpacing: 8, children: [
                        for (final b in benefits)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(color: ArjColors.ivory100, borderRadius: BorderRadius.circular(14)),
                            child: Row(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.auto_awesome, size: 14, color: ArjColors.gold700), const SizedBox(width: 6), Flexible(child: Text('$b', style: const TextStyle(fontSize: 13)))]),
                          ),
                      ]),
                    ],
                    if ((o['includes'] as List).isNotEmpty) ...[
                      const SectionTitle("What's included"),
                      _steps(o['includes'] as List, numbered: false),
                    ],
                    KeyedSubtree(key: _optionsKey, child: const SectionTitle('Options & prices')),
                    for (final v in variants)
                      Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        child: ListTile(
                          title: Text('${v['name']}', style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text([if (v['duration_min'] != null) '${v['duration_min']} min', if ((v['participants'] ?? 1) > 1) '${v['participants']} people', if (v['urgent'] == true) 'Priority', formatPaise((v['price_minor'] as num?)?.toInt())].join(' · ')),
                          trailing: v['price_minor'] == null
                              ? null
                              : FilledButton(
                                  style: FilledButton.styleFrom(minimumSize: const Size(72, 42), padding: const EdgeInsets.symmetric(horizontal: 16)),
                                  onPressed: () => widget._buy(context, o, v),
                                  child: Text(isSlotVariant(v) ? 'Book' : 'Buy', semanticsLabel: '${isSlotVariant(v) ? 'Book' : 'Buy'} ${v['name']}'),
                                ),
                        ),
                      ),
                    if (steps.isNotEmpty) ...[const SectionTitle('How booking works'), _steps(steps)],
                    AskAiCard(state: state, page: 'service:$slug', question: 'Tell me about ${o['title']}: how it works, what I get and the price options.',
                        suggestions: const ['Which option should I choose?', 'How do I book it?']),
                    if (faqs.isNotEmpty) ...[
                      const SectionTitle('Questions'),
                      for (final f in faqs)
                        Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ExpansionTile(title: Text('${f['q']}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14.5)), childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 14), children: [Text('${f['a']}', style: const TextStyle(height: 1.45))]),
                        ),
                    ],
                    if (o['disclaimer'] != null)
                      Container(margin: const EdgeInsets.only(top: 16), padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: ArjColors.gold50, borderRadius: BorderRadius.circular(12), border: Border.all(color: ArjColors.gold200)),
                          child: Text('Please note: ${o['disclaimer']}')),
                    const SizedBox(height: 20),
                    if (variants.isEmpty || variants.any((v) => v['price_minor'] == null)) ...[
                      FilledButton(onPressed: () => widget._request(context, o), child: const Text('Request this service')),
                      const SizedBox(height: 8),
                      const Text('Our team will call you to confirm the time and fee.', textAlign: TextAlign.center, style: TextStyle(color: ArjColors.ink600)),
                    ] else
                      TextButton(onPressed: () => widget._request(context, o), child: const Text('Have a question first? Send us a request')),
                  ]),
                ),
              ]),
            ),
            if (priced)
              Material(
                elevation: 8,
                color: Colors.white,
                child: SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
                    child: Row(children: [
                      Expanded(child: Text('${variants.length} option${variants.length == 1 ? '' : 's'} · pay securely online', style: const TextStyle(color: ArjColors.ink600, fontSize: 13))),
                      FilledButton.icon(
                        style: FilledButton.styleFrom(backgroundColor: ArjColors.gold400, foregroundColor: ArjColors.ink900),
                        onPressed: _toOptions,
                        icon: const Icon(Icons.event_available_outlined, size: 18),
                        label: const Text('Choose an option'),
                      ),
                    ]),
                  ),
                ),
              ),
          ]);
        },
      ),
    );
  }
}

extension on ServiceDetailScreen {
  void _request(BuildContext context, Map o) =>
      Navigator.of(context).push(MaterialPageRoute(builder: (_) => RequestScreen(state: state, slug: slug, title: '${o['title']}')));

  /// Priced variant: slot booking (optionally via a team astrologer) or direct checkout.
  Future<void> _buy(BuildContext context, Map o, Map v) async {
    if (!await ensureSignedIn(context, state, reason: 'Sign in to book and pay securely.') || !context.mounted) return;
    final nav = Navigator.of(context);
    final title = '${o['title']}';
    if (!isSlotVariant(v)) {
      await nav.push(MaterialPageRoute(builder: (_) => ServiceBuyScreen(state: state, title: title, variant: v)));
      return;
    }
    Map? practitioner;
    if (teamOfferingSlugs.contains(slug)) {
      practitioner = await nav.push<Map>(MaterialPageRoute(builder: (_) => PractitionerPickerScreen(state: state, offeringSlug: slug, title: title)));
      if (practitioner == null) return;
    }
    await nav.push(MaterialPageRoute(
      builder: (_) => SlotBookingScreen(
        state: state, variantRef: '${v['ref']}', title: title, variantName: '${v['name']}', priceMinor: (v['price_minor'] as num?)?.toInt(), practitioner: practitioner),
    ));
  }
}

class RequestScreen extends StatefulWidget {
  const RequestScreen({super.key, required this.state, this.slug, required this.title});
  final AppState state;
  final String? slug;
  final String title;
  @override
  State<RequestScreen> createState() => _RequestScreenState();
}

class _RequestScreenState extends State<RequestScreen> {
  late final _name = TextEditingController(text: '${widget.state.user?['name'] ?? ''}');
  late final _phone = TextEditingController(text: '${widget.state.user?['phone'] ?? ''}');
  final _msg = TextEditingController();
  bool _consent = false, _busy = false;
  String? _mode, _language;
  DateTime? _date;
  Map<String, String> _errors = {};

  /// Same modes and languages the server accepts (OfferingVariant::MODES, EnquiryApiController).
  static const modes = {'video': 'Video call', 'audio': 'Audio call', 'chat': 'Chat', 'in_person': 'In person (Patna office)', 'written': 'Written answer', 'report': 'Report', 'onsite': 'On-site visit', 'puja': 'Puja'};
  static const languages = ['Hindi', 'Maithili', 'Sanskrit', 'English'];

  Future<void> _submit() async {
    setState(() { _busy = true; _errors = {}; });
    try {
      final r = await widget.state.api.post('/enquiries', {
        'offering': widget.slug, 'name': _name.text.trim(), 'phone': _phone.text.trim(), 'message': _msg.text.trim(), 'consent': _consent,
        'mode': ?_mode, 'language': ?_language, if (_date != null) 'preferred_date': DateFormat('yyyy-MM-dd').format(_date!),
      });
      if (!mounted) return;
      await showDialog<void>(context: context, builder: (_) => AlertDialog(title: const Text('Request received'),
          content: Text('Thank you. Our team will call you to confirm. Reference ${('${r['data']['ref']}').substring(18)}.'), actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('OK'))]));
      if (mounted) Navigator.of(context).pop();
    } on ApiException catch (e) {
      setState(() => _errors = e.fieldErrors.isEmpty ? {'_': e.message} : e.fieldErrors);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Send request')),
        body: SafeArea(
          child: ListView(padding: const EdgeInsets.all(20), children: [
            Text(widget.title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 16),
            TextField(controller: _name, decoration: InputDecoration(labelText: 'Your name', errorText: _errors['name'])),
            const SizedBox(height: 12),
            TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: InputDecoration(labelText: 'Mobile number', errorText: _errors['phone'])),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _mode,
              decoration: InputDecoration(labelText: 'Preferred mode (optional)', errorText: _errors['mode']),
              items: [for (final e in modes.entries) DropdownMenuItem(value: e.key, child: Text(e.value))],
              onChanged: (v) => setState(() => _mode = v),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _language,
              decoration: InputDecoration(labelText: 'Language (optional)', errorText: _errors['language']),
              items: [for (final l in languages) DropdownMenuItem(value: l, child: Text(l))],
              onChanged: (v) => setState(() => _language = v),
            ),
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.event_outlined),
              title: Text(_date == null ? 'Preferred date (optional)' : DateFormat('EEE, d MMM yyyy').format(_date!)),
              subtitle: _errors['preferred_date'] != null ? Text(_errors['preferred_date']!, style: TextStyle(color: Theme.of(context).colorScheme.error)) : null,
              trailing: _date == null ? null : IconButton(tooltip: 'Clear date', onPressed: () => setState(() => _date = null), icon: const Icon(Icons.clear)),
              onTap: () async {
                final now = DateTime.now();
                final d = await showDatePicker(context: context, firstDate: DateTime(now.year, now.month, now.day), lastDate: now.add(const Duration(days: 365)), initialDate: _date ?? now);
                if (d != null) setState(() => _date = d);
              },
            ),
            TextField(controller: _msg, maxLines: 3, decoration: const InputDecoration(labelText: 'Your question (optional)')),
            CheckboxListTile(contentPadding: EdgeInsets.zero, value: _consent, controlAffinity: ListTileControlAffinity.leading, onChanged: (v) => setState(() => _consent = v ?? false),
                title: const Text('I agree to be contacted about this request'), subtitle: _errors['consent'] != null ? Text(_errors['consent']!, style: TextStyle(color: Theme.of(context).colorScheme.error)) : null),
            if (_errors['_'] != null) Text(_errors['_']!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            const SizedBox(height: 12),
            FilledButton(onPressed: _busy ? null : _submit, child: Text(_busy ? 'Sending…' : 'Send request')),
          ]),
        ),
      );
}
