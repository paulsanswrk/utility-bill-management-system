<?php

namespace App\Http\Controllers;

use App\Mail\ChangeEmailNotification;
use App\Models\EmailChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Inertia\Inertia;

class ManageUsersController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return [
                'success' => false,
                'message' => 'You have no access to this page',
            ];
        }


        $page = $request->get('page', 1);
        $rows = $request->get('rows', 20);
        $sortField = $request->get('sortField', 'name');
        $sortOrder = $request->get('sortOrder', 'asc');

        $users = User::with('email_change_request:id,new_email,expires_at')
            ->select('id', 'name', 'email', 'is_admin')
            ->orderBy($sortField, $sortOrder)
            ->paginate($rows, ['*'], 'page', $page)
            ->toArray();

        foreach ($users['data'] as $n => $user) {
            if (!$user['email_change_request'] || ($user['email_change_request']['expires_at'] < now())) {
                unset($users['data'][$n]['email_change_request']);
            }
        }

        return [
            'success' => true,
            'users' => $users,
        ];

    }

    public function update_user(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return [
                'success' => false,
                'message' => 'You have no permission for this action',
            ];
        }

        $user = User::find($request->id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        $user->name = $request->name;
        if (auth()->id() !== $user->id) {
            $user->is_admin = $request->is_admin;
        }

        $notification = '';
        if ($user->email != $request->email) {
            $this->change_email($user, $request->email);
            $notification = 'An email was sent to the new address. Waiting for the user to confirm the change.';
        }
//        $user->email = $request->email;


        $user->save();

        return ['success' => true, 'notification' => $notification];
    }

    private function change_email($user, $new_email)
    {

        $req = EmailChangeRequest::updateOrCreate(
            ['id' => $user->id],
            [
                'id' => $user->id,
                'new_email' => $new_email,
                'expires_at' => now()->addHours(24),
                'uuid' => (string)Str::uuid()
            ]);

        \Mail::to($new_email)->send(new ChangeEmailNotification($user, $req->uuid));

//        Log::channel('ubms')->info("Notification sent to user: " . $user->id);
    }

    // /confirmemailchange

    public function change_email_confirmation(Request $request, $uuid)
    {
        $emailChangeRequest = EmailChangeRequest::where('uuid', $uuid)->where('expires_at', '>', now())->first();

        if (!$emailChangeRequest) {

            return Inertia::render('Information', [
                'title' => 'Error',
                'status' => 'error',
                'text' => 'The email change request you provided is either invalid or has expired. Please request a new one if needed.',
            ]);

        }

        $user = (new User)->find($emailChangeRequest->id);

        if (!$user) {
            return Inertia::render('Information', [
                'title' => 'Error',
                'status' => 'error',
                'text' => 'No user associated with this email change request was found. Please check your request or contact support.',
            ]);

        }

        $user->email = $emailChangeRequest->new_email;
        $user->email_verified_at = now();
        $user->save();

        $emailChangeRequest->delete(); //not working?

        return Inertia::render('Information', [
            'title' => 'Success',
            'status' => 'success',
            'text' => 'Email successfully changed',
        ]);

    }

    public function change_pwd(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return [
                'success' => false,
                'message' => 'You have no permission for this action',
            ];
        }

        // checks that the `password_confirmation` field matches the `password` field
        $validated = $request->validate([
            'password' => ['required', PasswordRules::defaults(), 'confirmed'],
        ]);

        $user = User::find($request->user_id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return ['success' => true,];
    }

    public function send_pwd_reset_link(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return [
                'success' => false,
                'message' => 'You have no permission for this action',
            ];
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return ['success' => true,];
        } else {
            return ['success' => false, 'message' => __($status),];
        }

    }

    public function impersonate(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return [
                'success' => false,
                'message' => 'You have no access to this functionality',
            ];
        }

        $user_id = $request->user_id;
        $admin_id = auth()->id();

        $user = User::find($user_id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        auth('web')->login($user);

        session(['impersonating_admin_id' => $admin_id]);
        session(['impersonated_user_id' => $user_id]);
        session(['impersonated_user_name' => $user->name]);
        session(['impersonated_user_email' => $user->email]);

        return ['success' => true,];
    }

    public function exit_impersonation()
    {
        $impersonating_admin_id = session('impersonating_admin_id');

        if (!$impersonating_admin_id) {
            return [
                'success' => false,
                'message' => 'Np impersonation data found',
            ];
        }

        session()->forget(['impersonating_admin_id', 'impersonated_user_id', 'impersonated_user_name', 'impersonated_user_email']);

        auth('web')->loginUsingId($impersonating_admin_id);

        return redirect()->route('manage_users');
    }

}
