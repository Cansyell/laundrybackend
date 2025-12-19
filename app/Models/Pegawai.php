<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;

    // Nama tabel (opsional jika mengikuti konvensi)
    protected $table = 'pegawai';

    // Primary key (opsional jika menggunakan 'id')
    protected $primaryKey = 'id';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'user_id',
        'nama_pegawai',
        'no_telp',
        'email',
    ];

    // Kolom yang disembunyikan saat serialization
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Cast tipe data
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}