<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\AttemptModel;
use App\Models\ExamModel;

class DashboardController extends BaseController
{
    public function studentStats()
    {
        $userId = $this->request->user->uid;
        $attemptModel = new AttemptModel();
        $examModel = new ExamModel();

        $db = \Config\Database::connect();

        // 1. Hitung Rata-rata Nilai (Skala 100)
        $query = $db->query("SELECT AVG(score) as avg_score FROM exam_attempts WHERE user_id = $userId AND status = 'finished'");
        $avgScore = $query->getRow()->avg_score ?? 0;

        // 2. Hitung IPK (Konversi Skala 100 ke 4.0)
        $ipk = $avgScore > 0 ? ($avgScore / 100) * 4.0 : 0;

        // 3. Hitung Ujian yang tersedia hari ini (Sederhana: total ujian yang ada)
        $todayExams = $examModel->countAllResults();

        // 4. Data Chart Tren (Ambil 5 ujian terbaru, lalu urutkan sesuai deret waktu)
        $builder = $db->table('exam_attempts ea');
        $builder->select('ea.score, ea.end_time, e.title');
        $builder->join('exams e', 'e.id = ea.exam_id'); // Join untuk dapatkan judul ujian
        $builder->where('ea.user_id', $userId);
        $builder->where('ea.status', 'finished');
        $builder->orderBy('ea.end_time', 'DESC'); // Ambil 5 yang PALING BARU disubmit
        $builder->limit(5);

        $recentAttempts = $builder->get()->getResultArray();

        // Balik array-nya agar urut dari waktu tertua di kiri -> terbaru di kanan
        $recentAttempts = array_reverse($recentAttempts);

        $trend = [];
        foreach ($recentAttempts as $att) {
            // Ambil format jam dan menit (Misal: "17:00")
            $jam = date('H:i', strtotime($att['end_time']));

            // Ambil maksimal 8 huruf pertama dari judul ujian agar muat di grafik
            $judulSingkat = substr($att['title'], 0, 8);

            $trend[] = [
                // Gunakan \n agar teks di grafik turun ke baris baru (Jam di atas, Judul di bawah)
                'label' => $jam . "\n" . $judulSingkat,
                'value' => (int) $att['score']
            ];
        }

        if (empty($trend)) {
            $trend = [['label' => '-', 'value' => 0]];
        }

        // 5. Kesiapan Ujian (Misal diambil dari rasio ketuntasan nilai)
        $readiness = $avgScore > 0 ? ($avgScore / 100) : 0;

        return $this->apiResponse('success', 200, 'Statistik Dashboard', [
            'ipk' => number_format($ipk, 2),
            'average_score' => round($avgScore),
            'today_exams' => $todayExams,
            'readiness' => $readiness,
            'trend' => $trend
        ]);
    }
}