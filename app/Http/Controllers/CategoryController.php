<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori berhasil diambil',
            'data' => $categories
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'type' => 'nullable|string|max:100',
        ]);

        $category = Category::create($request->only('name', 'type'));

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat',
            'data' => $category
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail kategori',
            'data' => $category
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $category->id,
            'type' => 'nullable|string|max:100',
        ]);

        $category->update($request->only('name', 'type'));

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified category from storage (soft delete).
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus',
            'data' => null
        ], Response::HTTP_OK);
    }
}