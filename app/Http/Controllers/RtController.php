<?php

namespace App\Http\Controllers;

use App\Models\External\House;
use App\Models\External\BillIpl;
use App\Models\External\BillCash;
use App\Models\External\BillPkk;
use App\Models\External\Transaction;
use App\Models\External\Vote;
use App\Models\External\Information;
use App\Models\External\Contribution;
use App\Models\External\Complaint;
use App\Models\External\Suggestion;
use App\Models\External\Balance;
use App\Models\External\BaseExternalModel;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RtController extends Controller
{
    use ApiResponse;

    protected string $connection;

    /**
     * Validate RT parameter and set connection
     */
    protected function setConnection(string|int $rt): bool
    {
        $connection = is_numeric($rt) ? "rt{$rt}" : $rt;

        if (!in_array($connection, BaseExternalModel::$validConnections)) {
            return false;
        }

        $this->connection = $connection;
        return true;
    }

    // ==================== HOUSES ====================

    /**
     * Get all houses with relationships
     */
    public function houses(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = House::on($this->connection)
            ->with(['owner', 'occupant']);

        // Filter by block
        if ($request->has('block')) {
            $query->where('block', $request->block);
        }

        // Filter by occupied
        if ($request->has('occupied')) {
            $query->where('occupied', $request->occupied);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('block', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('occupant', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Order
        $query->orderBy('block')->orderBy('no');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data rumah RT{$rt} berhasil diambil");
    }

    /**
     * Get single house with all relationships
     */
    public function house(int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)
            ->with([
                'owner',
                'occupant',
                'billsIpl' => fn($q) => $q->latest('year_bill')->latest('month_bill')->limit(12),
                'billsCash' => fn($q) => $q->latest('year_bill')->latest('month_bill')->limit(12),
                'billsPkk' => fn($q) => $q->latest('year_bill')->latest('month_bill')->limit(12),
            ])
            ->find($id);

        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$id} tidak ditemukan");
        }

        return $this->successResponse($house, "Detail rumah berhasil diambil");
    }

    // ==================== BILLS ====================

    /**
     * Get IPL bills summary
     */
    public function billsIpl(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = BillIpl::on($this->connection)
            ->with('house:id,block,no');

        // Filter by year & month
        if ($request->has('year')) {
            $query->where('year_bill', $request->year);
        }
        if ($request->has('month')) {
            $query->where('month_bill', $request->month);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $query->orderBy('year_bill', 'desc')
            ->orderBy('month_bill', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data tagihan IPL RT{$rt} berhasil diambil");
    }

    /**
     * Get Cash bills summary
     */
    public function billsCash(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = BillCash::on($this->connection)
            ->with('house:id,block,no');

        if ($request->has('year')) {
            $query->where('year_bill', $request->year);
        }
        if ($request->has('month')) {
            $query->where('month_bill', $request->month);
        }
        if ($request->has('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $query->orderBy('year_bill', 'desc')
            ->orderBy('month_bill', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data tagihan Kas RT{$rt} berhasil diambil");
    }

    /**
     * Get PKK bills summary
     */
    public function billsPkk(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = BillPkk::on($this->connection)
            ->with('house:id,block,no');

        if ($request->has('year')) {
            $query->where('year_bill', $request->year);
        }
        if ($request->has('month')) {
            $query->where('month_bill', $request->month);
        }
        if ($request->has('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $query->orderBy('year_bill', 'desc')
            ->orderBy('month_bill', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data tagihan PKK RT{$rt} berhasil diambil");
    }

    /**
     * Get bills summary for a specific house
     */
    public function houseBills(int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)
            ->with([
                'billsIpl' => fn($q) => $q->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc'),
                'billsCash' => fn($q) => $q->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc'),
                'billsPkk' => fn($q) => $q->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc'),
            ])
            ->find($houseId);

        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $summary = [
            'house' => [
                'id' => $house->id,
                'address' => $house->full_address,
            ],
            'ipl' => [
                'unpaid_count' => $house->billsIpl->where('status', 'UNPAID')->count(),
                'unpaid_total' => $house->billsIpl->where('status', 'UNPAID')->sum('amount'),
                'bills' => $house->billsIpl,
            ],
            'cash' => [
                'unpaid_count' => $house->billsCash->where('status', 'UNPAID')->count(),
                'unpaid_total' => $house->billsCash->where('status', 'UNPAID')->sum('amount'),
                'bills' => $house->billsCash,
            ],
            'pkk' => [
                'unpaid_count' => $house->billsPkk->where('status', 'UNPAID')->count(),
                'unpaid_total' => $house->billsPkk->where('status', 'UNPAID')->sum('amount'),
                'bills' => $house->billsPkk,
            ],
        ];

        return $this->successResponse($summary, "Ringkasan tagihan berhasil diambil");
    }

    // ==================== TRANSACTIONS ====================

    /**
     * Get transactions
     */
    public function transactions(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = Transaction::on($this->connection);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', strtoupper($request->category));
        }

        // Filter by mutation
        if ($request->has('mutation')) {
            $query->where('mutation', strtoupper($request->mutation));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data transaksi RT{$rt} berhasil diambil");
    }

    /**
     * Get balance
     */
    public function balance(int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $balance = Balance::on($this->connection)->latest()->first();

        if (!$balance) {
            return $this->successResponse([
                'balance' => 0,
                'balance_pkk' => 0,
                'total' => 0,
            ], "Saldo RT{$rt}");
        }

        return $this->successResponse([
            'balance' => $balance->balance,
            'balance_pkk' => $balance->balance_pkk,
            'total' => $balance->total_balance,
            'last_updated' => $balance->updated_at,
        ], "Saldo RT{$rt}");
    }

    // ==================== VOTES ====================

    /**
     * Get votes
     */
    public function votes(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = Vote::on($this->connection)
            ->with(['options'])
            ->withCount('answers');

        // Filter active only
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data voting RT{$rt} berhasil diambil");
    }

    /**
     * Get vote detail with results
     */
    public function vote(int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $vote = Vote::on($this->connection)
            ->with(['options.answers'])
            ->withCount('answers')
            ->find($id);

        if (!$vote) {
            return $this->notFoundResponse("Voting dengan ID {$id} tidak ditemukan");
        }

        // Calculate results
        $totalVotes = $vote->answers_count;
        $results = $vote->options->map(function ($option) use ($totalVotes) {
            $count = $option->answers->count();
            return [
                'id' => $option->id,
                'option_name' => $option->option_name,
                'option_desc' => $option->option_desc,
                'vote_count' => $count,
                'percentage' => $totalVotes > 0 ? round(($count / $totalVotes) * 100, 2) : 0,
            ];
        });

        return $this->successResponse([
            'vote' => $vote->only(['id', 'title', 'description', 'start_vote', 'end_vote', 'is_active', 'is_multi_choice']),
            'total_votes' => $totalVotes,
            'results' => $results,
        ], "Detail voting berhasil diambil");
    }

    // ==================== INFORMATION ====================

    /**
     * Get information/announcements
     */
    public function information(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = Information::on($this->connection)->ordered();

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data informasi RT{$rt} berhasil diambil");
    }

    // ==================== CONTRIBUTIONS ====================

    /**
     * Get contributions/donations
     */
    public function contributions(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = Contribution::on($this->connection)
            ->withCount('transactions');

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        // Add computed attributes
        $data->getCollection()->transform(function ($contribution) {
            $contribution->total_collected = $contribution->total_collected;
            $contribution->progress_percentage = $contribution->progress_percentage;
            return $contribution;
        });

        return $this->paginatedResponse($data, "Data iuran/donasi RT{$rt} berhasil diambil");
    }

    // ==================== COMPLAINTS & SUGGESTIONS ====================

    /**
     * Get complaints
     */
    public function complaints(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = Complaint::on($this->connection)
            ->with('house:id,block,no');

        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data keluhan RT{$rt} berhasil diambil");
    }

    /**
     * Get suggestions
     */
    public function suggestions(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $query = Suggestion::on($this->connection)
            ->with('house:id,block,no');

        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data saran RT{$rt} berhasil diambil");
    }

    // ==================== DASHBOARD / SUMMARY ====================

    /**
     * Get RT dashboard summary
     */
    public function dashboard(int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $totalHouses = House::on($this->connection)->count();
        $occupiedHouses = House::on($this->connection)->where('occupied', '1')->count();

        $balance = Balance::on($this->connection)->latest()->first();

        $unpaidIpl = BillIpl::on($this->connection)->where('status', 'UNPAID')->count();
        $unpaidCash = BillCash::on($this->connection)->where('status', 'UNPAID')->count();
        $unpaidPkk = BillPkk::on($this->connection)->where('status', 'UNPAID')->count();

        $unreadComplaints = Complaint::on($this->connection)->where('is_read', '0')->count();
        $unreadSuggestions = Suggestion::on($this->connection)->where('is_read', '0')->count();

        $activeVotes = Vote::on($this->connection)->active()->count();

        return $this->successResponse([
            'rt' => $rt,
            'houses' => [
                'total' => $totalHouses,
                'occupied' => $occupiedHouses,
                'empty' => $totalHouses - $occupiedHouses,
            ],
            'balance' => [
                'main' => $balance?->balance ?? 0,
                'pkk' => $balance?->balance_pkk ?? 0,
                'total' => $balance?->total_balance ?? 0,
            ],
            'unpaid_bills' => [
                'ipl' => $unpaidIpl,
                'cash' => $unpaidCash,
                'pkk' => $unpaidPkk,
                'total' => $unpaidIpl + $unpaidCash + $unpaidPkk,
            ],
            'pending' => [
                'complaints' => $unreadComplaints,
                'suggestions' => $unreadSuggestions,
            ],
            'active_votes' => $activeVotes,
        ], "Dashboard RT{$rt}");
    }
}
