<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\CustomerProfileRequest;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function index(): View
    {
        $customers = CustomerProfile::with('user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15);

        $users = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'customer'))
            ->with('customerProfile')
            ->orderBy('name')
            ->get();

        return view('management.customers.index', compact('customers', 'users'));
    }

    public function create(): View
    {
        return view('management.customers.form', [
            'customer' => new CustomerProfile,
            'users' => $this->availableUsers(),
        ]);
    }

    public function store(CustomerProfileRequest $request): RedirectResponse
    {
        $createsAccount = $request->boolean('create_account');

        DB::transaction(function () use ($request, $createsAccount): void {
            $data = $request->profileData();

            if ($createsAccount) {
                $data['user_id'] = $this->createCustomerAccount($data, $request->string('account_password')->toString())->id;
            }

            CustomerProfile::create($data);
        });

        return redirect()
            ->route('management.customers.index')
            ->with('success', $createsAccount ? 'Customer profile and account created successfully.' : 'Customer profile created successfully.');
    }

    public function edit(CustomerProfile $customer): View
    {
        return view('management.customers.form', [
            'customer' => $customer,
            'users' => $this->availableUsers($customer),
        ]);
    }

    public function update(CustomerProfileRequest $request, CustomerProfile $customer): RedirectResponse
    {
        $createsAccount = $request->boolean('create_account');

        DB::transaction(function () use ($request, $customer, $createsAccount): void {
            $data = $request->profileData();

            if ($createsAccount) {
                $data['user_id'] = $this->createCustomerAccount($data, $request->string('account_password')->toString())->id;
            }

            $customer->update($data);
        });

        return redirect()
            ->route('management.customers.index')
            ->with('success', $createsAccount ? 'Customer profile and account updated successfully.' : 'Customer profile updated successfully.');
    }

    public function toggleStatus(CustomerProfile $customer): RedirectResponse
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', 'Customer status updated successfully.');
    }

    private function availableUsers(?CustomerProfile $customer = null): Collection
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'customer'))
            ->with('customerProfile')
            ->where(function ($query) use ($customer) {
                $query->whereDoesntHave('customerProfile');

                if ($customer?->user_id) {
                    $query->orWhereKey($customer->user_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $profileData
     */
    private function createCustomerAccount(array $profileData, string $password): User
    {
        $customerRole = Role::where('name', 'customer')->firstOrFail();
        $name = trim(implode(' ', array_filter([
            $profileData['first_name'],
            $profileData['last_name'] ?? null,
        ])));

        return User::create([
            'role_id' => $customerRole->id,
            'name' => $name,
            'email' => $profileData['email'],
            'password' => $password,
        ]);
    }
}
