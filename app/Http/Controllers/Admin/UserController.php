<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'role' => ['nullable', 'in:user,admin']]);
        $users = User::withCount('orders')->when($data['q'] ?? null, function ($query, $keyword) {
            $query->where(function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')->orWhere('email', 'like', '%' . $keyword . '%');
            });
        })->when($data['role'] ?? null, function ($query, $role) { $query->where('role', $role); })
            ->latest('id')->paginate(15)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', 'in:user,admin']]);
        DB::transaction(function () use ($request, $user, $data) {
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($user->id);
            if ($data['role'] !== 'admin' && ($user->id === $request->user()->id || ($user->role === 'admin' && $admins->count() <= 1))) {
                throw ValidationException::withMessages(['role' => 'Không thể gỡ quyền tài khoản đang sử dụng hoặc quản trị viên cuối cùng.']);
            }
            $user->update($data);
        });
        return back()->with('success', 'Đã cập nhật quyền người dùng.');
    }
}
