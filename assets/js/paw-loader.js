/**
 * ASENA Multi-Animal Liquid Fill Rising Paw Loader (Artistically Enhanced)
 * Features:
 *  - 4 Anatomically & Artistically Crafted Animal Footprint Models:
 *      * 🐱 Cat Paw: Smooth organic kitten cushion + 4 rounded toe beans
 *      * 🐶 Dog Paw: Bold triangular canine pad + 4 claw-topped oval beans
 *      * 🐥 Chick Footprint: Smooth continuous baby bird footprint with 3 splayed forward toes, rear spur & rounded toe joints
 *      * 🐮 Cow Cloven Hoof: Elegant twin crescent cloven hoof segments with natural cleft & upper dewclaws
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
                <path d="M 23,32 C 16,36 15,48 21,54 C 27,60 35,54 33,42 C 32,34 27,30 23,32 Z" />
                <path d="M 42,16 C 35,19 34,31 40,38 C 46,44 54,39 52,27 C 51,19 46,14 42,16 Z" />
                <path d="M 58,16 C 65,19 66,31 60,38 C 54,44 46,39 48,27 C 49,19 54,14 58,16 Z" />
                <path d="M 77,32 C 84,36 85,48 79,54 C 73,60 65,54 67,42 C 68,34 73,30 77,32 Z" />
                <!-- Subtle Claw Points -->
                <path d="M 22,25 C 23,21 26,22 25,26 Z" />
                <path d="M 42,9 C 43,5 46,6 45,10 Z" />
                <path d="M 58,9 C 57,5 54,6 55,10 Z" />
                <path d="M 78,25 C 77,21 74,22 75,26 Z" />
            `
        },
        {
            id: 'chick',
            name: 'جوجه و پرندگان',
            emoji: '🐥',
            viewBox: '0 0 100 100',
            path: `
                <!-- Beautiful Unified Baby Chick / Bird Footprint with Organic Webbed Curves & Rounded Pads -->
                <path d="
                    M 50,58 
                    C 52,44 49,30 50,15 
                    C 47,11 53,11 50,7
                    C 47,11 53,11 50,15
                    C 51,30 48,44 50,58
                    C 58,52 68,44 78,35
                    C 82,31 85,36 82,39
                    C 72,48 62,56 55,62
                    C 55,70 54,78 52,88
                    C 50,93 48,93 48,88
                    C 46,78 45,70 45,62
                    C 38,56 28,48 18,39
                    C 15,36 18,31 22,35
                    C 32,44 42,52 50,58 Z
                " />
                <!-- Central Ankle Cushion & Digit Knuckles -->
                <circle cx="50" cy="60" r="7" />
                <circle cx="50" cy="18" r="5" />
                <circle cx="21" cy="37" r="4.5" />
                <circle cx="79" cy="37" r="4.5" />
                <circle cx="50" cy="87" r="4" />
                <!-- Claw Tips -->
                <path d="M 50,13 L 50,6" stroke-width="3" stroke-linecap="round" />
                <path d="M 23,35 L 14,28" stroke-width="3" stroke-linecap="round" />
                <path d="M 77,35 L 86,28" stroke-width="3" stroke-linecap="round" />
                <path d="M 50,89 L 50,95" stroke-width="2.5" stroke-linecap="round" />
            `
        },
        {
            id: 'cow',
            name: 'دام و گاو',
            emoji: '🐮',
            viewBox: '0 0 100 100',
            path: `
                <!-- Left Cloven Hoof Half (Natural Arched Kidney Silhouette) -->
                <path d="M 45,18 C 34,18 20,30 18,52 C 16,68 23,80 37,82 C 43,83 45,79 45,72 C 45,54 44,36 45,18 Z" />
                <!-- Right Cloven Hoof Half (Natural Arched Kidney Silhouette) -->
                <path d="M 55,18 C 66,18 80,30 82,52 C 84,68 77,80 63,82 C 57,83 55,79 55,72 C 55,54 56,36 55,18 Z" />
                <!-- 2 Accessory Dewclaw Prints (Soft Bovine Heel Pods) -->
                <ellipse cx="28" cy="91" rx="6" ry="4" transform="rotate(-15 28 91)" />
                <ellipse cx="72" cy="91" rx="6" ry="4" transform="rotate(15 72 91)" />
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
                <div class="paw-loader-sub">ردپای هنری ${chosen.name} • بارگذاری سریع...</div>
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
