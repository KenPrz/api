<?php

namespace App\Services;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService {
    protected $userRepository;
    protected $tokenRepository;

    public function __construct(
        UserInterface $userRepository,
    )
    {
        $this->userRepository = $userRepository;
    }

    public function attemptLogin($credentials): array
    {
        $is_authenticated = Auth::attempt($credentials);

        if (!$is_authenticated) {
            return ['success' => false];
        }

        $user = $this->userRepository->findByEmail($credentials['email']);
        $token = $user->createToken('auth_token')->plainTextToken;
            
        return ['success' => true, 'token' => $token];
    }

    public function attemptRegister(array $credentials): array
    {
        $user = $this->userRepository->create($credentials);
        $token = $user->createToken('auth_token')->plainTextToken;
        event(new Registered($user));

        return ['success' => true, 'token' => $token];
    }

    public function attemptLogout(string $token): array
    {
        $res = $this->userRepository->revokeUserToken($token);

        if (!$res) {
            return ['error' => 'User not found'];
        }
        
        return ['message' => 'User logged out successfully'];
    }

}