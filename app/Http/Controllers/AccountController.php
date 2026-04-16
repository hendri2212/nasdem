<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Account\StoreUserRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Show the account management page.
     */
    public function index(): Response
    {
        $this->authorizeAccountManagement();

        $currentUser = request()->user();

        return Inertia::render('Account', [
            'users' => User::query()
                ->when($currentUser?->role !== UserRole::Superadmin, function ($query) {
                    $query->where('role', '!=', UserRole::Superadmin->value);
                })
                ->latest()
                ->get(['id', 'name', 'email', 'role', 'created_at']),
            'roles' => UserRole::values(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return to_route('account')->with('status', 'User created successfully.');
    }

    /**
     * Ensure the current user may manage accounts.
     *
     * @throws AuthorizationException
     */
    protected function authorizeAccountManagement(): void
    {
        if (request()->user()?->role === UserRole::User) {
            throw new AuthorizationException;
        }
    }
}
