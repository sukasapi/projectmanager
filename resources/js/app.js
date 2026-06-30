import './bootstrap';
import Swal from 'sweetalert2';

window.Swal = Swal;

// PWA: daftarkan service worker (installable di mobile). Aman untuk Livewire (network-first).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// Dialog konfirmasi & toast berbasis SweetAlert2 (mengganti window.confirm/alert).
document.addEventListener('alpine:init', () => {
    // $confirm('pesan', { danger: true })  → Promise<boolean>
    window.Alpine.magic('confirm', () => (message, opts = {}) =>
        Swal.fire({
            title: opts.title ?? 'Konfirmasi',
            text: message,
            icon: opts.icon ?? (opts.danger ? 'warning' : 'question'),
            showCancelButton: true,
            confirmButtonText: opts.confirmText ?? 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: opts.danger ? '#dc2626' : '#2b4f62',
            cancelButtonColor: '#94a3b8',
            reverseButtons: true,
        }).then((r) => r.isConfirmed),
    );

    // $toast('pesan', 'success'|'error'|'info')
    window.Alpine.magic('toast', () => (message, icon = 'success') =>
        Swal.fire({
            toast: true,
            position: 'top-end',
            timer: 2800,
            timerProgressBar: true,
            showConfirmButton: false,
            icon,
            title: message,
        }),
    );
});

// Toast dari server: dispatch('toast', { message, icon })
document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (e) => {
        const data = Array.isArray(e) ? e[0] : e;
        window.Swal.fire({
            toast: true,
            position: 'top-end',
            timer: 2800,
            timerProgressBar: true,
            showConfirmButton: false,
            icon: data?.icon ?? 'success',
            title: data?.message ?? '',
        });
    });

    // Popup notifikasi belum dibaca saat masuk dashboard (sekali per sesi browser).
    window.Livewire.on('notif-popup', (e) => {
        const data = Array.isArray(e) ? e[0] : e;
        if (!data || !data.count || sessionStorage.getItem('notifPopupShown')) {
            return;
        }
        sessionStorage.setItem('notifPopupShown', '1');

        window.Swal.fire({
            title: 'Notifikasi belum dibaca',
            text: `Anda punya ${data.count} notifikasi yang belum dibaca.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Lihat notifikasi',
            cancelButtonText: 'Nanti',
            confirmButtonColor: '#2b4f62',
            cancelButtonColor: '#94a3b8',
            reverseButtons: true,
        }).then((r) => {
            if (r.isConfirmed && data.url) {
                window.Livewire.navigate(data.url);
            }
        });
    });
});
