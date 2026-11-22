import './bootstrap';
import Alpine from 'alpinejs';
import PerfectScrollbar from 'perfect-scrollbar';
import { initSelects, observeSelects, syncSelects } from './select';
import { observeDatepickers } from './datepicker';

window.Alpine = Alpine;
window.osSyncSelects = syncSelects;

/**
 * Komponen Alpine untuk halaman master: daftar + modal CRUD lewat API.
 */
window.masterPage = (config) => ({
    endpoint: config.endpoint,
    columns: config.columns,
    fields: config.fields,
    defaults: config.defaults,
    canManage: config.canManage,
    rows: [],
    meta: { current_page: 1, last_page: 1, total: 0, from: null, to: null },
    search: new URLSearchParams(window.location.search).get('search') ?? '',
    loading: false,
    loaded: false,
    saving: false,
    modalOpen: false,
    editingId: null,
    form: JSON.parse(JSON.stringify(config.defaults)),
    errors: {},
    status: '',

    async load(page = 1) {
        this.loading = true;

        try {
            const { data } = await axios.get(this.endpoint, { params: { page, search: this.search } });
            this.rows = data.data;
            this.meta = data.meta;
            this.loaded = true;
        } finally {
            this.loading = false;
        }
    },

    openCreate() {
        this.editingId = null;
        this.errors = {};
        this.form = JSON.parse(JSON.stringify(this.defaults));
        this.modalOpen = true;
        this.$nextTick(() => initSelects());
    },

    openEdit(row) {
        this.editingId = row.id;
        this.errors = {};
        this.form = JSON.parse(JSON.stringify(this.defaults));

        Object.keys(this.fields).forEach((key) => {
            this.form[key] = row[key] ?? this.defaults[key];
        });

        this.modalOpen = true;
        this.$nextTick(() => {
            initSelects();
            syncSelects();
        });
    },

    async submit() {
        this.saving = true;
        this.errors = {};

        try {
            if (this.editingId) {
                await axios.put(`${this.endpoint}/${this.editingId}`, this.form);
            } else {
                await axios.post(this.endpoint, this.form);
            }

            this.modalOpen = false;
            this.status = 'Data tersimpan.';
            await this.load(this.meta.current_page);
        } catch (error) {
            this.errors = error.response?.status === 422
                ? (error.response.data.errors ?? {})
                : { nama: [error.response?.data?.message ?? 'Gagal menyimpan data.'] };
        } finally {
            this.saving = false;
        }
    },

    async destroy(row) {
        if (! window.confirm('Hapus data ini?')) {
            return;
        }

        try {
            await axios.delete(`${this.endpoint}/${row.id}`);
            this.status = 'Data dihapus.';
            await this.load(this.meta.current_page);
        } catch (error) {
            window.alert(error.response?.data?.message ?? 'Gagal menghapus data.');
        }
    },
});

/**
 * Komponen Alpine untuk form LPLPO: item dikelola di modal, tabel read-only.
 */
window.lplpoForm = (config) => ({
    rows: config.rows ?? [],
    names: config.names ?? {},
    itemModal: false,
    itemIndex: null,
    itemError: '',
    autoPemasukan: false,
    autoStok: false,
    draft: {
        obat_id: '',
        stok_awal: 0,
        pemasukan: 0,
        pemakaian: 0,
        stok_akhir: 0,
        jumlah_diminta: 0,
        keterangan: '',
    },

    obatName(id) {
        return this.names[id] ?? '';
    },

    emptyDraft() {
        return {
            obat_id: '',
            stok_awal: 0,
            pemasukan: 0,
            pemakaian: 0,
            stok_akhir: 0,
            jumlah_diminta: 0,
            keterangan: '',
        };
    },

    openAdd() {
        this.itemIndex = null;
        this.itemError = '';
        this.autoPemasukan = false;
        this.autoStok = false;
        this.draft = this.emptyDraft();
        this.itemModal = true;
        this.$nextTick(() => window.osSyncSelects?.());
    },

    openEdit(index) {
        this.itemIndex = index;
        this.itemError = '';
        this.autoPemasukan = false;
        this.autoStok = false;
        this.draft = { ...this.emptyDraft(), ...this.rows[index] };
        this.itemModal = true;
        this.$nextTick(() => {
            window.osSyncSelects?.();
            this.fetchLookup(true);
        });
    },

    get formRoot() {
        return document.querySelector('[data-lplpo-form]') ?? document;
    },

    fieldValue(name) {
        return this.formRoot.querySelector(`[name="${name}"]`)?.value ?? '';
    },

    fetchLookup(preserveValues = false) {
        if (! this.draft.obat_id) {
            return;
        }

        const params = {
            obat_id: this.draft.obat_id,
            bulan: this.fieldValue('bulan') || undefined,
            tahun: this.fieldValue('tahun') || undefined,
            facility_id: this.fieldValue('facility_id') || undefined,
        };

        axios.get('/lplpo/item-lookup', { params })
            .then(({ data }) => {
                this.draft.stok_awal = data.stok_awal;

                if (! preserveValues) {
                    this.draft.pemasukan = data.pemasukan;
                    this.draft.pemakaian = data.pemakaian;
                    this.draft.stok_akhir = data.stok_akhir;
                    this.autoPemasukan = true;
                    this.autoStok = true;
                } else {
                    this.recomputeStok();
                    this.autoStok = true;
                }
            })
            .catch(() => {
                // Data otomatis tidak tersedia; biarkan input manual.
            });
    },

    onObatChange() {
        this.fetchLookup(false);
    },

    recomputeStok() {
        const awal = Number(this.draft.stok_awal) || 0;
        const masuk = Number(this.draft.pemasukan) || 0;
        const keluar = Number(this.draft.pemakaian) || 0;
        this.draft.stok_akhir = Math.max(0, awal + masuk - keluar);
    },

    onPemasukanInput() {
        this.autoPemasukan = false;

        if (this.autoStok) {
            this.recomputeStok();
        }
    },

    onPemakaianInput() {
        if (this.autoStok) {
            this.recomputeStok();
        }
    },

    onStokAkhirInput() {
        this.autoStok = false;
    },

    saveItem() {
        this.itemError = '';

        if (! this.draft.obat_id) {
            this.itemError = 'Obat wajib dipilih.';
            return;
        }

        const duplicate = this.rows.some((row, index) =>
            String(row.obat_id) === String(this.draft.obat_id) && index !== this.itemIndex
        );

        if (duplicate) {
            this.itemError = 'Obat sudah ada di daftar item.';
            return;
        }

        const numbers = ['pemasukan', 'pemakaian', 'stok_akhir', 'jumlah_diminta'];

        for (const key of numbers) {
            if (Number(this.draft[key]) < 0) {
                this.itemError = 'Angka tidak boleh negatif.';
                return;
            }
        }

        const item = {
            obat_id: String(this.draft.obat_id),
            pemasukan: Number(this.draft.pemasukan) || 0,
            pemakaian: Number(this.draft.pemakaian) || 0,
            stok_akhir: Number(this.draft.stok_akhir) || 0,
            jumlah_diminta: Number(this.draft.jumlah_diminta) || 0,
            keterangan: this.draft.keterangan ?? '',
        };

        if (this.itemIndex === null) {
            this.rows.push(item);
        } else {
            this.rows.splice(this.itemIndex, 1, item);
        }

        this.itemModal = false;
    },

    remove(index) {
        this.rows.splice(index, 1);
    },
});

/**
 * Komponen Alpine untuk form permintaan: item dikelola di modal, tabel read-only.
 */
window.permintaanForm = (config) => ({
    rows: config.rows ?? [],
    names: config.names ?? {},
    itemModal: false,
    itemIndex: null,
    itemError: '',
    draft: {
        obat_id: '',
        jumlah_diminta: 1,
        keterangan: '',
    },

    obatName(id) {
        return this.names[id] ?? '';
    },

    emptyDraft() {
        return {
            obat_id: '',
            jumlah_diminta: 1,
            keterangan: '',
        };
    },

    openAdd() {
        this.itemIndex = null;
        this.itemError = '';
        this.draft = this.emptyDraft();
        this.itemModal = true;
        this.$nextTick(() => window.osSyncSelects?.());
    },

    openEdit(index) {
        this.itemIndex = index;
        this.itemError = '';
        this.draft = { ...this.emptyDraft(), ...this.rows[index] };
        this.itemModal = true;
        this.$nextTick(() => window.osSyncSelects?.());
    },

    saveItem() {
        this.itemError = '';

        if (! this.draft.obat_id) {
            this.itemError = 'Obat wajib dipilih.';
            return;
        }

        if (Number(this.draft.jumlah_diminta) < 1) {
            this.itemError = 'Jumlah diminta minimal 1.';
            return;
        }

        const duplicate = this.rows.some((row, index) =>
            String(row.obat_id) === String(this.draft.obat_id) && index !== this.itemIndex
        );

        if (duplicate) {
            this.itemError = 'Obat sudah ada di daftar item.';
            return;
        }

        const item = {
            obat_id: String(this.draft.obat_id),
            jumlah_diminta: Number(this.draft.jumlah_diminta) || 0,
            keterangan: this.draft.keterangan ?? '',
        };

        if (this.itemIndex === null) {
            this.rows.push(item);
        } else {
            this.rows.splice(this.itemIndex, 1, item);
        }

        this.itemModal = false;
    },

    remove(index) {
        this.rows.splice(index, 1);
    },
});

/**
 * Komponen Alpine untuk form distribusi: pratinjau item FEFO saat permintaan dipilih.
 */
window.distributionForm = (config) => ({
    permintaanId: config.permintaanId ? String(config.permintaanId) : '',
    endpoint: config.endpoint,
    loading: false,
    preview: null,
    error: '',
    requestId: 0,

    async loadPreview(value = null) {
        if (value !== null) {
            this.permintaanId = value ? String(value) : '';
        }

        if (! this.permintaanId) {
            this.preview = null;
            this.error = '';
            return;
        }

        const current = ++this.requestId;
        this.loading = true;
        this.error = '';

        try {
            const { data } = await axios.get(this.endpoint, {
                params: { permintaan_id: this.permintaanId },
            });

            if (current !== this.requestId) {
                return;
            }

            this.preview = data;
        } catch (error) {
            if (current !== this.requestId) {
                return;
            }

            this.preview = null;
            this.error = error.response?.data?.message ?? 'Gagal memuat pratinjau distribusi.';
        } finally {
            if (current === this.requestId) {
                this.loading = false;
            }
        }
    },

    formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value) || 0);
    },
});

/**
 * Komponen Alpine untuk modal konfirmasi penerimaan distribusi.
 */
window.confirmModal = (config) => ({
    confirmModal: false,
    useMyData: false,
    formError: '',
    me: config.me ?? {},
    defaults: config.defaults ?? {},
    form: {
        penerima_nama: '',
        penerima_nip: '',
        penerima_jabatan: '',
        penerima_unit: '',
    },

    openConfirm() {
        this.formError = '';
        this.form = {
            penerima_nama: this.defaults.nama ?? '',
            penerima_nip: this.defaults.nip ?? '',
            penerima_jabatan: this.defaults.jabatan ?? '',
            penerima_unit: this.defaults.unit ?? '',
        };
        this.useMyData = false;
        this.confirmModal = true;
    },

    toggleMyData(checked) {
        this.useMyData = checked;

        if (checked) {
            this.form.penerima_nama = this.me.nama ?? '';
            this.form.penerima_nip = this.me.nip ?? '';
            this.form.penerima_jabatan = this.me.jabatan ?? '';
            this.form.penerima_unit = this.me.unit ?? '';
        } else {
            this.form.penerima_nama = '';
            this.form.penerima_nip = '';
            this.form.penerima_jabatan = '';
            this.form.penerima_unit = '';
        }
    },
});

/**
 * Komponen Alpine untuk form PO: item dikelola di modal, tabel read-only.
 */
window.poForm = (config) => ({
    rows: config.rows ?? [],
    meta: config.meta ?? {},
    itemModal: false,
    itemIndex: null,
    itemError: '',
    draft: { obat_id: '', jumlah: 1, harga_beli: 0 },

    rupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(value) || 0));
    },

    obatName(id) {
        return this.meta[id]?.nama ?? '';
    },

    subtotal(row) {
        return (Number(row.jumlah) || 0) * (Number(row.harga_beli) || 0);
    },

    get grandTotal() {
        return this.rows.reduce((total, row) => total + this.subtotal(row), 0);
    },

    get draftSubtotal() {
        return (Number(this.draft.jumlah) || 0) * (Number(this.draft.harga_beli) || 0);
    },

    openAdd() {
        this.itemIndex = null;
        this.itemError = '';
        this.draft = { obat_id: '', jumlah: 1, harga_beli: 0 };
        this.itemModal = true;
        this.syncObatSelect();
    },

    openEdit(index) {
        this.itemIndex = index;
        this.itemError = '';
        this.draft = { ...this.rows[index] };
        this.itemModal = true;
        this.syncObatSelect();
    },

    syncObatSelect() {
        this.$nextTick(() => window.osSyncSelects?.());
    },

    onObatChange() {
        const meta = this.meta[this.draft.obat_id];

        if (meta && (Number(this.draft.harga_beli) || 0) === 0) {
            this.draft.harga_beli = meta.harga;
        }
    },

    saveItem() {
        this.itemError = '';

        if (!this.draft.obat_id) {
            this.itemError = 'Obat wajib dipilih.';
            return;
        }

        if (Number(this.draft.jumlah) < 1) {
            this.itemError = 'Jumlah minimal 1.';
            return;
        }

        if (Number(this.draft.harga_beli) < 0) {
            this.itemError = 'Harga beli tidak boleh negatif.';
            return;
        }

        const duplicate = this.rows.some((row, index) =>
            String(row.obat_id) === String(this.draft.obat_id) && index !== this.itemIndex
        );

        if (duplicate) {
            this.itemError = 'Obat sudah ada di daftar item.';
            return;
        }

        const item = {
            obat_id: String(this.draft.obat_id),
            jumlah: Number(this.draft.jumlah),
            harga_beli: Number(this.draft.harga_beli),
        };

        if (this.itemIndex === null) {
            this.rows.push(item);
        } else {
            this.rows.splice(this.itemIndex, 1, item);
        }

        this.itemModal = false;
    },

    remove(index) {
        this.rows.splice(index, 1);
    },
});

/**
 * Filter + pencarian list via AJAX tanpa reload halaman.
 * Controller mengembalikan partial HTML saat parameter `partial=1`.
 */
window.ajaxFilter = (config) => ({
    endpoint: config.endpoint,
    loading: false,
    requestId: 0,

    async load(page = 1) {
        const form = this.$refs.filterForm;
        const params = new URLSearchParams(new FormData(form));
        params.set('page', page);
        params.set('partial', '1');

        const current = ++this.requestId;
        this.loading = true;

        try {
            const { data } = await axios.get(this.endpoint, { params });
            // Abaikan respons lama bila pengguna mengetik cepat.
            if (current !== this.requestId) {
                return;
            }
            this.$refs.list.innerHTML = data;

            const clean = new URLSearchParams(new FormData(form));
            clean.set('page', page);
            window.history.replaceState({}, '', `${this.endpoint}?${clean.toString()}`);
        } finally {
            if (current === this.requestId) {
                this.loading = false;
            }
        }
    },

    submit() {
        this.load(1);
    },

    onListClick(event) {
        const anchor = event.target.closest('a[href]');

        if (! anchor) {
            return;
        }

        const url = new URL(anchor.href, window.location.origin);

        if (! url.searchParams.has('page')) {
            return;
        }

        event.preventDefault();
        this.load(Number(url.searchParams.get('page')) || 1);
    },

    reset() {
        this.$refs.filterForm.reset();
        window.osSyncSelects?.();
        this.$refs.filterForm.querySelectorAll('[data-date-range]').forEach((el) => el._flatpickr?.clear());
        this.load(1);
    },
});

const syncTopbar = () => {
    document
        .querySelector('.os-topbar')
        ?.classList.toggle('os-topbar--scrolled', window.scrollY > 0);
};

let sidebarScrollbar = null;
let sidebarResizeTimer;

const openActiveMenuGroup = () => {
    const active = document.querySelector('.os-nav-link--active');
    const group = active?.closest('[data-group]')?.dataset.group;

    if (group) {
        window.dispatchEvent(new CustomEvent('os-open-group', { detail: group }));
        setTimeout(() => sidebarScrollbar?.update(), 0);
    }
};

const syncSidebarShadow = () => {
    const scrollTop = document.querySelector('.os-sidebar-nav')?.scrollTop ?? 0;

    document
        .querySelector('.os-sidebar-shadow')
        ?.classList.toggle('os-sidebar-shadow--visible', scrollTop > 0);
};

const initSidebarScrollbar = () => {
    const nav = document.querySelector('.os-sidebar-nav');

    sidebarScrollbar?.destroy();
    sidebarScrollbar = null;

    if (nav === null || window.innerWidth < 1200) {
        syncSidebarShadow();

        return;
    }

    sidebarScrollbar = new PerfectScrollbar(nav, {
        wheelPropagation: false,
        suppressScrollX: true,
    });

    syncSidebarShadow();
};

window.addEventListener('scroll', syncTopbar, { passive: true });
window.addEventListener('resize', () => {
    clearTimeout(sidebarResizeTimer);
    sidebarResizeTimer = setTimeout(initSidebarScrollbar, 200);
});

document.addEventListener('ps-scroll-y', syncSidebarShadow, true);

document.addEventListener('click', (event) => {
    if (event.target.closest('.os-nav-parent')) {
        setTimeout(() => sidebarScrollbar?.update(), 0);
    }

    if (event.target.closest('.os-sidebar-brand .os-icon-btn')) {
        setTimeout(() => sidebarScrollbar?.update(), 350);
    }
});

syncTopbar();
initSidebarScrollbar();

setTimeout(openActiveMenuGroup, 0);

Alpine.start();
observeSelects();
observeDatepickers();
