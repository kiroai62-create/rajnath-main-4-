import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api/api_client.dart';
import '../models/slots.dart';
import '../payments/checkout.dart';
import '../state/app_state.dart';
import '../theme/app_theme.dart';
import '../util/validators.dart';
import '../widgets/common.dart';
import 'routing.dart';
import 'sign_in_screen.dart';

/// Returns true when signed in, prompting the sign-in screen first if needed.
Future<bool> ensureSignedIn(BuildContext context, AppState state, {String? reason}) async {
  if (state.signedIn) return true;
  final go = await showDialog<bool>(
    context: context,
    builder: (c) => AlertDialog(
      title: const Text('Please sign in'),
      content: Text(reason ?? 'Sign in to continue. It only takes a one-time code.'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Not now')),
        FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Sign in')),
      ],
    ),
  );
  if (go != true || !context.mounted) return false;
  await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => SignInScreen(state: state)));
  return state.signedIn;
}

/// "Use wallet balance" switch that shows the current balance.
class WalletSwitch extends StatefulWidget {
  const WalletSwitch({super.key, required this.api, required this.value, required this.onChanged});
  final ApiClient api;
  final bool value;
  final ValueChanged<bool> onChanged;
  @override
  State<WalletSwitch> createState() => _WalletSwitchState();
}

class _WalletSwitchState extends State<WalletSwitch> {
  int? _balance;

  @override
  void initState() {
    super.initState();
    widget.api.get('/wallet').then((r) {
      if (mounted) setState(() => _balance = (r['balance_minor'] as num?)?.toInt());
    }).catchError((_) {});
  }

  @override
  Widget build(BuildContext context) => SwitchListTile(
        contentPadding: EdgeInsets.zero,
        title: const Text('Use wallet balance'),
        subtitle: Text(_balance == null ? 'Applied first; the rest is paid online.' : 'Available: ${formatPaise(_balance)}'),
        value: widget.value,
        onChanged: _balance == 0 ? null : widget.onChanged,
      );
}

/// Optional coupon code with Apply. Checks it via POST /coupons/check with [target]
/// (`{'variant': ref}` or `{'product': ref, 'quantity': n}`) and reports the quote
/// (`{code, description, list_minor, discount_minor, total_minor}`) or null when removed / invalid.
class CouponField extends StatefulWidget {
  const CouponField({super.key, required this.api, required this.target, required this.onChanged});
  final ApiClient api;
  final Map<String, dynamic> target;
  final ValueChanged<Map<String, dynamic>?> onChanged;
  @override
  State<CouponField> createState() => _CouponFieldState();
}

class _CouponFieldState extends State<CouponField> {
  final _code = TextEditingController();
  Map<String, dynamic>? _quote;
  String? _error;
  bool _busy = false;

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _apply() async {
    final code = _code.text.trim();
    if (code.isEmpty) return setState(() => _error = 'Enter a coupon code.');
    setState(() { _busy = true; _error = null; });
    try {
      final q = unwrap(await widget.api.post('/coupons/check', {'code': code, ...widget.target}));
      if (!mounted) return;
      setState(() => _quote = q);
      widget.onChanged(q);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() { _quote = null; _error = e.fieldErrors['coupon'] ?? e.fieldErrors['code'] ?? e.message; });
      widget.onChanged(null);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _remove() {
    setState(() { _quote = null; _error = null; _code.clear(); });
    widget.onChanged(null);
  }

  @override
  Widget build(BuildContext context) {
    final q = _quote;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Expanded(
          child: TextField(
            controller: _code,
            enabled: q == null && !_busy,
            textCapitalization: TextCapitalization.characters,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _apply(),
            decoration: InputDecoration(labelText: 'Coupon code', helperText: q == null ? 'Optional' : null, errorText: _error, errorMaxLines: 2),
          ),
        ),
        const SizedBox(width: 8),
        Padding(
          padding: const EdgeInsets.only(top: 8),
          child: q == null
              ? OutlinedButton(onPressed: _busy ? null : _apply, child: Text(_busy ? 'Checking…' : 'Apply'))
              : TextButton(onPressed: _remove, child: const Text('Remove')),
        ),
      ]),
      if (q != null) ...[
        const SizedBox(height: 8),
        Text('${q['code']} applied: you save ${formatPaise((q['discount_minor'] as num?)?.toInt() ?? 0)}',
            style: const TextStyle(color: ArjColors.success700, fontWeight: FontWeight.w600)),
        if (q['description'] != null) Text('${q['description']}', style: const TextStyle(fontSize: 12, color: ArjColors.ink600)),
        Text('New total ${formatPaise((q['total_minor'] as num?)?.toInt())}'),
      ],
    ]);
  }
}

/// Confirm-and-pay screen for priced, non-slot variants (reports, puja, chadhava, palmistry).
class ServiceBuyScreen extends StatefulWidget {
  const ServiceBuyScreen({super.key, required this.state, required this.title, required this.variant});
  final AppState state;
  final String title;
  final Map variant;
  @override
  State<ServiceBuyScreen> createState() => _ServiceBuyScreenState();
}

class _ServiceBuyScreenState extends State<ServiceBuyScreen> {
  final _attempt = PurchaseAttempt();
  final _details = GlobalKey<ServiceDetailsFormState>();
  String? get _kind => widget.variant['details'] as String?;
  bool _useWallet = true, _busy = false;
  String? _error;
  Map<String, dynamic>? _coupon;

  Future<void> _buy() async {
    Map<String, dynamic>? fields;
    if (_kind != null) {
      fields = await _details.currentState?.collect();
      if (fields == null) return setState(() => _error = 'Please complete the details above.');
    }
    setState(() { _busy = true; _error = null; });
    try {
      final ref = '${widget.variant['ref']}';
      final coupon = _coupon?['code'] as String?;
      final base = {
        'variant': ref,
        'use_wallet': _useWallet,
        'coupon': ?coupon,
        'key': _attempt.keyFor('$ref|$_useWallet|${coupon ?? ''}|${fields == null ? '' : fields.length}'),
      };
      final r = fields == null
          ? await widget.state.api.post('/checkout/service', base)
          : await widget.state.api.postMultipart('/checkout/service', FormData.fromMap({...base, 'use_wallet': _useWallet ? '1' : '0', ...fields}));
      if (!mounted) return;
      final order = await runCheckout(context, widget.state.api, r);
      if (!mounted) return;
      if (!isPendingPayment(order)) _attempt.reset();
      if (isPaidLike(order)) await afterPurchase(context, widget.state, order!, replace: true);
    } on ApiException catch (e) {
      if (e.fieldErrors.isNotEmpty) _details.currentState?.showErrors(e.fieldErrors);
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final v = widget.variant;
    return Scaffold(
      appBar: AppBar(title: Text(_kind == null ? 'Confirm and pay' : 'Details and payment')),
      body: SafeArea(
        child: ListView(padding: const EdgeInsets.all(20), children: [
          Text(widget.title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text('${v['name']}', style: const TextStyle(color: ArjColors.ink600)),
          if (_kind != null) ...[
            const SizedBox(height: 16),
            Card(margin: EdgeInsets.zero, child: Padding(padding: const EdgeInsets.all(16), child: ServiceDetailsForm(key: _details, state: widget.state, kind: _kind!, variant: v))),
          ],
          const SizedBox(height: 16),
          // One child, so the coupon field below keeps its state when the total row appears.
          Column(children: [
            _PriceRow(label: 'Price', minor: (v['price_minor'] as num?)?.toInt()),
            if (_coupon != null) _PriceRow(label: 'To pay after coupon', minor: (_coupon!['total_minor'] as num?)?.toInt()),
          ]),
          const SizedBox(height: 16),
          CouponField(key: const ValueKey('coupon'), api: widget.state.api, target: {'variant': '${v['ref']}'}, onChanged: (q) => setState(() => _coupon = q)),
          const Divider(height: 32),
          WalletSwitch(api: widget.state.api, value: _useWallet, onChanged: (x) => setState(() => _useWallet = x)),
          if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
          const SizedBox(height: 20),
          FilledButton(onPressed: _busy ? null : _buy, child: Text(_busy ? 'Please wait…' : 'Pay securely')),
          const SizedBox(height: 8),
          Text(_kind == null ? 'Payments are processed by Razorpay.' : 'Payment is the last step. Once paid, there is nothing more to do — we update you at every step.',
              textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, color: ArjColors.ink600)),
        ]),
      ),
    );
  }
}

class _PriceRow extends StatelessWidget {
  const _PriceRow({required this.label, required this.minor});
  final String label;
  final int? minor;
  @override
  Widget build(BuildContext context) => Row(children: [
        Expanded(child: Text(label)),
        Text(formatPaise(minor), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 18)),
      ]);
}

/// Pick a team astrologer for a team-consultation offering. Pops with the practitioner map.
class PractitionerPickerScreen extends StatelessWidget {
  const PractitionerPickerScreen({super.key, required this.state, required this.offeringSlug, required this.title});
  final AppState state;
  final String offeringSlug;
  final String title;

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Choose an astrologer')),
        body: SafeArea(
          child: AsyncView<List<Map<String, dynamic>>>(
            load: () async => unwrapList(await state.api.get('/practitioners', query: {'offering': offeringSlug})),
            isEmpty: (l) => l.isEmpty,
            emptyText: 'No astrologers are available for $title right now.\nPlease check back soon, or send us a request and our team will arrange it.',
            builder: (context, list, _) => ListView(padding: const EdgeInsets.all(16), children: [
              for (final p in list)
                Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  clipBehavior: Clip.antiAlias,
                  child: InkWell(
                    onTap: () => Navigator.of(context).pop(p),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        CircleAvatar(
                          radius: 28,
                          backgroundColor: ArjColors.ivory200,
                          foregroundImage: p['photo_url'] != null ? NetworkImage('${p['photo_url']}') : null,
                          child: Text(initialOf(p['display_name']), semanticsLabel: ''),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text('${p['display_name']}', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
                            Text([
                              if (p['experience_years'] != null) '${p['experience_years']} yrs experience',
                              if ((p['languages'] as List?)?.isNotEmpty ?? false) (p['languages'] as List).join(', '),
                            ].join(' · '), style: const TextStyle(color: ArjColors.ink600)),
                            if ((p['specialties'] as List?)?.isNotEmpty ?? false) ...[
                              const SizedBox(height: 6),
                              Wrap(spacing: 6, runSpacing: 6, children: [for (final s in p['specialties'] as List) Chip(label: Text('$s'), visualDensity: VisualDensity.compact)]),
                            ],
                            if (p['bio'] != null) ...[const SizedBox(height: 6), Text('${p['bio']}', maxLines: 3, overflow: TextOverflow.ellipsis)],
                          ]),
                        ),
                        const Icon(Icons.chevron_right, semanticLabel: 'Choose'),
                      ]),
                    ),
                  ),
                ),
            ]),
          ),
        ),
      );
}

/// Slot picker. Books a new consultation, or reschedules [rescheduleRef] when given.
class SlotBookingScreen extends StatefulWidget {
  const SlotBookingScreen({super.key, required this.state, required this.variantRef, required this.title, this.variantName, this.priceMinor, this.practitioner, this.rescheduleRef});
  final AppState state;
  final String variantRef;
  final String title;
  final String? variantName;
  final int? priceMinor;
  final Map? practitioner;
  final String? rescheduleRef;
  bool get rescheduling => rescheduleRef != null;
  @override
  State<SlotBookingScreen> createState() => _SlotBookingScreenState();
}

class _SlotBookingScreenState extends State<SlotBookingScreen> {
  final _attempt = PurchaseAttempt();
  final _topic = TextEditingController();
  Slot? _slot;
  String? _profile;
  bool _useWallet = true, _busy = false;
  String? _error;
  Map<String, dynamic>? _coupon;

  @override
  void dispose() {
    _topic.dispose();
    super.dispose();
  }

  Future<(List<SlotDay>, List<Map<String, dynamic>>)> _load() async {
    final api = widget.state.api;
    final slots = parseSlots(await api.get('/slots', query: {'variant': widget.variantRef, if (widget.practitioner?['ref'] != null) 'practitioner': widget.practitioner!['ref']}));
    var profiles = <Map<String, dynamic>>[];
    if (!widget.rescheduling) {
      try {
        profiles = unwrapList(await api.get('/birth-profiles'));
      } on ApiException catch (_) {/* optional */}
    }
    return (slots, profiles);
  }

  Future<void> _submit() async {
    final slot = _slot;
    if (slot == null) return setState(() => _error = 'Please choose a time.');
    setState(() { _busy = true; _error = null; });
    final api = widget.state.api;
    try {
      if (widget.rescheduling) {
        await api.post('/bookings/${widget.rescheduleRef}/reschedule', {'start': slot.start});
        if (!mounted) return;
        showSnack(context, 'Rescheduled to ${slot.label}.');
        Navigator.of(context).pop(true);
        return;
      }
      final practitioner = widget.practitioner?['ref'];
      final topic = _topic.text.trim();
      final coupon = _coupon?['code'] as String?;
      final r = await api.post('/checkout/booking', {
        'variant': widget.variantRef,
        'start': slot.start,
        'practitioner': ?practitioner,
        'birth_profile': ?_profile,
        if (topic.isNotEmpty) 'topic': topic,
        'use_wallet': _useWallet,
        'coupon': ?coupon,
        'key': _attempt.keyFor('${widget.variantRef}|${slot.start}|$practitioner|$_useWallet|${coupon ?? ''}'),
      });
      if (!mounted) return;
      final order = await runCheckout(context, api, r);
      if (!mounted) return;
      if (!isPendingPayment(order)) _attempt.reset();
      if (isPaidLike(order)) {
        await afterPurchase(context, widget.state, {...order!, 'next': order['next'] ?? {'type': 'booking', 'ref': (r['booking'] as Map?)?['ref']}}, replace: true);
      }
    } on ApiException catch (e) {
      setState(() => _error = e.fieldErrors['start'] ?? e.fieldErrors['coupon'] ?? e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.rescheduling ? 'Choose a new time' : 'Choose a time')),
        body: SafeArea(
          child: AsyncView<(List<SlotDay>, List<Map<String, dynamic>>)>(
            load: _load,
            isEmpty: (d) => d.$1.isEmpty,
            emptyText: 'No times are open in the next few days. Please check again later.',
            builder: (context, d, _) => ListView(padding: const EdgeInsets.all(16), children: [
              Text(widget.title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600)),
              Text([
                if (widget.variantName != null) widget.variantName!,
                if (widget.practitioner != null) 'with ${widget.practitioner!['display_name']}',
                if (widget.priceMinor != null) formatPaise(widget.priceMinor),
              ].join(' · '), style: const TextStyle(color: ArjColors.ink600)),
              const SizedBox(height: 4),
              const Text('Times are shown in Indian Standard Time (IST).', style: TextStyle(fontSize: 12, color: ArjColors.ink600)),
              for (final day in d.$1) ...[
                SectionTitle(day.title),
                Wrap(spacing: 8, runSpacing: 8, children: [
                  for (final s in day.slots)
                    ChoiceChip(
                      label: Text(s.label),
                      tooltip: '${day.title}, ${s.label}',
                      selected: _slot?.start == s.start,
                      onSelected: (_) => setState(() => _slot = s),
                    ),
                ]),
              ],
              if (!widget.rescheduling) ...[
                const SectionTitle('About you (optional)'),
                if (d.$2.isNotEmpty)
                  DropdownButtonFormField<String?>(
                    initialValue: _profile,
                    decoration: const InputDecoration(labelText: 'Birth profile'),
                    items: [
                      const DropdownMenuItem<String?>(value: null, child: Text('None')),
                      for (final p in d.$2) DropdownMenuItem<String?>(value: '${p['ref']}', child: Text('${p['name']}')),
                    ],
                    onChanged: (v) => setState(() => _profile = v),
                  ),
                const SizedBox(height: 12),
                TextField(controller: _topic, maxLines: 2, maxLength: 300, decoration: const InputDecoration(labelText: 'What would you like to discuss?')),
                CouponField(key: const ValueKey('coupon'), api: widget.state.api, target: {'variant': widget.variantRef}, onChanged: (q) => setState(() => _coupon = q)),
                const SizedBox(height: 8),
                WalletSwitch(api: widget.state.api, value: _useWallet, onChanged: (x) => setState(() => _useWallet = x)),
              ],
              if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _busy || _slot == null ? null : _submit,
                child: Text(_busy ? 'Please wait…' : widget.rescheduling ? 'Confirm new time' : _slot == null ? 'Choose a time' : 'Book ${_slot!.label}'),
              ),
            ]),
          ),
        ),
      );
}

String initialOf(Object? name) {
  final s = '${name ?? ''}'.trim();
  return s.isEmpty ? '?' : s.characters.first.toUpperCase();
}

/// Details collected before paying (palm photos, sankalp, birth details, visit address).
/// [kind] comes from the API (`variant.details`): palm, puja, report, question or visit.
class ServiceDetailsForm extends StatefulWidget {
  const ServiceDetailsForm({super.key, required this.state, required this.kind, required this.variant, this.picker});
  final AppState state;
  final String kind;
  final Map variant;
  final ImagePicker? picker;
  @override
  State<ServiceDetailsForm> createState() => ServiceDetailsFormState();
}

class ServiceDetailsFormState extends State<ServiceDetailsForm> {
  late final _picker = widget.picker ?? ImagePicker();
  XFile? _left, _right;
  String _hand = 'right', _language = 'en', _location = 'online';
  bool _consent = false;
  final _question = TextEditingController();
  final _gotra = TextEditingController();
  final _wish = TextEditingController();
  final _address = TextEditingController();
  final _city = TextEditingController();
  final _notes = TextEditingController();
  late final _phone = TextEditingController(text: '${widget.state.user?['phone'] ?? widget.state.user?['phone_e164'] ?? ''}'.replaceAll('null', ''));
  late final List<TextEditingController> _names = List.generate(
      ((widget.variant['participants'] as num?)?.toInt() ?? 1).clamp(1, 8), (i) => TextEditingController(text: i == 0 ? '${widget.state.user?['name'] ?? ''}' : ''));
  DateTime? _date;
  List<Map> _profiles = const [];
  String? _profileA, _profileB;
  Map<String, String> errors = {};

  bool get _two => widget.variant['needs_two'] == true;

  @override
  void initState() {
    super.initState();
    _location = '${widget.variant['name']}'.toLowerCase().contains('home') ? 'home' : 'online';
    if (widget.kind == 'report' || widget.kind == 'question') _loadProfiles();
  }

  Future<void> _loadProfiles() async {
    try {
      final list = unwrapList(await widget.state.api.get('/birth-profiles'));
      if (mounted) setState(() => _profiles = list);
    } catch (_) {/* shown as "add birth details" */}
  }

  @override
  void dispose() {
    for (final c in [_question, _gotra, _wish, _address, _city, _notes, _phone, ..._names]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _pick(bool left) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      builder: (c) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          ListTile(leading: const Icon(Icons.photo_camera_outlined), title: const Text('Take a photo'), onTap: () => Navigator.pop(c, ImageSource.camera)),
          ListTile(leading: const Icon(Icons.photo_library_outlined), title: const Text('Choose from gallery'), onTap: () => Navigator.pop(c, ImageSource.gallery)),
        ]),
      ),
    );
    if (source == null) return;
    try {
      final f = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 2000);
      if (f != null && mounted) setState(() => left ? _left = f : _right = f);
    } catch (_) {
      if (mounted) showSnack(context, 'Could not open the ${source == ImageSource.camera ? 'camera' : 'gallery'}.');
    }
  }

  String _ymd(DateTime d) => '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  /// Validates the fields; returns the form fields to send, or null (errors are shown).
  Future<Map<String, dynamic>?> collect() async {
    final e = <String, String>{};
    final out = <String, dynamic>{'with_details': '1'};
    final phone = _phone.text.trim();
    switch (widget.kind) {
      case 'palm':
        if (_left == null) e['left'] = 'Add a photo of your left palm.';
        if (_right == null) e['right'] = 'Add a photo of your right palm.';
        if (!_consent) e['consent'] = 'Please give consent so we can read your palms.';
        if (e.isEmpty) {
          out['left'] = await MultipartFile.fromFile(_left!.path, filename: 'left.jpg');
          out['right'] = await MultipartFile.fromFile(_right!.path, filename: 'right.jpg');
          out.addAll({'dominant_hand': _hand, 'language': _language, 'consent': '1', if (_question.text.trim().isNotEmpty) 'question': _question.text.trim()});
        }
      case 'puja':
        final names = [for (final c in _names) if (c.text.trim().isNotEmpty) c.text.trim()];
        if (names.isEmpty) e['devotees'] = 'Enter at least one name for the sankalp.';
        if (!isValidIndianMobile(phone) && !phone.startsWith('+')) e['phone'] = 'Enter a valid mobile number.';
        if (_location == 'home' && _address.text.trim().isEmpty) e['address'] = 'Enter the address for a puja at home.';
        for (final (i, n) in names.indexed) {
          out['devotees[$i][name]'] = n;
        }
        out.addAll({'location': _location, 'phone': phone, if (_gotra.text.trim().isNotEmpty) 'gotra': _gotra.text.trim(), if (_wish.text.trim().isNotEmpty) 'wish': _wish.text.trim(),
          if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(), if (_date != null) 'preferred_date': _ymd(_date!)});
      case 'report':
      case 'question':
        if (_profileA == null) e['profile_a'] = 'Choose the birth details.';
        if (_two && (_profileB == null || _profileB == _profileA)) e['profile_b'] = 'Choose the second person.';
        if (widget.kind == 'question' && _question.text.trim().length < 5) e['question'] = 'Write your question.';
        out.addAll({'profile_a': _profileA, if (_two) 'profile_b': _profileB, 'language': _language, if (_question.text.trim().isNotEmpty) 'question': _question.text.trim()});
      case 'visit':
        final onsite = widget.variant['mode'] == 'onsite';
        if (!isValidIndianMobile(phone) && !phone.startsWith('+')) e['phone'] = 'Enter a valid mobile number.';
        if (onsite && _address.text.trim().isEmpty) e['address'] = 'Enter the property address.';
        if (onsite && _city.text.trim().isEmpty) e['city'] = 'Enter the city or town.';
        out.addAll({'phone': phone, if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(), if (_city.text.trim().isNotEmpty) 'city': _city.text.trim(),
          if (_notes.text.trim().isNotEmpty) 'notes': _notes.text.trim(), if (_date != null) 'preferred_date': _ymd(_date!)});
    }
    setState(() => errors = e);
    return e.isEmpty ? out : null;
  }

  void showErrors(Map<String, String> e) => setState(() => errors = e);

  Widget _err(String k) => errors[k] == null ? const SizedBox.shrink() : Padding(padding: const EdgeInsets.only(top: 4), child: FormError(errors[k]));

  Widget _photo(String label, XFile? f, bool left) => Expanded(
        child: Semantics(
          button: true,
          label: f == null ? 'Add $label photo' : 'Change $label photo',
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () => _pick(left),
            child: Container(
              height: 140,
              decoration: BoxDecoration(color: ArjColors.ivory100, borderRadius: BorderRadius.circular(16), border: Border.all(color: errors[left ? 'left' : 'right'] != null ? ArjColors.danger700 : ArjColors.ink200)),
              clipBehavior: Clip.antiAlias,
              child: f != null
                  ? Image.file(File(f.path), fit: BoxFit.cover)
                  : Column(mainAxisAlignment: MainAxisAlignment.center, children: [const Icon(Icons.back_hand_outlined, color: ArjColors.gold700, size: 32), const SizedBox(height: 6), Text(label)]),
            ),
          ),
        ),
      );

  Widget _dateField() => OutlinedButton.icon(
        onPressed: () async {
          final now = DateTime.now();
          final d = await showDatePicker(context: context, firstDate: now, lastDate: now.add(const Duration(days: 365)), initialDate: _date ?? now.add(const Duration(days: 3)));
          if (d != null) setState(() => _date = d);
        },
        icon: const Icon(Icons.event_outlined),
        label: Text(_date == null ? 'Preferred date (optional)' : 'Preferred date: ${_ymd(_date!)}'),
      );

  @override
  Widget build(BuildContext context) {
    const gap = SizedBox(height: 12);
    final lang = DropdownButtonFormField<String>(
      initialValue: _language,
      decoration: const InputDecoration(labelText: 'Report language'),
      items: const [DropdownMenuItem(value: 'en', child: Text('English')), DropdownMenuItem(value: 'hi', child: Text('हिन्दी'))],
      onChanged: (v) => setState(() => _language = v ?? 'en'),
    );
    final children = <Widget>[];
    switch (widget.kind) {
      case 'palm':
        children.addAll([
          const Text('Upload photos of both palms', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          const SizedBox(height: 4),
          const Text('Daylight, palm open and flat, phone straight above the hand.', style: TextStyle(color: ArjColors.ink600, fontSize: 13)),
          gap,
          Row(children: [_photo('Left palm', _left, true), const SizedBox(width: 12), _photo('Right palm', _right, false)]),
          _err('left'), _err('right'),
          gap,
          SegmentedButton<String>(
            segments: const [ButtonSegment(value: 'left', label: Text('Left-handed')), ButtonSegment(value: 'right', label: Text('Right-handed'))],
            selected: {_hand},
            onSelectionChanged: (s) => setState(() => _hand = s.first),
          ),
          gap, lang, gap,
          TextField(controller: _question, maxLength: 1000, maxLines: 2, decoration: const InputDecoration(labelText: 'Your main question (optional)')),
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            value: _consent,
            onChanged: (v) => setState(() => _consent = v ?? false),
            title: const Text('My palm photos may be used only to prepare this report; they are deleted automatically afterwards.', style: TextStyle(fontSize: 13)),
          ),
          _err('consent'),
        ]);
      case 'puja':
        children.addAll([
          const Text('Sankalp details', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          gap,
          for (final (i, c) in _names.indexed) Padding(padding: const EdgeInsets.only(bottom: 8), child: TextField(controller: c, textCapitalization: TextCapitalization.words, decoration: InputDecoration(labelText: i == 0 ? 'Name for the sankalp' : 'Name ${i + 1} (optional)'))),
          _err('devotees'),
          TextField(controller: _gotra, decoration: const InputDecoration(labelText: 'Gotra (optional)')),
          gap,
          TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: InputDecoration(labelText: 'Mobile for coordination', errorText: errors['phone'])),
          gap,
          DropdownButtonFormField<String>(
            initialValue: _location,
            decoration: const InputDecoration(labelText: 'Where'),
            items: const [
              DropdownMenuItem(value: 'online', child: Text('Performed on my behalf')),
              DropdownMenuItem(value: 'temple', child: Text('At a temple, I will attend')),
              DropdownMenuItem(value: 'home', child: Text('At my home')),
            ],
            onChanged: (v) => setState(() => _location = v ?? 'online'),
          ),
          gap,
          TextField(controller: _address, maxLines: 2, decoration: InputDecoration(labelText: 'Address (for puja at home)', errorText: errors['address'])),
          gap,
          _dateField(),
          gap,
          TextField(controller: _wish, maxLength: 500, maxLines: 2, decoration: const InputDecoration(labelText: 'Your wish / purpose (optional)')),
        ]);
      case 'report':
      case 'question':
        final items = [for (final p in _profiles) DropdownMenuItem(value: '${p['ref']}', child: Text('${p['name']} · ${p['birth_date'] ?? ''}', overflow: TextOverflow.ellipsis))];
        children.addAll([
          const Text('Birth details', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          gap,
          if (_profiles.isEmpty)
            const Text('Add your birth details first: Account › Birth profiles.', style: TextStyle(color: ArjColors.ink600))
          else ...[
            DropdownButtonFormField<String>(initialValue: _profileA, isExpanded: true, decoration: InputDecoration(labelText: _two ? 'First person' : 'Birth profile', errorText: errors['profile_a']), items: items, onChanged: (v) => setState(() => _profileA = v)),
            if (_two) ...[
              gap,
              DropdownButtonFormField<String>(initialValue: _profileB, isExpanded: true, decoration: InputDecoration(labelText: 'Second person', errorText: errors['profile_b']), items: items, onChanged: (v) => setState(() => _profileB = v)),
            ],
          ],
          gap, lang, gap,
          TextField(controller: _question, maxLines: 3, maxLength: 2000, decoration: InputDecoration(labelText: widget.kind == 'question' ? 'Your question' : 'Anything to focus on (optional)', errorText: errors['question'])),
        ]);
      case 'visit':
        final onsite = widget.variant['mode'] == 'onsite';
        children.addAll([
          Text(onsite ? 'Visit details' : 'Your details', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          gap,
          TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: InputDecoration(labelText: 'Mobile number', errorText: errors['phone'])),
          if (onsite) ...[
            gap,
            TextField(controller: _city, decoration: InputDecoration(labelText: 'City / town', errorText: errors['city'])),
            gap,
            TextField(controller: _address, maxLines: 2, decoration: InputDecoration(labelText: 'Property address', errorText: errors['address'])),
          ],
          gap,
          _dateField(),
          gap,
          TextField(controller: _notes, maxLines: 3, maxLength: 1000, decoration: const InputDecoration(labelText: 'What should we know? (optional)')),
        ]);
    }
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: children);
  }
}
