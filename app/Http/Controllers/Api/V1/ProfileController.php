<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\PresignAvatarRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Aws\S3\S3Client;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'bio' => $u->bio,
            'avatar_path' => $u->avatar_path,
            'avatar_url' => $this->publicUrl($u->avatar_path),
            'settings' => $u->settings ?? (object)[],
            'roles' => method_exists($u, 'getRoleNames') ? $u->getRoleNames() : [],
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $u = $request->user();
        $data = $request->validated();
        foreach (['name','bio','settings'] as $f) {
            if (array_key_exists($f, $data)) {
                $u->{$f} = $data[$f];
            }
        }
        $u->save();

        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'bio' => $u->bio,
            'avatar_path' => $u->avatar_path,
            'avatar_url' => $this->publicUrl($u->avatar_path),
            'settings' => $u->settings ?? (object)[],
        ]);
    }

    public function presignAvatar(PresignAvatarRequest $request)
    {
        $u = $request->user();
        $ext = $request->string('extension')->lower();
        $ctype = $request->string('content_type') ?: $this->guessContentType($ext);

        $bucket = env('AWS_BUCKET');
        if (!$bucket) {
            return response()->json(['message' => 'S3 bucket is not configured'], 500);
        }

        $key = sprintf('avatars/%d/%s.%s', $u->id, (string) Str::uuid(), $ext);

        $client = $this->s3();
        $cmd = $client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $key,
            'ContentType' => $ctype,
        ]);
        $requestSigned = $client->createPresignedRequest($cmd, '+10 minutes');
        $url = (string) $requestSigned->getUri();

        return response()->json([
            'upload' => [
                'url' => $url,
                'method' => 'PUT',
                'headers' => [
                    'Content-Type' => $ctype,
                ],
            ],
            'file' => [
                'bucket' => $bucket,
                'key' => $key,
                'public_url' => $this->publicUrl($key),
            ],
        ], 201);
    }

    public function attachAvatar(Request $request)
    {
        $request->validate([ 'key' => ['required','string'] ]);
        $key = $request->string('key');
        $u = $request->user();

        $prefix = 'avatars/'.$u->id.'/';
        if (!str_starts_with($key, $prefix)) {
            return response()->json(['message' => 'Invalid key'], 422);
        }

        $u->avatar_path = $key;
        $u->save();

        return response()->json([
            'avatar_path' => $u->avatar_path,
            'avatar_url' => $this->publicUrl($u->avatar_path),
        ]);
    }

    private function publicUrl(?string $key): ?string
    {
        if (!$key) return null;
        $base = rtrim(env('AWS_URL') ?: (rtrim((string) env('AWS_ENDPOINT'), '/').'/'.env('AWS_BUCKET')), '/');
        return $base.'/'.$key;
    }

    private function s3(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION','ru-central1'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => filter_var(env('AWS_USE_PATH_STYLE_ENDPOINT', true), FILTER_VALIDATE_BOOLEAN),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    private function guessContentType(string $ext): string
    {
        return match ($ext) {
            'jpg','jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            default => 'application/octet-stream',
        };
    }
}
