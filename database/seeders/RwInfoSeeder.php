<?php

namespace Database\Seeders;

use App\Models\RwInfo;
use Illuminate\Database\Seeder;

class RwInfoSeeder extends Seeder
{
    public function run(): void
    {
        // Organization Info
        RwInfo::setValue('name', 'RW 011');
        RwInfo::setValue('fullName', 'RW 011 Desa Sukamantri');
        RwInfo::setValue('desa', 'Desa Sukamantri');
        RwInfo::setValue('kecamatan', 'Kecamatan Pasar Kemis');
        RwInfo::setValue('kabupaten', 'Kabupaten Tangerang');
        RwInfo::setValue('provinsi', 'Banten');
        RwInfo::setValue('alamat', 'Sekretariat RW 011, Desa Sukamantri, Kec. Pasar Kemis, Kab. Tangerang, Banten');
        RwInfo::setValue('telepon', '+62 812-3456-7890');
        RwInfo::setValue('whatsapp', '6281234567890');
        RwInfo::setValue('email', 'rw011sukamantri@gmail.com');
        RwInfo::setValue('googleMapsEmbed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d349.6062493641003!2d106.5518995125581!3d-6.159831221360605!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ff0041586bf5%3A0x38d05456db9e25af!2sGSG%20RW011!5e0!3m2!1sid!2sid!4v1771488692468!5m2!1sid!2sid');

        // Social Media
        RwInfo::setValue('socialMedia', [
            'facebook' => 'https://facebook.com/rw011sukamantri',
            'instagram' => 'https://instagram.com/rw011sukamantri',
            'youtube' => 'https://youtube.com/@rw011sukamantri',
        ]);

        // Tentang RW
        RwInfo::setValue('about', 'RW 011 merupakan salah satu rukun warga di Desa Sukamantri, Kecamatan Pasar Kemis, Kabupaten Tangerang, Provinsi Banten. Wilayah RW 011 terdiri dari 11 RT yang mencakup beberapa klaster perumahan, antara lain Taman Merpati PuriJaya, Puri Kasuari, Puri Cendrawasih, dan Puri Pelican.');

        RwInfo::setValue('visi', 'Mewujudkan lingkungan RW 011 yang aman, nyaman, bersih, tertib, dan sejahtera berlandaskan semangat gotong royong.');

        RwInfo::setValue('misi', [
            'Meningkatkan keamanan dan ketertiban lingkungan melalui sistem keamanan lingkungan (Siskamling)',
            'Membangun solidaritas dan gotong royong antar warga melalui kegiatan sosial kemasyarakatan',
            'Mendorong partisipasi aktif warga dalam pembangunan dan pemeliharaan fasilitas umum',
            'Meningkatkan kebersihan dan keindahan lingkungan secara berkelanjutan',
            'Memfasilitasi pelayanan administrasi kependudukan bagi warga',
            'Mengembangkan kegiatan keagamaan dan pembinaan generasi muda',
        ]);

        RwInfo::setValue('sambutanKetuaRW', "Assalamu'alaikum Warahmatullahi Wabarakatuh.

Puji syukur kita panjatkan kehadirat Allah SWT atas segala rahmat dan karunia-Nya. Selamat datang di portal resmi RW 011 Desa Sukamantri.

Portal ini kami hadirkan sebagai sarana informasi dan komunikasi antara pengurus RW dengan seluruh warga. Melalui portal ini, warga dapat mengakses berbagai informasi seputar kegiatan, data kependudukan, dokumen publik, serta menyampaikan saran dan masukan untuk kemajuan lingkungan kita bersama.

Kami mengajak seluruh warga untuk berpartisipasi aktif dalam setiap kegiatan dan pembangunan di lingkungan RW 011. Mari bersama-sama kita wujudkan lingkungan yang aman, nyaman, bersih, dan sejahtera.

Wassalamu'alaikum Warahmatullahi Wabarakatuh.");
    }
}
