import flatpickr from 'flatpickr';
import 'flatpickr/dist/l10n/id.js';

const toYmd = (date) => {
    if (! date) {
        return '';
    }

    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
};

/**
 * Service datepicker global: input tanggal tunggal dan rentang (range).
 * - `data-date`            → satu tanggal (nilai Y-m-d).
 * - `data-date-range`      → rentang; tulis ke input `data-from`/`data-to`.
 */
export function initDatepickers(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;

    scope.querySelectorAll('[data-date]').forEach((el) => {
        if (el._flatpickr) {
            return;
        }

        flatpickr(el, {
            locale: 'id',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: false,
        });
    });

    scope.querySelectorAll('[data-date-range]').forEach((el) => {
        if (el._flatpickr) {
            return;
        }

        const fromInput = document.querySelector(el.dataset.from);
        const toInput = document.querySelector(el.dataset.to);

        flatpickr(el, {
            locale: 'id',
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            altInputClass: 'os-filter-select w-full',
            allowInput: false,
            defaultDate: (el.dataset.default ?? '')
                .split(',')
                .map((value) => value.trim())
                .filter(Boolean),
            onChange(selectedDates) {
                if (! fromInput || ! toInput) {
                    return;
                }

                fromInput.value = toYmd(selectedDates[0]);
                toInput.value = toYmd(selectedDates[1] ?? selectedDates[0]);
                el.dispatchEvent(new CustomEvent('os-date-range', { bubbles: true }));
            },
        });
    });
}

/**
 * Pantau DOM agar datepicker yang dibuat belakangan ikut terinisialisasi.
 */
export function observeDatepickers() {
    initDatepickers(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    initDatepickers(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
