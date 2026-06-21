<?php

namespace App\Models;

use CodeIgniter\Model;

class AttemptModel extends Model
{
    protected $table = 'exam_attempts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = ['exam_id', 'user_id', 'start_time', 'end_time', 'score', 'status'];

    protected $useTimestamps = false;
}