<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\CustomerProfileRequest;
use App\Models\CustomerProfile;
use App\Services\ManagementProfileAccountService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    private const ROLE_NAME = 'customer';

    private const PROFILE_RELATION = 'customerProfile';

    public function __construct(private readonly ManagementProfileAccountService $accounts) {}

    public function index(): View
    {
        $customers = CustomerProfile::with('user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15);

        $users = $this->availableUsers();

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
                $data['user_id'] = $this->accounts
                    ->createLinkedAccount(self::ROLE_NAME, $data, $request->string('account_password')->toString())
                    ->id;
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
                $data['user_id'] = $this->accounts
                    ->createLinkedAccount(self::ROLE_NAME, $data, $request->string('account_password')->toString())
                    ->id;
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
        return $this->accounts->availableUsers(self::ROLE_NAME, self::PROFILE_RELATION, $customer);
    }
}
