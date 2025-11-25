<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['user:id,name', 'category:id,name']);

        // Filter by user (optional, jika ingin user hanya lihat expense nya sendiri)
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Search by description or ref_no
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ref_no', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $expenses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Expenses retrieved successfully',
            'data' => $expenses
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'expenses_category_id' => 'nullable|exists:expenses_categories,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'ref_no' => 'nullable|string|max:255|unique:expenses,ref_no',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense = Expense::create($request->all());
        $expense->load(['user:id,name', 'category:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully',
            'data' => $expense
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['user:id,name', 'category:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Expense retrieved successfully',
            'data' => $expense
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|exists:users,id',
            'expenses_category_id' => 'nullable|exists:expenses_categories,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'ref_no' => 'nullable|string|max:255|unique:expenses,ref_no,' . $expense->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense->update($request->all());
        $expense->load(['user:id,name', 'category:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully',
            'data' => $expense
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
            'data' => null
        ], 200);
    }

    /**
     * Get expense summary/statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $query = Expense::query();

        // Filter by user
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        $summary = [
            'total_expenses' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'average_amount' => $query->avg('amount'),
            'by_category' => Expense::with('category:id,name')
                ->selectRaw('expenses_category_id, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('expenses_category_id')
                ->get(),
            'by_payment_method' => Expense::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->whereNotNull('payment_method')
                ->groupBy('payment_method')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Expense summary retrieved successfully',
            'data' => $summary
        ], 200);
    }

    /**
     * Get expenses by month
     */
    public function byMonth(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));
        $userId = $request->get('user_id');

        $query = Expense::selectRaw('MONTH(date) as month, SUM(amount) as total, COUNT(*) as count')
            ->whereYear('date', $year);

        if ($userId) {
            $query->byUser($userId);
        }

        $expenses = $query->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Monthly expenses retrieved successfully',
            'data' => [
                'year' => $year,
                'expenses' => $expenses
            ]
        ], 200);
    }
}