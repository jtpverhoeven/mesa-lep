<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $users = User::query()
            ->with(['profile', 'roles'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', fn ($profile) => $profile->where('username', 'like', "%{$search}%"));
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $user = $createUser->handle($request->validated());

        return to_route('users.edit', $user)->with('success', 'Gebruiker aangemaakt.');
    }

    public function edit(User $user): View
    {
        $user->load(['profile', 'roles']);

        if (! $user->profile) {
            [$firstName, $lastName] = array_pad(explode(' ', trim($user->name), 2), 2, '');
            $user->setRelation('profile', new Profile([
                'username' => $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'locale' => Str::before(app()->getLocale(), '_'),
            ]));
        }

        return view('users.edit', [
            'editedUser' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $data = $request->validated();
        abort_if($request->user()->is($user) && ! $data['enabled'], 422, 'U kunt uw eigen account niet blokkeren.');
        $updateUser->handle($user, $data);

        return to_route('users.edit', $user)->with('success', 'Gebruiker bijgewerkt.');
    }
}
