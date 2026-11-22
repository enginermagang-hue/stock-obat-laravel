import TomSelect from 'tom-select';

const SELECTOR = 'select:not([data-no-tomselect])';
const visibilityObserver = typeof IntersectionObserver !== 'undefined'
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                visibilityObserver.unobserve(entry.target);
                initSelect(entry.target);
            }
        });
    })
    : null;

/**
 * Service TomSelect: mengganti seluruh <select> native dengan TomSelect.
 * Aman dipanggil berulang; elemen yang sudah diinisialisasi akan dilewati.
 */
export function initSelects(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;

    scope.querySelectorAll(SELECTOR).forEach((el) => initSelect(el));

    if (scope instanceof Element && scope.matches(SELECTOR)) {
        initSelect(scope);
    }
}

function isVisible(el) {
    return el.isConnected && el.getClientRects().length > 0;
}

function initSelect(el) {
    if (!(el instanceof HTMLSelectElement) || el.tomselect instanceof TomSelect) {
        return;
    }

    // Abaikan select internal komponen pihak ketiga (mis. dropdown bulan/tahun Flatpickr).
    if (el.closest('.flatpickr-calendar')) {
        return;
    }

    // Elemen di dalam modal/panel yang masih tersembunyi belum punya ukuran;
    // tunggu sampai terlihat agar lebar kontrol tidak 0.
    if (!isVisible(el)) {
        visibilityObserver?.observe(el);

        return;
    }

    const initialWidth = el.getBoundingClientRect().width;

    // Dropdown di dalam modal harus menempel ke panel modal agar ikut scroll
    // dan tidak terpotong; di luar modal tetap menempel ke body.
    const modalBody = el.closest('.os-modal-body');
    const positionedParent = el.closest('.os-floating');
    const forceBodyParent = el.dataset.dropdown === 'body';

    // Di dalam modal, dropdown menempel pada wadah ber-`position: relative`
    // agar ikut scroll. Select dengan data-dropdown="body" sengaja ditempel
    // ke body supaya tidak terpotong overflow modal.
    const dropdownParent = forceBodyParent
        ? 'body'
        : (modalBody ? (positionedParent ?? modalBody) : 'body');

    const instance = new TomSelect(el, {
        allowEmptyOption: true,
        create: false,
        maxItems: 1,
        dropdownParent,
        closeAfterScroll: modalBody === null,
        copyClassesToDropdown: false,
        onDropdownOpen() {
            this.wrapper.classList.add('ts-wrapper--open');
        },
        onDropdownClose() {
            this.wrapper.classList.remove('ts-wrapper--open');
        },
    });

    // TomSelect menyalin class <select> ke wrapper; buang agar gaya os-* tidak dobel.
    ['os-floating-input', 'os-select', 'os-filter-select'].forEach((className) => {
        instance.wrapper.classList.remove(className);
    });

    // Sinkronkan nilai TomSelect kembali ke <select> (dan Alpine x-model).
    instance.on('change', (value) => {
        const next = value ?? '';

        if (el.value !== next) {
            el.value = next;
        }
    });

    el.addEventListener('change', () => {
        if (instance.getValue() !== el.value) {
            instance.setValue(el.value, true);
        }
    });

    if (el.style.width) {
        instance.wrapper.style.width = el.style.width;
        instance.wrapper.style.minWidth = el.style.width;
    } else if (el.closest('.os-table')) {
        // Di dalam tabel, <select> sebelumnya mengisi lebar sel; pertahankan agar
        // layout tabel tidak menyusut setelah diganti TomSelect.
        instance.wrapper.style.width = Math.max(initialWidth, 160) + 'px';
    }
}

/**
 * Sinkronkan nilai state (mis. Alpine x-model yang diubah programatik)
 * ke seluruh instance TomSelect.
 */
export function syncSelects(root = document) {
    root.querySelectorAll(SELECTOR).forEach((el) => {
        if (el.tomselect instanceof TomSelect && el.tomselect.getValue() !== el.value) {
            el.tomselect.setValue(el.value, true);
        }
    });
}

/**
 * Pantau DOM agar <select> yang dibuat belakangan (modal, baris dinamis)
 * ikut terinisialisasi otomatis.
 */
export function observeSelects() {
    initSelects(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    initSelects(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Dropdown yang menempel ke body tidak ikut bergerak saat body modal discroll;
    // tutup agar tidak "melayang". Scroll di dalam daftar opsi diabaikan.
    document.addEventListener(
        'scroll',
        (event) => {
            const target = event.target;

            if (!(target instanceof Element)) {
                return;
            }

            if (target.closest('.ts-dropdown')) {
                return;
            }

            if (target.closest('.os-modal-body')) {
                document.querySelectorAll('select.tomselected').forEach((el) => {
                    el.tomselect?.close();
                });
            }
        },
        { passive: true, capture: true },
    );
}
