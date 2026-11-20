import './bootstrap';

const syncTopbar = () => {
    document
        .querySelector('.os-topbar')
        ?.classList.toggle('os-topbar--scrolled', window.scrollY > 0);
};

window.addEventListener('scroll', syncTopbar, { passive: true });
document.addEventListener('livewire:navigated', syncTopbar);
syncTopbar();
