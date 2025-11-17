<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Get all services
     */
    public function index(Request $request)
    {
        $query = Service::with('category')->active();

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_fr', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('order')->get();

        return response()->json([
            'services' => $services
        ]);
    }

    /**
     * Get service details
     */
    public function show($id)
    {
        $service = Service::with('category')->findOrFail($id);

        // Get average price from providers
        $averagePrice = \App\Models\ProviderService::where('service_id', $id)
            ->avg('price');

        $service->average_price = $averagePrice;

        return response()->json([
            'service' => $service
        ]);
    }

    /**
     * Get all service categories
     */
    public function categories()
    {
        $categories = ServiceCategory::with('services')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }
}
