/**
 * Utilidad ligera y sin dependencias externas para efectos de celebración (Confeti + Sonido Chime + Vibración Háptica).
 */

export function triggerHapticVibration(pattern = [40, 50, 40]) {
    try {
        if (typeof window !== 'undefined' && typeof navigator !== 'undefined' && navigator.vibrate) {
            navigator.vibrate(pattern);
        }
    } catch {
        // Fallo silencioso en navegadores sin soporte de vibración
    }
}

export function playSuccessChime() {
    triggerHapticVibration([40, 60, 40]);
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }

        const notes = [523.25, 659.25, 783.99, 1046.50]; // Do, Mi, Sol, Do (C5, E5, G5, C6)
        notes.forEach((freq, index) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + index * 0.08);

            gain.gain.setValueAtTime(0.12, ctx.currentTime + index * 0.08);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + index * 0.08 + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(ctx.currentTime + index * 0.08);
            osc.stop(ctx.currentTime + index * 0.08 + 0.35);
        });
    } catch (e) {
        // Fallo silencioso si las políticas de audio del navegador lo restringen
    }
}

export function triggerConfetti() {
    triggerHapticVibration([60, 40, 60]);
    if (typeof window === 'undefined') return;

    let canvas = document.getElementById('epycus-confetti-canvas');
    if (!canvas) {
        canvas = document.createElement('canvas');
        canvas.id = 'epycus-confetti-canvas';
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100vw';
        canvas.style.height = '100vh';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '9999';
        document.body.appendChild(canvas);
    }

    const ctx = canvas.getContext('2d');
    const width = (canvas.width = window.innerWidth);
    const height = (canvas.height = window.innerHeight);

    const colors = ['#f43f5e', '#ec4899', '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#06b6d4'];
    const particles = [];
    const particleCount = 65;

    for (let i = 0; i < particleCount; i++) {
        particles.push({
            x: width / 2 + (Math.random() - 0.5) * 200,
            y: height / 2 + (Math.random() - 0.5) * 100,
            vx: (Math.random() - 0.5) * 12,
            vy: (Math.random() - 0.7) * 14,
            size: Math.random() * 8 + 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rSpeed: (Math.random() - 0.5) * 10,
            opacity: 1,
        });
    }

    let animationFrame;
    const startTime = Date.now();

    function render() {
        const elapsed = Date.now() - startTime;
        ctx.clearRect(0, 0, width, height);

        particles.forEach((p) => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.35; // Gravedad
            p.vx *= 0.98; // Resistencia
            p.rotation += p.rSpeed;
            p.opacity = Math.max(0, 1 - elapsed / 2200);

            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = p.opacity;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.7);
            ctx.restore();
        });

        if (elapsed < 2200) {
            animationFrame = requestAnimationFrame(render);
        } else {
            ctx.clearRect(0, 0, width, height);
            cancelAnimationFrame(animationFrame);
        }
    }

    render();
}
