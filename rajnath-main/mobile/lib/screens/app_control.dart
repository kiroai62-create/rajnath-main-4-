import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api/api_client.dart';
import '../state/app_state.dart';
import '../theme/app_theme.dart';
import '../widgets/common.dart';
import 'activity_screens.dart';
import 'assistant_screen.dart';
import 'astro_screens.dart';
import 'content_screens.dart';
import 'services_screens.dart';
import 'store_screens.dart';

/// Screen for an `app:` link from Admin › Mobile app (banner buttons), or null if unknown.
/// Examples: app:store, app:puja, app:chadhava, app:kundli, app:panchang, app:rashifal,
/// app:assistant, app:service/<slug>, app:product/<slug>.
Widget? screenForAppLink(AppState state, String link) {
  final path = link.replaceFirst('app:', '');
  final slash = path.indexOf('/');
  final kind = slash < 0 ? path : path.substring(0, slash);
  final ref = slash < 0 ? '' : path.substring(slash + 1);
  Widget family(String title, String f) => Scaffold(appBar: AppBar(title: Text(title)), body: SafeArea(child: ServicesScreen(state: state, initialFamily: f)));
  return switch (kind) {
    'store' => StoreScreen(state: state),
    'puja' => family('Online puja', 'puja'),
    'chadhava' => family('Chadhava', 'chadhava'),
    'services' => Scaffold(appBar: AppBar(title: const Text('Our services')), body: SafeArea(child: ServicesScreen(state: state))),
    'kundli' => KundliScreen(state: state),
    'matching' => KundliScreen(state: state, matching: true),
    'panchang' => PanchangScreen(state: state),
    'rashifal' => HoroscopeScreen(state: state),
    'assistant' => AssistantScreen(state: state),
    'service' when ref.isNotEmpty => ServiceDetailScreen(state: state, slug: ref),
    'product' when ref.isNotEmpty => ProductDetailScreen(state: state, slug: ref),
    _ => null,
  };
}

/// Opens a banner link: `app:` screens inside the app, website or https links in the browser.
Future<void> openAppLink(BuildContext context, AppState state, String? link) async {
  final l = (link ?? '').trim();
  if (l.isEmpty) return;
  if (l.startsWith('app:')) {
    final s = screenForAppLink(state, l);
    if (s != null) {
      await Navigator.of(context).push(MaterialPageRoute(builder: (_) => s));
      return;
    }
  }
  await openExternal(context, l.startsWith('/') ? '$siteOrigin$l' : l);
}

/// Announcement banner from Admin › Mobile app, styled like the website's announcement bar.
class AnnouncementBanner extends StatelessWidget {
  const AnnouncementBanner({super.key, required this.state, required this.data});
  final AppState state;
  final Map data;

  @override
  Widget build(BuildContext context) {
    final style = '${data['style'] ?? 'gold'}';
    final dark = style != 'gold';
    final gradient = switch (style) {
      'cosmic' => ArjStyle.cosmicGradient,
      'maroon' => const LinearGradient(colors: [ArjColors.maroon700, ArjColors.maroon600, ArjColors.saffron500]),
      _ => ArjStyle.goldGradient,
    };
    final fg = dark ? Colors.white : ArjColors.ink950;
    final cta = '${data['cta'] ?? ''}'.trim();
    final link = '${data['link'] ?? ''}'.trim();
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(gradient: gradient, borderRadius: BorderRadius.circular(ArjStyle.radiusCard), boxShadow: ArjStyle.softShadow),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(ArjStyle.radiusCard),
          onTap: link.isEmpty ? null : () => openAppLink(context, state, link),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 12, 12, 12),
            child: Row(children: [
              Icon(Icons.campaign_outlined, color: dark ? ArjColors.gold300 : ArjColors.ink900),
              const SizedBox(width: 10),
              Expanded(child: Text('${data['text']}', style: TextStyle(color: fg, fontWeight: FontWeight.w600, height: 1.3))),
              if (link.isNotEmpty) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                  decoration: BoxDecoration(color: dark ? ArjColors.gold300 : ArjColors.ink900, borderRadius: BorderRadius.circular(40)),
                  child: Text(cta.isEmpty ? 'Open' : cta, style: TextStyle(color: dark ? ArjColors.ink950 : Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
                ),
              ],
            ]),
          ),
        ),
      ),
    );
  }
}

/// "Update available" note (dismissible for this session).
class UpdateNote extends StatelessWidget {
  const UpdateNote({super.key, required this.state, required this.onClose});
  final AppState state;
  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(bottom: 14),
        padding: const EdgeInsets.fromLTRB(14, 10, 4, 10),
        decoration: BoxDecoration(color: ArjColors.gold50, borderRadius: BorderRadius.circular(ArjStyle.radiusCard), border: Border.all(color: ArjColors.gold200)),
        child: Row(children: [
          const Icon(Icons.system_update_outlined, color: ArjColors.gold700),
          const SizedBox(width: 10),
          Expanded(child: Text('${state.updateInfo['message'] ?? 'A new version of the app is available.'}', style: const TextStyle(fontSize: 13.5))),
          TextButton(onPressed: () => openExternal(context, '${state.updateInfo['url'] ?? ''}'), child: const Text('Update')),
          IconButton(tooltip: 'Close', onPressed: onClose, icon: const Icon(Icons.close, size: 18)),
        ]),
      );
}

/// Full-screen notice for maintenance or a required update, on the website's night-sky background.
class AppGateScreen extends StatelessWidget {
  const AppGateScreen.maintenance({super.key, required this.state}) : update = false;
  const AppGateScreen.update({super.key, required this.state}) : update = true;
  final AppState state;
  final bool update;

  @override
  Widget build(BuildContext context) {
    final phone = '${state.support['phone'] ?? ''}'.replaceAll(' ', '');
    final wa = '${state.support['whatsapp'] ?? ''}'.replaceAll(RegExp(r'[^0-9]'), '');
    return Scaffold(
      body: CosmicBackground(
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Image.asset('assets/images/logo-mark-light.png', width: 84, height: 84, excludeFromSemantics: true),
                const SizedBox(height: 20),
                Text(update ? 'Please update the app' : 'We will be back soon',
                    textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineSmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700)),
                const SizedBox(height: 12),
                Text(update ? '${state.updateInfo['message'] ?? 'This version is no longer supported.'}' : state.maintenanceMessage ?? '',
                    textAlign: TextAlign.center, style: TextStyle(color: Colors.white.withValues(alpha: 0.82), height: 1.5)),
                const SizedBox(height: 24),
                if (update)
                  GoldButton(label: 'Update now', icon: Icons.system_update, onPressed: () => openExternal(context, '${state.updateInfo['url'] ?? ''}'))
                else
                  GoldButton(label: 'Try again', icon: Icons.refresh, onPressed: () => state.loadMeta()),
                const SizedBox(height: 12),
                if (wa.isNotEmpty)
                  TextButton.icon(
                      style: TextButton.styleFrom(foregroundColor: ArjColors.gold200),
                      onPressed: () => launchUrl(Uri.parse('https://wa.me/$wa'), mode: LaunchMode.externalApplication),
                      icon: const Icon(Icons.chat_outlined),
                      label: const Text('WhatsApp us')),
                if (phone.isNotEmpty)
                  TextButton.icon(
                      style: TextButton.styleFrom(foregroundColor: ArjColors.gold200),
                      onPressed: () => launchUrl(Uri.parse('tel:$phone')),
                      icon: const Icon(Icons.call_outlined),
                      label: Text('Call $phone')),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
