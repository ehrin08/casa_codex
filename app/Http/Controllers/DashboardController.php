<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $route = match (true) {
            $user->isManagement() => 'management.index',
            $user->isTherapist() => 'therapist.index',
            $user->isCustomer() => 'customer.index',
            default => 'home',
        };

        return redirect()->route($route);
    }
}
