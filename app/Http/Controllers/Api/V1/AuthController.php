<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'staff',
        ]);

        $deviceName = $request->input('device_name', 'auth-token');
        $token = $user->createToken($deviceName, [$user->role])->plainTextToken;

        return $this->successResponse(
            data: [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            message: 'Registrasi berhasil. Akun Anda telah siap digunakan.',
            statusCode: 201
        );
    }

    /**
     * Handle user authentication and token creation.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', strtolower($validated['email']))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse(
                message: 'Kredensial login tidak cocok dengan data kami.',
                statusCode: 401,
                errors: [
                    'email' => ['Email atau password yang Anda masukkan salah.']
                ]
            );
        }

        $deviceName = $validated['device_name'] ?? 'auth-token';
        $token = $user->createToken($deviceName, [$user->role])->plainTextToken;

        return $this->successResponse(
            data: [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            message: 'Login berhasil. Selamat datang kembali.'
        );
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: new UserResource($request->user()),
            message: 'Profil pengguna berhasil diambil.'
        );
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            data: null,
            message: 'Logout berhasil. Sesi token Anda telah dicabut.'
        );
    }

    /**
     * Revoke all access tokens for the authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(
            data: null,
            message: 'Semua sesi token Anda pada seluruh perangkat telah berhasil dicabut.'
        );
    }
}
