document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const sliderContainer = document.getElementById('sliderContainer');
    const dotsContainer = document.getElementById('dotsContainer');

    if (!slides.length || !sliderContainer || !dotsContainer) {
        console.error('Slider elements not found. Check HTML IDs and classes.');
        return;
    }

    let currentIndex = 0;
    const totalSlides = slides.length;

    // Create dots based on number of slides
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('span');
        dot.classList.add('dot');
        dot.addEventListener('click', () => goToSlide(i));
        dotsContainer.appendChild(dot);
    }

    const dots = document.querySelectorAll('.dot');
    if (dots.length > 0) {
        dots[0].classList.add('active');
    }

    function updateSlide() {
        sliderContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
        dots.forEach(dot => dot.classList.remove('active'));
        if (dots[currentIndex]) {
            dots[currentIndex].classList.add('active');
        }
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlide();
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateSlide();
    }

    function goToSlide(index) {
        currentIndex = index;
        updateSlide();
    }

    // Auto slide every 10 seconds
    setInterval(nextSlide, 5000);

    // Expose functions globally for onclick (if needed)
    window.nextSlide = nextSlide;
    window.prevSlide = prevSlide;
});