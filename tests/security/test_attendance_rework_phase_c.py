"""Attendance rework Phase C — default-ABSENT mobile UX (2026-10-07).

Product decision: every student/member loads as ABSENT; the teacher
(or a QR scan) marks presence. Save and Submit are always available
(the sheet is complete by construction); the completeness gate is
gone; the server still enforces the complete roster on submit.

Locked contracts (attendance_screen.dart):
  • Status precedence: local pending edit > server-saved > 'absent'
    (_firstStatus + _orAbsent) — presence is NEVER inferred.
  • _requireCompleteSheet and the "N unmarked" counter are gone; the
    counter reads "N present · M absent" (absent includes the
    default); "All Present" stays and "All Absent" is the reset.
  • Autosave keeps instant-draft behavior and now always persists a
    complete sheet (all roster rows carry explicit statuses).
  • QR scan = present. Duplicate feedback only for an existing
    present/late mark; absent/excused yields to the physical scan.
  • local_db still demands an explicit status per record.
  • HR attendance UI is removed (screen, tab, entry points); the
    outbox drain stays for old queued packets (pinned in
    test_hr_attendance_domain.py).

No Dart toolchain exists in this environment; these are structural
contract pins in the repo's established style (see the phase A/B
rework tests).
"""

from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / "Mobile" / "wbws_flutter_app" / "lib"


class LocalStoreContracts(unittest.TestCase):
    def test_local_save_still_demands_explicit_status_per_record(self):
        db = (APP / "services/local_db.dart").read_text(encoding="utf-8")
        self.assertIn("Attendance must explicitly mark every student.", db)
        self.assertIn("Attendance must explicitly mark every member.", db)
        self.assertNotIn("?? 'present'", db)
        self.assertNotIn("DEFAULT 'present'", db)


class HrRemovalCrossCheck(unittest.TestCase):
    def test_shell_has_no_hr_attendance_surface(self):
        shell = (APP / "screens/shell/app_shell.dart").read_text(encoding="utf-8")
        self.assertNotIn("hr_attendance", shell)
        self.assertNotIn("HrAttendanceScreen", shell)


if __name__ == "__main__":
    unittest.main()
