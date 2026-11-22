<?php

namespace App\Services\Documents;

use App\Models\Facility;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class KopAssets
{
    /** @var array{line1: string, line2: string, line3: string}|null */
    private ?array $lines = null;

    /** @return array{line1: string, line2: string, line3: string} */
    public function lines(): array
    {
        return $this->lines ??= [
            'line1' => Setting::get('kop_line1', config('kop.line1')),
            'line2' => Setting::get('kop_line2', config('kop.line2')),
            'line3' => Setting::get('kop_line3', config('kop.line3')),
        ];
    }

    public function dinasLogo(): ?string
    {
        return $this->dataUri(Setting::get('kop_logo_path'));
    }

    public function facilityLogo(?Facility $facility): ?string
    {
        return $this->dataUri($facility?->logo_path);
    }

    private function dataUri(?string $path): ?string
    {
        if ($path === null || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
