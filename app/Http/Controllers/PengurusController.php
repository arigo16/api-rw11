<?php

namespace App\Http\Controllers;

use App\Models\Pengurus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PengurusController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Pengurus::query();

        if ($request->has('bidang')) {
            $query->where('bidang', $request->bidang);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('sequence')->orderBy('bidang');

        $perPage = $request->input('per_page', 15);
        $pengurus = $query->paginate($perPage);

        return $this->paginatedResponse($pengurus, 'Data pengurus retrieved successfully');
    }

    public function show(Pengurus $pengurus)
    {
        return $this->successResponse($pengurus, 'Data pengurus retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'bidang' => 'required|string|max:255',
            'periode' => 'nullable|string|max:100',
            'sequence' => 'nullable|integer',
            'foto' => 'nullable|string',
        ]);

        $pengurus = Pengurus::create($request->all());

        return $this->createdResponse($pengurus, 'Pengurus berhasil ditambahkan');
    }

    public function update(Request $request, Pengurus $pengurus)
    {
        $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'jabatan' => 'sometimes|required|string|max:255',
            'bidang' => 'sometimes|required|string|max:255',
            'periode' => 'nullable|string|max:100',
            'sequence' => 'nullable|integer',
            'foto' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $pengurus->update($request->all());

        return $this->successResponse($pengurus, 'Pengurus berhasil diupdate');
    }

    public function destroy(Pengurus $pengurus)
    {
        $pengurus->delete();

        return $this->successResponse(null, 'Pengurus berhasil dihapus');
    }

    public function byBidang()
    {
        $pengurus = Pengurus::where('is_active', true)
            ->orderBy('sequence')
            ->get()
            ->groupBy('bidang');

        return $this->successResponse($pengurus, 'Data pengurus by bidang retrieved successfully');
    }
}
