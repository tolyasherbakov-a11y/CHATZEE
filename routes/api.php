<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\ConversationReadController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\TypingController;

Route::prefix("v1")->group(function () {
    // --- Auth (public) ---
    Route::post("/auth/register", [AuthController::class, "register"]);
    Route::post("/auth/login", [AuthController::class, "login"]);

    // --- Password reset (public) ---
    Route::post("/password/forgot", [PasswordResetController::class, "forgot"])->middleware("throttle:6,1");
    Route::post("/password/reset", [PasswordResetController::class, "reset"])->middleware("throttle:6,1");

    // --- Email verification (verify link — публичный, но подписанный) ---
    Route::get("/verify-email/{id}/{hash}", [EmailVerificationController::class, "verify"])
        ->name("verification.verify.api")
        ->middleware("signed");

    // --- Protected (auth:sanctum) ---
    Route::middleware("auth:sanctum")->group(function () {
        // Bootstrap + typing
        Route::get("/bootstrap",        BootstrapController::class);
        Route::post("/conversations/{conversation}/typing/start", [TypingController::class, "start"]);
        Route::post("/conversations/{conversation}/typing/stop",  [TypingController::class, "stop"]);

        // Auth protected
        Route::get("/auth/me",          [AuthController::class, "me"]);
        Route::post("/auth/logout",     [AuthController::class, "logout"]);
        Route::post("/auth/tokens",     [AuthController::class, "issueToken"]);
        Route::delete("/auth/tokens/{id}", [AuthController::class, "revokeToken"]);

        // Отправка письма для подтверждения e-mail
        Route::post("/email/verification-notification", [EmailVerificationController::class, "send"])
            ->middleware("throttle:6,1");

        // Profile
        Route::get("/profile",                  [ProfileController::class, "show"]);
        Route::patch("/profile",                [ProfileController::class, "update"]);
        Route::post("/profile/avatar/presign",  [ProfileController::class, "presignAvatar"]);
        Route::post("/profile/avatar/attach",   [ProfileController::class, "attachAvatar"]);

        // Conversations & Messages (M4)
        Route::get("/conversations",                                   [ConversationController::class, "index"]);
        Route::post('/conversations/start',                            [ConversationController::class, 'startDirect']);
        Route::get("/conversations/{conversation}/messages",           [MessageController::class, "index"]);
        Route::post("/conversations/{conversation}/messages",          [MessageController::class, "store"]);
        Route::post("/conversations/{conversation}/attachments/presign", [AttachmentController::class, "presign"]);
        Route::post("/conversations/{conversation}/attachments/attach", [AttachmentController::class, "attach"]);
        Route::post("/conversations/{conversation}/read-up-to", [ConversationReadController::class, "upTo"]);
    });
});