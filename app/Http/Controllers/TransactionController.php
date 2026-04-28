<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\Balance;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    use ApiResponse;

    // ==================== PUBLIC ENDPOINTS ====================

    /**
     * Get transactions for public (landing page) - limited data
     */
    public function publicIndex(Request $request)
    {
        $query = Transaction::with(['type:id,name'])
            ->select(['id', 'id_settlement', 'mutation', 'type_id', 'amount', 'balance_after', 'transaction_date', 'description']);

        // Filter by mutation (IN/OUT)
        if ($request->has('mutation')) {
            $query->where('mutation', strtoupper($request->mutation));
        }

        // Filter by type
        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Filter by month/year
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('transaction_date', $request->month)
                ->whereYear('transaction_date', $request->year);
        } elseif ($request->has('year')) {
            $query->whereYear('transaction_date', $request->year);
        }

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        $perPage = min($request->input('per_page', 10), 50); // max 50 for public
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, 'Data transaksi berhasil diambil');
    }

    /**
     * Get balance for public (landing page) - limited data
     */
    public function publicBalance()
    {
        $balance = Balance::current();

        return $this->successResponse([
            'balance' => (float) $balance->balance,
            'formatted_balance' => 'Rp ' . number_format($balance->balance, 0, ',', '.'),
            'last_updated' => $balance->updated_at,
        ], 'Saldo berhasil diambil');
    }

    // ==================== TRANSACTIONS ====================

    /**
     * Get all transactions with filters
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['type', 'creator:id,name']);

        // Filter by mutation (IN/OUT)
        if ($request->has('mutation')) {
            $query->where('mutation', strtoupper($request->mutation));
        }

        // Filter by type
        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Filter by month/year
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('transaction_date', $request->month)
                ->whereYear('transaction_date', $request->year);
        } elseif ($request->has('year')) {
            $query->whereYear('transaction_date', $request->year);
        }

        // Search by description or id_settlement
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id_settlement', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, 'Data transaksi berhasil diambil');
    }

    /**
     * Get single transaction
     */
    public function show(int $id)
    {
        $transaction = Transaction::with(['type', 'creator:id,name'])->find($id);

        if (!$transaction) {
            return $this->notFoundResponse("Transaksi dengan ID {$id} tidak ditemukan");
        }

        return $this->successResponse($transaction, 'Detail transaksi berhasil diambil');
    }

    /**
     * Store new transaction
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mutation' => 'required|in:IN,OUT',
            'type_id' => 'required|exists:transaction_types,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Get current balance
            $currentBalance = Balance::current();
            $balanceBefore = (float) $currentBalance->balance;

            // Calculate balance after
            $amount = (float) $validated['amount'];
            if ($validated['mutation'] === 'IN') {
                $balanceAfter = $balanceBefore + $amount;
            } else {
                // Check if sufficient balance for OUT
                if ($balanceBefore < $amount) {
                    return $this->errorResponse(
                        "Saldo tidak mencukupi. Saldo saat ini: Rp " . number_format($balanceBefore, 0, ',', '.'),
                        422
                    );
                }
                $balanceAfter = $balanceBefore - $amount;
            }

            // Generate settlement ID
            $idSettlement = Transaction::generateSettlementId();

            // Handle file upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = $idSettlement . '_' . time() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('transactions', $filename, 'public');
            }

            // Create transaction
            $transaction = Transaction::create([
                'id_settlement' => $idSettlement,
                'mutation' => $validated['mutation'],
                'type_id' => $validated['type_id'],
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? null,
                'attachment' => $attachmentPath,
                'created_by' => $request->user()->id,
            ]);

            // Update balance
            Balance::updateAfterTransaction($transaction);

            DB::commit();

            $transaction->load(['type', 'creator:id,name']);

            $mutationLabel = $validated['mutation'] === 'IN' ? 'masuk' : 'keluar';
            return $this->successResponse($transaction, "Transaksi {$mutationLabel} berhasil dicatat", 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menyimpan transaksi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update transaction (only description and attachment)
     */
    public function update(Request $request, int $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return $this->notFoundResponse("Transaksi dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file
            if ($transaction->attachment && Storage::disk('public')->exists($transaction->attachment)) {
                Storage::disk('public')->delete($transaction->attachment);
            }

            $file = $request->file('attachment');
            $filename = $transaction->id_settlement . '_' . time() . '.' . $file->getClientOriginalExtension();
            $validated['attachment'] = $file->storeAs('transactions', $filename, 'public');
        }

        $transaction->update($validated);
        $transaction->load(['type', 'creator:id,name']);

        return $this->successResponse($transaction, 'Transaksi berhasil diperbarui');
    }

    /**
     * Delete transaction (soft delete + recalculate balance)
     */
    public function destroy(Request $request, int $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return $this->notFoundResponse("Transaksi dengan ID {$id} tidak ditemukan");
        }

        DB::beginTransaction();

        try {
            // Recalculate balance - reverse the transaction
            $currentBalance = Balance::current();
            $amount = (float) $transaction->amount;

            if ($transaction->mutation === 'IN') {
                // Was income, now removing it = subtract
                $newBalance = (float) $currentBalance->balance - $amount;
            } else {
                // Was expense, now removing it = add back
                $newBalance = (float) $currentBalance->balance + $amount;
            }

            // Soft delete transaction
            $transaction->delete();

            // Update balance
            $currentBalance->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

            DB::commit();

            return $this->successResponse(null, 'Transaksi berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menghapus transaksi: ' . $e->getMessage(), 500);
        }
    }

    // ==================== BALANCE ====================

    /**
     * Get current balance
     */
    public function balance()
    {
        $balance = Balance::current();
        $balance->load('lastTransaction');

        return $this->successResponse([
            'balance' => (float) $balance->balance,
            'formatted_balance' => 'Rp ' . number_format($balance->balance, 0, ',', '.'),
            'last_transaction' => $balance->lastTransaction,
            'last_updated' => $balance->updated_at,
        ], 'Saldo berhasil diambil');
    }

    /**
     * Get summary (total in, total out, balance)
     */
    public function summary(Request $request)
    {
        $query = Transaction::query();

        // Filter by month/year
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('transaction_date', $request->month)
                ->whereYear('transaction_date', $request->year);
        } elseif ($request->has('year')) {
            $query->whereYear('transaction_date', $request->year);
        }

        $totalIn = (clone $query)->where('mutation', 'IN')->sum('amount');
        $totalOut = (clone $query)->where('mutation', 'OUT')->sum('amount');
        $countIn = (clone $query)->where('mutation', 'IN')->count();
        $countOut = (clone $query)->where('mutation', 'OUT')->count();

        $currentBalance = Balance::current();

        return $this->successResponse([
            'total_in' => (float) $totalIn,
            'total_out' => (float) $totalOut,
            'count_in' => $countIn,
            'count_out' => $countOut,
            'net' => (float) ($totalIn - $totalOut),
            'current_balance' => (float) $currentBalance->balance,
            'formatted' => [
                'total_in' => 'Rp ' . number_format($totalIn, 0, ',', '.'),
                'total_out' => 'Rp ' . number_format($totalOut, 0, ',', '.'),
                'net' => 'Rp ' . number_format($totalIn - $totalOut, 0, ',', '.'),
                'current_balance' => 'Rp ' . number_format($currentBalance->balance, 0, ',', '.'),
            ],
        ], 'Ringkasan transaksi berhasil diambil');
    }

    // ==================== TRANSACTION TYPES ====================

    /**
     * Get all transaction types
     */
    public function types(Request $request)
    {
        $query = TransactionType::query();

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $types = $query->orderBy('name')->get();

        return $this->successResponse($types, 'Data tipe transaksi berhasil diambil');
    }

    /**
     * Store new transaction type
     */
    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:transaction_types,name',
        ]);

        $type = TransactionType::create($validated);

        return $this->successResponse($type, 'Tipe transaksi berhasil ditambahkan', 201);
    }

    /**
     * Update transaction type
     */
    public function updateType(Request $request, int $id)
    {
        $type = TransactionType::find($id);

        if (!$type) {
            return $this->notFoundResponse("Tipe transaksi dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|unique:transaction_types,name,' . $id,
            'is_active' => 'sometimes|boolean',
        ]);

        $type->update($validated);

        return $this->successResponse($type, 'Tipe transaksi berhasil diperbarui');
    }

    /**
     * Delete transaction type
     */
    public function destroyType(int $id)
    {
        $type = TransactionType::find($id);

        if (!$type) {
            return $this->notFoundResponse("Tipe transaksi dengan ID {$id} tidak ditemukan");
        }

        // Check if type is used
        if ($type->transactions()->exists()) {
            return $this->errorResponse('Tipe transaksi tidak dapat dihapus karena masih digunakan', 422);
        }

        $type->delete();

        return $this->successResponse(null, 'Tipe transaksi berhasil dihapus');
    }
}
