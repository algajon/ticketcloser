import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ── Three.js ambient background (only on pages with #three-canvas) ──
if (document.getElementById('three-canvas')) {
    import('./three-bg.js').then(({ initThreeBackground }) => {
        initThreeBackground();
    });
}
