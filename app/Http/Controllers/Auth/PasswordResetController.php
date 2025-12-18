<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\ResetPasswordMail;

class PasswordResetController extends Controller
{

    public function sendResetLink(Request $request)
    {
        \Log::info("📩 SEND RESET LINK REQUEST", $request->all());

        try {

            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $token = Str::random(64);

            \Log::info("🔑 TOKEN GENERATED", ["token" => $token]);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token'      => $token,
                    'created_at' => now()  
                ]
            );

            \Log::info("💾 TOKEN SAVED", [
                "email" => $request->email,
                "token" => $token
            ]);

            $url = config('app.frontend_url')
                . "/confirm-pass?token={$token}&email=" . urlencode($request->email);

            \Log::info("🔗 RESET LINK GENERATED", ["url" => $url]);

            Mail::to($request->email)->send(new ResetPasswordMail($url));

            return response()->json([
                'message' => "Enviamos la liga de restablecimiento a tu correo.\nNo olvides revisar la carpeta de spam."
            ]);

        } catch (\Throwable $e) {

            \Log::error("❌ ERROR IN sendResetLink()", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);

            return response()->json([
                "message" => "Error al enviar enlace.",
                "error"   => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        \Log::info("🔁 RESET PASSWORD REQUEST", $request->all());

        try {

            $request->validate([
                'token'    => 'required',
                'email'    => 'required|email',
                'password' => 'required|min:6|confirmed',
            ]);

            $cleanEmail = strtolower(trim($request->email));

            // Buscar token en BD
            $resetData = DB::table('password_reset_tokens')
                ->where('email', $cleanEmail)
                ->where('token', $request->token)
                ->first();

            \Log::info("🔎 TOKEN LOOKUP", ["resetData" => $resetData]);

            if (!$resetData) {
                \Log::warning("⛔ TOKEN INVALID");
                return response()->json([
                    'message' => 'Token inválido.'
                ], 400);
            }

            $createdAt = Carbon::parse($resetData->created_at);
            $secondsPassed = $createdAt->diffInRealSeconds(now());

            \Log::info("⏳ TOKEN AGE CHECK", [
                "created_at"     => $resetData->created_at,
                "seconds_passed" => $secondsPassed
            ]);

            if ($secondsPassed > 120) {

                DB::table('password_reset_tokens')->where('email', $cleanEmail)->delete();

                \Log::warning("⛔ TOKEN EXPIRED AT {$secondsPassed} seconds");

                return response()->json([
                    "message" => "El enlace ha expirado. Solicita uno nuevo."
                ], 400);
            }

            $updated = User::where('email', $cleanEmail)->update([
                'password' => Hash::make($request->password),
            ]);

            \Log::info("🔧 PASSWORD UPDATED", ["updated" => $updated]);

            // Eliminar token usado
            DB::table('password_reset_tokens')->where('email', $cleanEmail)->delete();

            \Log::info("🗑️ TOKEN DELETED AFTER SUCCESS");

            return response()->json([
                'message' => 'Contraseña actualizada correctamente.'
            ]);

        } catch (\Throwable $e) {

            \Log::error("❌ ERROR IN resetPassword()", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);

            return response()->json([
                "message" => "No se pudo restablecer la contraseña.",
                "error"   => $e->getMessage()
            ], 500);
        }
    }


    public function validateToken(Request $request)
    {
        \Log::info("🔍 TOKEN VALIDATION REQUEST", $request->all());

        try {

            $request->validate([
                'token' => 'required',
                'email' => 'required|email'
            ]);

            $cleanEmail = strtolower(trim($request->email));

            $resetData = DB::table('password_reset_tokens')
                ->where('email', $cleanEmail)
                ->where('token', $request->token)
                ->first();

            if (!$resetData) {
                return response()->json(["valid" => false, "reason" => "not_found"], 400);
            }

            $secondsPassed = Carbon::parse($resetData->created_at)
                ->diffInRealSeconds(now());

            if ($secondsPassed > 120) {
                DB::table('password_reset_tokens')->where('email', $cleanEmail)->delete();
                return response()->json(["valid" => false, "reason" => "expired"], 400);
            }

            return response()->json(["valid" => true, "reason" => "ok"]);

        } catch (\Throwable $e) {

            \Log::error("❌ ERROR IN validateToken()", [
                "error" => $e->getMessage()
            ]);

            return response()->json([
                "valid" => false,
                "reason" => "server_error"
            ], 500);
        }
    }
}
