/**
 * Match Reels carousel – navegación, touch, dots, vídeo activo, modal
 * Sin dependencias. Solo se ejecuta si existe .vcf-reels-section
 */
(function () {
    'use strict';

    var section = document.querySelector('.vcf-reels-section');
    if (!section) return;

    var track = section.querySelector('.vcf-reels-track');
    var viewport = section.querySelector('.vcf-reels-viewport');
    var cards = section.querySelectorAll('.vcf-reels-card');
    var dotsContainer = section.querySelector('.vcf-reels-dots');
    var dots = section.querySelectorAll('.vcf-reels-dot');
    var btnPrev = section.querySelector('.vcf-reels-prev');
    var btnNext = section.querySelector('.vcf-reels-next');
    var modal = document.getElementById('vcfReelsModal');
    var modalVideo = modal ? modal.querySelector('.vcf-reels-modal-video') : null;
    var modalBackdrop = modal ? modal.querySelector('.vcf-reels-modal-backdrop') : null;
    var modalClose = modal ? modal.querySelector('.vcf-reels-modal-close') : null;

    var total = cards.length;
    if (total === 0) return;

    var currentIndex = 0;
    var GAP = 16; /* 1rem */

    /**
     * Obtiene el desplazamiento en px para el índice dado
     */
    function getOffsetForIndex(index) {
        var card = cards[0];
        if (!card) return 0;
        var cardWidth = card.offsetWidth;
        return index * (cardWidth + GAP);
    }

    /**
     * Actualiza la posición del track
     */
    function updateTrack() {
        var offset = getOffsetForIndex(currentIndex);
        track.style.transform = 'translateX(-' + offset + 'px)';
    }

    /**
     * Actualiza dots activo y ARIA
     */
    function updateDots() {
        dots.forEach(function (dot, i) {
            var isActive = i === currentIndex;
            dot.classList.toggle('vcf-reels-dot-active', isActive);
            dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    /**
     * Reproduce solo el vídeo de la tarjeta activa; pausa el resto
     */
    function playActiveVideo() {
        cards.forEach(function (card, i) {
            var video = card.querySelector('.vcf-reels-video');
            if (!video) return;
            if (i === currentIndex) {
                video.play().catch(function () {});
            } else {
                video.pause();
                video.currentTime = 0;
            }
        });
    }

    /**
     * Pausa todos los vídeos del carrusel
     */
    function pauseAllVideos() {
        cards.forEach(function (card) {
            var video = card.querySelector('.vcf-reels-video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
        });
    }

    /**
     * Ir al slide por índice (0-based)
     */
    function goTo(index) {
        index = Math.max(0, Math.min(index, total - 1));
        if (index === currentIndex) return;
        currentIndex = index;
        updateTrack();
        updateDots();
        playActiveVideo();
        updateButtons();
    }

    /**
     * Habilita/deshabilita botones prev/next según posición
     */
    function updateButtons() {
        if (btnPrev) btnPrev.disabled = currentIndex === 0;
        if (btnNext) btnNext.disabled = currentIndex === total - 1;
    }

    /**
     * Touch/Swipe
     */
    function setupTouch() {
        if (!viewport) return;
        var startX = 0;
        var minSwipe = 50;

        viewport.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
        }, { passive: true });

        viewport.addEventListener('touchend', function (e) {
            var endX = e.changedTouches[0].clientX;
            var delta = startX - endX;
            if (delta > minSwipe) {
                goTo(currentIndex + 1);
            } else if (delta < -minSwipe) {
                goTo(currentIndex - 1);
            }
        }, { passive: true });
    }

    /**
     * Modal: abrir con URL del vídeo y reproducir con sonido
     */
    function openModal(videoUrl) {
        if (!modal || !modalVideo) return;
        modalVideo.src = videoUrl;
        modal.setAttribute('aria-hidden', 'false');
        modalVideo.muted = false;
        modalVideo.play().catch(function () {});
    }

    function closeModal() {
        if (!modal || !modalVideo) return;
        modalVideo.pause();
        modalVideo.removeAttribute('src');
        modal.setAttribute('aria-hidden', 'true');
    }

    function setupModal() {
        if (!modal) return;

        section.querySelectorAll('.vcf-reels-card-fullscreen').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var card = btn.closest('.vcf-reels-card');
                var url = card ? card.getAttribute('data-video-url') : '';
                if (url) openModal(url);
            });
        });

        if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
        if (modalClose) modalClose.addEventListener('click', closeModal);

        modal.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    }

    /**
     * Resize: recalcular posición del track
     */
    function onResize() {
        updateTrack();
    }

    /* Inicialización */
    function init() {
        updateTrack();
        updateDots();
        playActiveVideo();
        updateButtons();
        setupTouch();
        setupModal();

        if (btnPrev) btnPrev.addEventListener('click', function () { goTo(currentIndex - 1); });
        if (btnNext) btnNext.addEventListener('click', function () { goTo(currentIndex + 1); });

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { goTo(i); });
        });

        window.addEventListener('resize', onResize);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
