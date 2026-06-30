<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()
                ->route($request->user()->dashboardRouteName())
                ->with('success', 'Your email is already verified.');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = $request->user();

        if (! hash_equals($id, (string) $user->getKey()) ||
            ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()
                ->route('verification.notice')
                ->withErrors([
                    'verification' => 'That verification link is invalid or has expired. Please send a new verification link.',
                ]);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route($user->dashboardRouteName())
                ->with('success', 'Your email is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()
            ->route($user->dashboardRouteName())
            ->with('success', 'Your email has been verified.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()
                ->route($request->user()->dashboardRouteName())
                ->with('success', 'Your email is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
