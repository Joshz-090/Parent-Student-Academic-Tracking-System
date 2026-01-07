<?php

namespace App\Models;

class School
{
    public int $id;
    public string $name;
    public ?string $logo;
    public string $primaryColor;
    public ?string $footerText;
    public string $academicYear;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->name = $data['school_name'] ?? '';
        $this->logo = $data['logo'] ?? null;
        $this->primaryColor = $data['primary_color'] ?? '#3B82F6';
        $this->footerText = $data['footer_text'] ?? null;
        $this->academicYear = $data['academic_year'] ?? '';
    }
}
