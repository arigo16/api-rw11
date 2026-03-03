<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Berita::with('author:id,name');

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->boolean('published_only', false)) {
            $query->published();
        }

        if ($request->has('search')) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $berita = $query->paginate($perPage);

        return $this->paginatedResponse($berita, 'Data berita retrieved successfully');
    }

    public function published(Request $request)
    {
        $query = Berita::with('author:id,name')->published();

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('search')) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('published_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $berita = $query->paginate($perPage);

        return $this->paginatedResponse($berita, 'Data berita retrieved successfully');
    }

    public function show(Berita $berita)
    {
        $berita->load('author:id,name');

        return $this->successResponse($berita, 'Data berita retrieved successfully');
    }

    public function showBySlug(string $slug)
    {
        $berita = Berita::with('author:id,name')
            ->where('slug', $slug)
            ->first();

        if (!$berita) {
            return $this->notFoundResponse('Berita tidak ditemukan');
        }

        return $this->successResponse($berita, 'Data berita retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'ringkasan' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'kategori' => 'nullable|string|max:100',
            'is_published' => 'nullable|boolean',
        ], [
            'thumbnail.image' => 'File harus berupa gambar',
            'thumbnail.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'thumbnail.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $data = $request->except('thumbnail');
        $data['author_id'] = $request->user()->id;

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('berita', $filename, 'public');
            $data['thumbnail'] = $path;
        }

        if ($request->boolean('is_published') && !$request->has('published_at')) {
            $data['published_at'] = now();
        }

        $berita = Berita::create($data);

        return $this->createdResponse($berita, 'Berita berhasil ditambahkan');
    }

    public function update(Request $request, Berita $berita)
    {
        $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'konten' => 'sometimes|required|string',
            'ringkasan' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'kategori' => 'nullable|string|max:100',
            'is_published' => 'nullable|boolean',
        ], [
            'thumbnail.image' => 'File harus berupa gambar',
            'thumbnail.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'thumbnail.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $data = $request->except('thumbnail');

        if ($request->hasFile('thumbnail')) {
            // Hapus thumbnail lama jika ada
            if ($berita->thumbnail && Storage::disk('public')->exists($berita->thumbnail)) {
                Storage::disk('public')->delete($berita->thumbnail);
            }

            $file = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('berita', $filename, 'public');
            $data['thumbnail'] = $path;
        }

        if ($request->boolean('is_published') && !$berita->is_published) {
            $data['published_at'] = now();
        }

        $berita->update($data);

        return $this->successResponse($berita, 'Berita berhasil diupdate');
    }

    public function destroy(Berita $berita)
    {
        $berita->delete();

        return $this->successResponse(null, 'Berita berhasil dihapus');
    }

    public function publish(Berita $berita)
    {
        $berita->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return $this->successResponse($berita, 'Berita berhasil dipublish');
    }

    public function unpublish(Berita $berita)
    {
        $berita->update([
            'is_published' => false,
        ]);

        return $this->successResponse($berita, 'Berita berhasil di-unpublish');
    }
}
