<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Dokumen::with('uploader:id,name');

        if ($request->has('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->input('search') . '%');
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $dokumen = $query->paginate($perPage);

        return $this->paginatedResponse($dokumen, 'Data dokumen retrieved successfully');
    }

    public function publicDocs(Request $request)
    {
        $query = Dokumen::with('uploader:id,name')->public();

        if ($request->has('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->input('search') . '%');
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $dokumen = $query->paginate($perPage);

        return $this->paginatedResponse($dokumen, 'Data dokumen retrieved successfully');
    }

    public function show(Dokumen $dokumen)
    {
        $dokumen->load('uploader:id,name');

        return $this->successResponse($dokumen, 'Data dokumen retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240',
            'is_public' => 'nullable|boolean',
        ], [
            'file.required' => 'File dokumen wajib diupload',
            'file.file' => 'Upload harus berupa file',
            'file.mimes' => 'Format file harus pdf, doc, docx, xls, xlsx, ppt, atau pptx',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        $data = $request->except('file');
        $data['uploaded_by'] = $request->user()->id;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('dokumen', $filename, 'public');

            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $dokumen = Dokumen::create($data);

        return $this->createdResponse($dokumen, 'Dokumen berhasil ditambahkan');
    }

    public function update(Request $request, Dokumen $dokumen)
    {
        $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240',
            'is_public' => 'nullable|boolean',
        ], [
            'file.file' => 'Upload harus berupa file',
            'file.mimes' => 'Format file harus pdf, doc, docx, xls, xlsx, ppt, atau pptx',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        $data = $request->except('file');

        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
                Storage::disk('public')->delete($dokumen->file_path);
            }

            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('dokumen', $filename, 'public');

            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $dokumen->update($data);

        return $this->successResponse($dokumen, 'Dokumen berhasil diupdate');
    }

    public function destroy(Dokumen $dokumen)
    {
        $dokumen->delete();

        return $this->successResponse(null, 'Dokumen berhasil dihapus');
    }

    public function togglePublic(Dokumen $dokumen)
    {
        $dokumen->update([
            'is_public' => !$dokumen->is_public,
        ]);

        $message = $dokumen->is_public ? 'Dokumen sekarang public' : 'Dokumen sekarang private';

        return $this->successResponse($dokumen, $message);
    }
}
