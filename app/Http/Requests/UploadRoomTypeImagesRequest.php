<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRoomTypeImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images'   => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=400,min_height=300',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required'     => 'Please select at least one image.',
            'images.max'          => 'You can upload maximum 8 images per room type.',
            'images.*.image'      => 'The file must be an image.',
            'images.*.mimes'      => 'Only JPG, PNG, and WebP formats are allowed.',
            'images.*.max'        => 'Each image must be under 5MB.',
            'images.*.dimensions' => 'Images must be at least 400x300 pixels.',
        ];
    }
}