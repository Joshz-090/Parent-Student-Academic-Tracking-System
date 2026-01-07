<?php

namespace App\Models;

class User
{
    public int $id;
    public string $firstName;
    public string $lastName;
    public string $email;
    public string $role;
    public int $schoolId;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->role = $data['role'] ?? '';
        $this->schoolId = (int)($data['school_id'] ?? 0);
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
