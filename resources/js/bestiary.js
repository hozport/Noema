import './noema-markup-fields.js';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('bestiary-create-creature')?.addEventListener('click', () => {
        document.getElementById('creature-create-dialog')?.showModal();
    });

    document.getElementById('creature-open-edit')?.addEventListener('click', () => {
        document.getElementById('creature-edit-dialog')?.showModal();
    });

    document.querySelectorAll('[data-bestiary-dialog-close]').forEach((el) => {
        el.addEventListener('click', () => {
            const dialog = el.closest('dialog');
            dialog?.close();
        });
    });

    document.querySelectorAll('[data-gallery-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const url = btn.getAttribute('data-gallery-open');
            const dlg = document.getElementById('bestiary-gallery-lightbox');
            const img = document.getElementById('bestiary-gallery-lightbox-img');
            if (img && url) {
                img.src = url;
                img.alt = '';
            }
            dlg?.showModal();
        });
    });

    document.querySelectorAll('[data-gallery-lightbox-close]').forEach((el) => {
        el.addEventListener('click', () => {
            document.getElementById('bestiary-gallery-lightbox')?.close();
        });
    });

    const lightbox = document.getElementById('bestiary-gallery-lightbox');
    lightbox?.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            lightbox.close();
        }
    });
});
