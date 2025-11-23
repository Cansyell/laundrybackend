<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddOnController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/add-ons
     */
    public function index()
    {
        try {
            $addOns = AddOn::latest()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Data add-ons berhasil diambil',
                'data' => $addOns
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data add-ons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/add-ons
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:add_ons,name',
            'price' => 'required|numeric|min:0|max:999999999999.99',
        ], [
            'name.required' => 'Nama add-on wajib diisi',
            'name.unique' => 'Nama add-on sudah digunakan',
            'price.required' => 'Harga wajib diisi',
            'price.numeric' => 'Harga harus berupa angka',
            'price.min' => 'Harga tidak boleh negatif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $addOn = AddOn::create([
                'name' => $request->name,
                'price' => $request->price,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Add-on berhasil ditambahkan',
                'data' => $addOn
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan add-on',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/add-ons/{id}
     */
    public function show($id)
    {
        try {
            $addOn = AddOn::findOrFail($id);
            
            // Optional: Include transaction details
            $transactionDetails = TransactionDetail::where('add_on_id', $addOn->id)
                ->with(['transaction', 'customer'])
                ->latest()
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Detail add-on berhasil diambil',
                'data' => [
                    'add_on' => $addOn,
                    'transaction_count' => $transactionDetails->count(),
                    'transactions' => $transactionDetails
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Add-on tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail add-on',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/add-ons/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $addOn = AddOn::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:add_ons,name,' . $addOn->id,
                'price' => 'required|numeric|min:0|max:999999999999.99',
            ], [
                'name.required' => 'Nama add-on wajib diisi',
                'name.unique' => 'Nama add-on sudah digunakan',
                'price.required' => 'Harga wajib diisi',
                'price.numeric' => 'Harga harus berupa angka',
                'price.min' => 'Harga tidak boleh negatif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $addOn->update([
                'name' => $request->name,
                'price' => $request->price,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Add-on berhasil diperbarui',
                'data' => $addOn->fresh()
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Add-on tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui add-on',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/add-ons/{id}
     */
    public function destroy($id)
    {
        try {
            $addOn = AddOn::findOrFail($id);
            
            // Cek apakah add-on sedang digunakan di transaction details
            $isUsed = TransactionDetail::where('add_on_id', $addOn->id)->exists();
            
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Add-on tidak dapat dihapus karena sedang digunakan dalam transaksi'
                ], 409); // 409 Conflict
            }

            $addOn->delete();

            return response()->json([
                'success' => true,
                'message' => 'Add-on berhasil dihapus'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Add-on tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus add-on',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get add-ons with usage statistics
     * GET /api/add-ons/statistics
     */
    public function statistics()
    {
        try {
            $addOns = AddOn::withCount('transactionDetails')
                ->with(['transactionDetails' => function($query) {
                    $query->selectRaw('add_on_id, SUM(line_total) as total_revenue, SUM(quantity) as total_quantity')
                        ->groupBy('add_on_id');
                }])
                ->get()
                ->map(function($addOn) {
                    $detail = $addOn->transactionDetails->first();
                    return [
                        'id' => $addOn->id,
                        'name' => $addOn->name,
                        'price' => $addOn->price,
                        'usage_count' => $addOn->transaction_details_count,
                        'total_quantity_sold' => $detail ? $detail->total_quantity : 0,
                        'total_revenue' => $detail ? $detail->total_revenue : 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Statistik add-ons berhasil diambil',
                'data' => $addOns
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik add-ons',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}