<!-- JagaSewa Single Source of Truth Design Tokens -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    /* Primary Brand: Trust Emerald */
    --color-primary-50:  #ecfdf5;
    --color-primary-100: #d1fae5;
    --color-primary-200: #a7f3d0;
    --color-primary-300: #6ee7b7;
    --color-primary-400: #34d399;
    --color-primary-500: #10b981;
    --color-primary-600: #059669;
    --color-primary-700: #047857;
    --color-primary-800: #065f46;
    --color-primary-900: #064e3b;
    --color-primary-950: #022c22;

    /* Neutral Scale: Slate */
    --color-neutral-0:   #ffffff;
    --color-neutral-50:  #f8fafc;
    --color-neutral-100: #f1f5f9;
    --color-neutral-200: #e2e8f0;
    --color-neutral-300: #cbd5e1;
    --color-neutral-400: #94a3b8;
    --color-neutral-500: #64748b;
    --color-neutral-600: #475569;
    --color-neutral-700: #334155;
    --color-neutral-800: #1e293b;
    --color-neutral-900: #0f172a;
    --color-neutral-950: #020617;

    /* 5 Semantic Status Tokens (Strictly for state & feedback) */
    --color-status-neutral-bg:     #f1f5f9;
    --color-status-neutral-text:   #475569;
    --color-status-neutral-border: #e2e8f0;

    --color-status-info-bg:        #f0f9ff;
    --color-status-info-text:      #0369a1;
    --color-status-info-border:    #bae6fd;

    --color-status-success-bg:     #f0fdf4;
    --color-status-success-text:   #166534;
    --color-status-success-border: #bbf7d0;

    --color-status-warning-bg:     #fffbeb;
    --color-status-warning-text:   #92400e;
    --color-status-warning-border: #fde68a;

    --color-status-danger-bg:      #fef2f2;
    --color-status-danger-text:    #991b1b;
    --color-status-danger-border:  #fecaca;

    /* Buffer Calendar Specific */
    --color-buffer-bg:     #f1f5f9;
    --color-buffer-border: #cbd5e1;
    --color-buffer-text:   #475569;

    --color-brand-whatsapp: #25d366;
    --color-brand-whatsapp-hover: #20ba5a;

    --font-family-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --touch-target-min: 44px;
    --radius-default: 0.75rem; /* 12px (rounded-xl) - Single Standard Radius */
}

body {
    font-family: var(--font-family-sans);
    background-color: var(--color-neutral-50);
    color: var(--color-neutral-900);
    -webkit-tap-highlight-color: transparent;
}

/* Status Badge Utility Classes */
.badge-status-neutral {
    background-color: var(--color-status-neutral-bg);
    color: var(--color-status-neutral-text);
    border-color: var(--color-status-neutral-border);
}

.badge-status-info {
    background-color: var(--color-status-info-bg);
    color: var(--color-status-info-text);
    border-color: var(--color-status-info-border);
}

.badge-status-success {
    background-color: var(--color-status-success-bg);
    color: var(--color-status-success-text);
    border-color: var(--color-status-success-border);
}

.badge-status-warning {
    background-color: var(--color-status-warning-bg);
    color: var(--color-status-warning-text);
    border-color: var(--color-status-warning-border);
}

.badge-status-danger {
    background-color: var(--color-status-danger-bg);
    color: var(--color-status-danger-text);
    border-color: var(--color-status-danger-border);
}

/* Diagonal Stripe Pattern for Buffer Calendar Days */
.bg-buffer-stripes {
    background-color: #f1f5f9;
    background-image: repeating-linear-gradient(
        -45deg,
        #f1f5f9,
        #f1f5f9 4px,
        #e2e8f0 4px,
        #e2e8f0 8px
    );
}

/* Focus Visible Standard */
:focus-visible {
    outline: 2px solid var(--color-primary-600);
    outline-offset: 2px;
}
</style>

<script>
    if (typeof tailwind !== 'undefined') {
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    borderRadius: {
                        'xl': '0.75rem', // 12px standard
                    },
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        },
                        neutral: {
                            0: '#ffffff',
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        },
                        status: {
                            neutral: { bg: '#f1f5f9', border: '#e2e8f0', text: '#475569' },
                            info: { bg: '#f0f9ff', border: '#bae6fd', text: '#0369a1' },
                            success: { bg: '#f0fdf4', border: '#bbf7d0', text: '#166534' },
                            warning: { bg: '#fffbeb', border: '#fde68a', text: '#92400e' },
                            danger: { bg: '#fef2f2', border: '#fecaca', text: '#991b1b' },
                        },
                        whatsapp: {
                            DEFAULT: '#25d366',
                            hover: '#20ba5a',
                        }
                    },
                    boxShadow: {
                        'xs': '0 1px 2px 0 rgba(15, 23, 42, 0.04)',
                        'sm': '0 1px 3px 0 rgba(15, 23, 42, 0.06), 0 1px 2px -1px rgba(15, 23, 42, 0.04)',
                    }
                }
            }
        };
    }
</script>
