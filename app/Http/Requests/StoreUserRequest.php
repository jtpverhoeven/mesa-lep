<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:profiles,username'],
            'title' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::default()],
            'gender' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'locale' => ['required', Rule::in(['nl', 'en'])],
            'enabled' => ['required', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ];
    }
}