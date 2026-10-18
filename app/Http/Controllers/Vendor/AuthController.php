<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle an incoming vendor login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('vendor')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var VendorUser $user */
        $user = Auth::guard('vendor')->user();

        if (! $user->vendor || ! $user->vendor->is_active) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi support.',
            ]);
        }

        $request->session()->regenerate();
        session(['vendor_id' => $user->vendor_id]);
        $user->updateQuietly(['last_login_at' => now()]);

        return redirect()->intended('/vendor');
    }

    /**
     * Handle an incoming vendor registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:vendor_users,email'],
            'wa_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $vendorUser = DB::transaction(function () use ($validated) {
            $baseSlug = Str::slug($validated['store_name']);
            if (empty($baseSlug)) {
                $baseSlug = 'rental-'.Str::lower(Str::random(6));
            }

            $slug = $baseSlug;
            $counter = 1;
            while (Vendor::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $vendor = Vendor::create([
                'name' => $validated['store_name'],
                'slug' => $slug,
                'wa_number' => $validated['wa_number'],
                'email' => $validated['email'],
                'is_active' => true,
            ]);

            return VendorUser::create([
                'vendor_id' => $vendor->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'owner',
                'last_login_at' => now(),
            ]);
        });

        Auth::guard('vendor')->login($vendorUser);
        $request->session()->regenerate();
        session(['vendor_id' => $vendorUser->vendor_id]);

        return redirect('/vendor');
    }

    /**
     * Log the vendor out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/vendor/login');
    }
}
