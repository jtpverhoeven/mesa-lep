<?php

namespace App\Http\Controllers;

use App\Actions\UserGroups\CreateUserGroup;
use App\Actions\UserGroups\UpdateUserGroup;
use App\Http\Requests\SaveUserGroupRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserGroupController extends Controller
{
    public function index(): View
    {
        return view('user-groups.index', [
            'groups' => Role::with('leader')->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeSuperGroup($request);

        return $this->formView('user-groups.create');
    }

    public function store(SaveUserGroupRequest $request, CreateUserGroup $createUserGroup): RedirectResponse
    {
        $this->authorizeSuperGroup($request, (bool) $request->boolean('superGroup'));
        $role = $createUserGroup->handle($request->validated());

        return to_route('user-groups.edit', $role)->with('success', 'Gebruikersgroep aangemaakt.');
    }

    public function edit(Request $request, Role $userGroup): View
    {
        $this->authorizeSuperGroup($request, $userGroup->superGroup);

        return $this->formView('user-groups.edit', $userGroup->load(['permissions', 'users']));
    }

    public function update(
        SaveUserGroupRequest $request,
        Role $userGroup,
        UpdateUserGroup $updateUserGroup,
    ): RedirectResponse {
        $this->authorizeSuperGroup($request, $userGroup->superGroup || $request->boolean('superGroup'));
        $updateUserGroup->handle($userGroup, $request->validated());

        return to_route('user-groups.edit', $userGroup)->with('success', 'Gebruikersgroep bijgewerkt.');
    }

    public function destroy(Request $request, Role $userGroup): RedirectResponse
    {
        abort_unless($request->user()?->can('groups.manage'), 403);
        $this->authorizeSuperGroup($request, $userGroup->superGroup);
        abort_if($userGroup->name === 'administrator', 422, 'De beheerdersgroep kan niet worden verwijderd.');
        $userGroup->delete();

        return to_route('user-groups.index')->with('success', 'Gebruikersgroep verwijderd.');
    }

    private function formView(string $view, ?Role $role = null): View
    {
        return view($view, [
            'group' => $role,
            'users' => User::with('profile')->orderBy('name')->get(),
            'permissionGroups' => PermissionCatalog::groups(),
        ]);
    }

    private function authorizeSuperGroup(Request $request, bool $needed = false): void
    {
        if ($needed) {
            abort_unless($request->user()?->roles()->where('superGroup', true)->exists(), 403);
        }
    }
}