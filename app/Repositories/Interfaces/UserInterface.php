<?php
namespace App\Repositories\Interfaces;

interface UserInterface {
    public function create(array $attributes);
    public function findByEmail(string $email);
    public function revokeUserToken(string $token);
}
