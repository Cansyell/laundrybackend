<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'customer', 'details.service']);

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by laundry status
        if ($request->has('laundry_status')) {
            $query->where('laundry_status', $request->laundry_status);
        }

        // Filter by customer_id
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $transactions = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi berhasil diambil',
            'data' => $transactions
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        // Validasi dasar (non-detail)
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'customer_id' => 'required|exists:customers,id',
            'transaction_date' => 'required|date',
            'estimated_completion' => 'nullable|date',
            'payment_status' => 'required|in:unpaid,paid,partial',
            'laundry_status' => 'nullable|in:pending,in_queue,in_process,ready,delivered',
            'payment_method' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.weight' => 'nullable|integer|min:0',
            'details.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Validasi manual untuk service_id & add_on_id, minimal salah satu harus ada & valid
        foreach ($request->details as $i => $detail) {
            if (empty($detail['service_id']) && empty($detail['add_on_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Baris detail ke-" . ($i+1) . " wajib isi service_id atau add_on_id.",
                    'errors' => ["details.$i" => ["service_id atau add_on_id wajib diisi"]]
                ], 422);
            }
            if (!empty($detail['service_id']) && !\App\Models\Service::where('id', $detail['service_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "service_id pada baris ke-" . ($i+1) . " tidak valid.",
                    'errors' => ["details.$i.service_id" => ["service_id tidak ditemukan"]]
                ], 422);
            }
            if (!empty($detail['add_on_id']) && !\App\Models\AddOn::where('id', $detail['add_on_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "add_on_id pada baris ke-" . ($i+1) . " tidak valid.",
                    'errors' => ["details.$i.add_on_id" => ["add_on_id tidak ditemukan"]]
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Hitung total transaksi
            $total = 0;
            foreach ($request->details as $detail) {
                $total += $detail['unit_price'] * $detail['quantity'];
            }

            // Create Transaction
            $transaction = Transaction::create([
                'user_id' => $request->user_id,
                'customer_id' => $request->customer_id,
                'transaction_date' => $request->transaction_date,
                'estimated_completion' => $request->estimated_completion,
                'total' => $total,
                'payment_status' => $request->payment_status,
                'laundry_status' => $request->laundry_status ?? 'pending',
                'payment_method' => $request->payment_method,
                'paid_amount' => $request->payment_status === 'paid' ? $total : 0,
                'notes' => $request->notes,
            ]);

            // Create TransactionDetail
            foreach ($request->details as $detail) {
                $lineTotal = $detail['unit_price'] * $detail['quantity'];
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'customer_id' => $request->customer_id,
                    'service_id' => $detail['service_id'] ?? null,
                    'add_on_id' => $detail['add_on_id'] ?? null,
                    'quantity' => $detail['quantity'],
                    'weight' => $detail['weight'] ?? null,
                    'unit_price' => $detail['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            DB::commit();

            $transaction->load(['customer', 'details.service', 'details.addOn']);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibuat',
                'data' => $transaction
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage(),
                'data' => null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'customer', 'details.service', 'details.addOn']);

        return response()->json([
            'success' => true,
            'message' => 'Detail transaksi',
            'data' => $transaction
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'payment_status' => 'sometimes|in:unpaid,paid,partial',
            'laundry_status' => 'sometimes|in:pending,in_queue,in_process,ready,delivered',
            'payment_method' => 'nullable|string|max:255',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($request->only([
            'payment_status',
            'laundry_status',
            'payment_method',
            'paid_amount',
            'notes'
        ]));

        $transaction->load(['customer', 'details.service']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diperbarui',
            'data' => $transaction
        ], Response::HTTP_OK);
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus',
            'data' => null
        ], Response::HTTP_OK);
    }
}
