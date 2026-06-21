<?php

namespace App\Models;

use CodeIgniter\Model;

class QuestionOptionModel extends Model
{
    protected $table = 'question_options';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    // is_correct bertipe boolean (0 atau 1)
    protected $allowedFields = ['question_id', 'option_text', 'is_correct'];

    protected $useTimestamps = false;
}