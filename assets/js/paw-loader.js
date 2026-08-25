/**
 * ASENA Multi-Animal Liquid Fill Rising Paw Loader
 * Features:
 *  - 4 Unique Vector Paws: Cat Paw (گربه), Dog Paw (سگ), Chick Footprint (جوجه/پرنده), Cow Hoof (سم گاو)
 *  - Randomized selection on every page load
 *  - Rising liquid fill animation from bottom to top
 *  - Model-Aware Exact Color Adaptation:
 *      * Pharmacy Edition: Pure Teal (#0f766e) -> Emerald (#059669)
 *      * Standard Petshop: Navy (#002d72) -> Warm Orange (#ea580c)
 *      * Premium Enterprise: Royal Navy (#002d72) -> Indigo (#3d5ca2)
 *      * Basic Petshop: Navy (#002d72) -> Classic Blue (#1d4ed8)
 *  - Auto-dismiss on page load with fallback safety timer
 */
(function () {
    if (document.getElementById('asena-paw-loader')) return;

    // Detect exact model and theme colors
    function getThemeColors() {
        const path = window.location.pathname.toLowerCase();
        const title = (document.title || '').toLowerCase();
        
        // 1. Pharmacy Model Detection
        if (path.includes('pharmacy') || 
            title.includes('داروخانه') || 
            document.body?.classList?.contains('pharma-mode')) {
            return {
                start: '#0f766e',
                end: '#059669',
                title: '#0f766e',
                subtitle: 'آسنا | داروخانه تخصصی دامپزشکی',
                glow: 'rgba(15, 118, 110, 0.25)'
            };
        }
        
        // 2. Premium / Enterprise Model Detection
        if (path.includes('premium') || title.includes('پریمیوم') || title.includes('سازمانی')) {
            return {
                start: '#002d72',
                end: '#3d5ca2',
                title: '#002d72',
                subtitle: 'آسنا | سامانه سازمانی کلینیک و بیمارستان دامپزشکی',
                glow: 'rgba(61, 92, 162, 0.25)'
            };
        }

        // 3. Basic Model Detection
        if (path.includes('basic') || title.includes('پایه')) {
            return {
                start: '#002d72',
                end: '#1d4ed8',
                title: '#002d72',
                subtitle: 'آسنا | سامانه پایه کلینیک و پت‌شاپ',
                glow: 'rgba(0, 45, 114, 0.22)'
            };
        }

        // 4. Standard / Best Deal Model (Default Pet Care)
        return {
            start: '#002d72',
            end: '#ea580c',
            title: '#002d72',
            subtitle: 'آسنا | کلینیک و پت‌شاپ تخصصی',
            glow: 'rgba(234, 88, 12, 0.25)'
        };
    }

    const theme = getThemeColors();
    const gradId = 'pawLiquidGrad_' + Math.random().toString(36).substr(2, 9);

    // 4 Distinct Animal SVG Vector Models
    const animalModels = [
        {
            id: 'cat',
            name: 'گربه ملوس',
            emoji: '🐱',
            viewBox: '0 0 100 100',
            path: `
                <!-- Cat Main Pad -->
                <path d="M 50,48 C 36,48 24,60 28,74 C 31,84 40,88 50,88 C 60,88 69,84 72,74 C 76,60 64,48 50,48 Z" />
                <!-- 4 Rounded Toe Beans -->
                <ellipse cx="28" cy="40" rx="9" ry="12" transform="rotate(-18 28 40)" />
                <ellipse cx="43" cy="30" rx="9" ry="13" transform="rotate(-6 43 30)" />
                <ellipse cx="57" cy="30" rx="9" ry="13" transform="rotate(6 57 30)" />
                <ellipse cx="72" cy="40" rx="9" ry="12" transform="rotate(18 72 40)" />
            `
        },
        {
            id: 'dog',
            name: 'سگ وفادار',
            emoji: '🐶',
            viewBox: '0 0 100 100',
            path: `
                <!-- Dog Heart/Tri-lobed Main Pad -->
                <path d="M 50,50 C 35,50 20,62 25,78 C 29,90 40,92 50,86 C 60,92 71,90 75,78 C 80,62 65,50 50,50 Z" />
                <!-- 4 Canine Toes + Claws -->
                <ellipse cx="24" cy="42" rx="9.5" ry="14" transform="rotate(-25 24 42)" />
                <ellipse cx="42" cy="28" rx="10" ry="15" transform="rotate(-8 42 28)" />
                <ellipse cx="58" cy="28" rx="10" ry="15" transform="rotate(8 58 28)" />
                <ellipse cx="76" cy="42" rx="9.5" ry="14" transform="rotate(25 76 42)" />
            `
        },
        {
            id: 'chick',
            name: 'جوجه و پرندگان',
            emoji: '🐥',
            viewBox: '0 0 100 100',
            path: `
                <!-- 3 Front Toes + 1 Back Spur Connected -->
                <path d="M 50,62 L 50,88 L 47,88 L 47,64 L 16,36 L 20,32 L 48,58 L 48,12 L 52,12 L 52,58 L 80,32 L 84,36 L 53,64 L 53,88 L 50,88 Z" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                <!-- Toe Claw Nodes -->
                <circle cx="50" cy="14" r="5" />
                <circle cx="17" cy="35" r="5" />
                <circle cx="83" cy="35" r="5" />
                <circle cx="50" cy="88" r="4.5" />
            `
        },
        {
            id: 'cow',
            name: 'سم گاو و دام',
            emoji: '🐮',
            viewBox: '0 0 100 100',
            path: `
                <!-- Cow Left Cloven Hoof Half -->
                <path d="M 47,20 C 35,20 22,34 22,60 C 22,78 33,88 47,88 L 47,20 Z" />
                <!-- Cow Right Cloven Hoof Half -->
                <path d="M 53,20 C 65,20 78,34 78,60 C 78,78 67,88 53,88 L 53,20 Z" />
                <!-- Accessory Dewclaws at Top -->
                <ellipse cx="32" cy="14" rx="6" ry="4" transform="rotate(-15 32 14)" />
                <ellipse cx="68" cy="14" rx="6" ry="4" transform="rotate(15 68 14)" />
            `
        }
    ];

    // Pick random animal model on every page visit
    const chosen = animalModels[Math.floor(Math.random() * animalModels.length)];

    const loaderDiv = document.createElement('div');
    loaderDiv.id = 'asena-paw-loader';
    loaderDiv.innerHTML = `
        <div class="paw-loader-content">
            <div class="paw-svg-container" style="filter: drop-shadow(0 8px 18px ${theme.glow});">
                <svg viewBox="${chosen.viewBox}" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="${gradId}" x1="0%" y1="100%" x2="0%" y2="0%">
                            <stop offset="0%" stop-color="${theme.start}" />
                            <stop offset="100%" stop-color="${theme.end}" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Background Empty Frame -->
                    <g class="paw-bg-path">
                        ${chosen.path}
                    </g>
                    
                    <!-- Animated Rising Liquid Fill -->
                    <g class="paw-fill-path" fill="url(#${gradId})" stroke="${theme.start}" stroke-width="1">
                        ${chosen.path}
                    </g>
                </svg>
            </div>
            <div style="text-align: center;">
                <div class="paw-loader-title" style="color: ${theme.title};">
                    <span>${chosen.emoji}</span>
                    <span>${theme.subtitle}</span>
                </div>
                <div class="paw-loader-sub">ردپای ${chosen.name} • در حال بارگذاری...</div>
            </div>
        </div>
    `;

    // Insert as earliest element in body
    if (document.body) {
        document.body.prepend(loaderDiv);
    } else {
        document.addEventListener('DOMContentLoaded', () => document.body.prepend(loaderDiv));
    }

    // Dismiss seamlessly when page is ready
    function dismissLoader() {
        const loader = document.getElementById('asena-paw-loader');
        if (loader && !loader.classList.contains('loader-hidden')) {
            loader.classList.add('loader-hidden');
            setTimeout(() => loader.remove(), 400);
        }
    }

    if (document.readyState === 'complete') {
        setTimeout(dismissLoader, 300);
    } else {
        window.addEventListener('load', () => setTimeout(dismissLoader, 300));
    }

    // Safety fallback timer so it never blocks the user
    setTimeout(dismissLoader, 1100);
})();
