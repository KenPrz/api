<?php

namespace App\Http\Requests\Auth;

use App\Rules\NoEmojiRule;
use App\Rules\UserHandleRule;

use Illuminate\Foundation\Http\FormRequest;
class RegisterUserRequest extends FormRequest
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
            'handle' => [
                'required',
                'string',
                'max:30',
                'min:5',
                new NoEmojiRule(),
                new UserHandleRule(), // user's username | handle
                'unique:users,handle', // unique username | handle
            ],
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/u', new NoEmojiRule()],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/u', new NoEmojiRule()],
            'birthday' => 'required|date',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'lot_block_no' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'zip_code' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_no' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
