<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class VerifyFederatedJWT
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Ambil token dari header Authorization: Bearer <TOKEN>
        $token = $this->cleanBearerToken($request->bearerToken());
        
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Token JWT tidak ditemukan pada Header'
            ], 401);
        }

        if (substr_count($token, '.') !== 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format token JWT salah. Di Postman pilih Authorization > Bearer Token, lalu isi hanya token mentah tanpa kata Bearer, tanpa tanda kutip, dan tanpa JSON response.'
            ], 401);
        }

        try {
            // 2. Ambil Public Key (JWKS) dari Server SSO Dosen
            $jwksUrl = env('SSO_JWKS_URL', 'https://iae-sso.virtualfri.id/.well-known/jwks.json');
            $jwksResponse = Http::timeout(10)->retry(1, 500)->get($jwksUrl);
            
            if ($jwksResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengambil public keys dari SSO Dosen'
                ], 500);
            }

            $jwks = $jwksResponse->json();

            // 3. Verifikasi signature token secara offline dengan JWK Set.
            // SSO lab memiliki selisih clock beberapa jam, jadi leeway dibuat eksplisit.
            JWT::$leeway = (int) env('SSO_JWT_LEEWAY', 28800);
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));

            // 4. Memetakan user dan role ke database lokal
            $roleName = $decoded->role ?? 'warga';
            $profile = $decoded->profile ?? null;
            $email = $decoded->email ?? $profile?->email ?? $decoded->sub ?? null;
            $name = $decoded->name ?? $profile?->name ?? ($email ? explode('@', $email)[0] : null);

            if (! $email) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token SSO valid, tetapi payload tidak memiliki email pengguna'
                ], 401);
            }
            
            // Dapatkan atau buat Role lokal
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Dapatkan atau buat User lokal
            $user = User::query()->where('email', $email)->first();

            if (!$user) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)), // password acak karena login via SSO
                    'role_id' => $role->id,
                ]);
            } else {
                // Update role jika berubah
                if ($user->role_id !== $role->id) {
                    $user->update(['role_id' => $role->id]);
                }
            }

            // Loginkan user secara lokal di request context
            Auth::setUser($user);

            // Simpan data user ke request attribute
            $request->attributes->add([
                'user_email' => $email,
                'user_role' => $roleName,
                'user_id' => $user->id,
            ]);

            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau telah kedaluwarsa: ' . $e->getMessage()
            ], 401);
        }
    }

    private function cleanBearerToken(?string $token): ?string
    {
        if (! $token) {
            return null;
        }

        $token = trim($token);
        $token = preg_replace('/^Bearer\s+/i', '', $token);
        $token = trim($token, " \t\n\r\0\x0B\"'");

        return $token !== '' ? $token : null;
    }
}
