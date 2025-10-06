<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    // POST /api/v1/email/verification-notification  (auth:sanctum)
    public function send(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified'], 200);
        }

        // Build signed URL (valid 60 minutes)
        $url = URL::temporarySignedRoute(
            'verification.verify.api',
            Carbon::now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        // Send notification via built-in notification channel (Mail)
        $user->sendEmailVerificationNotification();

        // Also return URL in dev mode (optional; comment out in prod)
        if (app()->environment('local', 'testing')) {
            return response()->json(['message' => 'Verification link sent', 'debug_verify_url' => $url], 201);
        }

        return response()->json(['message' => 'Verification link sent'], 201);
    }

    // GET /api/v1/verify-email/{id}/{hash}?signature=...&expires=...  (named: verification.verify.api, middleware:signed)
    public function verify(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages(['hash' => 'Invalid hash.']);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified'], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified'], 200);
    }
}
