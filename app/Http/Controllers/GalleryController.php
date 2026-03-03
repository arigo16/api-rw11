<?php

namespace App\Http\Controllers;

use App\Models\GalleryEvent;
use App\Models\GalleryPhoto;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = GalleryEvent::with('photos');

        if ($request->has('search')) {
            $query->where('nama_event', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('tanggal_event', 'desc');

        $perPage = $request->input('per_page', 15);
        $events = $query->paginate($perPage);

        return $this->paginatedResponse($events, 'Data gallery retrieved successfully');
    }

    public function show(GalleryEvent $gallery)
    {
        $gallery->load('photos');

        return $this->successResponse($gallery, 'Data gallery retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_event' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_event' => 'nullable|date',
            'lokasi' => 'nullable|string|max:255',
        ]);

        $event = GalleryEvent::create($request->all());

        return $this->createdResponse($event, 'Event gallery berhasil ditambahkan');
    }

    public function update(Request $request, GalleryEvent $gallery)
    {
        $request->validate([
            'nama_event' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_event' => 'nullable|date',
            'lokasi' => 'nullable|string|max:255',
        ]);

        $gallery->update($request->all());

        return $this->successResponse($gallery, 'Event gallery berhasil diupdate');
    }

    public function destroy(GalleryEvent $gallery)
    {
        $gallery->delete();

        return $this->successResponse(null, 'Event gallery berhasil dihapus');
    }

    // Photo management
    public function addPhoto(Request $request, GalleryEvent $gallery)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer',
        ], [
            'foto.required' => 'Foto wajib diupload',
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'foto.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('gallery', $filename, 'public');
            $data['foto'] = $path;
        }

        $photo = $gallery->photos()->create($data);

        return $this->createdResponse($photo, 'Foto berhasil ditambahkan');
    }

    public function addPhotos(Request $request, GalleryEvent $gallery)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*.foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'photos.*.caption' => 'nullable|string|max:255',
            'photos.*.sequence' => 'nullable|integer',
        ], [
            'photos.*.foto.required' => 'Foto wajib diupload',
            'photos.*.foto.image' => 'File harus berupa gambar',
            'photos.*.foto.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'photos.*.foto.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $photos = [];
        foreach ($request->photos as $index => $photoData) {
            $data = [
                'caption' => $photoData['caption'] ?? null,
                'sequence' => $photoData['sequence'] ?? $index,
            ];

            if (isset($photoData['foto']) && $photoData['foto']->isValid()) {
                $file = $photoData['foto'];
                $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('gallery', $filename, 'public');
                $data['foto'] = $path;
            }

            $photos[] = $gallery->photos()->create($data);
        }

        return $this->createdResponse($photos, 'Foto berhasil ditambahkan');
    }

    public function updatePhoto(Request $request, GalleryPhoto $photo)
    {
        $request->validate([
            'foto' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sequence' => 'nullable|integer',
        ], [
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp',
            'foto.max' => 'Ukuran gambar maksimal 2MB',
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($photo->foto && Storage::disk('public')->exists($photo->foto)) {
                Storage::disk('public')->delete($photo->foto);
            }

            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('gallery', $filename, 'public');
            $data['foto'] = $path;
        }

        $photo->update($data);

        return $this->successResponse($photo, 'Foto berhasil diupdate');
    }

    public function deletePhoto(GalleryPhoto $photo)
    {
        $photo->delete();

        return $this->successResponse(null, 'Foto berhasil dihapus');
    }
}
