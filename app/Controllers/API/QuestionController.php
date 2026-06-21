<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\QuestionModel;
use App\Models\QuestionOptionModel;

class QuestionController extends BaseController
{
    public function getByExam($examId = null)
    {
        if (!$examId)
            return $this->apiResponse('error', 400, 'ID Ujian diperlukan');

        $questionModel = new QuestionModel();
        $optionModel = new QuestionOptionModel();

        $questions = $questionModel->where('exam_id', $examId)->findAll();

        // Looping untuk memasukkan pilihan ganda ke dalam masing-masing soal
        foreach ($questions as &$q) {
            // Jika yang request adalah siswa (sedang ujian),  sembunyikan is_correct
            if ($this->request->user->role === 'student') {
                $options = $optionModel->select('id, question_id, option_text')->where('question_id', $q['id'])->findAll();
            } else {
                // Jika guru/admin, tampilkan mana jawaban yang benar
                $options = $optionModel->where('question_id', $q['id'])->findAll();
            }
            $q['options'] = $options;
        }

        return $this->apiResponse('success', 200, 'Berhasil mengambil soal', $questions);
    }

    public function create()
    {
        $rules = [
            'exam_id' => 'required|numeric',
            'question' => 'required',
            'score' => 'permit_empty|numeric',
            'options' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->apiResponse('error', 400, 'Validasi gagal', $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        $db = \Config\Database::connect();
        $questionModel = new QuestionModel();
        $optionModel = new QuestionOptionModel();

        // Memulai Transaksi Database agar aman
        $db->transStart();

        try {
            $questionData = [
                'exam_id' => $data['exam_id'],
                'question' => $data['question'],
                'score' => $data['score'] ?? 10
            ];
            $questionModel->insert($questionData);
            $questionId = $questionModel->getInsertID();

            $optionsData = [];
            foreach ($data['options'] as $opt) {
                $optionsData[] = [
                    'question_id' => $questionId,
                    'option_text' => $opt['option_text'],
                    'is_correct' => $opt['is_correct'] ?? false
                ];
            }
            $optionModel->insertBatch($optionsData);

            // Selesaikan transaksi
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->apiResponse('error', 500, 'Gagal menyimpan data ke database');
            }

            return $this->apiResponse('success', 201, 'Soal berhasil ditambahkan');

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->apiResponse('error', 500, 'Terjadi kesalahan sistem', $e->getMessage());
        }
    }
}