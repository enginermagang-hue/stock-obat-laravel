<div class="relative" x-data="{
        open: false,
        query: '',
        results: {},
        loading: false,
        timer: null,
        search() {
            clearTimeout(this.timer);
            const term = this.query.trim();
            if (term.length < 2) { this.results = {}; return; }
            this.timer = setTimeout(async () => {
                this.loading = true;
                try {
                    const { data } = await axios.get('/search', { params: { q: term } });
                    this.results = data.groups ?? {};
                } finally { this.loading = false; }
            }, 250);
        },
        route(group) {
            return { 'Puskesmas': '/master/facilities', 'Sumber Dana': '/master/sumber-dana', 'Supplier': '/master/supplier' }[group] ?? '/master/obat';
        },
    }" @keydown.escape.window="open = false">
    <div class="os-topbar-search">
        <x-icon name="search-line" />
        <input type="text" x-model="query" @input="search()" @focus="open = true"
               @keydown.ctrl.k.prevent="$el.focus()" x-ref="input"
               placeholder="Search [CTRL + K]" aria-label="Cari data master" autocomplete="off">
    </div>

    <div x-show="open && query.trim().length >= 2" x-cloak @click.outside="open = false"
         class="os-dropdown absolute left-0 right-0 top-12 z-40 max-h-80 overflow-y-auto p-2">
        <template x-for="(items, group) in results" :key="group">
            <div>
                <div class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-faint" x-text="group"></div>
                <template x-for="item in items" :key="item.id">
                    <a class="os-dropdown-item" :href="route(group) + '?search=' + encodeURIComponent(item.nama)">
                        <span x-text="item.nama"></span>
                        <span class="text-faint" x-text="item.kode ?? item.kode_obat"></span>
                    </a>
                </template>
            </div>
        </template>
        <div x-show="Object.keys(results).length === 0" class="px-4 py-3 text-[13px] text-faint">
            Tidak ada hasil untuk "<span x-text="query"></span>".
        </div>
    </div>
</div>
