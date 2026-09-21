<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadHotelImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (We'll handle authorization in the controller instead)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules
     *
     * We validate:
     * - 'images' must be an array
     * - Must upload at least 1 image, max 10
     * - Each file must be an image
     * - Each file must be one of: jpg, jpeg, png, webp
     * - Each file must be under 5MB
     * - Each file must be at least 400x300 (thumbnail size)
     */
    public function rules(): array
    {
        return [
            'images'   => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => [
                'required',
                'image', // Must be jpg, jpeg, png, bmp, gif, svg, or webp
                'mimes:jpg,jpeg,png,webp', // Restrict to safer formats
                'max:5120', // Max 5MB (5120 KB)
                'dimensions:min_width=400,min_height=300', // Minimum size
            ],
        ];
    }

    /**
     * Custom error messages for better UX
     */
    public function messages(): array
    {
        return [
            'images.required'          => 'Please select at least one image.',
            'images.max'               => 'You can upload maximum 10 images at once.',
            'images.*.image'           => 'The file must be an image.',
            'images.*.mimes'           => 'Only JPG, PNG, and WebP formats are allowed.',
            'images.*.max'             => 'Each image must be under 5MB.',
            'images.*.dimensions'      => 'Images must be at least 400x300 pixels.',
        ];
    }
}