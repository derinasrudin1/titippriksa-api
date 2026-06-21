<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ExamModel;

class ExamController extends BaseController
{
    public function index()
    {
        $examModel = new \App\Models\ExamModel();
        $attemptModel = new \App\Models\AttemptModel();

        $userId = $this->request->user->uid; // Ambil ID user yang sedang login

        // Ambil semua daftar ujian
        $exams = $examModel->findAll();

        // Ambil semua riwayat pengerjaan milik user ini yang sudah selesai
        $attempts = $attemptModel->where('user_id', $userId)
            ->where('status', 'finished')
            ->findAll();

        // Buat map (kamus) attempt berdasarkan exam_id agar mudah dicari
        $attemptMap = [];
        foreach ($attempts as $att) {
            $attemptMap[$att['exam_id']] = $att;
        }

        // Looping semua ujian, lalu sisipkan status pengerjaan siswa
        foreach ($exams as &$exam) {
            if (isset($attemptMap[$exam['id']])) {
                // Jika sudah pernah dikerjakan dan selesai
                $exam['attempt_status'] = 'finished';
                $exam['score'] = $attemptMap[$exam['id']]['score'];
            } else {
                // Jika belum pernah dikerjakan
                $exam['attempt_status'] = 'available';
                $exam['score'] = null;
            }
        }

        return $this->apiResponse('success', 200, 'Berhasil mengambil daftar ujian', $exams);
    }

    public function create()
    {
        $rules = [
            'title' => 'required|min_length[3]',
            'duration' => 'required|numeric' // Durasi dalam menit
        ];

        if (!$this->validate($rules)) {
            return $this->apiResponse('error', 400, 'Validasi gagal', $this->validator->getErrors());
        }

        $examModel = new ExamModel();

        // get payload JSON
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $userId = $this->request->user->uid;

        $examData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration' => $data['duration'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'created_by' => $userId
        ];

        $examModel->insert($examData);
        $examData['id'] = $examModel->getInsertID();

        return $this->apiResponse('success', 201, 'Ujian berhasil dibuat', $examData);
    }
}