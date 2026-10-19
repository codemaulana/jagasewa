<!-- JagaSewa Single Source of Truth Design Tokens -->
<link rel="stylesheet" href="/fonts/inter-tight/inter-tight.css">

<style>
:root {
    /* Primary Action: Solid Black (#111111) */
    --color-primary-50:  #f7f7f7;
    --color-primary-100: #e5e5e5;
    --color-primary-200: #cccccc;
    --color-primary-300: #999999;
    --color-primary-400: #555555;
    --color-primary-500: #262626;
    --color-primary-600: #111111; /* Default Primary CTA (Solid Black) */
    --color-primary-700: #000000;
    --color-primary-800: #000000;
    --color-primary-900: #000000;
    --color-primary-950: #000000;

    /* Single Brand Accent: Lime (#86F04C) - Background Only with Dark Text */
    --color-accent-lime:      #86F04C;
    --color-accent-lime-text: #0A0A0A;
    --color-accent-lime-glow: #D9F5C8;

    /* Neutral Scale: Warm Neutral (Latar #F2F2F0, Permukaan #FFFFFF, Border #E5E5E3) */
    --color-neutral-0:   #ffffff;
    --color-neutral-50:  #F2F2F0; /* Warm background */
    --color-neutral-100: #EBEBE8; /* Secondary surface */
    --color-neutral-200: #E5E5E3; /* Subtle 1px border */
    --color-neutral-300: #D4D4D0;
    --color-neutral-400: #A3A39E;
    --color-neutral-500: #5C5C5C; /* Secondary text - passes WCAG AA 5.8:1 */
    --color-neutral-600: #404040;
    --color-neutral-700: #262626;
    --color-neutral-800: #171717;
    --color-neutral-900: #0A0A0A; /* Main heading text (near black) */
    --color-neutral-950: #050505;

    /* 5 Semantic Status Tokens (Strictly for state & feedback - never lime) */
    --color-status-neutral-bg:     #F2F2F0;
    --color-status-neutral-text:   #5C5C5C;
    --color-status-neutral-border: #E5E5E3;

    --color-status-info-bg:        #F0F9FF;
    --color-status-info-text:      #0369A1;
    --color-status-info-border:    #BAE6FD;

    --color-status-success-bg:     #F0FDF4;
    --color-status-success-text:   #166534;
    --color-status-success-border: #BBF7D0;

    --color-status-warning-bg:     #FFFBEB;
    --color-status-warning-text:   #92400E;
    --color-status-warning-border: #FDE68A;

    --color-status-danger-bg:      #FEF2F2;
    --color-status-danger-text:    #991B1B;
    --color-status-danger-border:  #FECACA;

    /* Buffer Calendar Specific */
    --color-buffer-bg:     #F2F2F0;
    --color-buffer-border: #E5E5E3;
    --color-buffer-text:   #5C5C5C;

    --color-brand-whatsapp: #25D366;
    --color-brand-whatsapp-hover: #20BA5A;

    --font-family-sans: 'Inter Tight', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --touch-target-min: 44px;
    --radius-default: 0.75rem; /* 12px (rounded-xl) */
    --radius-pill: 9999px;     /* Pill / rounded-full */
    --radius-lg: 1.5rem;       /* 24px (rounded-3xl) for outer section containers */
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

/* Accent Lime Pill Utility */
.badge-accent-lime {
    background-color: var(--color-accent-lime);
    color: var(--color-accent-lime-text);
}

/* Diagonal Stripe Pattern for Buffer Calendar Days */
.bg-buffer-stripes {
    background-color: #F2F2F0;
    background-image: repeating-linear-gradient(
        -45deg,
        #F2F2F0,
        #F2F2F0 4px,
        #E5E5E3 4px,
        #E5E5E3 8px
    );
}

/* Focus Visible Standard */
:focus-visible {
    outline: 2px solid var(--color-neutral-900);
    outline-offset: 2px;
}
</style>

<script>
    if (typeof tailwind !== 'undefined') {
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter Tight', 'sans-serif'],
                    },
                    borderRadius: {
                        'xl': '0.75rem',  // 12px standard
                        '2xl': '1rem',    // 16px
                        '3xl': '1.5rem',  // 24px container
                    },
                    colors: {
                        primary: {
                            50: '#f7f7f7',
                            100: '#e5e5e5',
                            200: '#cccccc',
                            300: '#999999',
                            400: '#555555',
                            500: '#262626',
                            600: '#111111',
                            700: '#000000',
                            800: '#000000',
                            900: '#000000',
                            950: '#000000',
                        },
                        accent: {
                            lime: '#86F04C',
                            'lime-text': '#0A0A0A',
                            'lime-glow': '#D9F5C8',
                        },
                        neutral: {
                            0: '#ffffff',
                            50: '#F2F2F0',
                            100: '#EBEBE8',
                            200: '#E5E5E3',
                            300: '#D4D4D0',
                            400: '#A3A39E',
                            500: '#5C5C5C',
                            600: '#404040',
                            700: '#262626',
                            800: '#171717',
                            900: '#0A0A0A',
                            950: '#050505',
                        },
                        status: {
                            neutral: { bg: '#F2F2F0', border: '#E5E5E3', text: '#5C5C5C' },
                            info: { bg: '#F0F9FF', border: '#BAE6FD', text: '#0369A1' },
                            success: { bg: '#F0FDF4', border: '#BBF7D0', text: '#166534' },
                            warning: { bg: '#FFFBEB', border: '#FDE68A', text: '#92400E' },
                            danger: { bg: '#FEF2F2', border: '#FECACA', text: '#991B1B' },
                        },
                        whatsapp: {
                            DEFAULT: '#25D366',
                            hover: '#20BA5A',
                        }
                    },
                    boxShadow: {
                        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.04)',
                        'soft': '0 2px 8px 0 rgba(0, 0, 0, 0.03), 0 1px 2px 0 rgba(0, 0, 0, 0.02)',
                    }
                }
            }
        };
    }
</script>
