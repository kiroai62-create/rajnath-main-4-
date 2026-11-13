/// Version of this build. Keep equal to `version:` in pubspec.yaml (a test checks it).
/// Admin › Mobile app compares it with the minimum and latest versions.
const appVersion = '1.13.0';

/// Compares dotted versions ("1.10.2" vs "1.9"): negative when [a] is older than [b].
int compareVersions(String a, String b) {
  List<int> parts(String v) => v.split('+').first.split('.').map((p) => int.tryParse(p.trim()) ?? 0).toList();
  final x = parts(a), y = parts(b);
  for (var i = 0; i < 3; i++) {
    final d = (i < x.length ? x[i] : 0) - (i < y.length ? y[i] : 0);
    if (d != 0) return d;
  }
  return 0;
}
