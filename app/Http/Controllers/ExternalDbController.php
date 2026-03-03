<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExternalDbController extends Controller
{
    use ApiResponse;

    protected $connection = 'rt9';

    /**
     * Get list of all tables in external database
     */
    public function tables()
    {
        $tables = DB::connection($this->connection)
            ->select('SHOW TABLES');

        $dbName = config("database.connections.{$this->connection}.database");
        $key = "Tables_in_{$dbName}";

        $tableNames = array_map(function ($table) use ($key) {
            return $table->$key;
        }, $tables);

        return $this->successResponse($tableNames, 'Daftar tabel berhasil diambil');
    }

    /**
     * Get table structure/columns
     */
    public function structure(string $table)
    {
        if (!$this->tableExists($table)) {
            return $this->notFoundResponse("Tabel '{$table}' tidak ditemukan");
        }

        $columns = DB::connection($this->connection)
            ->select("DESCRIBE {$table}");

        return $this->successResponse($columns, "Struktur tabel '{$table}' berhasil diambil");
    }

    /**
     * Get all records from a table (with pagination)
     */
    public function index(Request $request, string $table)
    {
        if (!$this->tableExists($table)) {
            return $this->notFoundResponse("Tabel '{$table}' tidak ditemukan");
        }

        $query = DB::connection($this->connection)->table($table);

        // Search functionality
        if ($request->has('search') && $request->has('search_column')) {
            $query->where($request->search_column, 'like', '%' . $request->search . '%');
        }

        // Order by
        if ($request->has('order_by')) {
            $direction = $request->input('order_direction', 'asc');
            $query->orderBy($request->order_by, $direction);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->paginatedResponse($data, "Data dari tabel '{$table}' berhasil diambil");
    }

    /**
     * Get single record by ID
     */
    public function show(Request $request, string $table, $id)
    {
        if (!$this->tableExists($table)) {
            return $this->notFoundResponse("Tabel '{$table}' tidak ditemukan");
        }

        $primaryKey = $request->input('primary_key', 'id');

        $data = DB::connection($this->connection)
            ->table($table)
            ->where($primaryKey, $id)
            ->first();

        if (!$data) {
            return $this->notFoundResponse("Data dengan {$primaryKey} = {$id} tidak ditemukan");
        }

        return $this->successResponse($data, 'Data berhasil diambil');
    }

    /**
     * Create new record
     */
    public function store(Request $request, string $table)
    {
        if (!$this->tableExists($table)) {
            return $this->notFoundResponse("Tabel '{$table}' tidak ditemukan");
        }

        $data = $request->except(['_token', '_method']);

        try {
            $id = DB::connection($this->connection)
                ->table($table)
                ->insertGetId($data);

            $insertedData = DB::connection($this->connection)
                ->table($table)
                ->where('id', $id)
                ->first();

            return $this->createdResponse($insertedData, 'Data berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update record
     */
    public function update(Request $request, string $table, $id)
    {
        if (!$this->tableExists($table)) {
            return $this->notFoundResponse("Tabel '{$table}' tidak ditemukan");
        }

        $primaryKey = $request->input('primary_key', 'id');
        $data = $request->except(['_token', '_method', 'primary_key']);

        $existing = DB::connection($this->connection)
            ->table($table)
            ->where($primaryKey, $id)
            ->first();

        if (!$existing) {
            return $this->notFoundResponse("Data dengan {$primaryKey} = {$id} tidak ditemukan");
        }

        try {
            DB::connection($this->connection)
                ->table($table)
                ->where($primaryKey, $id)
                ->update($data);

            $updatedData = DB::connection($this->connection)
                ->table($table)
                ->where($primaryKey, $id)
                ->first();

            return $this->successResponse($updatedData, 'Data berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengupdate data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete record
     */
    public function destroy(Request $request, string $table, $id)
    {
        if (!$this->tableExists($table)) {
            return $this->notFoundResponse("Tabel '{$table}' tidak ditemukan");
        }

        $primaryKey = $request->input('primary_key', 'id');

        $existing = DB::connection($this->connection)
            ->table($table)
            ->where($primaryKey, $id)
            ->first();

        if (!$existing) {
            return $this->notFoundResponse("Data dengan {$primaryKey} = {$id} tidak ditemukan");
        }

        try {
            DB::connection($this->connection)
                ->table($table)
                ->where($primaryKey, $id)
                ->delete();

            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Execute raw query (SELECT only for safety)
     */
    public function query(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        $query = trim($request->query('query') ?? $request->input('query'));

        // Only allow SELECT queries for safety
        if (!preg_match('/^SELECT/i', $query)) {
            return $this->errorResponse('Hanya query SELECT yang diperbolehkan', 400);
        }

        try {
            $results = DB::connection($this->connection)->select($query);

            return $this->successResponse($results, 'Query berhasil dijalankan');
        } catch (\Exception $e) {
            return $this->errorResponse('Query error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if table exists
     */
    protected function tableExists(string $table): bool
    {
        $tables = DB::connection($this->connection)
            ->select('SHOW TABLES');

        $dbName = config("database.connections.{$this->connection}.database");
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $t) {
            if ($t->$key === $table) {
                return true;
            }
        }

        return false;
    }
}
