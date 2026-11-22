<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FacilityLogoController extends Controller
{
    public function edit(Request $request): View
    {
        $facility = $this->facilityFor($request);

        return view('pages.pengaturan.logo-puskesmas', [
            'title' => 'Logo Puskesmas',
            'facility' => $facility,
            'hasLogo' => $facility->logo_path !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $facility = $this->facilityFor($request);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $old = $facility->logo_path;
        $path = $request->file('logo')->store('logo-facility', 'local');

        $facility->update(['logo_path' => $path]);

        if ($old !== null) {
            Storage::disk('local')->delete($old);
        }

        return back()->with('status', 'Logo puskesmas diperbarui.');
    }

    public function preview(Request $request): BinaryFileResponse
    {
        $facility = $this->facilityFor($request);

        abort_if($facility->logo_path === null, 404);

        $absolute = Storage::disk('local')->path($facility->logo_path);

        abort_unless(file_exists($absolute), 404);

        return response()->file($absolute);
    }

    private function facilityFor(Request $request): Facility
    {
        $facility = $request->user()->facility;

        abort_if($facility === null, 403, 'Akun ini belum terhubung ke fasilitas.');

        return $facility;
    }
}
