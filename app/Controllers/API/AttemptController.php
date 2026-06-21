<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\AttemptModel;
use App\Models\AnswerModel;

class AttemptController extends BaseController
{
    // Tetott mulai
    public function startExam()
    {
        $data = $this->request->getJSON(true);
        $examId = $data['exam_id'] ?? null;

        if (!$examId)
            return $this->apiResponse('error', 400, 'ID Ujian diperlukan');

        $userId = $this->request->user->uid;
        $attemptModel = new AttemptModel();

        // Cek apakah siswa sudah pernah mengerjakan ujian ini
        $existingAttempt = $attemptModel->where('exam_id', $examId)
            ->where('user_id', $userId)
            ->first();

        if ($existingAttempt) {
            if ($existingAttempt['status'] === 'finished') {
                return $this->apiResponse('error', 403, 'Anda sudah menyelesaikan ujian ini');
            }
            // Jika masih ongoing, kembalikan data attempt yang lama (resume)
            return $this->apiResponse('success', 200, 'Melanjutkan ujian', $existingAttempt);
        }

        // Buat record pengerjaan baru
        $attemptData = [
            'exam_id' => $examId,
            'user_id' => $userId,
            'start_time' => date('Y-m-d H:i:s'),
            'status' => 'ongoing',
            'score' => 0
        ];

        $attemptModel->insert($attemptData);
        $attemptData['id'] = $attemptModel->getInsertID();

        return $this->apiResponse('success', 201, 'Ujian dimulai', $attemptData);
    }

    // simpen jawaban 
    public function saveAnswer()
    {
        $data = $this->request->getJSON(true);
        $rules = [
            'attempt_id' => 'required|numeric',
            'question_id' => 'required|numeric',
            'selected_option_id' => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return $this->apiResponse('error', 400, 'Validasi gagal', $this->validator->getErrors());
        }

        $answerModel = new AnswerModel();

        // Cek dulu bray ini nomor soal udeh pernah dijawab sebelumnya di attempt ini
        $existingAnswer = $answerModel->where('attempt_id', $data['attempt_id'])
            ->where('question_id', $data['question_id'])
            ->first();

        if ($existingAnswer) {
            // Update jawaban
            $answerModel->update($existingAnswer['id'], ['selected_option_id' => $data['selected_option_id']]);
            return $this->apiResponse('success', 200, 'Jawaban diperbarui');
        } else {
            // Insert jawaban baru
            $answerModel->insert($data);
            return $this->apiResponse('success', 201, 'Jawaban disimpan');
        }
    }

    // Submit and hitung bray
    public function submitExam($attemptId = null)
    {
        if (!$attemptId)
            return $this->apiResponse('error', 400, 'ID Attempt diperlukan');

        $attemptModel = new AttemptModel();
        $attempt = $attemptModel->find($attemptId);

        if (!$attempt)
            return $this->apiResponse('error', 404, 'Data pengerjaan tidak ditemukan');
        if ($attempt['status'] === 'finished')
            return $this->apiResponse('error', 400, 'Ujian sudah disubmit sebelumnya');

        $db = \Config\Database::connect();

        // Logika SQL Otomatis: Hitung total skor dari jawaban yang benar
        // SELECT SUM(q.score) as total_score FROM answers a JOIN question_options qo ...
        $builder = $db->table('answers a');
        $builder->selectSum('q.score', 'total_score');
        $builder->join('question_options qo', 'qo.id = a.selected_option_id');
        $builder->join('questions q', 'q.id = a.question_id');
        $builder->where('a.attempt_id', $attemptId);
        $builder->where('qo.is_correct', 1); // Hanya hitung yang jawabannya benar

        $result = $builder->get()->getRow();
        $finalScore = $result->total_score ?? 0;

        // Update status attempt menjadi finished dan simpan nilainya
        $updateData = [
            'end_time' => date('Y-m-d H:i:s'),
            'status' => 'finished',
            'score' => (int) $finalScore
        ];
        $attemptModel->update($attemptId, $updateData);

        return $this->apiResponse('success', 200, 'Ujian selesai. Nilai berhasil dihitung.', [
            'attempt_id' => $attemptId,
            'final_score' => (int) $finalScore
        ]);
    }
}