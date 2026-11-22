<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('pages.profil', [
            'title' => 'Profil Saya',
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($user->id)],
            'jabatan' => ['nullable', 'string', 'max:120'],
        ]);

        $user->update($data);

        return back()->with('status', 'Profil diperbarui.');
    }
}
