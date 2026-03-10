<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $map = [
            'schools' => 'can_access_schools',
            'students' => 'can_access_students',
            'campaigns' => 'can_access_campaigns',
            'templates' => 'can_access_templates',
        ];

        $field = $map[$module] ?? null;

        // Special case: "students" module (telecaller leads)
        // If the user doesn't have the global flag but has any students assigned,
        // allow them to access leads/my-leads/followups for their own students.
        if ($module === 'students' && (! $field || ! (bool) ($user->{$field} ?? false))) {
            $hasAssignedStudents = Student::where('assigned_to', $user->id)->exists();
            if ($hasAssignedStudents) {
                return $next($request);
            }
            abort(403, __('Access denied.'));
        }

        if (! $field || ! (bool) ($user->{$field} ?? false)) {
            abort(403, __('Access denied.'));
        }

        return $next($request);
    }
}

