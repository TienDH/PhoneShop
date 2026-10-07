<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    protected $redirectTo = '/email/verified';

    public function show()
    {
        return view('auth.verify');
    }

    public function verify(Request $request)
    {
        $id = $request->route('id');
        $hash = $request->route('hash');

        $user = User::findOrFail($id);

        if (!hash_equals(
            (string) $hash,
            sha1($user->getEmailForVerification())
        )) {
            abort(403, 'Invalid verification link');
        }

        if (!$user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        return redirect($this->redirectTo);
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('verification.notice')->withErrors(['Bạn phải đăng nhập để gửi lại email xác thực.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectTo);
        }

        $user->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }
}