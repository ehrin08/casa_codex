<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\TherapistProfileRequest;
use App\Models\Role;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TherapistProfileController extends Controller
{
    public function index(): View
    {
        $therapists = TherapistProfile::with('user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15);

        $users = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'therapist'))
            ->with('therapistProfile')
            ->orderBy('name')
            ->get();

        return view('management.therapists.index', compact('therapists', 'users'));
    }

    public function create(): View
    {
        return view('management.therapists.form', [
            'therapist' => new TherapistProfile,
            'users' => $this->availableUsers(),
        ]);
    }

    public function store(TherapistProfileRequest $request): RedirectResponse
    {
        $createsAccount = $request->boolean('create_account');

        DB::transaction(function () use ($request, $createsAccount): void {
            $data = $request->profileData();

            if ($createsAccount) {
                $data['user_id'] = $this->createTherapistAccount($data, $request->string('account_password')->toString())->id;
            }

            TherapistProfile::create($data);
        });

        return redirect()
            ->route('management.therapists.index')
            ->with('success', $createsAccount ? 'Therapist profile and account created successfully.' : 'Therapist profile created successfully.');
    }

    public function edit(TherapistProfile $therapist): View
    {
        return view('management.therapists.form', [
            'therapist' => $therapist,
            'users' => $this->availableUsers($therapist),
        ]);
    }

    public function update(TherapistProfileRequest $request, TherapistProfile $therapist): RedirectResponse
    {
        $createsAccount = $request->boolean('create_account');

        DB::transaction(function () use ($request, $therapist, $createsAccount): void {
            $data = $request->profileData();

            if ($createsAccount) {
                $data['user_id'] = $this->createTherapistAccount($data, $request->string('account_password')->toString())->id;
            }

            $therapist->update($data);
        });

        return redirect()
            ->route('management.therapists.index')
            ->with('success', $createsAccount ? 'Therapist profile and account updated successfully.' : 'Therapist profile updated successfully.');
    }

    public function toggleStatus(TherapistProfile $therapist): RedirectResponse
    {
        $therapist->update([
            'status' => $therapist->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Therapist status updated successfully.');
    }

    private function availableUsers(?TherapistProfile $therapist = null): Collection
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'therapist'))
            ->with('therapistProfile')
            ->where(function ($query) use ($therapist) {
                $query->whereDoesntHave('therapistProfile');

                if ($therapist?->user_id) {
                    $query->orWhereKey($therapist->user_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $profileData
     */
    private function createTherapistAccount(array $profileData, string $password): User
    {
        $therapistRole = Role::where('name', 'therapist')->firstOrFail();
        $name = trim(implode(' ', array_filter([
            $profileData['first_name'],
            $profileData['last_name'] ?? null,
        ])));

        return User::create([
            'role_id' => $therapistRole->id,
            'name' => $name,
            'email' => $profileData['email'],
            'password' => $password,
        ]);
    }
}
