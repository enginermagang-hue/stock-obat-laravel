<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Documents\KopAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KopController extends Controller
{
    public function edit(): View
    {
        return view('pages.pengaturan.kop', [
            'title' => 'Pengaturan Kop',
            'lines' => app(KopAssets::class)->lines(),
            'hasLogo' => Setting::get('kop_logo_path') !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $data = $request->validate([
            'line1' => 'required|string|max:120',
            'line2' => 'required|string|max:120',
            'line3' => 'required|string|max:160',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        Setting::set('kop_line1', $data['line1']);
        Setting::set('kop_line2', $data['line2']);
        Setting::set('kop_line3', $data['line3']);

        if ($request->hasFile('logo')) {
            $old = Setting::get('kop_logo_path');
            $path = $request->file('logo')->store('kop', 'local');

            Setting::set('kop_logo_path', $path);

            if ($old !== null) {
                Storage::disk('local')->delete($old);
            }
        }

        return back()->with('status', 'Kop dokumen diperbarui.');
    }

    public function logo(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $path = Setting::get('kop_logo_path');

        abort_if($path === null, 404);

        $absolute = Storage::disk('local')->path($path);

        abort_unless(file_exists($absolute), 404);

        return response()->file($absolute);
    }
}
