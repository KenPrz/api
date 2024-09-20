<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login a user and return an API token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        $result = $this->authService->attemptLogin($credentials);

        if (!$result['success']) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json(['message' => 'Login successful','token' => $result['token']]);
    }

    public function register(RegisterUserRequest $request)
    {
        $credentials = $request->validated();

        $result = $this->authService->attemptRegister($credentials);

        if (!$result['success']) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json(['token' => $result['token']]);
    }

    /**
     * Logout a user and revoke their API token.
     *
     * @param  \Illuminate\Http\Request  $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        $result = $this->authService->attemptLogout($token);

        return response()->json($result);
    }
}
