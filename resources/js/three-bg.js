import * as THREE from 'three';

let scene, camera, renderer;
let stars;
let clock;
let alphas, baseColors;
const STAR_COUNT = 4000;

function init() {
    const canvas = document.getElementById('three-canvas');
    if (!canvas) return;

    scene = new THREE.Scene();
    // Pure black background for deep space
    scene.background = new THREE.Color(0x000000);

    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
    // Position camera in the center of the starfield
    camera.position.set(0, 0, 0);

    // Optimize renderer for max performance and speed
    renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: false, antialias: false, powerPreference: "high-performance" });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);

    clock = new THREE.Clock();

    createStarfield();

    window.addEventListener('resize', onWindowResize, false);
    animate();
}

function createFastTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 32;
    canvas.height = 32;
    const ctx = canvas.getContext('2d');

    const cx = 16;
    const cy = 16;

    // Core glow
    const halo = ctx.createRadialGradient(cx, cy, 0, cx, cy, 14);
    halo.addColorStop(0, 'rgba(255, 255, 255, 1)');
    halo.addColorStop(0.2, 'rgba(255, 255, 255, 0.7)');
    halo.addColorStop(1, 'rgba(255, 255, 255, 0)');
    ctx.fillStyle = halo;
    ctx.beginPath();
    ctx.arc(cx, cy, 14, 0, Math.PI * 2);
    ctx.fill();

    // Cross beams for retro aesthetic
    ctx.fillStyle = 'rgba(255,255,255,0.8)';
    ctx.beginPath();
    ctx.ellipse(cx, cy, 2, 16, 0, 0, Math.PI * 2);
    ctx.fill();

    ctx.beginPath();
    ctx.ellipse(cx, cy, 16, 2, 0, 0, Math.PI * 2);
    ctx.fill();

    return new THREE.CanvasTexture(canvas);
}

function createStarfield() {
    const STAR_COUNT = 3000; // slightly reduced for perfect 60fps instant load on all devices
    const geometry = new THREE.BufferGeometry();

    // Use smaller typed arrays to save memory and initialization time
    const positions = new Float32Array(STAR_COUNT * 3);
    const colors = new Float32Array(STAR_COUNT * 3);
    alphas = new Float32Array(STAR_COUNT);
    baseColors = new Float32Array(STAR_COUNT * 3);

    // Pre-extract RGB values to avoid object creation in the tight loop
    const c1 = [1, 1, 1];             // White
    const c2 = [0.60, 0.83, 1];       // Light Blue
    const c3 = [1, 0.63, 0.95];       // Pink
    const c4 = [0.61, 0.36, 1];       // Purple
    const c5 = [1, 0.29, 0.39];       // Red

    for (let i = 0; i < STAR_COUNT; i++) {
        // Fast random sphere generation
        const r = 50 + Math.random() * 500;
        const theta = 2 * Math.PI * Math.random();
        const phi = Math.acos(2 * Math.random() - 1);

        const i3 = i * 3;
        positions[i3] = r * Math.sin(phi) * Math.cos(theta);
        positions[i3 + 1] = r * Math.sin(phi) * Math.sin(theta);
        positions[i3 + 2] = r * Math.cos(phi);

        // Fast color selection
        let col = c1;
        const rand = Math.random();
        if (rand > 0.6) col = c2;
        else if (rand > 0.8) col = c3;
        else if (rand > 0.9) col = c4;
        else if (rand > 0.95) col = c5;

        // Store base colors efficiently
        baseColors[i3] = col[0];
        baseColors[i3 + 1] = col[1];
        baseColors[i3 + 2] = col[2];

        // Initial color state (will be overwritten by animation loop immediately, but good for first frame)
        colors[i3] = col[0];
        colors[i3 + 1] = col[1];
        colors[i3 + 2] = col[2];

        alphas[i] = Math.random() * Math.PI * 2;
    }

    // Initialize display colors
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

    // Simple PointsMaterial is instantly compiled compared to ShaderMaterial
    const material = new THREE.PointsMaterial({
        size: 3.5, // slightly larger to emphasize the cross shape
        map: createFastTexture(),
        transparent: true,
        vertexColors: true,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
        sizeAttenuation: true
    });

    stars = new THREE.Points(geometry, material);
    scene.add(stars);
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function animate() {
    requestAnimationFrame(animate);

    const time = clock.getElapsedTime();

    if (stars) {
        const colorsAttr = stars.geometry.attributes.color.array;
        const count = alphas.length;

        // Fast, tight inner loop for CPU shimmering
        for (let i = 0; i < count; i++) {
            // Fast approx absolute sine
            const shimmer = 0.2 + 0.8 * Math.abs(Math.sin(time * 2.0 + alphas[i]));

            const i3 = i * 3;
            colorsAttr[i3] = baseColors[i3] * shimmer;
            colorsAttr[i3 + 1] = baseColors[i3 + 1] * shimmer;
            colorsAttr[i3 + 2] = baseColors[i3 + 2] * shimmer;
        }

        stars.geometry.attributes.color.needsUpdate = true;

        // Panning the camera/scene to simulate moving entirely through the starfield
        // We rotate the entire point cloud slowly on multiple axes
        stars.rotation.x = time * 0.015;
        stars.rotation.y = time * 0.01;
    }

    renderer.render(scene, camera);
}

export function initThreeBackground() {
    if (!document.getElementById('three-canvas')) return;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}
