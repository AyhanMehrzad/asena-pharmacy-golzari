/**
 * ASENA Enterprise - Universal Lazy Loading & Image Optimization Engine
 * 
 * Capabilities:
 *  1. Native loading="lazy" and decoding="async" injection across all images.
 *  2. High-performance IntersectionObserver for background images ([data-bg]) and lazy elements.
 *  3. Smooth zero-CLS fade-in animations (.lazy-img-init -> .lazy-img-loaded).
 *  4. Context-aware automatic fallbacks for broken/offline images (doctor, product, campaign, general).
 *  5. MutationObserver integration for seamless AJAX, pagination, and dynamic modals.
 */
(function() {
    'use strict';

    const FALLBACKS = {
        doctor: 'assets/images/placeholders/placeholder-doctor.svg',
        product: 'assets/images/placeholders/placeholder-product.svg',
        campaign: 'assets/images/placeholders/placeholder-campaign.svg',
        general: 'assets/images/placeholders/placeholder-no-image.svg'
    };

    function determineFallback(img) {
        if (img.dataset.fallback) return img.dataset.fallback;
        const src = (img.src || '').toLowerCase();
        const alt = (img.alt || '').toLowerCase();
        const className = (img.className || '').toLowerCase();

        if (src.includes('doctor') || alt.includes('پزشک') || alt.includes('دکتر') || className.includes('doctor')) {
            return FALLBACKS.doctor;
        }
        if (src.includes('product') || src.includes('shop') || src.includes('pharma') || alt.includes('محصول') || alt.includes('دارو')) {
            return FALLBACKS.product;
        }
        if (src.includes('banner') || src.includes('campaign') || alt.includes('بنر')) {
            return FALLBACKS.campaign;
        }
        return FALLBACKS.general;
    }

    function setupImage(img) {
        if (img.dataset.lazyProcessed) return;
        img.dataset.lazyProcessed = 'true';

        // Skip critical above-the-fold or explicitly prioritized assets
        const isPriority = img.classList.contains('no-lazy') || 
                           img.hasAttribute('data-priority') || 
                           img.classList.contains('hero-img');

        if (!isPriority) {
            if (!img.hasAttribute('loading')) {
                img.setAttribute('loading', 'lazy');
            }
            if (!img.hasAttribute('decoding')) {
                img.setAttribute('decoding', 'async');
            }
        }

        // Attach error fallback listener
        img.addEventListener('error', function() {
            if (img.dataset.hasFallbackApplied) return;
            img.dataset.hasFallbackApplied = 'true';
            const fallbackSrc = determineFallback(img);
            img.src = fallbackSrc;
            img.classList.add('lazy-img-error', 'lazy-img-loaded');
            img.classList.remove('lazy-img-init');
        }, { once: true });

        // Add smooth entrance transitions
        if (!img.complete) {
            img.classList.add('lazy-img-init');
            img.addEventListener('load', function() {
                img.classList.add('lazy-img-loaded');
                img.classList.remove('lazy-img-init');
            }, { once: true });
        } else {
            img.classList.add('lazy-img-loaded');
        }
    }

    function setupBackgrounds() {
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('[data-bg]').forEach(function(el) {
                const bg = el.getAttribute('data-bg');
                if (bg) {
                    el.style.backgroundImage = 'url("' + bg + '")';
                    el.classList.add('bg-loaded');
                    el.removeAttribute('data-bg');
                }
            });
            return;
        }

        const bgObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const bgUrl = target.getAttribute('data-bg');
                    if (bgUrl) {
                        target.style.backgroundImage = 'url("' + bgUrl + '")';
                        target.classList.add('bg-loaded');
                        target.removeAttribute('data-bg');
                    }
                    observer.unobserve(target);
                }
            });
        }, { rootMargin: '250px 0px' });

        document.querySelectorAll('[data-bg]:not([data-bg-observed])').forEach(function(el) {
            el.setAttribute('data-bg-observed', 'true');
            bgObserver.observe(el);
        });
    }

    function initLazyLoading() {
        const images = document.querySelectorAll('img:not([data-lazy-processed])');
        for (let i = 0; i < images.length; i++) {
            setupImage(images[i]);
        }
        setupBackgrounds();
    }

    // Initialize on DOM events
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLazyLoading);
    } else {
        initLazyLoading();
    }
    window.addEventListener('load', initLazyLoading);

    // Watch for dynamic DOM modifications (AJAX pagination, cart updates, modals)
    if ('MutationObserver' in window) {
        let debounceTimer;
        const domObserver = new MutationObserver(function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(initLazyLoading, 80);
        });

        if (document.body) {
            domObserver.observe(document.body, { childList: true, subtree: true });
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                domObserver.observe(document.body, { childList: true, subtree: true });
            });
        }
    }

    // Expose global API
    window.AsenaLazyLoader = {
        refresh: initLazyLoading
    };
})();
