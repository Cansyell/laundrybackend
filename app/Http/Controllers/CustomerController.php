<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index()
    {
        $customers = Customer::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar customer berhasil diambil',
            'data' => $customers
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $customer = Customer::create($request->only('name', 'phone', 'address'));

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dibuat',
            'data' => $customer
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail customer',
            'data' => $customer
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($request->only('name', 'phone', 'address'));

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil diperbarui',
            'data' => $customer
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified customer from storage (soft delete).
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dihapus',
            'data' => null
        ], Response::HTTP_OK);
    }
}