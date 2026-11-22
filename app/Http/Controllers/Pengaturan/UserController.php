<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('pages.pengaturan.pengguna', [
            'title' => 'Pengguna',
            'users' => User::with(['roles', 'facility'])->orderBy('name')->paginate(20),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $data = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($user->id)],
            'jabatan' => ['nullable', 'string', 'max:120'],
        ]);

        $user->update($data);

        return back()->with('status', 'Identitas pengguna diperbarui.');
    }
}
