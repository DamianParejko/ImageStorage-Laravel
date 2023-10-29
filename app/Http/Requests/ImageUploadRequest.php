<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:jpeg,png,webp,tiff,bmp|max:5120'
        ];
    }
    
    public function getImageDimensions()
    {
        if($this->file('image')){
            return getimagesize($this->file('image')->getRealPath());
        }
    }

    public function getImageExtension(): string
    {
        return $this->file('image')->extension();
    }

    public function getImageSize(): int
    {
        $sizeImage = $this->file('image')->getSize();
        
        return $sizeImage / 1024;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                [$width, $height] = $this->getImageDimensions();

                if($width < 500 || $height < 500){
                    $validator->errors()->add(
                        'Image',
                        'Image too small. Must be at least 500x500'
                    );
                }
            }
        ];
    }

}
