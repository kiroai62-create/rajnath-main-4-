import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api/api_client.dart';
import '../payments/checkout.dart';
import '../state/app_state.dart';
import '../theme/app_theme.dart';
import '../widgets/common.dart';
import 'astro_screens.dart';
import 'content_screens.dart';
import 'routing.dart';
import 'services_screens.dart';
import 'store_screens.dart';

/// Website support page; uses the origin of API_BASE so staging builds point at staging.
Uri get supportUrl => Uri.parse('$siteOrigin/account/support');

class _Msg {
  const _Msg(this.text, {required this.mine, this.actions = const []});
  final String text;
  final bool mine;

  /// Buttons under an assistant reply: pay for an order it started, open a service or a page.
  final List<Map> actions;
}

/// Ready-made questions shown before the first message.
const assistantSuggestions = [
  'Book a video consultation',
  'Online palm reading',
  'Griha pravesh puja price',
  'Aaj ka Rahu Kaal',
  'Which gemstone suits me?',
];

class AssistantScreen extends StatefulWidget {
  const AssistantScreen({super.key, required this.state, this.initialMessage});
  final AppState state;

  /// Sent straight away when the screen opens (from a suggestion elsewhere).
  final String? initialMessage;
  @override
  State<AssistantScreen> createState() => _AssistantScreenState();
}

class _AssistantScreenState extends State<AssistantScreen> {
  final _input = TextEditingController();
  final _scroll = ScrollController();
  final _messages = <_Msg>[
    const _Msg(
      'Namaste! I can find the right service, tell you prices, check free times and book your consultation or puja. Ask in Hindi, Hinglish or English.',
      mine: false,
    ),
  ];
  String? _conversation;
  String? _disclaimer;
  String? _notice;
  bool _handoff = false, _busy = false, _blocked = false;

  @override
  void initState() {
    super.initState();
    if (widget.initialMessage != null) {
      _input.text = widget.initialMessage!;
      WidgetsBinding.instance.addPostFrameCallback((_) => _send());
    }
  }

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _send([String? preset]) async {
    final text = (preset ?? _input.text).trim();
    if (text.isEmpty || _busy || _blocked) return;
    HapticFeedback.selectionClick();
    setState(() {
      _messages.add(_Msg(text, mine: true));
      _input.clear();
      _busy = true;
      _notice = null;
    });
    _toEnd();
    try {
      // The AI may look things up (panchang, prices, web search) before answering, so wait up to 90 s.
      final r = await widget.state.api.post('/assistant/chat', {
        'message': text,
        if (_conversation != null) 'conversation': _conversation,
      }, timeout: const Duration(seconds: 90));
      setState(() {
        _conversation = r['conversation'] as String? ?? _conversation;
        _disclaimer = r['disclaimer'] as String? ?? _disclaimer;
        _handoff = r['handoff'] == true || _handoff;
        // Without a working AI key the server answers from the website and sends a short notice.
        _notice = (r['notice'] as String?)?.trim().isNotEmpty == true ? r['notice'] as String : null;
        _messages.add(
          _Msg(
            '${r['reply'] ?? ''}',
            mine: false,
            actions: ((r['actions'] as List?) ?? const [])
                .whereType<Map>()
                .toList(),
          ),
        );
      });
    } on ApiException catch (e) {
      setState(() {
        if (e.status == 429 && e.code == 'daily_limit') {
          _blocked = true;
          _handoff = true;
          _notice = "You've reached today's limit for the assistant. Please try again tomorrow, or talk to our team.";
        } else if (e.status == 429) {
          // Per-minute limit only: the customer can ask again shortly.
          _notice = 'You are sending messages quickly. Please wait a minute and ask again.';
        } else if (e.status == 503 || e.code == 'assistant_unavailable') {
          _handoff = true;
          _notice = 'Just a moment, please. The assistant is busy right now; try again in a minute or talk to our team.';
        } else {
          _notice = e.message;
        }
      });
    } finally {
      if (mounted) setState(() => _busy = false);
      _toEnd();
    }
  }

  void _toEnd() => WidgetsBinding.instance.addPostFrameCallback((_) {
    if (_scroll.hasClients)
      _scroll.animateTo(
        _scroll.position.maxScrollExtent,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
  });

  Future<void> _openSupport() async {
    if (!await launchUrl(supportUrl, mode: LaunchMode.externalApplication) &&
        mounted)
      showSnack(context, 'Could not open Support.');
  }

  void _push(Widget page) =>
      Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

  /// Opens what an assistant button points to, inside the app where possible.
  Future<void> _action(Map a) async {
    final state = widget.state;
    switch (a['type']) {
      case 'pay':
        try {
          final r = await state.api.post('/orders/${a['order_ref']}/pay', {});
          if (!mounted) return;
          final order = await runCheckout(context, state.api, r);
          if (order != null && mounted)
            await afterPurchase(context, state, order);
        } on ApiException catch (e) {
          if (mounted) showSnack(context, e.message);
        }
      case 'service' || 'details':
        _push(ServiceDetailScreen(state: state, slug: '${a['slug']}'));
      case 'page' when a['page'] == 'store':
        _push(StoreScreen(state: state));
      case 'page' when a['page'] == 'panchang':
        _push(PanchangScreen(state: state));
      case 'page' when a['page'] == 'rashifal':
        _push(HoroscopeScreen(state: state));
      default:
        final url = Uri.tryParse('${a['url'] ?? ''}');
        if (url != null &&
            url.scheme == 'https' &&
            url.host == Uri.parse(siteOrigin).host) {
          await launchUrl(url, mode: LaunchMode.externalApplication);
        }
    }
  }

  Widget _bubble(_Msg m) => Align(
    alignment: m.mine ? Alignment.centerRight : Alignment.centerLeft,
    child: Container(
      constraints: BoxConstraints(
        maxWidth: MediaQuery.sizeOf(context).width * 0.82,
      ),
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: m.mine ? null : ArjColors.gold50,
        gradient: m.mine ? ArjStyle.cosmicGradient : null,
        borderRadius: BorderRadius.only(
          topLeft: const Radius.circular(18),
          topRight: const Radius.circular(18),
          bottomLeft: Radius.circular(m.mine ? 18 : 4),
          bottomRight: Radius.circular(m.mine ? 4 : 18),
        ),
        border: m.mine ? null : Border.all(color: ArjColors.gold200),
        boxShadow: m.mine ? null : ArjStyle.softShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Semantics(
            label: m.mine ? 'You said' : 'Assistant said',
            child: SelectableText(
              m.text,
              style: TextStyle(
                color: m.mine ? Colors.white : ArjColors.ink900,
                height: 1.4,
              ),
            ),
          ),
          if (m.actions.isNotEmpty) ...[
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final a in m.actions)
                  a['type'] == 'pay'
                      ? FilledButton.icon(
                          style: FilledButton.styleFrom(
                            backgroundColor: ArjColors.gold400,
                            foregroundColor: ArjColors.ink900,
                            minimumSize: const Size(0, 42),
                          ),
                          onPressed: () => _action(a),
                          icon: const Icon(Icons.lock_outline, size: 18),
                          label: Text('${a['label']}'),
                        )
                      : OutlinedButton(
                          style: OutlinedButton.styleFrom(
                            minimumSize: const Size(0, 40),
                          ),
                          onPressed: () => _action(a),
                          child: Text('${a['label']}'),
                        ),
              ],
            ),
          ],
        ],
      ),
    ),
  );

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      titleSpacing: 8,
      title: Row(
        children: [
          Image.asset(
            'assets/images/logo-mark.png',
            width: 34,
            height: 34,
            excludeFromSemantics: true,
          ),
          const SizedBox(width: 10),
          const Flexible(child: Text('Jyotish AI assistant')),
        ],
      ),
    ),
    body: SafeArea(
      child: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scroll,
              padding: const EdgeInsets.all(16),
              itemCount:
                  _messages.length +
                  (_busy ? 1 : 0) +
                  (_messages.length == 1 ? 1 : 0),
              itemBuilder: (context, i) {
                if (_messages.length == 1 && i == 1) {
                  return Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final s in assistantSuggestions)
                        ActionChip(
                          avatar: const Icon(
                            Icons.auto_awesome,
                            size: 16,
                            color: ArjColors.gold700,
                          ),
                          label: Text(s),
                          onPressed: _blocked ? null : () => _send(s),
                        ),
                    ],
                  );
                }
                if (i >= _messages.length) {
                  return const Align(
                    alignment: Alignment.centerLeft,
                    child: Padding(
                      padding: EdgeInsets.all(8),
                      child: SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          semanticsLabel: 'Assistant is typing',
                        ),
                      ),
                    ),
                  );
                }
                return _bubble(_messages[i]);
              },
            ),
          ),
          if (_notice != null)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: ArjColors.gold50,
                borderRadius: BorderRadius.circular(ArjStyle.radiusControl),
                border: Border.all(color: ArjColors.gold200),
              ),
              child: Row(
                children: [
                  const Icon(Icons.hourglass_top_rounded, size: 18, color: ArjColors.gold700),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _notice!,
                      style: const TextStyle(color: ArjColors.gold700, fontSize: 13, height: 1.35),
                    ),
                  ),
                ],
              ),
            ),
          if (_handoff)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: OutlinedButton.icon(
                onPressed: _openSupport,
                icon: const Icon(Icons.support_agent),
                label: const Text('Talk to our team'),
              ),
            ),
          Container(
            margin: const EdgeInsets.fromLTRB(12, 8, 12, 4),
            padding: const EdgeInsets.only(left: 6),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(28),
              border: Border.all(color: ArjColors.ink200),
              boxShadow: ArjStyle.softShadow,
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _input,
                    enabled: !_blocked,
                    minLines: 1,
                    maxLines: 4,
                    maxLength: 1000,
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) => _send(),
                    decoration: const InputDecoration(
                      hintText: 'Type your question',
                      counterText: '',
                      filled: false,
                      border: InputBorder.none,
                      enabledBorder: InputBorder.none,
                      focusedBorder: InputBorder.none,
                    ),
                  ),
                ),
                IconButton.filled(
                  tooltip: 'Send',
                  style: IconButton.styleFrom(
                    backgroundColor: ArjColors.ink900,
                    foregroundColor: Colors.white,
                  ),
                  onPressed: _busy || _blocked ? null : _send,
                  icon: const Icon(Icons.arrow_upward_rounded),
                ),
                const SizedBox(width: 4),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: Text(
              _disclaimer ?? 'Automated answers can be wrong. For personal guidance, book a consultation.',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 11, color: ArjColors.ink600),
            ),
          ),
        ],
      ),
    ),
  );
}
