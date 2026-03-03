<?php

namespace Database\Seeders;

use App\Models\Pengurus;
use Illuminate\Database\Seeder;

class PengurusSeeder extends Seeder
{
    public function run(): void
    {
        $pengurusData = [
            // Pengurus Inti
            ['nama' => 'Sungkono', 'jabatan' => 'Ketua RW', 'bidang' => 'Pengurus Inti', 'periode' => '2024–2029', 'sequence' => 1],
            ['nama' => 'Ferly Noviandri, ST', 'jabatan' => 'Sekretaris', 'bidang' => 'Pengurus Inti', 'periode' => '2024–2029', 'sequence' => 2],
            ['nama' => 'Dewanda Reza Remaldi', 'jabatan' => 'Sekretaris', 'bidang' => 'Pengurus Inti', 'periode' => '2024–2029', 'sequence' => 3],
            ['nama' => 'Komarudin, S.Kom.', 'jabatan' => 'Bendahara', 'bidang' => 'Pengurus Inti', 'periode' => '2024–2029', 'sequence' => 4],

            // Kasepuhan & Kerukunan Masyarakat
            ['nama' => 'Nurkolis, S.T', 'jabatan' => 'Kasepuhan & Kerukunan Masyarakat', 'bidang' => 'Kasepuhan & Kerukunan Masyarakat', 'periode' => '2024–2029', 'sequence' => 5],
            ['nama' => 'Prastyoko', 'jabatan' => 'Kasepuhan & Kerukunan Masyarakat', 'bidang' => 'Kasepuhan & Kerukunan Masyarakat', 'periode' => '2024–2029', 'sequence' => 6],

            // Pemberdayaan Sumber Daya, Ekonomi & Informasi Teknologi
            ['nama' => 'Dany Hermawan, S.T.', 'jabatan' => 'Pemberdayaan SDaya, Ekonomi & IT', 'bidang' => 'Pemberdayaan Sumber Daya, Ekonomi & Informasi Teknologi', 'periode' => '2024–2029', 'sequence' => 7],
            ['nama' => 'Adi Setiawan, S.Kom', 'jabatan' => 'Pemberdayaan SDaya, Ekonomi & IT', 'bidang' => 'Pemberdayaan Sumber Daya, Ekonomi & Informasi Teknologi', 'periode' => '2024–2029', 'sequence' => 8],
            ['nama' => 'Arigo, S.Kom., M.TI', 'jabatan' => 'Pemberdayaan SDaya, Ekonomi & IT', 'bidang' => 'Pemberdayaan Sumber Daya, Ekonomi & Informasi Teknologi', 'periode' => '2024–2029', 'sequence' => 9],

            // Hubungan Masyarakat
            ['nama' => 'Yansah', 'jabatan' => 'Hubungan Masyarakat', 'bidang' => 'Hubungan Masyarakat', 'periode' => '2024–2029', 'sequence' => 10],
            ['nama' => 'Ryan Ristianto', 'jabatan' => 'Hubungan Masyarakat', 'bidang' => 'Hubungan Masyarakat', 'periode' => '2024–2029', 'sequence' => 11],
            ['nama' => 'Joko Sulistiyono', 'jabatan' => 'Hubungan Masyarakat', 'bidang' => 'Hubungan Masyarakat', 'periode' => '2024–2029', 'sequence' => 12],

            // Keamanan & Ketertiban Masyarakat
            ['nama' => 'Tugimanto', 'jabatan' => 'Keamanan & Ketertiban Masyarakat', 'bidang' => 'Keamanan & Ketertiban Masyarakat', 'periode' => '2024–2029', 'sequence' => 13],
            ['nama' => 'Sutahar', 'jabatan' => 'Keamanan & Ketertiban Masyarakat', 'bidang' => 'Keamanan & Ketertiban Masyarakat', 'periode' => '2024–2029', 'sequence' => 14],
            ['nama' => 'Pendi Sugara', 'jabatan' => 'Keamanan & Ketertiban Masyarakat', 'bidang' => 'Keamanan & Ketertiban Masyarakat', 'periode' => '2024–2029', 'sequence' => 15],

            // Kepemudaan & Olahraga
            ['nama' => 'Dimas Sidiq Adhi Saputro, S.Par.', 'jabatan' => 'Kepemudaan & Olahraga', 'bidang' => 'Kepemudaan & Olahraga', 'periode' => '2024–2029', 'sequence' => 16],
            ['nama' => 'Zenal Mustopa', 'jabatan' => 'Kepemudaan & Olahraga', 'bidang' => 'Kepemudaan & Olahraga', 'periode' => '2024–2029', 'sequence' => 17],
            ['nama' => 'Edy Siswanto', 'jabatan' => 'Kepemudaan & Olahraga', 'bidang' => 'Kepemudaan & Olahraga', 'periode' => '2024–2029', 'sequence' => 18],

            // Utilitas & Aset
            ['nama' => 'Irwan', 'jabatan' => 'Utilitas & Aset', 'bidang' => 'Utilitas & Aset', 'periode' => '2024–2029', 'sequence' => 19],
            ['nama' => 'Mario', 'jabatan' => 'Utilitas & Aset', 'bidang' => 'Utilitas & Aset', 'periode' => '2024–2029', 'sequence' => 20],
            ['nama' => 'Supri', 'jabatan' => 'Utilitas & Aset', 'bidang' => 'Utilitas & Aset', 'periode' => '2024–2029', 'sequence' => 21],

            // Ketua RT
            ['nama' => 'Abu Kahar', 'jabatan' => 'Ketua RT 01', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 22],
            ['nama' => 'M. Martin', 'jabatan' => 'Ketua RT 02', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 23],
            ['nama' => 'Dadang Suryana', 'jabatan' => 'Ketua RT 03', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 24],
            ['nama' => 'Hamdan', 'jabatan' => 'Ketua RT 04', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 25],
            ['nama' => 'Basuki', 'jabatan' => 'Ketua RT 05', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 26],
            ['nama' => 'Rihat Immadudin', 'jabatan' => 'Ketua RT 06', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 27],
            ['nama' => 'Edi Eko Purwoko', 'jabatan' => 'Ketua RT 07', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 28],
            ['nama' => 'Sri Haryanto', 'jabatan' => 'Ketua RT 08', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 29],
            ['nama' => 'Hernando Buana', 'jabatan' => 'Ketua RT 09', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 30],
            ['nama' => 'Dodi Wibowo', 'jabatan' => 'Ketua RT 10', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 31],
            ['nama' => 'Widiyantoro', 'jabatan' => 'Ketua RT 11', 'bidang' => 'Ketua RT', 'periode' => '2024–2029', 'sequence' => 32],
        ];

        foreach ($pengurusData as $pengurus) {
            Pengurus::create($pengurus);
        }
    }
}
