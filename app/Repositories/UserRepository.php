<?php
namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserInterface;
use Laravel\Sanctum\PersonalAccessToken;

class UserRepository implements UserInterface
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function findByEmail(string $email)
    {
        return $this->user->where('email', $email)->first();
    }

    public function create(array $attributes)
    {
        return $this->user->create($attributes);
    }

    public function revokeUserToken(string $token)
    {
        return PersonalAccessToken::findToken($token)->delete();
    }
}