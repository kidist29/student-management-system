<?php
/**
 * Role-based access control.
 *
 * Four roles: super_admin, admin (registrar), teacher, student.
 *
 * super_admin and admin share the same permissions on academic data
 * (students, teachers, departments, courses, enrollments, grades,
 * transcripts) — the only difference between them is that only
 * super_admin can manage user accounts/roles and system settings.
 *
 * teacher and student get their own separate, narrower portals
 * (see /teacher/ and /student/) rather than the admin/* pages — those
 * pages enforce row-level ownership directly (e.g. "only courses where
 * teacher_id = the logged-in teacher's own id"), which a role name
 * alone can't express.
 *
 * Requires auth_check.php (session, redirect(), setFlash()) to already
 * be loaded.
 */

/** The logged-in user's role, or null if not logged in. */
function currentRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/** The logged-in user's linked teacher_id (only set for role = teacher). */
function currentTeacherId(): ?int
{
    return isset($_SESSION['user_teacher_id']) ? (int) $_SESSION['user_teacher_id'] : null;
}

/** The logged-in user's linked student_id (only set for role = student). */
function currentStudentId(): ?int
{
    return isset($_SESSION['user_student_id']) ? (int) $_SESSION['user_student_id'] : null;
}

/** True if the logged-in user's role is one of the given roles. */
function hasRole(string ...$roles): bool
{
    $role = currentRole();
    return $role !== null && in_array($role, $roles, true);
}

/** Where a role lands after login / when denied access to a page outside its area. */
function roleHomeUrl(?string $role): string
{
    return match ($role) {
        'super_admin', 'admin' => 'admin/dashboard.php',
        'teacher' => 'teacher/dashboard.php',
        'student' => 'student/dashboard.php',
        default => 'auth/login.php',
    };
}

/**
 * Require the logged-in user to have one of the given roles, or redirect
 * them away — to login if not authenticated at all, or to their own
 * area's home page (with an explanatory message) if logged in as a role
 * that isn't allowed here. Always call this server-side, on every
 * protected page — never rely on hiding a link/button as the only guard.
 */
function requireRole(string ...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        setFlash('danger', "You don't have permission to view that page.");
        redirect(roleHomeUrl(currentRole()));
    }
}
