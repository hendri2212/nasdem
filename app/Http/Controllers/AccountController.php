<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreUserRequest;
use App\Models\User;
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
        return Inertia::render('Account', [
            'users' => User::query()
                ->latest()
                ->get(['id', 'name', 'email', 'email_verified_at', 'created_at']),
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
}
