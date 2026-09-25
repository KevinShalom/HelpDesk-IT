<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /**
     * Get all active categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Get all priorities with SLA definitions.
     */
    public function priorities(): JsonResponse
    {
        return response()->json([
            'data' => Priority::orderBy('sla_hours')->get(),
        ]);
    }

    /**
     * Get active departments.
     */
    public function departments(): JsonResponse
    {
        return response()->json([
            'data' => Department::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Get technicians available for ticket assignment.
     */
    public function technicians(): JsonResponse
    {
        $technicianRole = Role::whereIn('slug', [Role::TECHNICIAN, Role::SUPERVISOR])->pluck('id');

        $technicians = User::whereIn('role_id', $technicianRole)
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'role_id', 'department_id')
            ->with('role:id,name,slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $technicians,
        ]);
    }
}
