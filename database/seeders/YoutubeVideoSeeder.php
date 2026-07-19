<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use Illuminate\Database\Seeder;

/**
 * Seeder: Video tutorial YouTube nyata tentang pengeringan gabah/padi.
 * Video ID diverifikasi dari IRRI Rice Knowledge Bank (knowledgebank.irri.org).
 */
class YoutubeVideoSeeder extends Seeder
{
    public function run(): void
    {
        $videos = [
            [
                'category'        => 'video_tutorial',
                'title'           => 'Video: Metode Pengeringan Gabah (Paddy Drying Methods)',
                'content'         => "Tutorial lengkap metode pengeringan gabah/padi dari IRRI (International Rice Research Institute).\n\nVideo ini menjelaskan berbagai metode pengeringan padi termasuk sun drying, flatbed dryer, dan solar dryer.\n\nTonton video: https://www.youtube.com/watch?v=95xwlAD3z-Y",
                'tags'            => ['video', 'tutorial', 'pengeringan', 'gabah', 'metode', 'IRRI'],
                'priority_weight' => 9,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'video_tutorial',
                'title'           => 'Video: Cara Mengukur Kadar Air Gabah (Grain Moisture Content)',
                'content'         => "Panduan mengukur kadar air biji-bijian padi menggunakan moisture meter dari IRRI.\n\nPenting untuk memastikan gabah sudah mencapai kadar air aman untuk disimpan (≤14%).\n\nTonton video: https://www.youtube.com/watch?v=loCKGozm0Y8",
                'tags'            => ['video', 'kadar air', 'moisture meter', 'pengukuran', 'IRRI'],
                'priority_weight' => 9,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'video_tutorial',
                'title'           => 'Video: Pengeringan Gabah dengan Sinar Matahari (Sun Drying)',
                'content'         => "Teknik tradisional pengeringan gabah menggunakan sinar matahari langsung dari IRRI.\n\nMenjelaskan cara penjemuran yang benar, ketebalan lapisan gabah, dan waktu penjemuran optimal.\n\nTonton video: https://www.youtube.com/watch?v=mLaX8ToOoQQ",
                'tags'            => ['video', 'sun drying', 'penjemuran', 'tradisional', 'IRRI'],
                'priority_weight' => 8,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'video_tutorial',
                'title'           => 'Video: Flatbed Dryer — Peningkatan Kualitas Padi',
                'content'         => "Demonstrasi penggunaan flatbed dryer untuk meningkatkan kualitas padi di Kamboja oleh IRRI.\n\nRelevant untuk operator Solar Dryer: prinsip heated-air drying yang mirip dengan sistem Padi PRECISION.\n\nTonton video: https://www.youtube.com/watch?v=ldsReKPINOE",
                'tags'            => ['video', 'flatbed dryer', 'mekanis', 'kualitas', 'IRRI'],
                'priority_weight' => 8,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'video_tutorial',
                'title'           => 'Video: Reversible Airflow Flatbed Dryer',
                'content'         => "Demonstrasi flatbed dryer dengan aliran udara dua arah (reversible airflow) untuk keseragaman pengeringan dari IRRI.\n\nTeknik ini memastikan semua lapisan gabah kering merata — prinsip yang digunakan sistem Padi PRECISION.\n\nTonton video: https://www.youtube.com/watch?v=sZvB8b6vPro",
                'tags'            => ['video', 'flatbed dryer', 'airflow', 'reversible', 'IRRI'],
                'priority_weight' => 7,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'video_tutorial',
                'title'           => 'Video: Demonstrasi Rice Hull Furnace (Tungku Sekam Padi)',
                'content'         => "Demonstrasi tungku sekam padi semi-otomatis dari IRRI sebagai sumber panas alternatif untuk dryer.\n\nIde untuk sistem hybrid: sekam padi dapat digunakan sebagai backup energi selain solar panel.\n\nTonton video: https://www.youtube.com/watch?v=LzSIuyaP004",
                'tags'            => ['video', 'rice hull', 'tungku', 'sekam', 'energi', 'IRRI'],
                'priority_weight' => 7,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
        ];

        foreach ($videos as $video) {
            // Upsert berdasarkan title agar tidak duplikat jika dijalankan ulang
            KnowledgeBase::updateOrCreate(
                ['title' => $video['title']],
                array_merge($video, [
                    'created_by' => 1, // admin user ID
                ])
            );
        }

        $this->command->info('YouTube video seeder selesai: ' . count($videos) . ' video ditambahkan ke knowledge base.');
    }
}
