# RBAC Current-State Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement full action-level RBAC for internal staff roles from the current clean codebase state.

**Architecture:** Add RBAC metadata, persistence, and guards to `includes/sql.php`, create `access_control.php` as the role and permission management UI, and update the sidebar/page guards to use action permissions. Preserve Admin as full-access and Pelanggan as a client role, while all other roles become internal staff roles.

**Tech Stack:** PHP native, MySQL/MariaDB, Bootstrap 3, existing `includes/load.php`, session helper, and database helper.

---

## Current State Notes

- `access_control.php` does not exist yet.
- `layouts/staff_menu.php` does not exist yet.
- `includes/sql.php` has `page_require_level()` and `menu_can()`, but no `role_can()`, `require_module()`, `require_permission()`, or permission tables.
- `group.php`, `add_group.php`, `edit_group.php`, and `delete_group.php` still manage raw group levels and must be redirected or made safe.
- The committed spec is `docs/superpowers/specs/2026-07-06-rbac-penuh-manajemen-user-design.md`.

## Tasks

- [ ] Add `tests/rbac_permissions_smoke.php` before production code and verify it fails because RBAC helpers do not exist.
- [ ] Extend `includes/sql.php` with action metadata, `role_action_permissions`, role CRUD helpers, `role_can_action()`, `require_permission()`, compatibility aliases, and seed data.
- [ ] Create `access_control.php` with role CRUD and action-level permission matrix.
- [ ] Update admin/sidebar handling: dynamic role labels from `user_groups`, `layouts/staff_menu.php` for all internal staff roles, and admin menu link to `access_control.php`.
- [ ] Make old group management routes safe by redirecting to `access_control.php` or preventing protected/used role deletion.
- [ ] Replace operational `page_require_level()` calls with `require_permission(module, action)` on module pages.
- [ ] Hide create/update/delete/print/process buttons according to `role_can_action()`.
- [ ] Update `add_user.php` and `edit_user.php` to list active roles and support newly-created internal staff roles.
- [ ] Update `README.md` to describe action-level RBAC.
- [ ] Verify with `php -l` on changed PHP files and `php tests/rbac_permissions_smoke.php`.

## Verification Commands

```powershell
php tests/rbac_permissions_smoke.php
php -l includes/sql.php
php -l access_control.php
php -l layouts/header.php
php -l layouts/admin_menu.php
php -l layouts/staff_menu.php
php -l add_user.php
php -l edit_user.php
```

After page updates, run `php -l` on every touched page. Expected output is `No syntax errors detected` for each file and `RBAC permission smoke tests passed` for the smoke test.
