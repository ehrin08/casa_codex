<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\CustomerProfileRequest;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function index(): View
    {
        $customers = CustomerProfile::with('user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15);

        return view('management.customers.index', compact('customers'));
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
        CustomerProfile::create($request->validated());

        return redirect()
            ->route('management.customers.index')
            ->with('success', 'Customer profile created successfully.');
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
        $customer->update($request->validated());

        return redirect()
            ->route('management.customers.index')
            ->with('success', 'Customer profile updated successfully.');
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
            ->where(function ($query) use ($customer) {
                $query->whereDoesntHave('customerProfile');

                if ($customer?->user_id) {
                    $query->orWhereKey($customer->user_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
