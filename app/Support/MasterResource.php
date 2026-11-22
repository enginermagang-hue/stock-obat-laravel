<?php

namespace App\Support;

use App\Enums\BentukSediaan;
use App\Enums\GolonganObat;
use App\Enums\JenisLicense;
use App\Enums\JenisSumberDana;
use App\Models\Facility;
use App\Models\KategoriObat;
use App\Models\License;
use App\Models\Obat;
use App\Models\SumberDana;
use App\Models\Supplier;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Registry konfigurasi halaman master (obat, supplier, sumber dana, fasilitas, izin).
 * Dipakai bersama oleh halaman web (Alpine) dan endpoint API.
 */
final class MasterResource
{
    /**
     * @param  array<string, string>  $columns
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<int, string>  $searchable
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $model,
        public readonly array $columns,
        public readonly array $fields,
        public readonly array $searchable,
        public readonly array $unique = [],
    ) {}

    /** @return array<string, self> */
    public static function all(): array
    {
        return collect([
            self::obat(),
            self::supplier(),
            self::sumberDana(),
            self::facility(),
            self::license(),
        ])->keyBy(fn (self $resource) => $resource->key)->all();
    }

    public static function find(string $key): self
    {
        return self::all()[$key] ?? abort(404);
    }

    /** @return array<string, array<string, mixed>> */
    public function resolvedFields(): array
    {
        return collect($this->fields)
            ->map(fn (array $field) => array_diff_key($field, ['options' => true]) + [
                'options' => is_callable($field['options'] ?? null) ? ($field['options'])() : ($field['options'] ?? []),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->fields as $key => $field) {
            $defaults[$key] = $field['default'] ?? ((($field['type'] ?? 'text') === 'checkbox') ? false : '');
        }

        return $defaults;
    }

    /** @return array<string, string> */
    public function rules(?Model $record = null): array
    {
        $rules = [];

        foreach ($this->fields as $key => $field) {
            $rule = $field['rules'] ?? 'nullable';

            if (in_array($key, $this->unique, true)) {
                $table = (new $this->model)->getTable();
                $rule .= '|unique:'.$table.','.$key.($record ? ','.$record->getKey() : '');
            }

            $rules[$key] = $rule;
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    public function present(Model $record): array
    {
        $data = ['id' => $record->getKey()];

        foreach (array_keys($this->fields) as $key) {
            $value = $record->getAttribute($key);

            if ($value instanceof BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof CarbonInterface) {
                $value = $value->format('Y-m-d');
            }

            $data[$key] = $value;
        }

        foreach (array_keys($this->columns) as $key) {
            $data['display_'.$key] = $this->displayValue($record, $key);
        }

        return $data;
    }

    public function displayValue(Model $record, string $key): string
    {
        $value = $record->getAttribute($key);

        return match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof CarbonInterface => $value->format('d-m-Y'),
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            $value === null => '',
            default => (string) $value,
        };
    }

    private static function obat(): self
    {
        return new self(
            key: 'obat',
            title: 'Master Obat',
            model: Obat::class,
            columns: [
                'kode_obat' => 'Kode',
                'nama' => 'Nama',
                'bentuk_sediaan' => 'Bentuk',
                'golongan' => 'Golongan',
                'stok_minimum' => 'Stok min',
                'aktif' => 'Aktif',
            ],
            fields: [
                'kode_obat' => ['label' => 'Kode obat', 'rules' => 'required|string|max:50'],
                'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
                'kategori_id' => [
                    'label' => 'Kategori', 'type' => 'select', 'nullable' => true,
                    'options' => fn () => KategoriObat::query()->orderBy('nama')->pluck('nama', 'id')->all(),
                ],
                'bentuk_sediaan' => [
                    'label' => 'Bentuk sediaan', 'type' => 'select', 'rules' => 'required',
                    'options' => fn () => collect(BentukSediaan::cases())
                        ->mapWithKeys(fn (BentukSediaan $case) => [$case->value => ucfirst($case->value)])->all(),
                ],
                'kekuatan' => ['label' => 'Kekuatan', 'nullable' => true],
                'satuan' => ['label' => 'Satuan', 'rules' => 'required|string|max:50'],
                'golongan' => [
                    'label' => 'Golongan', 'type' => 'select', 'rules' => 'required',
                    'options' => fn () => collect(GolonganObat::cases())
                        ->mapWithKeys(fn (GolonganObat $case) => [$case->value => str_replace('_', ' ', $case->value)])->all(),
                ],
                'no_izin_edar' => ['label' => 'No. izin edar', 'nullable' => true],
                'produsen' => ['label' => 'Produsen', 'nullable' => true],
                'harga_satuan_terakhir' => ['label' => 'Harga satuan', 'type' => 'number', 'default' => 0, 'rules' => 'numeric|min:0'],
                'stok_minimum' => ['label' => 'Stok minimum', 'type' => 'number', 'default' => 0, 'rules' => 'integer|min:0'],
                'butuh_cold_chain' => ['label' => 'Butuh cold chain', 'type' => 'checkbox'],
                'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
            ],
            searchable: ['kode_obat', 'nama', 'bentuk_sediaan', 'golongan', 'stok_minimum', 'aktif'],
            unique: ['kode_obat'],
        );
    }

    private static function supplier(): self
    {
        return new self(
            key: 'supplier',
            title: 'Master Supplier',
            model: Supplier::class,
            columns: ['kode' => 'Kode', 'nama' => 'Nama', 'kontak' => 'Kontak', 'npwp' => 'NPWP', 'aktif' => 'Aktif'],
            fields: [
                'kode' => ['label' => 'Kode', 'rules' => 'required|string|max:50'],
                'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
                'alamat' => ['label' => 'Alamat', 'nullable' => true],
                'kontak' => ['label' => 'Kontak', 'nullable' => true],
                'npwp' => ['label' => 'NPWP', 'nullable' => true],
                'no_izin_usaha' => ['label' => 'No. izin usaha', 'nullable' => true],
                'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
            ],
            searchable: ['kode', 'nama', 'kontak', 'npwp', 'aktif'],
            unique: ['kode'],
        );
    }

    private static function sumberDana(): self
    {
        return new self(
            key: 'sumber-dana',
            title: 'Master Sumber Dana',
            model: SumberDana::class,
            columns: ['kode' => 'Kode', 'nama' => 'Nama', 'jenis' => 'Jenis', 'kode_rekening' => 'Kode rekening', 'aktif' => 'Aktif'],
            fields: [
                'kode' => ['label' => 'Kode', 'rules' => 'required|string|max:50'],
                'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
                'jenis' => [
                    'label' => 'Jenis', 'type' => 'select', 'rules' => 'required',
                    'options' => fn () => collect(JenisSumberDana::cases())
                        ->mapWithKeys(fn (JenisSumberDana $case) => [$case->value => str_replace('_', ' ', $case->value)])->all(),
                ],
                'kode_rekening' => ['label' => 'Kode rekening', 'nullable' => true],
                'program' => ['label' => 'Program', 'nullable' => true],
                'keterangan' => ['label' => 'Keterangan', 'nullable' => true],
                'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
            ],
            searchable: ['kode', 'nama', 'jenis', 'kode_rekening', 'aktif'],
            unique: ['kode'],
        );
    }

    private static function facility(): self
    {
        return new self(
            key: 'facilities',
            title: 'Puskesmas / Fasilitas',
            model: Facility::class,
            columns: ['kode' => 'Kode', 'nama' => 'Nama', 'jenis' => 'Jenis', 'kontak' => 'Kontak', 'aktif' => 'Aktif'],
            fields: [
                'kode' => ['label' => 'Kode', 'rules' => 'required|string|max:50'],
                'nama' => ['label' => 'Nama', 'rules' => 'required|string|max:255'],
                'jenis' => [
                    'label' => 'Jenis', 'type' => 'select', 'rules' => 'required',
                    'options' => ['rs' => 'RS', 'puskesmas' => 'Puskesmas'],
                ],
                'alamat' => ['label' => 'Alamat', 'nullable' => true],
                'kontak' => ['label' => 'Kontak', 'nullable' => true],
                'aktif' => ['label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
            ],
            searchable: ['kode', 'nama', 'jenis', 'kontak', 'aktif'],
            unique: ['kode'],
        );
    }

    private static function license(): self
    {
        return new self(
            key: 'licenses',
            title: 'Izin & CDOB',
            model: License::class,
            columns: [
                'jenis' => 'Jenis',
                'nomor' => 'Nomor',
                'tanggal_terbit' => 'Terbit',
                'tanggal_kedaluwarsa' => 'Kedaluwarsa',
                'status' => 'Status',
            ],
            fields: [
                'jenis' => [
                    'label' => 'Jenis izin', 'type' => 'select', 'rules' => 'required',
                    'options' => fn () => JenisLicense::options(),
                ],
                'nomor' => ['label' => 'Nomor izin', 'rules' => 'required|string|max:255'],
                'tanggal_terbit' => ['label' => 'Tanggal terbit', 'type' => 'date', 'nullable' => true, 'rules' => 'nullable|date'],
                'tanggal_kedaluwarsa' => ['label' => 'Tanggal kedaluwarsa', 'type' => 'date', 'nullable' => true, 'rules' => 'nullable|date'],
                'file_path' => ['label' => 'File lampiran (path)', 'nullable' => true],
                'keterangan' => ['label' => 'Keterangan', 'type' => 'textarea', 'nullable' => true],
            ],
            searchable: ['nomor', 'file_path', 'keterangan'],
        );
    }
}
