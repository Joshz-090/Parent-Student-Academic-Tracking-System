<?php

namespace App\Models;

class Student extends User
{
    public string $studentCode;
    public string $gradeLevel;
    public ?string $photo;
    public int $parentId;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->studentCode = $data['student_code'] ?? '';
        $this->gradeLevel = $data['grade_level'] ?? '';
        $this->photo = $data['photo'] ?? null;
        $this->parentId = (int)($data['parent_id'] ?? 0);
    }
}
