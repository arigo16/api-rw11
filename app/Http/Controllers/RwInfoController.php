<?php

namespace App\Http\Controllers;

use App\Models\RwInfo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RwInfoController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->successResponse(RwInfo::getAllAsArray(), 'Data RW info retrieved successfully');
    }

    public function show(string $key)
    {
        $info = RwInfo::where('key', $key)->first();

        if (!$info) {
            return $this->notFoundResponse('Key tidak ditemukan');
        }

        $decoded = json_decode($info->value, true);

        return $this->successResponse([
            'key' => $info->key,
            'value' => $decoded !== null ? $decoded : $info->value,
        ], 'Data retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
        ]);

        $value = is_array($request->value) ? json_encode($request->value) : $request->value;

        $info = RwInfo::updateOrCreate(
            ['key' => $request->key],
            ['value' => $value]
        );

        return $this->createdResponse($info, 'Info berhasil disimpan');
    }

    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'required',
        ]);

        $info = RwInfo::where('key', $key)->first();

        if (!$info) {
            return $this->notFoundResponse('Key tidak ditemukan');
        }

        $value = is_array($request->value) ? json_encode($request->value) : $request->value;
        $info->update(['value' => $value]);

        return $this->successResponse($info, 'Info berhasil diupdate');
    }

    public function destroy(string $key)
    {
        $info = RwInfo::where('key', $key)->first();

        if (!$info) {
            return $this->notFoundResponse('Key tidak ditemukan');
        }

        $info->delete();

        return $this->successResponse(null, 'Info berhasil dihapus');
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
        ]);

        foreach ($request->data as $key => $value) {
            RwInfo::setValue($key, $value);
        }

        return $this->successResponse(RwInfo::getAllAsArray(), 'Info berhasil diupdate');
    }
}
