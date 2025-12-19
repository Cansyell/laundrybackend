<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    /**
     * Display a listing of pegawai.
     */
    public function index()
    {
        try {
            $pegawai = Pegawai::with('user')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Data pegawai berhasil diambil',
                'data' => $pegawai
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created pegawai.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_pegawai' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ], [
            'nama_pegawai.required' => 'Nama pegawai wajib diisi',
            'no_telp.required' => 'Nomor telepon wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Buat user untuk login pegawai
            $user = User::create([
                'name' => $request->nama_pegawai,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'pegawai', // atau sesuai role system Anda
            ]);

            // Buat data pegawai
            $pegawai = Pegawai::create([
                'user_id' => $user->id,
                'nama_pegawai' => $request->nama_pegawai,
                'no_telp' => $request->no_telp,
                'email' => $request->email,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => [
                    'pegawai' => $pegawai,
                    'user' => $user
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified pegawai.
     */
    public function show($id)
    {
        try {
            $pegawai = Pegawai::with('user')->find($id);

            if (!$pegawai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pegawai tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data pegawai berhasil diambil',
                'data' => $pegawai
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified pegawai.
     */
    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::find($id);

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_pegawai' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($pegawai->user_id)
            ],
            'password' => 'nullable|string|min:6',
        ], [
            'nama_pegawai.required' => 'Nama pegawai wajib diisi',
            'no_telp.required' => 'Nomor telepon wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update data user
            $user = User::find($pegawai->user_id);
            $user->name = $request->nama_pegawai;
            $user->email = $request->email;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();

            // Update data pegawai
            $pegawai->update([
                'nama_pegawai' => $request->nama_pegawai,
                'no_telp' => $request->no_telp,
                'email' => $request->email,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pegawai berhasil diupdate',
                'data' => [
                    'pegawai' => $pegawai,
                    'user' => $user
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified pegawai.
     */
    public function destroy($id)
    {
        $pegawai = Pegawai::find($id);

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $userId = $pegawai->user_id;
            
            // Hapus pegawai
            $pegawai->delete();
            
            // Hapus user
            User::destroy($userId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pegawai berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}