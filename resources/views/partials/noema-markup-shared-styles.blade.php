@once('noema-markup-preview-dialog-css')
    <style>
        .noema-markup-preview-dialog {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            overflow: hidden !important;
        }
        .noema-markup-preview-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .noema-markup-preview-dialog:not([open]) { display: none !important; }
        .noema-markup-preview-dialog[open] { display: block !important; }
        .noema-markup-preview-dialog .noema-markup-preview-viewport {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        .noema-markup-preview-dialog .noema-markup-preview-scrim {
            position: absolute;
            inset: 0;
            z-index: 0;
            cursor: pointer;
        }
        .noema-markup-preview-dialog .noema-markup-preview-panel {
            position: relative;
            z-index: 1;
            width: min(640px, calc(100vw - 2rem));
            max-height: min(85vh, calc(100dvh - 2rem));
            overflow: auto;
            background: var(--color-base-100, #1d232a);
            padding: 2.5rem 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border-radius: 0;
        }
    </style>
@endonce
