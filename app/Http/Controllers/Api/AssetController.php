<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Display a listing of assets with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['user:id,name,email', 'department:id,name']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('inventory_code', 'ilike', "%{$search}%")
                    ->orWhere('serial_number', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $assets = $query->orderBy('name')->paginate(15);

        return response()->json($assets);
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_code' => ['required', 'string', 'max:50', 'unique:assets,inventory_code'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:assets,serial_number'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'operating_system' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'in:active,maintenance,retired'],
            'user_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $asset = Asset::create($validated);

        return response()->json([
            'message' => 'Equipo registrado correctamente',
            'data' => $asset->load(['user', 'department']),
        ], 201);
    }

    /**
     * Display the specified asset.
     */
    public function show(Asset $asset): JsonResponse
    {
        return response()->json([
            'data' => $asset->load(['user', 'department', 'tickets']),
        ]);
    }
}
