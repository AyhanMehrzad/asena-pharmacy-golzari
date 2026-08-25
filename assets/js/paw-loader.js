/**
 * ASENA Multi-Animal Liquid Fill Rising Paw Loader (Artistically Polished)
 * Features:
 *  - 4 High-End Precision Animal Footprints:
 *      * 🐱 Cat Paw: Tri-lobed organic cushion + 4 rounded kitten beans
 *      * 🐶 Dog Paw: Heart canine pad + 4 almond toe pads with claw tips
 *      * 🐥 Chick Footprint: Beautiful continuous splayed 3-digit bird track + rear spur
 *      * 🐮 Cow Cloven Hoof: Clean, symmetrical twin-shield cloven hoof with rounded bulbs & dewclaws
 *  - Randomized selection on every page load
 *  - Rising liquid fill animation with matching linear progress indicator
 *  - Deterministic Edition & Palette Matching:
 *      * Pharmacy Edition (data-edition="pharmacy"): Deep Teal (#0f766e) -> Emerald (#059669)
 *      * Standard Petshop (data-edition="standard"): Navy Blue (#002d72) -> Warm Orange (#ea580c)
 *      * Premium Enterprise (data-edition="premium"): Royal Navy (#002d72) -> Indigo Blue (#3d5ca2)
 *      * Basic Petshop (data-edition="basic"): Navy Blue (#002d72) -> Classic Blue (#1d4ed8)
 *  - Auto-dismiss on page load with fallback safety timer
 */
(function () {
    if (document.getElementById('asena-paw-loader')) return;

    // Detect exact model and theme colors deterministically
    function getThemeColors() {
        const edition = document.documentElement.getAttribute('data-edition') || '';
        const path = window.location.pathname.toLowerCase();
        
        // 1. Pharmacy Edition
        if (edition === 'pharmacy' || 
            path.includes('pharmacy') || 
            document.body?.classList?.contains('pharma-mode')) {
            return {
                start: '#0f766e',
                end: '#059669',
                title: '#0f766e',
                subtitle: 'داروخانه تخصصی دامپزشکی آسنا',
                glow: 'rgba(15, 118, 110, 0.22)'
            };
        }
        
        // 2. Premium / Enterprise Clinic Model
        if (edition === 'premium' || path.includes('premium') || path.includes('enterprise')) {
            return {
                start: '#002d72',
                end: '#3d5ca2',
                title: '#002d72',
                subtitle: 'سامانه بیمارستان دامپزشکی آسنا',
                glow: 'rgba(61, 92, 162, 0.22)'
            };
        }

        // 3. Basic Clinic Model
        if (edition === 'basic' || path.includes('basic')) {
            return {
                start: '#002d72',
                end: '#1d4ed8',
                title: '#002d72',
                subtitle: 'سامانه پایه کلینیک و پت‌شاپ آسنا',
                glow: 'rgba(0, 45, 114, 0.20)'
            };
        }

        // 4. Standard / Best Deal Clinic & Petshop (Navy Blue + Warm Orange)
        return {
            start: '#002d72',
            end: '#ea580c',
            title: '#002d72',
            subtitle: 'کلینیک و پت‌شاپ تخصصی آسنا',
            glow: 'rgba(234, 88, 12, 0.22)'
        };
    }

    const theme = getThemeColors();
    const gradId = 'pawLiquidGrad_' + Math.random().toString(36).substr(2, 9);

    // 4 Artistically Crafted High-Definition Vector Footprint Models
    const animalModels = [
        {
            id: 'cat',
            name: 'گربه ملوس',
            emoji: '🐱',
            viewBox: '0 0 100 100',
            path: `
                <!-- Cat Main Palm Cushion with Smooth Tri-Lobe Base -->
                <path d="M 50,50 C 33,50 23,61 25,75 C 27,86 37,90 50,87 C 63,90 73,86 75,75 C 77,61 67,50 50,50 Z" />
                <!-- 4 Soft Rounded Kitten Toe Beans -->
                <ellipse cx="25" cy="40" rx="8" ry="12" transform="rotate(-22 25 40)" />
                <ellipse cx="42" cy="27" rx="8.5" ry="13" transform="rotate(-7 42 27)" />
                <ellipse cx="58" cy="27" rx="8.5" ry="13" transform="rotate(7 58 27)" />
                <ellipse cx="75" cy="40" rx="8" ry="12" transform="rotate(22 75 40)" />
            `
        },
        {
            id: 'dog',
            name: 'سگ باوفا',
            emoji: '🐶',
            viewBox: '0 0 100 100',
            path: `
                <!-- Dog Robust Heart-Shaped Palm Cushion -->
                <path d="M 50,52 C 35,52 20,63 24,79 C 28,91 40,93 50,87 C 60,93 72,91 76,79 C 80,63 65,52 50,52 Z" />
                <!-- 4 Canine Toes with Claw Tips -->
                <ellipse cx="24" cy="42" rx="9" ry="14" transform="rotate(-25 24 42)" />
                <ellipse cx="42" cy="27" rx="9.5" ry="14.5" transform="rotate(-8 42 27)" />
                <ellipse cx="58" cy="27" rx="9.5" ry="14.5" transform="rotate(8 58 27)" />
                <ellipse cx="76" cy="42" rx="9" ry="14" transform="rotate(25 76 42)" />
                <!-- Claw Accents -->
                <path d="M 21,27 Q 23,21 25,27" stroke-width="2" stroke-linecap="round" />
                <path d="M 40,11 Q 42,5 44,11" stroke-width="2" stroke-linecap="round" />
                <path d="M 56,11 Q 58,5 60,11" stroke-width="2" stroke-linecap="round" />
                <path d="M 75,27 Q 77,21 79,27" stroke-width="2" stroke-linecap="round" />
            `
        },
        {
            id: 'chick',
            name: 'جوجه و پرندگان',
            emoji: '🐥',
            viewBox: '0 0 100 100',
            path: `
                <!-- Clean Unified Baby Chick / Bird Track -->
                <path d="M 47,56 C 47,42 46,26 47,15 C 48,11 52,11 53,15 C 54,26 53,42 53,56 C 60,50 70,42 79,33 C 83,29 86,34 83,37 C 74,46 64,54 56,61 C 56,68 55,77 53,86 C 51,91 49,91 47,86 C 45,77 44,68 44,61 C 36,54 26,46 17,37 C 14,34 17,29 21,33 C 30,42 40,50 47,56 Z" />
                <!-- Central Ankle Cushion & Digit Knuckles -->
                <circle cx="50" cy="58" r="6.5" />
                <circle cx="50" cy="14" r="5" />
                <circle cx="81" cy="35" r="4.5" />
                <circle cx="19" cy="35" r="4.5" />
                <circle cx="50" cy="88" r="4" />
                <!-- Claw Tips -->
                <path d="M 50,11 L 50,5" stroke-width="2.5" stroke-linecap="round" />
                <path d="M 82,34 L 89,28" stroke-width="2.5" stroke-linecap="round" />
                <path d="M 18,34 L 11,28" stroke-width="2.5" stroke-linecap="round" />
                <path d="M 50,90 L 50,96" stroke-width="2" stroke-linecap="round" />
            `
        },
        {
            id: 'cow',
            name: 'دام و گاو',
            emoji: '🐮',
            viewBox: '0 0 100 100',
            path: `
                <!-- Left Cloven Hoof Half (Shield/Drop Curved Silhouette) -->
                <path d="M 46,14 C 41,13 32,20 23,34 C 14,48 13,66 18,78 C 22,87 34,89 43,84 C 46,82 46,76 46,68 C 45,50 45,32 46,14 Z" />
                <!-- Right Cloven Hoof Half (Shield/Drop Curved Silhouette) -->
                <path d="M 54,14 C 59,13 68,20 77,34 C 86,48 87,66 82,78 C 78,87 66,89 57,84 C 54,82 54,76 54,68 C 55,50 55,32 54,14 Z" />
                <!-- 2 Rounded Dewclaw Imprints -->
                <ellipse cx="27" cy="92" rx="6" ry="4" transform="rotate(-15 27 92)" />
                <ellipse cx="73" cy="92" rx="6" ry="4" transform="rotate(15 73 92)" />
            `
        }
    ];

    // Pick random animal model on every page visit
    const chosen = animalModels[Math.floor(Math.random() * animalModels.length)];

    const loaderDiv = document.createElement('div');
    loaderDiv.id = 'asena-paw-loader';
    loaderDiv.innerHTML = `
        <div class="paw-loader-card">
            <div class="paw-svg-container" style="box-shadow: 0 10px 25px -5px ${theme.glow};">
                <svg viewBox="${chosen.viewBox}" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="${gradId}" x1="0%" y1="100%" x2="0%" y2="0%">
                            <stop offset="0%" stop-color="${theme.start}" />
                            <stop offset="100%" stop-color="${theme.end}" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Background Soft Silhouette (Ghost Track) -->
                    <g class="paw-bg-path">
                        ${chosen.path}
                    </g>
                    
                    <!-- Rising Liquid Animated Fill -->
                    <g class="paw-fill-path" fill="url(#${gradId})" stroke="${theme.start}" stroke-width="0.8">
                        ${chosen.path}
                    </g>
                </svg>
            </div>
            <div style="text-align: center; display: flex; flex-direction: column; align-items: center;">
                <div class="paw-loader-title" style="color: ${theme.title};">
                    <span style="font-size: 20px;">${chosen.emoji}</span>
                    <span>${theme.subtitle}</span>
                </div>
                <div class="paw-loader-sub">ردپای ${chosen.name} • بارگذاری سریع...</div>
                <div class="paw-progress-pill">
                    <div class="paw-progress-bar" style="background: linear-gradient(to left, ${theme.start}, ${theme.end});"></div>
                </div>
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
        setTimeout(dismissLoader, 350);
    } else {
        window.addEventListener('load', () => setTimeout(dismissLoader, 350));
    }

    // Safety fallback timer so it never blocks the user
    setTimeout(dismissLoader, 1200);
})();
