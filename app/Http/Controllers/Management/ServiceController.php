<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::with('category')->orderBy('name')->paginate(15);

        return view('management.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('management.services.form', [
            'service' => new Service,
            'categories' => $this->categories(),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()
            ->route('management.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        return view('management.services.form', [
            'service' => $service,
            'categories' => $this->categories(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()
            ->route('management.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function toggleStatus(Service $service): RedirectResponse
    {
        $service->update([
            'status' => $service->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Service status updated successfully.');
    }

    private function categories(): Collection
    {
        return ServiceCategory::orderBy('sort_order')->orderBy('name')->get();
    }
}
