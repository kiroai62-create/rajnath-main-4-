import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Building blocks copied from the website's design (backend/resources/css/app.css):
/// .bg-cosmic + .stars, .eyebrow, .btn-gold, .text-gold-grad, the zodiac wheel and the
/// page hero used at the top of every website page.

/// Continuous decorative motion (the turning zodiac wheel). Switched on in main(); left off in
/// widget tests so pumpAndSettle can finish.
abstract final class ArjMotion {
  static bool enabled = false;
}

/// The website's `.bg-cosmic`: night-sky gradient with gold, maroon and indigo glows, plus stars.
class CosmicBackground extends StatelessWidget {
  const CosmicBackground({super.key, required this.child, this.borderRadius, this.stars = true});
  final Widget child;
  final BorderRadius? borderRadius;
  final bool stars;

  @override
  Widget build(BuildContext context) {
    final content = DecoratedBox(
      decoration: const BoxDecoration(gradient: ArjStyle.cosmicLinear),
      child: Stack(children: [
        const Positioned.fill(child: DecoratedBox(decoration: BoxDecoration(gradient: RadialGradient(center: Alignment(0.8, -0.9), radius: 0.9, colors: [Color(0x47E0A526), Colors.transparent])))),
        const Positioned.fill(child: DecoratedBox(decoration: BoxDecoration(gradient: RadialGradient(center: Alignment(-0.9, 0.9), radius: 1.0, colors: [Color(0x737A1838), Colors.transparent])))),
        if (stars) const Positioned.fill(child: IgnorePointer(child: CustomPaint(painter: StarsPainter()))),
        child,
      ]),
    );
    return borderRadius == null ? content : ClipRRect(borderRadius: borderRadius!, child: content);
  }
}

/// Small white and pale-gold dots like the website's `.stars` layer (fixed pattern, no randomness).
class StarsPainter extends CustomPainter {
  const StarsPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final white = Paint()..color = Colors.white.withValues(alpha: 0.75);
    final gold = Paint()..color = const Color(0xFFFDE68A).withValues(alpha: 0.8);
    const pts = [
      (0.06, 0.12, 1.1), (0.18, 0.62, 0.8), (0.27, 0.28, 1.3), (0.36, 0.84, 0.9), (0.44, 0.08, 0.8), (0.52, 0.46, 1.2),
      (0.61, 0.72, 0.8), (0.68, 0.18, 1.0), (0.77, 0.55, 1.4), (0.85, 0.9, 0.9), (0.92, 0.3, 1.1), (0.97, 0.7, 0.8),
      (0.12, 0.92, 1.0), (0.4, 0.6, 0.7), (0.72, 0.38, 0.7), (0.58, 0.95, 1.0), (0.88, 0.1, 0.8), (0.22, 0.45, 0.6),
    ];
    for (var i = 0; i < pts.length; i++) {
      final (x, y, r) = pts[i];
      canvas.drawCircle(Offset(x * size.width, y * size.height), r, i % 3 == 0 ? gold : white);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Gold zodiac wheel (rings, 12 divisions and sign glyphs) like the website's `<x-zodiac-wheel>`.
class ZodiacWheel extends StatefulWidget {
  const ZodiacWheel({super.key, this.size = 260, this.opacity = 0.5, this.spin = true});
  final double size;
  final double opacity;
  final bool spin;
  @override
  State<ZodiacWheel> createState() => _ZodiacWheelState();
}

class _ZodiacWheelState extends State<ZodiacWheel> with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(vsync: this, duration: const Duration(seconds: 140));

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final reduce = MediaQuery.maybeDisableAnimationsOf(context) ?? false;
    if (ArjMotion.enabled && widget.spin && !reduce && !_c.isAnimating) _c.repeat();
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => IgnorePointer(
        child: Opacity(
          opacity: widget.opacity,
          child: RotationTransition(turns: _c, child: CustomPaint(size: Size.square(widget.size), painter: const _WheelPainter())),
        ),
      );
}

class _WheelPainter extends CustomPainter {
  const _WheelPainter();
  static const _glyphs = ['♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓'];

  @override
  void paint(Canvas canvas, Size size) {
    final c = size.center(Offset.zero);
    final r = size.width / 2;
    final line = Paint()
      ..color = ArjColors.gold300
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    canvas.drawCircle(c, r - 1, line);
    canvas.drawCircle(c, r * 0.78, line);
    canvas.drawCircle(c, r * 0.5, line..strokeWidth = 0.8);
    for (var i = 0; i < 12; i++) {
      final a = i * math.pi / 6;
      canvas.drawLine(c + Offset(math.cos(a), math.sin(a)) * r * 0.78, c + Offset(math.cos(a), math.sin(a)) * (r - 1), line);
      final g = a + math.pi / 12;
      final tp = TextPainter(text: TextSpan(text: _glyphs[i], style: TextStyle(color: ArjColors.gold200, fontSize: r * 0.12)), textDirection: TextDirection.ltr)..layout();
      tp.paint(canvas, c + Offset(math.cos(g), math.sin(g)) * r * 0.89 - Offset(tp.width / 2, tp.height / 2));
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// The website's `.eyebrow`: small uppercase gold text after a rotated gold square.
class Eyebrow extends StatelessWidget {
  const Eyebrow(this.text, {super.key, this.onDark = false});
  final String text;
  final bool onDark;

  @override
  Widget build(BuildContext context) => Row(mainAxisSize: MainAxisSize.min, children: [
        Transform.rotate(
          angle: math.pi / 4,
          child: Container(width: 7, height: 7, decoration: BoxDecoration(borderRadius: BorderRadius.circular(2), gradient: const LinearGradient(colors: [ArjColors.gold300, ArjColors.saffron500]))),
        ),
        const SizedBox(width: 9),
        Flexible(
          child: Text(text.toUpperCase(),
              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, letterSpacing: 1.8, color: onDark ? ArjColors.gold200 : ArjColors.gold700)),
        ),
      ]);
}

/// Text painted with the website's `.text-gold-grad` (gold 200 → gold 300 → saffron 400).
class GoldGradientText extends StatelessWidget {
  const GoldGradientText(this.text, {super.key, this.style});
  final String text;
  final TextStyle? style;

  @override
  Widget build(BuildContext context) => ShaderMask(
        blendMode: BlendMode.srcIn,
        shaderCallback: (b) => const LinearGradient(colors: [ArjColors.gold200, ArjColors.gold300, ArjColors.saffron400]).createShader(b),
        child: Text(text, style: style),
      );
}

/// The website's `.btn-gold`: pill with a gold-to-saffron gradient and dark text.
class GoldButton extends StatelessWidget {
  const GoldButton({super.key, required this.label, required this.onPressed, this.icon, this.expand = false});
  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool expand;

  @override
  Widget build(BuildContext context) {
    final child = Ink(
      decoration: BoxDecoration(
        gradient: onPressed == null ? null : ArjStyle.goldGradient,
        color: onPressed == null ? ArjColors.ink200 : null,
        borderRadius: BorderRadius.circular(999),
        boxShadow: onPressed == null ? null : const [BoxShadow(color: Color(0x55E0A526), blurRadius: 22, offset: Offset(0, 10))],
      ),
      child: Container(
        constraints: const BoxConstraints(minHeight: 50),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        child: Row(mainAxisSize: expand ? MainAxisSize.max : MainAxisSize.min, mainAxisAlignment: MainAxisAlignment.center, children: [
          if (icon != null) ...[Icon(icon, size: 19, color: ArjColors.ink950), const SizedBox(width: 8)],
          Flexible(child: Text(label, textAlign: TextAlign.center, style: const TextStyle(color: ArjColors.ink950, fontWeight: FontWeight.w700, fontSize: 15.5, letterSpacing: 0.2))),
        ]),
      ),
    );
    return Semantics(
      button: true,
      enabled: onPressed != null,
      child: Material(color: Colors.transparent, child: InkWell(borderRadius: BorderRadius.circular(999), onTap: onPressed, child: child)),
    );
  }
}

/// Translucent pill on dark backgrounds (the hero's "Free Kundli" button on the website).
class GlassButton extends StatelessWidget {
  const GlassButton({super.key, required this.label, required this.onPressed, this.icon});
  final String label;
  final VoidCallback onPressed;
  final IconData? icon;

  @override
  Widget build(BuildContext context) => Material(
        color: Colors.white.withValues(alpha: 0.06),
        shape: StadiumBorder(side: BorderSide(color: ArjColors.gold200.withValues(alpha: 0.45))),
        child: InkWell(
          customBorder: const StadiumBorder(),
          onTap: onPressed,
          child: Container(
            constraints: const BoxConstraints(minHeight: 48),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              if (icon != null) ...[Icon(icon, size: 18, color: ArjColors.gold200), const SizedBox(width: 8)],
              Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 15)),
            ]),
          ),
        ),
      );
}

/// Page hero shown at the top of screens, matching the website's page headers:
/// night sky, stars, a faint turning zodiac wheel, eyebrow, title with a gold-gradient word.
class CosmicHeader extends StatelessWidget {
  const CosmicHeader({super.key, required this.title, this.eyebrow, this.highlight, this.subtitle, this.child, this.margin = const EdgeInsets.fromLTRB(16, 12, 16, 8)});
  final String title;

  /// Part of the title painted in gold (e.g. the last word), appended after [title].
  final String? highlight;
  final String? eyebrow;
  final String? subtitle;

  /// Extra content under the text (buttons, search box, chips).
  final Widget? child;
  final EdgeInsets margin;

  @override
  Widget build(BuildContext context) {
    final tt = Theme.of(context).textTheme;
    final h = tt.headlineSmall?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, height: 1.2);
    return Padding(
      padding: margin,
      child: CosmicBackground(
        borderRadius: BorderRadius.circular(24),
        child: Stack(children: [
          const Positioned(right: -70, top: -60, child: ZodiacWheel(size: 220, opacity: 0.22)),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              if (eyebrow != null) ...[Eyebrow(eyebrow!, onDark: true), const SizedBox(height: 10)],
              Text.rich(TextSpan(children: [
                TextSpan(text: title),
                if (highlight != null) ...[
                  const TextSpan(text: ' '),
                  WidgetSpan(alignment: PlaceholderAlignment.baseline, baseline: TextBaseline.alphabetic, child: GoldGradientText(highlight!, style: h)),
                ],
              ]), style: h),
              if (subtitle != null) ...[
                const SizedBox(height: 8),
                Text(subtitle!, style: TextStyle(color: Colors.white.withValues(alpha: 0.8), height: 1.45, fontSize: 14.5)),
              ],
              if (child != null) ...[const SizedBox(height: 14), child!],
            ]),
          ),
        ]),
      ),
    );
  }
}

/// Thin maroon → gold → saffron stripe, as on top of the website header.
class BrandStripe extends StatelessWidget implements PreferredSizeWidget {
  const BrandStripe({super.key});
  @override
  Size get preferredSize => const Size.fromHeight(3);
  @override
  Widget build(BuildContext context) =>
      Container(height: 3, decoration: const BoxDecoration(gradient: LinearGradient(colors: [ArjColors.maroon600, ArjColors.gold500, ArjColors.saffron500])));
}

/// Round gradient icon like the website's `.icon-orb` (ink → maroon with a gold ring).
class IconOrb extends StatelessWidget {
  const IconOrb({super.key, required this.child, this.size = 54});
  final Widget child;
  final double size;
  @override
  Widget build(BuildContext context) => Container(
        width: size,
        height: size,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [ArjColors.ink800, ArjColors.ink900, ArjColors.maroon700]),
          border: Border.all(color: ArjColors.gold500.withValues(alpha: 0.5), width: 2),
        ),
        child: child,
      );
}
