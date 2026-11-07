import 'package:flutter/material.dart';

/// Design tokens mirrored from backend/resources/css/app.css so web and app look the same.
abstract final class ArjColors {
  static const ivory50 = Color(0xFFFFFBF3);
  static const ivory100 = Color(0xFFFBF3E2);
  static const ivory200 = Color(0xFFF2E4C4);
  static const ink950 = Color(0xFF0F0A2E);
  static const ink900 = Color(0xFF1B1446);
  static const ink800 = Color(0xFF261B5E);
  static const ink700 = Color(0xFF362777);
  static const ink600 = Color(0xFF4E4384);
  static const ink200 = Color(0xFFDCD8EC);
  static const ink100 = Color(0xFFEEECF6);
  static const gold50 = Color(0xFFFDF6E3);
  static const gold200 = Color(0xFFF7DD96);
  static const gold300 = Color(0xFFF2C75A);
  static const gold400 = Color(0xFFEDB63E);
  static const gold500 = Color(0xFFE0A526);
  static const gold700 = Color(0xFF8A5A0C);
  static const saffron500 = Color(0xFFEE8420);
  static const maroon600 = Color(0xFF9B2348);
  static const maroon700 = Color(0xFF7A1838);
  static const danger700 = Color(0xFFA12A2A);
  static const success700 = Color(0xFF1F6B45);
}

/// Shared decoration values so custom surfaces match the themed ones.
abstract final class ArjStyle {
  static const radiusCard = 18.0;
  static const radiusControl = 14.0;

  /// Soft, low-contrast ink shadow used under cards and raised surfaces.
  static const softShadow = [
    BoxShadow(color: Color(0x141B1446), blurRadius: 18, offset: Offset(0, 6)),
    BoxShadow(color: Color(0x081B1446), blurRadius: 3, offset: Offset(0, 1)),
  ];

  /// Deep indigo gradient for hero and header surfaces.
  static const inkGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [ArjColors.ink900, ArjColors.ink800, ArjColors.ink700],
    stops: [0, 0.55, 1],
  );

  /// Night-sky gradient (same as the website's .bg-cosmic): indigo into kumkum maroon.
  static const cosmicGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [ArjColors.ink950, ArjColors.ink900, Color(0xFF2A1650), ArjColors.maroon700],
    stops: [0, 0.45, 0.8, 1],
  );

  /// Temple gold into saffron (the website's .btn-gold).
  static const goldGradient = LinearGradient(
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
    colors: [ArjColors.gold300, ArjColors.gold500, ArjColors.saffron500],
  );
}

ThemeData buildArjTheme() {
  const control = BorderRadius.all(Radius.circular(ArjStyle.radiusControl));
  const card = BorderRadius.all(Radius.circular(ArjStyle.radiusCard));
  final scheme = ColorScheme.fromSeed(
    seedColor: ArjColors.ink900,
    primary: ArjColors.ink900,
    onPrimary: Colors.white,
    primaryContainer: ArjColors.ink100,
    onPrimaryContainer: ArjColors.ink900,
    secondary: ArjColors.gold500,
    onSecondary: ArjColors.ink900,
    secondaryContainer: ArjColors.gold50,
    onSecondaryContainer: ArjColors.gold700,
    tertiary: ArjColors.gold700,
    surface: Colors.white,
    onSurface: ArjColors.ink900,
    onSurfaceVariant: ArjColors.ink600,
    surfaceTint: Colors.transparent,
    outline: ArjColors.ink200,
    outlineVariant: ArjColors.ink100,
    error: ArjColors.danger700,
  );

  final base = ThemeData(useMaterial3: true, colorScheme: scheme);
  final t = base.textTheme.apply(bodyColor: ArjColors.ink900, displayColor: ArjColors.ink900);
  final text = t.copyWith(
    displaySmall: t.displaySmall?.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.8, height: 1.15),
    headlineLarge: t.headlineLarge?.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.6, height: 1.2),
    headlineMedium: t.headlineMedium?.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.4, height: 1.22),
    headlineSmall: t.headlineSmall?.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.3, height: 1.25),
    titleLarge: t.titleLarge?.copyWith(fontWeight: FontWeight.w600, letterSpacing: -0.2),
    titleMedium: t.titleMedium?.copyWith(fontWeight: FontWeight.w600, letterSpacing: -0.1),
    titleSmall: t.titleSmall?.copyWith(fontWeight: FontWeight.w600),
    bodyLarge: t.bodyLarge?.copyWith(height: 1.5),
    bodyMedium: t.bodyMedium?.copyWith(height: 1.45),
    bodySmall: t.bodySmall?.copyWith(color: ArjColors.ink600, height: 1.4),
    labelLarge: t.labelLarge?.copyWith(fontWeight: FontWeight.w600, letterSpacing: 0.2),
    labelMedium: t.labelMedium?.copyWith(fontWeight: FontWeight.w600, letterSpacing: 0.6),
    labelSmall: t.labelSmall?.copyWith(fontWeight: FontWeight.w600, letterSpacing: 0.6),
  );

  const buttonText = TextStyle(fontSize: 15, fontWeight: FontWeight.w600, letterSpacing: 0.2);
  const buttonShape = RoundedRectangleBorder(borderRadius: control);
  const buttonPadding = EdgeInsets.symmetric(horizontal: 22, vertical: 14);

  return base.copyWith(
    textTheme: text,
    scaffoldBackgroundColor: ArjColors.ivory50,
    // Native-feeling screen changes (Material 3 fade-forward on Android).
    pageTransitionsTheme: const PageTransitionsTheme(builders: {TargetPlatform.android: FadeForwardsPageTransitionsBuilder()}),
    splashFactory: InkSparkle.splashFactory,
    visualDensity: VisualDensity.standard,
    appBarTheme: AppBarTheme(
      backgroundColor: ArjColors.ivory50,
      foregroundColor: ArjColors.ink900,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0.5,
      shadowColor: ArjColors.ink200,
      centerTitle: false,
      titleSpacing: 20,
      titleTextStyle: text.titleLarge?.copyWith(fontSize: 19, fontWeight: FontWeight.w600, color: ArjColors.ink900, letterSpacing: -0.2),
      iconTheme: const IconThemeData(color: ArjColors.ink900),
    ),
    cardTheme: const CardThemeData(
      color: Colors.white,
      surfaceTintColor: Colors.transparent,
      elevation: 1.5,
      shadowColor: Color(0x331B1446),
      shape: RoundedRectangleBorder(borderRadius: card, side: BorderSide(color: ArjColors.ink200, width: 0.8)),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: ArjColors.ink900,
        foregroundColor: Colors.white,
        disabledBackgroundColor: ArjColors.ink200,
        disabledForegroundColor: ArjColors.ink600,
        minimumSize: const Size(52, 52),
        padding: buttonPadding,
        shape: buttonShape,
        textStyle: buttonText,
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: ArjColors.ink900,
        foregroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        minimumSize: const Size(52, 52),
        padding: buttonPadding,
        shape: buttonShape,
        textStyle: buttonText,
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: ArjColors.ink900,
        backgroundColor: Colors.white,
        minimumSize: const Size(52, 52),
        padding: buttonPadding,
        side: const BorderSide(color: ArjColors.ink200),
        shape: buttonShape,
        textStyle: buttonText,
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: ArjColors.gold700,
        minimumSize: const Size(48, 48),
        shape: buttonShape,
        textStyle: const TextStyle(fontWeight: FontWeight.w600),
      ),
    ),
    iconButtonTheme: IconButtonThemeData(style: IconButton.styleFrom(foregroundColor: ArjColors.ink900)),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      labelStyle: const TextStyle(color: ArjColors.ink600),
      floatingLabelStyle: const TextStyle(color: ArjColors.gold700, fontWeight: FontWeight.w600),
      hintStyle: TextStyle(color: ArjColors.ink600.withValues(alpha: 0.7)),
      prefixIconColor: ArjColors.ink600,
      suffixIconColor: ArjColors.ink600,
      border: const OutlineInputBorder(borderRadius: control, borderSide: BorderSide(color: ArjColors.ink200)),
      enabledBorder: const OutlineInputBorder(borderRadius: control, borderSide: BorderSide(color: ArjColors.ink200)),
      focusedBorder: const OutlineInputBorder(borderRadius: control, borderSide: BorderSide(color: ArjColors.gold500, width: 2)),
      errorBorder: const OutlineInputBorder(borderRadius: control, borderSide: BorderSide(color: ArjColors.danger700)),
      focusedErrorBorder: const OutlineInputBorder(borderRadius: control, borderSide: BorderSide(color: ArjColors.danger700, width: 2)),
      disabledBorder: const OutlineInputBorder(borderRadius: control, borderSide: BorderSide(color: ArjColors.ink100)),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: Colors.white,
      selectedColor: ArjColors.gold50,
      disabledColor: ArjColors.ink100,
      checkmarkColor: ArjColors.gold700,
      surfaceTintColor: Colors.transparent,
      side: WidgetStateBorderSide.resolveWith(
        (s) => BorderSide(color: s.contains(WidgetState.selected) ? ArjColors.gold400 : ArjColors.ink200),
      ),
      labelStyle: const TextStyle(color: ArjColors.ink900, fontWeight: FontWeight.w600, fontSize: 13),
      secondaryLabelStyle: const TextStyle(color: ArjColors.gold700, fontWeight: FontWeight.w600, fontSize: 13),
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
      shape: const StadiumBorder(),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      height: 70,
      shadowColor: ArjColors.ink200,
      indicatorColor: ArjColors.gold200,
      indicatorShape: const StadiumBorder(),
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      iconTheme: WidgetStateProperty.resolveWith(
        (s) => IconThemeData(size: 24, color: s.contains(WidgetState.selected) ? ArjColors.ink900 : ArjColors.ink600),
      ),
      labelTextStyle: WidgetStateProperty.resolveWith(
        (s) => TextStyle(
          fontSize: 12,
          letterSpacing: 0.2,
          fontWeight: s.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500,
          color: s.contains(WidgetState.selected) ? ArjColors.ink900 : ArjColors.ink600,
        ),
      ),
    ),
    listTileTheme: const ListTileThemeData(
      iconColor: ArjColors.gold700,
      textColor: ArjColors.ink900,
      contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 2),
      shape: RoundedRectangleBorder(borderRadius: card),
      titleTextStyle: TextStyle(fontSize: 15.5, fontWeight: FontWeight.w600, color: ArjColors.ink900),
      subtitleTextStyle: TextStyle(fontSize: 13, color: ArjColors.ink600, height: 1.35),
      minVerticalPadding: 10,
    ),
    dividerTheme: const DividerThemeData(color: ArjColors.ink100, thickness: 1, space: 24),
    snackBarTheme: SnackBarThemeData(
      backgroundColor: ArjColors.ink900,
      contentTextStyle: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w500),
      actionTextColor: ArjColors.gold400,
      behavior: SnackBarBehavior.floating,
      elevation: 4,
      insetPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(ArjStyle.radiusControl)),
    ),
    bottomSheetTheme: const BottomSheetThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      modalBackgroundColor: Colors.white,
      showDragHandle: true,
      dragHandleColor: ArjColors.ink200,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(22))),
      titleTextStyle: text.titleLarge?.copyWith(fontWeight: FontWeight.w700, color: ArjColors.ink900),
      contentTextStyle: text.bodyMedium?.copyWith(color: ArjColors.ink600),
    ),
    popupMenuTheme: const PopupMenuThemeData(
      color: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: control, side: BorderSide(color: ArjColors.ink100)),
    ),
    tabBarTheme: const TabBarThemeData(
      labelColor: ArjColors.ink900,
      unselectedLabelColor: ArjColors.ink600,
      indicatorColor: ArjColors.gold500,
      dividerColor: ArjColors.ink100,
      labelStyle: TextStyle(fontWeight: FontWeight.w700),
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(color: ArjColors.gold500, linearTrackColor: ArjColors.gold50, circularTrackColor: Colors.transparent),
    badgeTheme: const BadgeThemeData(backgroundColor: ArjColors.danger700, textColor: Colors.white),
    checkboxTheme: CheckboxThemeData(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
      side: const BorderSide(color: ArjColors.ink600, width: 1.5),
      fillColor: WidgetStateProperty.resolveWith((s) => s.contains(WidgetState.selected) ? ArjColors.ink900 : null),
    ),
    switchTheme: SwitchThemeData(
      trackColor: WidgetStateProperty.resolveWith((s) => s.contains(WidgetState.selected) ? ArjColors.ink900 : ArjColors.ink100),
      thumbColor: WidgetStateProperty.resolveWith((s) => s.contains(WidgetState.selected) ? ArjColors.gold400 : ArjColors.ink600),
      trackOutlineColor: WidgetStateProperty.all(Colors.transparent),
    ),
    expansionTileTheme: const ExpansionTileThemeData(
      iconColor: ArjColors.gold700,
      collapsedIconColor: ArjColors.ink600,
      shape: Border(),
      collapsedShape: Border(),
    ),
  );
}
