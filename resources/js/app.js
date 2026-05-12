import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const prefetchedDocuments = new Set();
const prefetchedAnchorLimit = 18;

function shouldPrefetchLink(anchor) {
    if (!(anchor instanceof HTMLAnchorElement)) return false;
    if (!anchor.href || prefetchedDocuments.has(anchor.href)) return false;
    if (anchor.target && anchor.target !== '_self') return false;
    if (anchor.hasAttribute('download') || anchor.dataset.noPrefetch !== undefined) return false;

    const url = new URL(anchor.href, window.location.origin);

    if (url.origin !== window.location.origin) return false;
    if (url.pathname === window.location.pathname && url.search === window.location.search) return false;
    if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return false;

    return true;
}

function prefetchDocument(anchor) {
    if (!shouldPrefetchLink(anchor)) return;

    const url = new URL(anchor.href, window.location.origin);
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.as = 'document';
    link.href = url.toString();
    document.head.appendChild(link);
    prefetchedDocuments.add(url.toString());
}

function warmVisibleInternalLinks() {
    const anchors = Array.from(document.querySelectorAll('a[href]'))
        .filter((anchor) => shouldPrefetchLink(anchor))
        .slice(0, prefetchedAnchorLimit);

    anchors.forEach((anchor) => prefetchDocument(anchor));
}

document.addEventListener('pointerenter', (event) => {
    prefetchDocument(event.target.closest('a[href]'));
}, true);

document.addEventListener('focusin', (event) => {
    prefetchDocument(event.target.closest('a[href]'));
}, true);

document.addEventListener('touchstart', (event) => {
    prefetchDocument(event.target.closest('a[href]'));
}, { passive: true, capture: true });

if ('requestIdleCallback' in window) {
    window.requestIdleCallback(() => warmVisibleInternalLinks(), { timeout: 1500 });
} else {
    window.setTimeout(() => warmVisibleInternalLinks(), 600);
}

function initLandingMotion() {
    const shell = document.querySelector('.tc-landing-shell');

    if (!shell) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) return;

    const motionItems = Array.from(document.querySelectorAll('.tc-landing-motion-stage'));
    const nav = document.querySelector('.tc-landing-nav');
    const ambientCards = Array.from(document.querySelectorAll('.tc-landing-card, .tc-landing-step-card'));

    shell.classList.add('tc-landing-motion-ready');

    ambientCards.forEach((card, index) => {
        card.style.setProperty('--tc-float-delay', `${index * 0.35}s`);
    });

    window.requestAnimationFrame(() => {
        if (nav) {
            window.setTimeout(() => nav.classList.add('is-visible'), 30);
        }
    });

    const reveal = (element) => {
        if (element.classList.contains('is-visible')) return;
        element.classList.add('is-visible');
    };

    if (!('IntersectionObserver' in window)) {
        motionItems.forEach((item, index) => {
            window.setTimeout(() => reveal(item), 90 + (index * 70));
        });
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            reveal(entry.target);
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.16,
        rootMargin: '0px 0px -10% 0px',
    });

    motionItems.forEach((item, index) => {
        const aboveFold = item.getBoundingClientRect().top < window.innerHeight * 0.9;

        if (aboveFold) {
            window.setTimeout(() => reveal(item), 90 + (index * 70));
            return;
        }

        observer.observe(item);
    });
}

initLandingMotion();

// ── Three.js ambient background (only on pages with #three-canvas) ──
if (document.getElementById('three-canvas')) {
    import('./three-bg.js').then(({ initThreeBackground }) => {
        initThreeBackground();
    });
}
