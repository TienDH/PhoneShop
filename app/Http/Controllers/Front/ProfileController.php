<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^0[0-9]{9}$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
        $emailChanged = $data['email'] !== $user->email;
        $user->fill($data);
        if ($emailChanged) $user->email_verified_at = null;
        $user->save();
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            return redirect()->route('verification.notice')->with('success', 'Đã lưu hồ sơ. Vui lòng xác thực địa chỉ email mới.');
        }
        return back()->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if (!Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $request->user()->update(['password' => Hash::make($data['password'])]);
        $request->session()->regenerate();
        return back()->with('success', 'Đã thay đổi mật khẩu.');
    }
}
