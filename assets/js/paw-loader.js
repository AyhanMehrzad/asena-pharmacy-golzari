/**
 * ASENA Enterprise - Signature Liquid Paw Loader & Turbo Progress Bar System
 * 
 * Capabilities:
 *  1. Server-synchronized instant Paw Splash with guaranteed minimum animation duration (1.25s).
 *  2. Rising Liquid fill across Cat, Dog, Bird, and Cow footprints.
 *  3. Snappy Top Progress Bar (#asena-top-bar) with real-time acceleration.
 *  4. Global Test & Trigger API: window.AsenaLoader.show(), window.AsenaLoader.dismiss().
 */
(function() {
    'use strict';

    const ANIMATION_MIN_MS = 1250; // Guarantees the 1.1s liquid rise completes visibly
    const pageStartTime = performance.now();

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
    let currentProgress = 20;

    const AsenaProgress = {
        start: function() {
            if (!topBar) return;
            topBar.classList.remove('bar-hidden');
            currentProgress = 25;
            topBar.style.width = currentProgress + '%';
            clearInterval(progressTimer);
            progressTimer = setInterval(function() {
                if (currentProgress < 90) {
                    currentProgress += Math.random() * 15;
                    topBar.style.width = Math.min(90, currentProgress) + '%';
                }
            }, 120);
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
            }, 200);
        }
    };
    window.AsenaProgress = AsenaProgress;
    AsenaProgress.start();

    // -------------------------------------------------------------
    // 2. Liquid Paw Loader Controller
    // -------------------------------------------------------------
    function dismissLoader() {
        const loader = document.getElementById('asena-paw-loader');
        if (!loader || loader.classList.contains('loader-hidden')) return;

        AsenaProgress.done();

        // Calculate remaining time to satisfy the 1.25s animation threshold
        const elapsed = performance.now() - pageStartTime;
        const delay = Math.max(0, ANIMATION_MIN_MS - elapsed);

        setTimeout(function() {
            loader.classList.add('loader-hidden');
            setTimeout(function() {
                loader.style.display = 'none';
            }, 400);
        }, delay);
    }

    // Trigger dismissal after window has loaded, respecting minimum animation time
    if (document.readyState === 'complete') {
        dismissLoader();
    } else {
        window.addEventListener('load', dismissLoader);
    }

    // Maximum safety timeout (3.5s) to guarantee no infinite freeze under dead network conditions
    setTimeout(dismissLoader, 3500);

    // -------------------------------------------------------------
    // 3. Global Interactive API & Demo Triggers
    // -------------------------------------------------------------
    window.AsenaLoader = {
        show: function(customSub = 'در حال پردازش درخواست...') {
            let loader = document.getElementById('asena-paw-loader');
            if (!loader) return;
            
            const subText = loader.querySelector('.paw-loader-sub');
            if (subText) subText.textContent = customSub;

            // Reset liquid animation
            const fillPath = loader.querySelector('.paw-fill-path');
            if (fillPath) {
                fillPath.style.animation = 'none';
                void fillPath.offsetWidth; // Trigger reflow
                fillPath.style.animation = 'liquidRise 1.1s cubic-bezier(0.33, 1, 0.68, 1) forwards';
            }

            // Reset progress bar
            const bar = loader.querySelector('.paw-progress-bar');
            if (bar) {
                bar.style.animation = 'none';
                void bar.offsetWidth;
                bar.style.animation = 'barProgress 1.1s cubic-bezier(0.33, 1, 0.68, 1) forwards';
            }

            loader.style.display = 'flex';
            void loader.offsetWidth;
            loader.classList.remove('loader-hidden');
            AsenaProgress.start();

            // Auto dismiss after animation cycle
            setTimeout(function() {
                loader.classList.add('loader-hidden');
                AsenaProgress.done();
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 400);
            }, 1400);
        },
        dismiss: function() {
            dismissLoader();
        }
    };

    // -------------------------------------------------------------
    // 4. Instant Form & Link Micro-Feedback
    // -------------------------------------------------------------
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:') || link.target === '_blank') {
            return;
        }
        AsenaProgress.start();
    }, { passive: true });

    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.target === '_blank') return;
        AsenaProgress.start();
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.dataset.noSpinner) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.85';
            const spinner = document.createElement('span');
            spinner.className = 'material-symbols-outlined text-base animate-spin inline-block align-middle ml-1.5';
            spinner.textContent = 'progress_activity';
            submitBtn.prepend(spinner);
        }
    });

})();
