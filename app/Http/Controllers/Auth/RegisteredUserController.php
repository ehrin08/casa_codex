<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $customerRole = Role::where('name', 'customer')->firstOrFail();

            $user = User::create([
                'role_id' => $customerRole->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            [$firstName, $lastName] = $this->profileNames($data['name']);

            CustomerProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            return $user;
        });

        $user->sendEmailVerificationNotification();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('success', 'Your customer account is ready. Check your inbox for the verification link.');
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function profileNames(string $name): array
    {
        $normalizedName = trim((string) preg_replace('/\s+/', ' ', $name));
        $parts = explode(' ', $normalizedName, 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
