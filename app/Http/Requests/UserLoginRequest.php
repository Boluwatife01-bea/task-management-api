<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserLoginRequest extends FormRequest
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
            'email' => 'required|string|email|max:255',
            'password' =>'required|string|min:8',
            'remember_me' => 'boolean'
        ];
    }


    public function messages(): array
    {
        return[
            'email.required' => 'Email is required',
            'email.email' => 'Enter the registered Email address',
            'password.required' => 'Password is required',
            'remember_me.boolean'=> 'Remember Me must be true or false'

        ];
    }
}
