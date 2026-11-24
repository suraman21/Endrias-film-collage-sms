/// Department offline capability inventory.
///
/// SSMS has exactly **one** offline attendance scope:
/// * Education Department attendance — organized by **class**.
///
/// It is served by a single atomic endpoint `GET /attendance/preparation`
/// whose server-side predicate (`classRestricted` + `attendance_v2`) selects
/// the authorized scope. Grades is the
/// next department to go offline; all other departments remain online-only
/// until a separate inventory lands.
///
/// This file is a **guard, not a feature flag**: it documents the current
/// offline surface so future code/tests cannot claim an uninventoried
/// department is offline-capable. The preparation/fetcher/store classes for
/// an online-only department must not exist until its inventory is approved.
library;

enum OfflineDepartment {
  /// Education attendance — class-organized.
  educationAttendance,

  /// Grades — to be added (A→B).
  grades,

  // Online-only until inventoried:
  education,
  members,
  hr,
  finance,
  materials,
}

/// Single source of truth for which departments are offline-capable today.
///
/// Grades is included as *planned* but not yet *enabled*: callers must check
/// [isOfflineEnabled] rather than merely testing membership in
/// [OfflineDepartment.values].
class DepartmentOfflinePolicy {
  const DepartmentOfflinePolicy._();

  static const Set<OfflineDepartment> offlineEnabledToday = {
    OfflineDepartment.educationAttendance,
    OfflineDepartment.grades,
  };

  /// Next departments after Grades (members/HR/Finance/Materials); none
  /// currently planned as offline until inventory lands.
  static const Set<OfflineDepartment> plannedNext = {};

  static const Set<OfflineDepartment> onlineOnly = {
    OfflineDepartment.education,
    OfflineDepartment.members,
    OfflineDepartment.hr,
    OfflineDepartment.finance,
    OfflineDepartment.materials,
  };

  static bool isOfflineEnabled(OfflineDepartment d) => offlineEnabledToday.contains(d);
  static bool isPlanned(OfflineDepartment d) => plannedNext.contains(d);
  static bool isOnlineOnly(OfflineDepartment d) => onlineOnly.contains(d);
}
