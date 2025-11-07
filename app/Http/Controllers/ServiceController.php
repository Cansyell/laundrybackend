<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('category')->latest()->paginate(10);
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'min_order' => 'nullable|integer|min:1',
            'type' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'estimate' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $service = Service::create($validated);

        return response()->json($service->load('category'), Response::HTTP_CREATED);
    }

    public function show(Service $service)
    {
        return response()->json($service->load('category'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'min_order' => 'nullable|integer|min:1',
            'type' => 'nullable|string|max:100',
            'price' => 'sometimes|required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'estimate' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $service->update($validated);

        return response()->json($service->load('category'));
    }

    public function destroy(Service $service)
    {
        $service->delete(); // soft delete
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}