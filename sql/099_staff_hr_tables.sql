-- ════════════════════════════════════════════════════════════
-- 099 — HR staff domain: employee roster + staff attendance
-- register (HR fully separated from the admission/member domain)
-- ════════════════════════════════════════════════════════════
-- Product decision (2026-11-24): the HR department is a real
-- company human-resources department, not the student-management
-- surface it used to be. Admission keeps everything member-
-- centric (members, registration, family link, archive, student
-- ID cards); HR gets its own employee domain:
--
--   • staff             — the employee roster: identity, position,
--                         department, hire date, salary (stored
--                         data only — no payroll processing) and
--                         status (active/inactive).
--   • staff_attendance  — the daily attendance register: one mark
--                         per employee per date, recorded by HR
--                         in a month grid (present/late/absent/
--                         excused). Reuses the mark mechanics of
--                         the retired section-based HR domain
--                         (sql/026) — same status set, same
--                         UNIQUE(date, subject) shape — but for
--                         employees, without the taker/packet
--                         workflow.
--
-- Separation rules:
--   • NO foreign key between staff and members — the two domains
--     never reference each other.
--   • The retired hr_attendance / hr_submissions tables (sql/026)
--     are an untouched historical archive; nothing in this domain
--     reads or writes them.
--   • Student tables (members, attendance, wbws_groups, …) are
--     not touched by this migration.
--
-- Fully idempotent; CREATE TABLE IF NOT EXISTS only; safe to re-run.
-- ════════════════════════════════════════════════════════════

-- ── 1. Employee roster ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `staff` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_code`      VARCHAR(40)  NOT NULL,
    `full_name_am`    VARCHAR(255) NOT NULL,
    `full_name_en`    VARCHAR(255) NOT NULL DEFAULT '',
    `gender`          ENUM('male','female') NOT NULL DEFAULT 'male',
    `phone_number`    VARCHAR(40)  NOT NULL DEFAULT '',
    `email`           VARCHAR(255) NOT NULL DEFAULT '',
    `position`        VARCHAR(255) NOT NULL DEFAULT '',
    `department`      VARCHAR(120) NOT NULL DEFAULT '',
    `hire_date`       DATE DEFAULT NULL,
    `salary_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `salary_notes`    VARCHAR(255) NOT NULL DEFAULT '',
    `photo`           VARCHAR(255) DEFAULT NULL,
    `emergency_name`  VARCHAR(255) NOT NULL DEFAULT '',
    `emergency_phone` VARCHAR(40)  NOT NULL DEFAULT '',
    `address`         VARCHAR(255) NOT NULL DEFAULT '',
    `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `id_card_generated_at` TIMESTAMP NULL DEFAULT NULL,
    `notes`           TEXT DEFAULT NULL,
    `created_by`      INT UNSIGNED DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_code` (`staff_code`),
    KEY `idx_staff_status` (`status`),
    KEY `idx_staff_department` (`department`),
    KEY `idx_staff_phone` (`phone_number`),
    KEY `idx_staff_name` (`full_name_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Daily attendance register ────────────────────────────
CREATE TABLE IF NOT EXISTS `staff_attendance` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attendance_date` DATE NOT NULL,
    `staff_id`        BIGINT UNSIGNED NOT NULL,
    `status`          ENUM('present','late','absent','excused')
                      NOT NULL DEFAULT 'present',
    `notes`           VARCHAR(255) DEFAULT NULL,
    `marked_by`       INT UNSIGNED DEFAULT NULL,
    `marked_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_attendance_date_staff` (`attendance_date`, `staff_id`),
    KEY `idx_staff_attendance_date` (`attendance_date`),
    KEY `idx_staff_attendance_staff` (`staff_id`, `attendance_date`),
    KEY `idx_staff_attendance_status` (`status`),
    CONSTRAINT `fk_staff_attendance_staff`
        FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
