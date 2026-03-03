<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Asset::query();

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('kategori')->orderBy('nama');

        $perPage = $request->input('per_page', 15);
        $assets = $query->paginate($perPage);

        return $this->paginatedResponse($assets, 'Data assets retrieved successfully');
    }

    public function show(Asset $asset)
    {
        return $this->successResponse($asset, 'Data asset retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
            'kondisi' => 'nullable|in:baik,rusak_ringan,rusak_berat',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'jumlah' => 'nullable|integer|min:1',
            'tanggal_perolehan' => 'nullable|date',
        ], [
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'foto.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('assets', $filename, 'public');
            $data['foto'] = $path;
        }

        $asset = Asset::create($data);

        return $this->createdResponse($asset, 'Asset berhasil ditambahkan');
    }

    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'kategori' => 'sometimes|required|string|max:255',
            'kondisi' => 'nullable|in:baik,rusak_ringan,rusak_berat',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'jumlah' => 'nullable|integer|min:1',
            'tanggal_perolehan' => 'nullable|date',
        ], [
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'foto.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($asset->foto && Storage::disk('public')->exists($asset->foto)) {
                Storage::disk('public')->delete($asset->foto);
            }

            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('assets', $filename, 'public');
            $data['foto'] = $path;
        }

        $asset->update($data);

        return $this->successResponse($asset, 'Asset berhasil diupdate');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return $this->successResponse(null, 'Asset berhasil dihapus');
    }

    public function byKategori()
    {
        $assets = Asset::orderBy('nama')
            ->get()
            ->groupBy('kategori');

        return $this->successResponse($assets, 'Data assets by kategori retrieved successfully');
    }
}
