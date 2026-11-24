import 'package:flutter/widgets.dart';

/// Global navigator handle (P35).
///
/// Widgets mounted in `MaterialApp.builder` sit ABOVE the Navigator, so
/// their `BuildContext` has no Navigator ancestor and
/// `Navigator.of(context)` from inside them fails. Services and view
/// models that hold no `BuildContext` at all are in the same position.
///
/// This key is handed to `MaterialApp.navigatorKey`, giving such callers
/// a legitimate way to push routes or switch tabs.
class AppNavigator {
  const AppNavigator._();

  static final GlobalKey<NavigatorState> key = GlobalKey<NavigatorState>();

  /// The root navigator, or null before the first frame.
  static NavigatorState? get state => key.currentState;

  /// Pushes [route] on the root navigator. No-op (rather than a crash)
  /// if the navigator is not mounted yet.
  static Future<T?> push<T>(Route<T> route) async {
    final nav = state;
    if (nav == null) return null;
    return nav.push<T>(route);
  }

  /// Remove nested pages opened under an obsolete authorization scope. The
  /// coordinator also replaces the root shell, so its opened-tab widget cache
  /// is discarded rather than reused under the new role.
  static void popToRootForAuthorizationScope() {
    state?.popUntil((route) => route.isFirst);
  }
}
