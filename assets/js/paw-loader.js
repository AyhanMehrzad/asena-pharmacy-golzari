/**
 * ASENA Enterprise - Signature Liquid Paw Loader & Turbo Progress Bar System
 * 
 * Features:
 *  1. Micro Top Progress Bar: Real-time visual feedback for page loads and link clicks.
 *  2. Multi-Animal Liquid Paw Loader: Cat 🐱, Dog 🐶, Chick 🐥, Cow 🐮.
 *  3. Non-blocking & Accessible: Content renders underneath, auto-dismisses in <600ms.
 *  4. Global Controller: window.AsenaProgress.start(), window.AsenaProgress.done().
 */
(function() {
    'use strict';

    // -------------------------------------------------------------
    // 1. Top Turbo Progress Bar Controller
    // -------------------------------------------------------------
    let topBar = document.getElementById('asena-top-bar');
    if (!topBar) {
        topBar = document.createElement('div');
        topBar.id = 'asena-top-bar';
        document.documentElement.appendChild(topBar);
    }

    let progressTimer = null;
    let currentProgress = 0;

    const AsenaProgress = {
        start: function() {
            if (!topBar) return;
            topBar.classList.remove('bar-hidden');
            currentProgress = 15;
            topBar.style.width = currentProgress + '%';
            clearInterval(progressTimer);
            progressTimer = setInterval(function() {
                if (currentProgress < 85) {
                    currentProgress += Math.random() * 12;
                    topBar.style.width = currentProgress + '%';
                }
            }, 180);
        },
        set: function(val) {
            if (!topBar) return;
            currentProgress = Math.min(100, Math.max(0, val));
            topBar.style.width = currentProgress + '%';
        },
        done: function() {
            if (!topBar) return;
            clearInterval(progressTimer);
            currentProgress = 100;
            topBar.style.width = '100%';
            setTimeout(function() {
                topBar.classList.add('bar-hidden');
                setTimeout(function() {
                    topBar.style.width = '0%';
                    currentProgress = 0;
                }, 350);
            }, 250);
        }
    };
    window.AsenaProgress = AsenaProgress;

    // Start progress bar right away for document parsing
    AsenaProgress.start();

    // -------------------------------------------------------------
    // 2. Liquid Paw Loader Engine
    // -------------------------------------------------------------
    function getThemeColors() {
        const path = window.location.pathname.toLowerCase();
        const edition = document.documentElement.getAttribute('data-edition') || '';

        // Enterprise Edition (Sky Blue to Indigo)
        if (edition === 'enterprise' || path.includes('enterprise')) {
            return {
                start: '#0284c7',
                end: '#4f46e5',
                title: '#0369a1',
                subtitle: 'سامانه جامع کلینیک‌ها و بیمارستان‌های آسنا',
                glow: 'rgba(2, 132, 199, 0.22)'
            };
        }
        // Pharmacy Edition (Teal to Emerald)
        if (edition === 'pharmacy' || path.includes('pharmacy')) {
            return {
                start: '#0f766e',
                end: '#059669',
                title: '#0f766e',
                subtitle: 'سامانه تخصصی داروخانه و نسخه دامپزشکی آسنا',
                glow: 'rgba(15, 118, 110, 0.22)'
            };
        }
        // Premium / Standard Clinic (Navy to Warm Orange)
        return {
            start: '#002d72',
            end: '#ea580c',
            title: '#002d72',
            subtitle: 'کلینیک و پت‌شاپ تخصصی آسنا',
            glow: 'rgba(234, 88, 12, 0.22)'
        };
    }

    const theme = getThemeColors();
    const gradId = 'pawGrad_' + Math.random().toString(36).substr(2, 9);

    const animalModels = [
        {
            id: 'cat',
            name: 'گربه ملوس',
            emoji: '🐱',
            viewBox: '0 0 100 100',
            path: `
                <path d="M 50,50 C 33,50 23,61 25,75 C 27,86 37,90 50,87 C 63,90 73,86 75,75 C 77,61 67,50 50,50 Z" />
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
                <path d="M 50,52 C 35,52 20,63 24,79 C 28,91 40,93 50,87 C 60,93 72,91 76,79 C 80,63 65,52 50,52 Z" />
                <ellipse cx="24" cy="42" rx="9" ry="14" transform="rotate(-25 24 42)" />
                <ellipse cx="42" cy="27" rx="9.5" ry="14.5" transform="rotate(-8 42 27)" />
                <ellipse cx="58" cy="27" rx="9.5" ry="14.5" transform="rotate(8 58 27)" />
                <ellipse cx="76" cy="42" rx="9" ry="14" transform="rotate(25 76 42)" />
                <path d="M 21,27 Q 23,21 25,27" stroke-width="2" stroke-linecap="round" />
                <path d="M 40,11 Q 42,5 44,11" stroke-width="2" stroke-linecap="round" />
                <path d="M 56,11 Q 58,5 60,11" stroke-width="2" stroke-linecap="round" />
                <path d="M 75,27 Q 77,21 79,27" stroke-width="2" stroke-linecap="round" />
            `
        },
        {
            id: 'chick',
            name: 'پرندگان و طوطی‌سانان',
            emoji: '🐥',
            viewBox: '0 0 100 100',
            path: `
                <path d="M 47,56 C 47,42 46,26 47,15 C 48,11 52,11 53,15 C 54,26 53,42 53,56 C 60,50 70,42 79,33 C 83,29 86,34 83,37 C 74,46 64,54 56,61 C 56,68 55,77 53,86 C 51,91 49,91 47,86 C 45,77 44,68 44,61 C 36,54 26,46 17,37 C 14,34 17,29 21,33 C 30,42 40,50 47,56 Z" />
                <circle cx="50" cy="58" r="6.5" />
                <circle cx="50" cy="14" r="5" />
                <circle cx="81" cy="35" r="4.5" />
                <circle cx="19" cy="35" r="4.5" />
                <circle cx="50" cy="88" r="4" />
                <path d="M 50,11 L 50,5" stroke-width="2.5" stroke-linecap="round" />
                <path d="M 82,34 L 89,28" stroke-width="2.5" stroke-linecap="round" />
                <path d="M 18,34 L 11,28" stroke-width="2.5" stroke-linecap="round" />
            `
        },
        {
            id: 'cow',
            name: 'دام و حیوانات بزرگ',
            emoji: '🐮',
            viewBox: '0 0 100 100',
            path: `
                <path d="M 46,14 C 41,13 32,20 23,34 C 14,48 13,66 18,78 C 22,87 34,89 43,84 C 46,82 46,76 46,68 C 45,50 45,32 46,14 Z" />
                <path d="M 54,14 C 59,13 68,20 77,34 C 86,48 87,66 82,78 C 78,87 66,89 57,84 C 54,82 54,76 54,68 C 55,50 55,32 54,14 Z" />
                <ellipse cx="27" cy="92" rx="6" ry="4" transform="rotate(-15 27 92)" />
                <ellipse cx="73" cy="92" rx="6" ry="4" transform="rotate(15 73 92)" />
            `
        }
    ];

    const chosen = animalModels[Math.floor(Math.random() * animalModels.length)];

    let loaderDiv = document.getElementById('asena-paw-loader');
    if (!loaderDiv) {
        loaderDiv = document.createElement('div');
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
                        <g class="paw-bg-path">
                            ${chosen.path}
                        </g>
                        <g class="paw-fill-path" fill="url(#${gradId})" stroke="${theme.start}" stroke-width="0.8">
                            ${chosen.path}
                        </g>
                    </svg>
                </div>
                <div style="text-align: center; display: flex; flex-direction: column; align-items: center;">
                    <div class="paw-loader-title">
                        <span style="font-size: 18px;">${chosen.emoji}</span>
                        <span>${theme.subtitle}</span>
                    </div>
                    <div class="paw-loader-sub">ردپای ${chosen.name} • در حال پردازش...</div>
                    <div class="paw-progress-pill">
                        <div class="paw-progress-bar" style="background: linear-gradient(to left, ${theme.start}, ${theme.end});"></div>
                    </div>
                </div>
            </div>
        `;

        if (document.body) {
            document.body.prepend(loaderDiv);
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                document.body.prepend(loaderDiv);
            });
        }
    }

    function dismissLoader() {
        AsenaProgress.done();
        const loader = document.getElementById('asena-paw-loader');
        if (loader && !loader.classList.contains('loader-hidden')) {
            loader.classList.add('loader-hidden');
            setTimeout(function() {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 360);
        }
    }

    // Dismiss when DOM is ready or window finishes loading
    if (document.readyState === 'complete') {
        setTimeout(dismissLoader, 300);
    } else {
        window.addEventListener('load', function() {
            setTimeout(dismissLoader, 300);
        });
    }

    // Safety fallback: Never trap the user longer than 650ms under any network conditions
    setTimeout(dismissLoader, 650);

    // -------------------------------------------------------------
    // 3. Fast Tactile Feedback on Internal Navigation & Forms
    // -------------------------------------------------------------
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:') || link.target === '_blank') {
            return;
        }
        // Trigger top progress bar immediately on click
        AsenaProgress.start();
    }, { passive: true });

    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.target === '_blank') return;
        AsenaProgress.start();
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.dataset.noSpinner) {
            const originalHtml = submitBtn.innerHTML;
            submitBtn.dataset.originalHtml = originalHtml;
            const spinnerIcon = document.createElement('span');
            spinnerIcon.className = 'material-symbols-outlined text-base animate-spin inline-block align-middle ml-1.5';
            spinnerIcon.textContent = 'progress_activity';
            submitBtn.prepend(spinnerIcon);
        }
    });

})();
