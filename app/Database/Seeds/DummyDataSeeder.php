<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Cari ID Guru dan Siswa yang sudah Anda buat sebelumnya
        $teacher = $db->table('users')->where('role', 'teacher')->get()->getRow();
        $student = $db->table('users')->where('role', 'student')->get()->getRow();

        // Jika belum ada, kita buatkan akun default
        if (!$teacher) {
            $db->table('users')->insert([
                'nis' => 999999,
                'name' => 'Guru Master',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'teacher',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $teacherId = $db->insertID();
        } else {
            $teacherId = $teacher->id;
        }

        if (!$student) {
            $db->table('users')->insert([
                'nis' => 111111,
                'name' => 'Siswa Master',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'student',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $studentId = $db->insertID();
        } else {
            $studentId = $student->id;
        }

        // ==========================================
        // 2. INSERT BANYAK UJIAN SEKALIGUS
        // ==========================================
        $exams = [
            ['title' => 'Ujian Algoritma & Struktur Data', 'description' => 'Materi array, linked list, dan sorting.', 'duration' => 120, 'start_time' => date('Y-m-d H:i:s', strtotime('+1 days')), 'end_time' => date('Y-m-d H:i:s', strtotime('+2 days')), 'created_by' => $teacherId, 'created_at' => date('Y-m-d H:i:s')],
            ['title' => 'Kuis Pemrograman Mobile (Flutter)', 'description' => 'Pemahaman dasar widget dan state management.', 'duration' => 60, 'start_time' => date('Y-m-d H:i:s', strtotime('+3 days')), 'end_time' => date('Y-m-d H:i:s', strtotime('+4 days')), 'created_by' => $teacherId, 'created_at' => date('Y-m-d H:i:s')],
            ['title' => 'Evaluasi Basis Data Lanjut', 'description' => 'Query JOIN, Trigger, dan Stored Procedure.', 'duration' => 90, 'start_time' => null, 'end_time' => null, 'created_by' => $teacherId, 'created_at' => date('Y-m-d H:i:s')],
            ['title' => 'Ujian Rekayasa Perangkat Lunak', 'description' => 'Metode Agile, Scrum, dan SDLC.', 'duration' => 100, 'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')), 'end_time' => date('Y-m-d H:i:s', strtotime('+6 days')), 'created_by' => $teacherId, 'created_at' => date('Y-m-d H:i:s')],
            ['title' => 'Tes Keamanan Siber Dasar', 'description' => 'Enkripsi, Hashing, dan Serangan SQL Injection.', 'duration' => 60, 'start_time' => null, 'end_time' => null, 'created_by' => $teacherId, 'created_at' => date('Y-m-d H:i:s')],
        ];
        $db->table('exams')->insertBatch($exams);

        // Ambil ID dari ujian pertama yang baru di-insert untuk sampel soal
        $firstExamId = $db->insertID();

        // ==========================================
        // 3. INSERT SOAL & PILIHAN GANDA (Opsional untuk kelengkapan)
        // ==========================================
        $db->table('questions')->insert([
            'exam_id' => $firstExamId,
            'question' => 'Manakah yang BUKAN merupakan tipe data di Dart?',
            'score' => 10,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $qId = $db->insertID();
        $db->table('question_options')->insertBatch([
            ['question_id' => $qId, 'option_text' => 'String', 'is_correct' => 0],
            ['question_id' => $qId, 'option_text' => 'Boolean', 'is_correct' => 0],
            ['question_id' => $qId, 'option_text' => 'Float', 'is_correct' => 1], // Jawaban benar
            ['question_id' => $qId, 'option_text' => 'Integer', 'is_correct' => 0],
        ]);

        // ==========================================
        // 4. INSERT RIWAYAT PENGERJAAN (ATTEMPTS)
        // Ini yang akan membuat Chart Grafik Anda hidup!
        // ==========================================
        $attempts = [
            ['exam_id' => $firstExamId, 'user_id' => $studentId, 'start_time' => date('Y-m-d 08:00:00', strtotime('-5 days')), 'end_time' => date('Y-m-d 09:00:00', strtotime('-5 days')), 'score' => 70, 'status' => 'finished'],
            ['exam_id' => $firstExamId, 'user_id' => $studentId, 'start_time' => date('Y-m-d 08:00:00', strtotime('-4 days')), 'end_time' => date('Y-m-d 09:00:00', strtotime('-4 days')), 'score' => 75, 'status' => 'finished'],
            ['exam_id' => $firstExamId, 'user_id' => $studentId, 'start_time' => date('Y-m-d 08:00:00', strtotime('-3 days')), 'end_time' => date('Y-m-d 09:00:00', strtotime('-3 days')), 'score' => 85, 'status' => 'finished'],
            ['exam_id' => $firstExamId, 'user_id' => $studentId, 'start_time' => date('Y-m-d 08:00:00', strtotime('-2 days')), 'end_time' => date('Y-m-d 09:00:00', strtotime('-2 days')), 'score' => 90, 'status' => 'finished'],
            ['exam_id' => $firstExamId, 'user_id' => $studentId, 'start_time' => date('Y-m-d 08:00:00', strtotime('-1 days')), 'end_time' => date('Y-m-d 09:00:00', strtotime('-1 days')), 'score' => 95, 'status' => 'finished'],
        ];
        $db->table('exam_attempts')->insertBatch($attempts);

        echo "Berhasil! Dummy Ujian, Soal, dan Nilai Chart sudah ditambahkan.\n";
    }
}