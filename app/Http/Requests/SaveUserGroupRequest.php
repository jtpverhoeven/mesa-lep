<?php

namespace App\Http\Requests;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('groups.manage') ?? false;
    }

    public function rules(): array
    {
        $role = $this->route('userGroup');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->where('guard_name', 'web')->ignore($role)],
            'groupLeader' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'superGroup' => ['required', 'boolean'],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionCatalog::names())],
        ];
    }
}