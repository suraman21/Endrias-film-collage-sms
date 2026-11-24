import 'package:flutter/material.dart';

import '../../services/api_service.dart';
import '../../services/app_update_service.dart';
import '../../utils/theme.dart';
import '../../utils/transitions.dart';
import '../../widgets/feature_tile.dart';
import '../../widgets/notification_bell_button.dart';
import '../attendance_warnings/attendance_warnings_screen.dart';

/// HR department home — company HR (2026-11-24).
///
/// HR is now a real human-resources department: the employee roster,
/// the staff attendance register, staff ID cards and staff
/// registration all live on the school web HR dashboard
/// (HR → Staff / Attendance / ID Cards). This mobile home stays for
/// login, profile and notifications, plus the shared Education
/// attendance warnings the department reads.
class HrDeptHomeScreen extends StatefulWidget {
  const HrDeptHomeScreen({super.key});

  @override
  State<HrDeptHomeScreen> createState() => _HrDeptHomeScreenState();
}

class _HrDeptHomeScreenState extends State<HrDeptHomeScreen> {
  final _api = ApiService();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('HR Department'), actions: [const NotificationBellButton(color: Colors.white),]),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('HR DEPARTMENT',
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1)),
          const SizedBox(height: 12),
          // Attendance alerting Phase 2: HR reads Education
          // warnings and may close them with an outcome. Hidden until the
          // server reports the feature switched on.
          if (AppUpdateService().featureEnabled('attendance_v2', fallback: false)) ...[
            GridView.count(
              crossAxisCount: 2, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 10, crossAxisSpacing: 10, childAspectRatio: 2.1,
              children: [
                FeatureTile(
                  label: 'Attendance Warnings',
                  icon: Icons.warning_amber_rounded,
                  color: AppTheme.warning,
                  onTap: () => Navigator.of(context).push(
                      SmoothPageRoute(page: const AttendanceWarningsScreen())),
                ),
              ],
            ),
            const SizedBox(height: 12),
          ],
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: AppTheme.cardLight,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppTheme.borderLight),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(children: [
                  Icon(Icons.badge_outlined, color: AppTheme.primary),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Text('Employee management is on the web HR dashboard',
                        style: TextStyle(fontSize: 15.5, fontWeight: FontWeight.w700)),
                  ),
                ]),
                const SizedBox(height: 10),
                Text(
                  'HR is now a full human-resources department. The '
                  'employee roster, the staff attendance register, '
                  'staff ID cards and staff registration live on the '
                  'school web HR dashboard (HR → Staff, Attendance, '
                  'ID Cards). Education attendance warnings and reports '
                  'still show up here and on that dashboard.',
                  style: TextStyle(fontSize: 12.5, height: 1.55, color: AppTheme.textSecondary),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Text('Signed in as ${_api.userName}',
              style: TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
        ],
      ),
    );
  }
}
