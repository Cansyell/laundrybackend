<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /**
     * Helper function to format expense data with proper types
     */
    private function formatExpense($expense)
    {
        return [
            'id' => (int) $expense->id,
            'user_id' => (int) $expense->user_id,
            'expenses_category_id' => $expense->expenses_category_id ? (int) $expense->expenses_category_id : null,
            'amount' => (float) $expense->amount,
            'payment_method' => $expense->payment_method,
            'description' => $expense->description,
            'date' => $expense->date->format('Y-m-d'),
            'ref_no' => $expense->ref_no,
            'created_at' => $expense->created_at?->toIso8601String(),
            'updated_at' => $expense->updated_at?->toIso8601String(),
            'deleted_at' => $expense->deleted_at?->toIso8601String(),
            'user' => $expense->user ? [
                'id' => (int) $expense->user->id,
                'name' => $expense->user->name,
            ] : null,
            'category' => $expense->category ? [
                'id' => (int) $expense->category->id,
                'name' => $expense->category->name,
            ] : null,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['user:id,name', 'category:id,name']);

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

        // ✅ Format with proper type casting
        $formattedData = $expenses->map(function($expense) {
            return $this->formatExpense($expense);
        });

        return response()->json([
            'success' => true,
            'message' => 'Expenses retrieved successfully',
            'data' => $formattedData->values()->all()
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
            'data' => $this->formatExpense($expense)
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
            'data' => $this->formatExpense($expense)
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
            'data' => $this->formatExpense($expense)
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

        // Filter by date range (optional)
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Filter by category (optional)
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Total keseluruhan expense dari semua user
        $summary = [
            'total_expenses' => $query->count(),
            'total_amount' => (float) $query->sum('amount'),
            'average_amount' => (float) $query->avg('amount'),
            
            // Group by category
            'by_category' => Expense::with('category:id,name')
                ->selectRaw('expenses_category_id, COUNT(*) as count, SUM(amount) as total')
                ->when($request->has('start_date') && $request->has('end_date'), function($q) use ($request) {
                    return $q->dateRange($request->start_date, $request->end_date);
                })
                ->groupBy('expenses_category_id')
                ->get()
                ->map(function($item) {
                    return [
                        'expenses_category_id' => $item->expenses_category_id ? (int) $item->expenses_category_id : null,
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                        'category' => $item->category ? [
                            'id' => (int) $item->category->id,
                            'name' => $item->category->name,
                        ] : null,
                    ];
                }),
            
            // Group by payment method
            'by_payment_method' => Expense::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->whereNotNull('payment_method')
                ->when($request->has('start_date') && $request->has('end_date'), function($q) use ($request) {
                    return $q->dateRange($request->start_date, $request->end_date);
                })
                ->groupBy('payment_method')
                ->get()
                ->map(function($item) {
                    return [
                        'payment_method' => $item->payment_method,
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                    ];
                }),
            
            // Group by user
            'by_user' => Expense::with('user:id,name')
                ->selectRaw('user_id, COUNT(*) as count, SUM(amount) as total')
                ->when($request->has('start_date') && $request->has('end_date'), function($q) use ($request) {
                    return $q->dateRange($request->start_date, $request->end_date);
                })
                ->groupBy('user_id')
                ->get()
                ->map(function($item) {
                    return [
                        'user_id' => (int) $item->user_id,
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                        'user' => $item->user ? [
                            'id' => (int) $item->user->id,
                            'name' => $item->user->name,
                        ] : null,
                    ];
                }),
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

        $query = Expense::selectRaw('MONTH(date) as month, SUM(amount) as total, COUNT(*) as count')
            ->whereYear('date', $year);

        // Optional: Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        $expenses = $query->groupBy('month')
            ->orderBy('month')
            ->get();

        // Format response dengan data lengkap untuk semua 12 bulan
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $found = $expenses->firstWhere('month', $month);
            $monthlyData[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'total' => $found ? (float) $found->total : 0.0,
                'count' => $found ? (int) $found->count : 0,
            ];
        }

        // Hitung total tahunan
        $yearlyTotal = (float) $expenses->sum('total');
        $yearlyCount = (int) $expenses->sum('count');

        return response()->json([
            'success' => true,
            'message' => 'Monthly expenses retrieved successfully',
            'data' => [
                'year' => (int) $year,
                'yearly_total' => $yearlyTotal,
                'yearly_count' => $yearlyCount,
                'monthly_expenses' => $monthlyData,
            ]
        ], 200);
    }
}