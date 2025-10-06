<?php
namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresignAvatarRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $exts = ['jpg','jpeg','png','webp','heic'];
        return [
            'extension' => ['required','string', Rule::in($exts)],
            'content_type' => ['nullable','string','max:100'],
            'size' => ['nullable','integer','min:1','max:10485760'], // up to 10 MB
        ];
    }
}