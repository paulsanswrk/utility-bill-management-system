<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UBMS_Security_Service;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        return Inertia::render('Auth/Register');
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
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'security_question' => ['required', 'string', 'max:255'],
            'security_answer' => ['required', 'string', 'max:255'],
        ]);

        $keys = $this->ubms_security_service->gen_keys_4_new_user($request->password, $request->security_question, $request->security_answer);

        session([
            'session_key' => bin2hex($keys['session_key']),
            'pwd_ciphered' => bin2hex($keys['pwd_ciphered']),
            'work_key_ciphered' => bin2hex($keys['session_work_key_ciphered']),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            //added
            'security_question' => $request->security_question,
            'security_answer_hash' => bin2hex($keys['security_answer_hash']),
            'key_salt' => bin2hex($keys['key_salt']),
            'work_key_encrypted' => bin2hex($keys['work_key_ciphered']),
            'pwd_key_encrypted' => bin2hex($keys['pwd_key_ciphered']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
