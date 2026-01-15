<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DevPasswordController extends Controller
{
    public function show(Request $request)
    {
        // If no dev password is set, redirect to home
        if (! config('app.dev_password')) {
            return redirect()->route('home');
        }

        return Inertia::render('DevPassword', [
            'errors' => $request->session()->get('errors')?->getBag('default')?->getMessages(),
        ]);
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'dev_password' => 'required|string',
        ]);

        if ($request->input('dev_password') === config('app.dev_password')) {
            $request->session()->put('dev_authenticated', true);

            return redirect()->intended(route('home'));
        }

        return redirect()->route('dev-password')->withErrors([
            'dev_password' => 'Invalid password.',
        ]);
    }
}
