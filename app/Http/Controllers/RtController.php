<?php

namespace App\Http\Controllers;

use App\Models\External\House;
use App\Models\External\HouseMember;
use App\Models\External\HouseOwner;
use App\Models\External\HouseOccupied;
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
use Illuminate\Support\Facades\Storage;

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
                'members',
                'billsIpl' => fn($q) => $q->withTrashed()->latest('year_bill')->latest('month_bill'),
                'billsCash' => fn($q) => $q->withTrashed()->latest('year_bill')->latest('month_bill'),
                'billsPkk' => fn($q) => $q->withTrashed()->latest('year_bill')->latest('month_bill'),
            ])
            ->find($id);

        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$id} tidak ditemukan");
        }

        return $this->successResponse($house, "Detail rumah berhasil diambil");
    }

    /**
     * Create a new house
     */
    public function storeHouse(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $validated = $request->validate([
            'block' => 'required|string|max:10',
            'no' => 'required|integer|min:1',
            'occupied' => 'boolean',
            'occupied_by_owner' => 'boolean',
            'pay_ipl' => 'boolean',
            'ipl_amount' => 'nullable|numeric|min:0',
            'pay_cash' => 'boolean',
            'cash_amount' => 'nullable|numeric|min:0',
            'pay_pkk' => 'boolean',
            'pkk_amount' => 'nullable|numeric|min:0',
        ]);

        // Check if house with same block and no already exists
        $exists = House::on($this->connection)
            ->where('block', $validated['block'])
            ->where('no', $validated['no'])
            ->exists();

        if ($exists) {
            return $this->errorResponse("Rumah Blok {$validated['block']} No. {$validated['no']} sudah ada", 422);
        }

        // Add created_by from authenticated user
        $validated['created_by'] = (string) $request->user()->id;
        $validated['updated_by'] = (string) $request->user()->id;

        $house = new House($validated);
        $house->setConnection($this->connection);
        $house->save();

        $house->load(['owner', 'occupant']);

        return $this->successResponse($house, "Rumah berhasil ditambahkan", 201);
    }

    /**
     * Update an existing house
     */
    public function updateHouse(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($id);

        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'block' => 'sometimes|string|max:10',
            'no' => 'sometimes|integer|min:1',
            'occupied' => 'sometimes|boolean',
            'occupied_by_owner' => 'sometimes|boolean',
            'pay_ipl' => 'sometimes|boolean',
            'ipl_amount' => 'nullable|numeric|min:0',
            'pay_cash' => 'sometimes|boolean',
            'cash_amount' => 'nullable|numeric|min:0',
            'pay_pkk' => 'sometimes|boolean',
            'pkk_amount' => 'nullable|numeric|min:0',
        ]);

        // Check if updating block/no would create duplicate
        if (isset($validated['block']) || isset($validated['no'])) {
            $block = $validated['block'] ?? $house->block;
            $no = $validated['no'] ?? $house->no;

            $exists = House::on($this->connection)
                ->where('block', $block)
                ->where('no', $no)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return $this->errorResponse("Rumah Blok {$block} No. {$no} sudah ada", 422);
            }
        }

        // Add updated_by from authenticated user
        $validated['updated_by'] = (string) $request->user()->id;

        $house->update($validated);
        $house->load(['owner', 'occupant']);

        return $this->successResponse($house, "Rumah berhasil diperbarui");
    }

    /**
     * Delete a house
     */
    public function destroyHouse(int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($id);

        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$id} tidak ditemukan");
        }

        // Check if house has related records
        $hasRelations = $house->billsIpl()->exists() ||
                        $house->billsCash()->exists() ||
                        $house->billsPkk()->exists() ||
                        $house->complaints()->exists() ||
                        $house->suggestions()->exists();

        if ($hasRelations) {
            return $this->errorResponse("Rumah tidak dapat dihapus karena memiliki data tagihan/keluhan/saran terkait", 422);
        }

        // Delete related owner and occupant first
        $house->owner()->delete();
        $house->occupant()->delete();

        $house->delete();

        return $this->successResponse(null, "Rumah berhasil dihapus");
    }

    // ==================== HOUSE MEMBERS ====================

    /**
     * Get house members
     */
    public function houseMembers(int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $members = HouseMember::on($this->connection)
            ->where('house_id', $houseId)
            ->get();

        return $this->successResponse([
            'house' => [
                'id' => $house->id,
                'address' => $house->full_address,
            ],
            'members' => $members,
        ], "Data anggota rumah berhasil diambil");
    }

    /**
     * Get single house member
     */
    public function houseMember(int $rt, int $houseId, int $memberId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $member = HouseMember::on($this->connection)
            ->where('house_id', $houseId)
            ->find($memberId);

        if (!$member) {
            return $this->notFoundResponse("Anggota rumah dengan ID {$memberId} tidak ditemukan");
        }

        $member->load('house:id,block,no');

        return $this->successResponse($member, "Detail anggota rumah berhasil diambil");
    }

    /**
     * Store house member (form-data with file upload)
     */
    public function storeHouseMember(Request $request, int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'handphone' => 'nullable|string|max:20',
            'religion' => 'nullable|string|in:' . implode(',', HouseMember::RELIGIONS),
            'relatives' => 'nullable|string|in:' . implode(',', HouseMember::RELATIVES),
            'is_domicile' => 'nullable|boolean',
            'set_as_occupant' => 'nullable|boolean',
        ]);

        $validated['house_id'] = $houseId;
        $setAsOccupant = $validated['set_as_occupant'] ?? false;
        unset($validated['set_as_occupant']);

        // Handle file upload
        if ($request->hasFile('kk')) {
            $file = $request->file('kk');
            $filename = 'kk_' . $houseId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("rt{$rt}/kk", $filename, 'public');
            $validated['kk'] = $path;
        }

        $member = new HouseMember($validated);
        $member->setConnection($this->connection);
        $member->save();

        // If set_as_occupant is true, also create/update house_occupied
        if ($setAsOccupant) {
            $occupantData = [
                'house_id' => $houseId,
                'name' => $validated['name'],
                'handphone' => $validated['handphone'] ?? null,
                'religion' => $validated['religion'] ?? null,
                'kk' => $validated['kk'] ?? null,
            ];

            $existingOccupant = HouseOccupied::on($this->connection)
                ->where('house_id', $houseId)
                ->first();

            if ($existingOccupant) {
                $existingOccupant->update($occupantData);
            } else {
                $occupant = new HouseOccupied($occupantData);
                $occupant->setConnection($this->connection);
                $occupant->save();
            }
        }

        $member->load('house:id,block,no');

        return $this->successResponse($member, "Anggota rumah berhasil ditambahkan", 201);
    }

    /**
     * Update house member (form-data with optional file upload)
     */
    public function updateHouseMember(Request $request, int $rt, int $houseId, int $memberId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $member = HouseMember::on($this->connection)
            ->where('house_id', $houseId)
            ->find($memberId);

        if (!$member) {
            return $this->notFoundResponse("Anggota rumah dengan ID {$memberId} tidak ditemukan");
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'kk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'handphone' => 'nullable|string|max:20',
            'religion' => 'nullable|string|in:' . implode(',', HouseMember::RELIGIONS),
            'relatives' => 'nullable|string|in:' . implode(',', HouseMember::RELATIVES),
            'is_domicile' => 'nullable|boolean',
            'set_as_occupant' => 'nullable|boolean',
        ]);

        $setAsOccupant = $validated['set_as_occupant'] ?? false;
        unset($validated['set_as_occupant']);

        // Handle file upload
        if ($request->hasFile('kk')) {
            // Delete old file if exists
            if ($member->kk && Storage::disk('public')->exists($member->kk)) {
                Storage::disk('public')->delete($member->kk);
            }

            $file = $request->file('kk');
            $filename = 'kk_' . $houseId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("rt{$rt}/kk", $filename, 'public');
            $validated['kk'] = $path;
        }

        $member->update($validated);

        // If set_as_occupant is true, also create/update house_occupied
        if ($setAsOccupant) {
            $occupantData = [
                'house_id' => $houseId,
                'name' => $member->name,
                'handphone' => $member->handphone,
                'religion' => $member->religion,
                'kk' => $member->kk,
            ];

            $existingOccupant = HouseOccupied::on($this->connection)
                ->where('house_id', $houseId)
                ->first();

            if ($existingOccupant) {
                $existingOccupant->update($occupantData);
            } else {
                $occupant = new HouseOccupied($occupantData);
                $occupant->setConnection($this->connection);
                $occupant->save();
            }
        }

        $member->load('house:id,block,no');

        return $this->successResponse($member, "Anggota rumah berhasil diperbarui");
    }

    /**
     * Delete house member (soft delete)
     */
    public function destroyHouseMember(int $rt, int $houseId, int $memberId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $member = HouseMember::on($this->connection)
            ->where('house_id', $houseId)
            ->find($memberId);

        if (!$member) {
            return $this->notFoundResponse("Anggota rumah dengan ID {$memberId} tidak ditemukan");
        }

        $member->delete();

        return $this->successResponse(null, "Anggota rumah berhasil dihapus");
    }

    /**
     * Copy data from house_owner or house_occupied to house_member
     */
    public function copyToMember(Request $request, int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $validated = $request->validate([
            'source' => 'required|string|in:owner,occupant',
            'is_domicile' => 'required|boolean',
            'relatives' => 'required|string|in:' . implode(',', HouseMember::RELATIVES),
        ]);

        // Get source data
        if ($validated['source'] === 'owner') {
            $source = HouseOwner::on($this->connection)
                ->where('house_id', $houseId)
                ->first();

            if (!$source) {
                return $this->notFoundResponse("Data pemilik rumah tidak ditemukan");
            }
        } else {
            $source = HouseOccupied::on($this->connection)
                ->where('house_id', $houseId)
                ->first();

            if (!$source) {
                return $this->notFoundResponse("Data penghuni rumah tidak ditemukan");
            }
        }

        // Create house_member from source data
        $memberData = [
            'house_id' => $houseId,
            'name' => $source->name,
            'handphone' => $source->handphone,
            'religion' => $source->religion,
            'kk' => $source->kk,
            'is_domicile' => $validated['is_domicile'],
            'relatives' => $validated['relatives'],
        ];

        $member = new HouseMember($memberData);
        $member->setConnection($this->connection);
        $member->save();

        $member->load('house:id,block,no');

        return $this->successResponse($member, "Data berhasil disalin ke anggota rumah", 201);
    }

    // ==================== HOUSE OWNER ====================

    /**
     * Get house owner
     */
    public function houseOwner(int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $owner = HouseOwner::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        return $this->successResponse([
            'house' => [
                'id' => $house->id,
                'address' => $house->full_address,
            ],
            'owner' => $owner,
        ], "Data pemilik rumah berhasil diambil");
    }

    /**
     * Store or update house owner (form-data with file upload)
     */
    public function storeHouseOwner(Request $request, int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'handphone' => 'nullable|string|max:20',
            'sum_family' => 'nullable|string|in:' . implode(',', HouseOwner::SUM_FAMILY),
            'religion' => 'nullable|string|in:' . implode(',', HouseMember::RELIGIONS),
            'is_occupied' => 'nullable|boolean',
        ]);

        $isOccupied = $validated['is_occupied'] ?? false;
        unset($validated['is_occupied']);

        // Check if owner already exists
        $owner = HouseOwner::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        // Handle file upload
        if ($request->hasFile('kk')) {
            // Delete old file if exists
            if ($owner && $owner->kk && Storage::disk('public')->exists($owner->kk)) {
                Storage::disk('public')->delete($owner->kk);
            }

            $file = $request->file('kk');
            $filename = 'kk_owner_' . $houseId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("rt{$rt}/kk", $filename, 'public');
            $validated['kk'] = $path;
        }

        if ($owner) {
            // Update existing owner
            $owner->update($validated);
            $message = "Data pemilik rumah berhasil diperbarui";
        } else {
            // Create new owner
            $validated['house_id'] = $houseId;
            $owner = new HouseOwner($validated);
            $owner->setConnection($this->connection);
            $owner->save();
            $message = "Data pemilik rumah berhasil ditambahkan";
        }

        // If is_occupied is true, soft delete existing occupant and create new one from owner data
        if ($isOccupied) {
            // Soft delete existing occupant if any
            $existingOccupant = HouseOccupied::on($this->connection)
                ->where('house_id', $houseId)
                ->first();

            if ($existingOccupant) {
                $existingOccupant->delete();
            }

            // Create new occupant from owner data
            $occupantData = [
                'house_id' => $houseId,
                'name' => $owner->name,
                'handphone' => $owner->handphone,
                'sum_family' => $owner->sum_family,
                'religion' => $owner->religion,
                'kk' => $owner->kk,
            ];

            $occupant = new HouseOccupied($occupantData);
            $occupant->setConnection($this->connection);
            $occupant->save();

            // Update house occupied_by_owner to true
            $house->occupied_by_owner = '1';
            $house->occupied = '1';
            $house->save();
        }

        $owner->load('house:id,block,no');

        return $this->successResponse($owner, $message, $owner->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Delete house owner (soft delete)
     */
    public function destroyHouseOwner(int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $owner = HouseOwner::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        if (!$owner) {
            return $this->notFoundResponse("Pemilik rumah tidak ditemukan");
        }

        // Soft delete owner (don't delete file, keep for history)
        $owner->delete();

        // Set occupied_by_owner to false (owner deleted)
        // Keep occupied = 1 if house_occupied still exists
        $house->occupied_by_owner = '0';

        // Check if house_occupied exists
        $occupant = HouseOccupied::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        // If no occupant, set occupied to 0
        if (!$occupant) {
            $house->occupied = '0';
        }

        $house->save();

        return $this->successResponse(null, "Data pemilik rumah berhasil dihapus");
    }

    // ==================== HOUSE OCCUPANT ====================

    /**
     * Get house occupant
     */
    public function houseOccupant(int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $occupant = HouseOccupied::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        return $this->successResponse([
            'house' => [
                'id' => $house->id,
                'address' => $house->full_address,
            ],
            'occupant' => $occupant,
        ], "Data penghuni rumah berhasil diambil");
    }

    /**
     * Store or update house occupant (form-data with file upload)
     */
    public function storeHouseOccupant(Request $request, int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kk' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'handphone' => 'nullable|string|max:20',
            'sum_family' => 'nullable|string|in:' . implode(',', HouseOwner::SUM_FAMILY),
            'religion' => 'nullable|string|in:' . implode(',', HouseMember::RELIGIONS),
            'is_owner' => 'nullable|boolean',
        ]);

        $isOwner = $validated['is_owner'] ?? false;
        unset($validated['is_owner']);

        // Check if occupant already exists
        $occupant = HouseOccupied::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        // Handle file upload
        if ($request->hasFile('kk')) {
            // Delete old file if exists
            if ($occupant && $occupant->kk && Storage::disk('public')->exists($occupant->kk)) {
                Storage::disk('public')->delete($occupant->kk);
            }

            $file = $request->file('kk');
            $filename = 'kk_occupant_' . $houseId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("rt{$rt}/kk", $filename, 'public');
            $validated['kk'] = $path;
        }

        if ($occupant) {
            // Update existing occupant
            $occupant->update($validated);
            $message = "Data penghuni rumah berhasil diperbarui";
        } else {
            // Create new occupant
            $validated['house_id'] = $houseId;
            $occupant = new HouseOccupied($validated);
            $occupant->setConnection($this->connection);
            $occupant->save();
            $message = "Data penghuni rumah berhasil ditambahkan";
        }

        // If is_owner is true, soft delete existing owner and create new one from occupant data
        if ($isOwner) {
            // Soft delete existing owner if any
            $existingOwner = HouseOwner::on($this->connection)
                ->where('house_id', $houseId)
                ->first();

            if ($existingOwner) {
                $existingOwner->delete();
            }

            // Create new owner from occupant data
            $ownerData = [
                'house_id' => $houseId,
                'name' => $occupant->name,
                'handphone' => $occupant->handphone,
                'sum_family' => $occupant->sum_family,
                'religion' => $occupant->religion,
                'kk' => $occupant->kk,
            ];

            $owner = new HouseOwner($ownerData);
            $owner->setConnection($this->connection);
            $owner->save();

            // Update house occupied and occupied_by_owner to true
            $house->occupied = '1';
            $house->occupied_by_owner = '1';
            $house->save();
        } else {
            // Just set occupied to true (not owner)
            $house->occupied = '1';
            $house->save();
        }

        $occupant->load('house:id,block,no');

        return $this->successResponse($occupant, $message, $occupant->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Delete house occupant (soft delete)
     */
    public function destroyHouseOccupant(int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->notFoundResponse("Rumah dengan ID {$houseId} tidak ditemukan");
        }

        $occupant = HouseOccupied::on($this->connection)
            ->where('house_id', $houseId)
            ->first();

        if (!$occupant) {
            return $this->notFoundResponse("Penghuni rumah tidak ditemukan");
        }

        // Soft delete occupant (don't delete file, keep for history)
        $occupant->delete();

        // Set occupied and occupied_by_owner to 0
        $house->occupied = '0';
        $house->occupied_by_owner = '0';
        $house->save();

        return $this->successResponse(null, "Data penghuni rumah berhasil dihapus");
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

    // ==================== BILLS CRUD ====================

    /**
     * Create IPL bill
     */
    public function storeBillIpl(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $validated = $request->validate([
            'house_id' => 'required|integer',
            'year_bill' => 'required|integer|min:2020|max:2100',
            'month_bill' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
        ]);

        // Check if house exists
        $house = House::on($this->connection)->find($validated['house_id']);
        if (!$house) {
            return $this->errorResponse("Rumah dengan ID {$validated['house_id']} tidak ditemukan", 404);
        }

        // Check duplicate
        $exists = BillIpl::on($this->connection)
            ->where('house_id', $validated['house_id'])
            ->where('year_bill', $validated['year_bill'])
            ->where('month_bill', $validated['month_bill'])
            ->exists();

        if ($exists) {
            return $this->errorResponse("Tagihan IPL untuk rumah ini pada periode tersebut sudah ada", 422);
        }

        $validated['status'] = $validated['status'] ?? 'UNPAID';
        $validated['created_by'] = (string) $request->user()->id;
        $validated['updated_by'] = (string) $request->user()->id;

        $bill = new BillIpl($validated);
        $bill->setConnection($this->connection);
        $bill->save();

        $bill->load('house:id,block,no');

        return $this->successResponse($bill, "Tagihan IPL berhasil ditambahkan", 201);
    }

    /**
     * Update IPL bill
     */
    public function updateBillIpl(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $bill = BillIpl::on($this->connection)->find($id);
        if (!$bill) {
            return $this->notFoundResponse("Tagihan IPL dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'house_id' => 'sometimes|integer',
            'year_bill' => 'sometimes|integer|min:2020|max:2100',
            'month_bill' => 'sometimes|integer|min:1|max:12',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
            'transactions_id' => 'nullable|integer',
        ]);

        $validated['updated_by'] = (string) $request->user()->id;

        $bill->update($validated);
        $bill->load('house:id,block,no');

        return $this->successResponse($bill, "Tagihan IPL berhasil diperbarui");
    }

    /**
     * Delete IPL bill
     */
    public function destroyBillIpl(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $bill = BillIpl::on($this->connection)->find($id);
        if (!$bill) {
            return $this->notFoundResponse("Tagihan IPL dengan ID {$id} tidak ditemukan");
        }

        if ($bill->status === 'PAID') {
            return $this->errorResponse("Tagihan yang sudah dibayar tidak dapat dihapus", 422);
        }

        $bill->deleted_by = (string) $request->user()->id;
        $bill->save();
        $bill->delete();

        return $this->successResponse(null, "Tagihan IPL berhasil dihapus");
    }

    /**
     * Create Cash bill
     */
    public function storeBillCash(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $validated = $request->validate([
            'house_id' => 'required|integer',
            'year_bill' => 'required|integer|min:2020|max:2100',
            'month_bill' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
        ]);

        $house = House::on($this->connection)->find($validated['house_id']);
        if (!$house) {
            return $this->errorResponse("Rumah dengan ID {$validated['house_id']} tidak ditemukan", 404);
        }

        $exists = BillCash::on($this->connection)
            ->where('house_id', $validated['house_id'])
            ->where('year_bill', $validated['year_bill'])
            ->where('month_bill', $validated['month_bill'])
            ->exists();

        if ($exists) {
            return $this->errorResponse("Tagihan Kas untuk rumah ini pada periode tersebut sudah ada", 422);
        }

        $validated['status'] = $validated['status'] ?? 'UNPAID';
        $validated['created_by'] = (string) $request->user()->id;
        $validated['updated_by'] = (string) $request->user()->id;

        $bill = new BillCash($validated);
        $bill->setConnection($this->connection);
        $bill->save();

        $bill->load('house:id,block,no');

        return $this->successResponse($bill, "Tagihan Kas berhasil ditambahkan", 201);
    }

    /**
     * Update Cash bill
     */
    public function updateBillCash(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $bill = BillCash::on($this->connection)->find($id);
        if (!$bill) {
            return $this->notFoundResponse("Tagihan Kas dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'house_id' => 'sometimes|integer',
            'year_bill' => 'sometimes|integer|min:2020|max:2100',
            'month_bill' => 'sometimes|integer|min:1|max:12',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
            'transactions_id' => 'nullable|integer',
        ]);

        $validated['updated_by'] = (string) $request->user()->id;

        $bill->update($validated);
        $bill->load('house:id,block,no');

        return $this->successResponse($bill, "Tagihan Kas berhasil diperbarui");
    }

    /**
     * Delete Cash bill
     */
    public function destroyBillCash(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $bill = BillCash::on($this->connection)->find($id);
        if (!$bill) {
            return $this->notFoundResponse("Tagihan Kas dengan ID {$id} tidak ditemukan");
        }

        if ($bill->status === 'PAID') {
            return $this->errorResponse("Tagihan yang sudah dibayar tidak dapat dihapus", 422);
        }

        $bill->deleted_by = (string) $request->user()->id;
        $bill->save();
        $bill->delete();

        return $this->successResponse(null, "Tagihan Kas berhasil dihapus");
    }

    /**
     * Create PKK bill
     */
    public function storeBillPkk(Request $request, int $rt)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $validated = $request->validate([
            'house_id' => 'required|integer',
            'year_bill' => 'required|integer|min:2020|max:2100',
            'month_bill' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
        ]);

        $house = House::on($this->connection)->find($validated['house_id']);
        if (!$house) {
            return $this->errorResponse("Rumah dengan ID {$validated['house_id']} tidak ditemukan", 404);
        }

        $exists = BillPkk::on($this->connection)
            ->where('house_id', $validated['house_id'])
            ->where('year_bill', $validated['year_bill'])
            ->where('month_bill', $validated['month_bill'])
            ->exists();

        if ($exists) {
            return $this->errorResponse("Tagihan PKK untuk rumah ini pada periode tersebut sudah ada", 422);
        }

        $validated['status'] = $validated['status'] ?? 'UNPAID';
        $validated['created_by'] = (string) $request->user()->id;
        $validated['updated_by'] = (string) $request->user()->id;

        $bill = new BillPkk($validated);
        $bill->setConnection($this->connection);
        $bill->save();

        $bill->load('house:id,block,no');

        return $this->successResponse($bill, "Tagihan PKK berhasil ditambahkan", 201);
    }

    /**
     * Update PKK bill
     */
    public function updateBillPkk(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $bill = BillPkk::on($this->connection)->find($id);
        if (!$bill) {
            return $this->notFoundResponse("Tagihan PKK dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'house_id' => 'sometimes|integer',
            'year_bill' => 'sometimes|integer|min:2020|max:2100',
            'month_bill' => 'sometimes|integer|min:1|max:12',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
            'transactions_id' => 'nullable|integer',
        ]);

        $validated['updated_by'] = (string) $request->user()->id;

        $bill->update($validated);
        $bill->load('house:id,block,no');

        return $this->successResponse($bill, "Tagihan PKK berhasil diperbarui");
    }

    /**
     * Delete PKK bill
     */
    public function destroyBillPkk(Request $request, int $rt, int $id)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $bill = BillPkk::on($this->connection)->find($id);
        if (!$bill) {
            return $this->notFoundResponse("Tagihan PKK dengan ID {$id} tidak ditemukan");
        }

        if ($bill->status === 'PAID') {
            return $this->errorResponse("Tagihan yang sudah dibayar tidak dapat dihapus", 422);
        }

        $bill->deleted_by = (string) $request->user()->id;
        $bill->save();
        $bill->delete();

        return $this->successResponse(null, "Tagihan PKK berhasil dihapus");
    }

    /**
     * Bulk update bills by house_id
     */
    public function bulkUpdateBills(Request $request, int $rt, int $houseId)
    {
        if (!$this->setConnection($rt)) {
            return $this->errorResponse("RT{$rt} tidak valid", 400);
        }

        $house = House::on($this->connection)->find($houseId);
        if (!$house) {
            return $this->errorResponse("Rumah dengan ID {$houseId} tidak ditemukan", 404);
        }

        $validated = $request->validate([
            'ipl' => 'sometimes|array',
            'ipl.*.id' => 'required|integer',
            'ipl.*.status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
            'ipl.*.amount' => 'sometimes|numeric|min:0',
            'ipl.*.transactions_id' => 'nullable|integer',
            'ipl.*.restore' => 'sometimes|boolean',
            'ipl.*.delete' => 'sometimes|boolean',

            'cash' => 'sometimes|array',
            'cash.*.id' => 'required|integer',
            'cash.*.status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
            'cash.*.amount' => 'sometimes|numeric|min:0',
            'cash.*.transactions_id' => 'nullable|integer',
            'cash.*.restore' => 'sometimes|boolean',
            'cash.*.delete' => 'sometimes|boolean',

            'pkk' => 'sometimes|array',
            'pkk.*.id' => 'required|integer',
            'pkk.*.status' => 'sometimes|string|in:UNPAID,PENDING,PAID',
            'pkk.*.amount' => 'sometimes|numeric|min:0',
            'pkk.*.transactions_id' => 'nullable|integer',
            'pkk.*.restore' => 'sometimes|boolean',
            'pkk.*.delete' => 'sometimes|boolean',
        ]);

        $userId = (string) $request->user()->id;
        $results = ['ipl' => [], 'cash' => [], 'pkk' => []];

        // Update IPL bills
        if (isset($validated['ipl'])) {
            foreach ($validated['ipl'] as $data) {
                $bill = BillIpl::on($this->connection)
                    ->withTrashed()
                    ->where('id', $data['id'])
                    ->where('house_id', $houseId)
                    ->first();

                if ($bill) {
                    $shouldRestore = $data['restore'] ?? false;
                    $shouldDelete = $data['delete'] ?? false;
                    unset($data['id'], $data['restore'], $data['delete']);
                    $data['updated_by'] = $userId;

                    // Handle restore/delete
                    if ($shouldRestore && $bill->trashed()) {
                        $bill->restore();
                    } elseif ($shouldDelete && !$bill->trashed()) {
                        $bill->deleted_by = $userId;
                        $bill->save();
                        $bill->delete();
                    }

                    // Update other fields
                    if (!empty($data)) {
                        $bill->update($data);
                    }

                    $results['ipl'][] = $bill->fresh();
                }
            }
        }

        // Update Cash bills
        if (isset($validated['cash'])) {
            foreach ($validated['cash'] as $data) {
                $bill = BillCash::on($this->connection)
                    ->withTrashed()
                    ->where('id', $data['id'])
                    ->where('house_id', $houseId)
                    ->first();

                if ($bill) {
                    $shouldRestore = $data['restore'] ?? false;
                    $shouldDelete = $data['delete'] ?? false;
                    unset($data['id'], $data['restore'], $data['delete']);
                    $data['updated_by'] = $userId;

                    // Handle restore/delete
                    if ($shouldRestore && $bill->trashed()) {
                        $bill->restore();
                    } elseif ($shouldDelete && !$bill->trashed()) {
                        $bill->deleted_by = $userId;
                        $bill->save();
                        $bill->delete();
                    }

                    // Update other fields
                    if (!empty($data)) {
                        $bill->update($data);
                    }

                    $results['cash'][] = $bill->fresh();
                }
            }
        }

        // Update PKK bills
        if (isset($validated['pkk'])) {
            foreach ($validated['pkk'] as $data) {
                $bill = BillPkk::on($this->connection)
                    ->withTrashed()
                    ->where('id', $data['id'])
                    ->where('house_id', $houseId)
                    ->first();

                if ($bill) {
                    $shouldRestore = $data['restore'] ?? false;
                    $shouldDelete = $data['delete'] ?? false;
                    unset($data['id'], $data['restore'], $data['delete']);
                    $data['updated_by'] = $userId;

                    // Handle restore/delete
                    if ($shouldRestore && $bill->trashed()) {
                        $bill->restore();
                    } elseif ($shouldDelete && !$bill->trashed()) {
                        $bill->deleted_by = $userId;
                        $bill->save();
                        $bill->delete();
                    }

                    // Update other fields
                    if (!empty($data)) {
                        $bill->update($data);
                    }

                    $results['pkk'][] = $bill->fresh();
                }
            }
        }

        return $this->successResponse([
            'house' => ['id' => $house->id, 'address' => $house->full_address],
            'updated' => [
                'ipl' => count($results['ipl']),
                'cash' => count($results['cash']),
                'pkk' => count($results['pkk']),
            ],
            'bills' => $results,
        ], "Tagihan berhasil diperbarui");
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
                'billsIpl' => fn($q) => $q->withTrashed()->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc'),
                'billsCash' => fn($q) => $q->withTrashed()->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc'),
                'billsPkk' => fn($q) => $q->withTrashed()->orderBy('year_bill', 'desc')->orderBy('month_bill', 'desc'),
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
