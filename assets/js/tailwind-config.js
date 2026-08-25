try {
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    /* --- Veterinary Pharmacy Natural Harmony Palette (Teal + Emerald + Cyan) --- */
                    "primary": "#0f766e",             /* Deep Rich Teal */
                    "primary-container": "#115e59",   /* Dark Teal Container */
                    "primary-light": "#0d9488",       /* Vibrant Medium Teal */
                    "primary-fixed": "#ccfbf1",       /* Soft Fresh Mint */
                    "primary-fixed-dim": "#99f6e4",
                    "on-primary": "#ffffff",
                    "on-primary-container": "#ccfbf1",
                    "on-primary-fixed": "#042f2e",
                    "on-primary-fixed-variant": "#115e59",
                    "inverse-primary": "#99f6e4",
                    
                    /* Complementary Emerald Accents (Replaces Orange for Pharmacy) */
                    "secondary": "#047857",           /* Deep Forest Emerald */
                    "secondary-container": "#059669", /* Vibrant Emerald Green CTA */
                    "secondary-fixed": "#d1fae5",     /* Soft Mint Background */
                    "secondary-fixed-dim": "#a7f3d0",
                    "on-secondary": "#ffffff",
                    "on-secondary-container": "#ffffff",
                    "on-secondary-fixed": "#064e3b",
                    "on-secondary-fixed-variant": "#047857",
                    
                    /* Medical Cyan / Aqua Complement */
                    "tertiary": "#0284c7",
                    "tertiary-container": "#0369a1",
                    "tertiary-fixed": "#e0f2fe",
                    "tertiary-fixed-dim": "#bae6fd",
                    "on-tertiary": "#ffffff",
                    "on-tertiary-container": "#e0f2fe",
                    "on-tertiary-fixed": "#082f49",
                    "on-tertiary-fixed-variant": "#0369a1",
                    
                    /* Clean Fresh Canvases */
                    "surface-tint": "#0f766e",
                    "surface": "#f8fafc",
                    "surface-bright": "#ffffff",
                    "surface-dim": "#e2e8f0",
                    "surface-alt": "#f0fdfa",
                    "surface-variant": "#e6fffa",
                    "surface-container": "#f1f5f9",
                    "surface-container-low": "#f8fafc",
                    "surface-container-high": "#e2e8f0",
                    "surface-container-highest": "#cbd5e1",
                    "surface-container-lowest": "#ffffff",
                    "background": "#f8fafc",
                    
                    "on-background": "#0f172a",
                    "on-surface": "#0f172a",
                    "on-surface-variant": "#334155",
                    "inverse-surface": "#1e293b",
                    "inverse-on-surface": "#f8fafc",
                    
                    "outline": "#64748b",
                    "outline-variant": "#cbd5e1",
                    
                    "status-active": "#059669",
                    "status-warning": "#d97706",
                    "status-paused": "#64748b",
                    "error": "#dc2626",
                    "error-container": "#fee2e2",
                    "on-error": "#ffffff",
                    "on-error-container": "#7f1d1d"
                },
                "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
                "spacing": {
                    "margin-mobile": "16px",
                    "base": "4px",
                    "margin-desktop": "24px",
                    "gutter": "16px",
                    "container-max": "1280px"
                },
                "fontFamily": {
                    "headline-lg-mobile": ["Geist"],
                    "headline-lg": ["Geist"],
                    "label-sm": ["Geist"],
                    "headline-md": ["Geist"],
                    "display-lg": ["Geist"],
                    "title-lg": ["Geist"],
                    "label-lg": ["Geist"],
                    "body-lg": ["Geist"],
                    "body-md": ["Geist"]
                },
                "fontSize": {
                    "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "600"}],
                    "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                    "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "title-lg": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "label-lg": ["14px", {"lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "600"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
                }
            },
        },
    }
} catch (_e) {}
