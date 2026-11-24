-- Migration 098: drop the retired Finance and Materials module tables.
--
-- The Finance and Materials departments were removed from the application
-- (roles, pages, APIs, mobile screens, docs and tests). Their tables were
-- created by the archived legacy migration
-- sql/legacy/php-migrations/admin/004_add_finance_material_tables.php and
-- are no longer referenced by any live code.
--
-- Child tables are dropped before the parents they reference.
--
-- Owner note: take a database backup before applying this. The DROPs make
-- this migration irreversible.
--
-- Owner action (separate, owner-approved step): review any accounts whose
-- users.role is still 'finance_dept' or 'material_dept' and reassign or
-- deactivate them (e.g. via the Users page). This migration does not
-- change user rows.

DROP TABLE IF EXISTS finance_member_fees;
DROP TABLE IF EXISTS finance_transactions;
DROP TABLE IF EXISTS material_transactions;
DROP TABLE IF EXISTS material_requests;
DROP TABLE IF EXISTS material_items;
DROP TABLE IF EXISTS material_categories;
DROP TABLE IF EXISTS finance_categories;
DROP TABLE IF EXISTS finance_budgets;
