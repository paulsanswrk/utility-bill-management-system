<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\User;
use App\Services\UBMS_Security_Service;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{

    private UBMS_Security_Service $ubms_security_service;

    function __construct(UBMS_Security_Service $ubms_security_service)
    {
        $this->ubms_security_service = $ubms_security_service;
    }

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'email' => session('email'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
//            'captcha_token'  => [new Recaptcha],
        ]);

        $lang = $request->cookie('user_lang') ?? 'en';
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'language' => $lang,
        ]);

/*        if (session('no_email_verification_required')) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }
        }*/

        $lang = $request->cookie('user_lang');


        $pending_invitations = DB::table('household_invitations')
            ->where('invitee_email', $request->email)
            ->where('invitation_status', 'accepted')
            ->pluck('household_ids')
            ->toArray();

        if (empty($pending_invitations)) {
            $household = Household::create([
                'name' => trans('Default Household', [], $lang),
            ]);

            Household::add_user_households($user->id, (string)$household->id);
        } else {
            Household::add_user_households($user->id, implode(',', $pending_invitations));

            DB::table('household_invitations')
                ->where('invitee_email', $request->email)
                ->where('invitation_status', 'accepted')
                ->delete();

            if ($user->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }

        }


        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
