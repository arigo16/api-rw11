<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\ContributorBill;
use App\Models\Transaction;
use App\Models\Balance;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContributorController extends Controller
{
    use ApiResponse;

    // ==================== CONTRIBUTORS ====================

    /**
     * Get all contributors
     */
    public function index(Request $request)
    {
        $query = Contributor::with('transactionType');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', strtoupper($request->type));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('type')->orderBy('code');

        if ($request->boolean('all')) {
            return $this->successResponse($query->get(), 'Data contributor berhasil diambil');
        }

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, 'Data contributor berhasil diambil');
    }

    /**
     * Get single contributor with bills summary
     */
    public function show(int $id)
    {
        $contributor = Contributor::with(['transactionType', 'bills' => function ($q) {
            $q->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc');
        }])->find($id);

        if (!$contributor) {
            return $this->notFoundResponse("Contributor dengan ID {$id} tidak ditemukan");
        }

        return $this->successResponse($contributor, 'Detail contributor berhasil diambil');
    }

    /**
     * Store new contributor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:contributors,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:RT,RUKO,LAINNYA',
            'amount' => 'required|numeric|min:0',
            'transaction_type_id' => 'nullable|integer|exists:transaction_types,id',
            'is_active' => 'boolean',
            'start_month' => 'nullable|integer|min:1|max:12',
            'start_year' => 'nullable|integer|min:2020|max:2100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Default transaction_type_id to 1 (Iuran Warga) if not provided
        $validated['transaction_type_id'] = $validated['transaction_type_id'] ?? 1;

        $contributor = Contributor::create($validated);

        return $this->successResponse($contributor, 'Contributor berhasil ditambahkan', 201);
    }

    /**
     * Update contributor
     */
    public function update(Request $request, int $id)
    {
        $contributor = Contributor::find($id);

        if (!$contributor) {
            return $this->notFoundResponse("Contributor dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:contributors,code,' . $id,
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:RT,RUKO,LAINNYA',
            'amount' => 'sometimes|numeric|min:0',
            'transaction_type_id' => 'nullable|integer|exists:transaction_types,id',
            'is_active' => 'boolean',
            'start_month' => 'nullable|integer|min:1|max:12',
            'start_year' => 'nullable|integer|min:2020|max:2100',
            'notes' => 'nullable|string|max:500',
        ]);

        $contributor->update($validated);

        return $this->successResponse($contributor, 'Contributor berhasil diperbarui');
    }

    /**
     * Delete contributor
     */
    public function destroy(int $id)
    {
        $contributor = Contributor::find($id);

        if (!$contributor) {
            return $this->notFoundResponse("Contributor dengan ID {$id} tidak ditemukan");
        }

        // Check if has paid bills
        if ($contributor->bills()->paid()->exists()) {
            return $this->errorResponse('Contributor tidak dapat dihapus karena memiliki tagihan yang sudah dibayar', 422);
        }

        // Delete unpaid bills first
        $contributor->bills()->unpaid()->delete();
        $contributor->delete();

        return $this->successResponse(null, 'Contributor berhasil dihapus');
    }

    // ==================== CONTRIBUTOR BILLS ====================

    /**
     * Get all bills with filters
     */
    public function bills(Request $request)
    {
        $query = ContributorBill::with(['contributor', 'paidByUser:id,name']);

        // Filter by contributor
        if ($request->has('contributor_id')) {
            $query->where('contributor_id', $request->contributor_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', strtoupper($request->status));
        }

        // Filter by year
        if ($request->has('year')) {
            $query->where('year_bill', $request->year);
        }

        // Filter by month
        if ($request->has('month')) {
            $query->where('month_bill', $request->month);
        }

        // Filter by contributor type
        if ($request->has('type')) {
            $query->whereHas('contributor', function ($q) use ($request) {
                $q->where('type', strtoupper($request->type));
            });
        }

        $query->orderBy('year_bill', 'desc')
            ->orderBy('month_bill', 'desc')
            ->orderBy('contributor_id');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, 'Data tagihan berhasil diambil');
    }

    /**
     * Get single bill
     */
    public function showBill(int $id)
    {
        $bill = ContributorBill::with(['contributor', 'transaction', 'paidByUser:id,name'])->find($id);

        if (!$bill) {
            return $this->notFoundResponse("Tagihan dengan ID {$id} tidak ditemukan");
        }

        return $this->successResponse($bill, 'Detail tagihan berhasil diambil');
    }

    /**
     * Generate bills for a specific period
     */
    public function generateBills(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'contributor_ids' => 'nullable|array',
            'contributor_ids.*' => 'integer|exists:contributors,id',
        ]);

        $year = $validated['year'];
        $month = $validated['month'];

        // Get active contributors that should have bills for this period
        $query = Contributor::active()
            ->where(function ($q) use ($year, $month) {
                // Has start period set and this period is >= start period
                $q->whereNotNull('start_year')
                    ->whereNotNull('start_month')
                    ->where(function ($q2) use ($year, $month) {
                        $q2->where('start_year', '<', $year)
                            ->orWhere(function ($q3) use ($year, $month) {
                                $q3->where('start_year', $year)
                                    ->where('start_month', '<=', $month);
                            });
                    });
            });

        // Filter specific contributors if provided
        if (!empty($validated['contributor_ids'])) {
            $query->whereIn('id', $validated['contributor_ids']);
        }

        $contributors = $query->get();

        if ($contributors->isEmpty()) {
            return $this->errorResponse('Tidak ada contributor yang eligible untuk periode ini', 422);
        }

        $created = 0;
        $skipped = 0;

        foreach ($contributors as $contributor) {
            // Check if bill already exists
            $exists = ContributorBill::where('contributor_id', $contributor->id)
                ->where('year_bill', $year)
                ->where('month_bill', $month)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            ContributorBill::create([
                'contributor_id' => $contributor->id,
                'year_bill' => $year,
                'month_bill' => $month,
                'amount' => $contributor->amount,
                'status' => ContributorBill::STATUS_UNPAID,
            ]);

            $created++;
        }

        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $this->successResponse([
            'period' => $months[$month] . ' ' . $year,
            'created' => $created,
            'skipped' => $skipped,
        ], "Berhasil generate {$created} tagihan untuk {$months[$month]} {$year}");
    }

    /**
     * Pay a bill - creates transaction and updates balance
     */
    public function payBill(Request $request, int $id)
    {
        $bill = ContributorBill::with('contributor')->find($id);

        if (!$bill) {
            return $this->notFoundResponse("Tagihan dengan ID {$id} tidak ditemukan");
        }

        if ($bill->status === ContributorBill::STATUS_PAID) {
            return $this->errorResponse('Tagihan sudah dibayar', 422);
        }

        $validated = $request->validate([
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Get current balance
            $currentBalance = Balance::current();
            $balanceBefore = (float) $currentBalance->balance;
            $amount = (float) $bill->amount;
            $balanceAfter = $balanceBefore + $amount;

            // Generate settlement ID
            $idSettlement = Transaction::generateSettlementId();

            // Handle file upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = $idSettlement . '_' . time() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('transactions', $filename, 'public');
            }

            // Create transaction with dynamic type from contributor
            $transactionTypeId = $bill->contributor->transaction_type_id ?? 1;
            $transaction = Transaction::create([
                'id_settlement' => $idSettlement,
                'mutation' => 'IN',
                'type_id' => $transactionTypeId,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $validated['paid_at'] ?? now(),
                'description' => "Iuran {$bill->contributor->name} - {$bill->period_label}",
                'attachment' => $attachmentPath,
                'created_by' => $request->user()->id,
            ]);

            // Update balance
            Balance::updateAfterTransaction($transaction);

            // Update bill
            $bill->update([
                'status' => ContributorBill::STATUS_PAID,
                'transaction_id' => $transaction->id,
                'paid_at' => $validated['paid_at'] ?? now(),
                'paid_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            $bill->load(['contributor', 'transaction', 'paidByUser:id,name']);

            return $this->successResponse($bill, 'Pembayaran berhasil dicatat');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal memproses pembayaran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Unpay a bill - reverses the transaction
     */
    public function unpayBill(Request $request, int $id)
    {
        $bill = ContributorBill::with('transaction')->find($id);

        if (!$bill) {
            return $this->notFoundResponse("Tagihan dengan ID {$id} tidak ditemukan");
        }

        if ($bill->status !== ContributorBill::STATUS_PAID) {
            return $this->errorResponse('Tagihan belum dibayar', 422);
        }

        DB::beginTransaction();

        try {
            // Reverse balance if transaction exists
            if ($bill->transaction) {
                $currentBalance = Balance::current();
                $amount = (float) $bill->transaction->amount;
                $newBalance = (float) $currentBalance->balance - $amount;

                // Soft delete transaction
                $bill->transaction->delete();

                // Update balance
                $currentBalance->update([
                    'balance' => $newBalance,
                    'updated_at' => now(),
                ]);
            }

            // Update bill
            $bill->update([
                'status' => ContributorBill::STATUS_UNPAID,
                'transaction_id' => null,
                'paid_at' => null,
                'paid_by' => null,
            ]);

            DB::commit();

            $bill->load('contributor');

            return $this->successResponse($bill, 'Status pembayaran berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal membatalkan pembayaran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a bill (only unpaid)
     */
    public function destroyBill(int $id)
    {
        $bill = ContributorBill::find($id);

        if (!$bill) {
            return $this->notFoundResponse("Tagihan dengan ID {$id} tidak ditemukan");
        }

        if ($bill->status === ContributorBill::STATUS_PAID) {
            return $this->errorResponse('Tagihan yang sudah dibayar tidak dapat dihapus', 422);
        }

        $bill->delete();

        return $this->successResponse(null, 'Tagihan berhasil dihapus');
    }

    /**
     * Get bills summary
     */
    public function billsSummary(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $summary = ContributorBill::selectRaw('
                month_bill,
                COUNT(*) as total_bills,
                SUM(CASE WHEN status = "PAID" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "UNPAID" THEN 1 ELSE 0 END) as unpaid_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = "PAID" THEN amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = "UNPAID" THEN amount ELSE 0 END) as unpaid_amount
            ')
            ->where('year_bill', $year)
            ->groupBy('month_bill')
            ->orderBy('month_bill')
            ->get();

        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $result = $summary->map(function ($item) use ($months) {
            return [
                'month' => $item->month_bill,
                'month_name' => $months[$item->month_bill],
                'total_bills' => $item->total_bills,
                'paid_count' => (int) $item->paid_count,
                'unpaid_count' => (int) $item->unpaid_count,
                'total_amount' => (float) $item->total_amount,
                'paid_amount' => (float) $item->paid_amount,
                'unpaid_amount' => (float) $item->unpaid_amount,
            ];
        });

        return $this->successResponse([
            'year' => (int) $year,
            'months' => $result,
            'totals' => [
                'total_bills' => $summary->sum('total_bills'),
                'paid_count' => $summary->sum('paid_count'),
                'unpaid_count' => $summary->sum('unpaid_count'),
                'total_amount' => (float) $summary->sum('total_amount'),
                'paid_amount' => (float) $summary->sum('paid_amount'),
                'unpaid_amount' => (float) $summary->sum('unpaid_amount'),
            ],
        ], 'Ringkasan tagihan berhasil diambil');
    }
}
