// Reusable function to initialize a carousel by its ID
function initCarousel(carouselId) {
    const carousel = document.getElementById(carouselId);
    if (!carousel) return; // Safety check if the carousel doesn't exist

    // Scope selectors to the carousel's parent container (the .featured-carousel-container)
    const container = carousel.parentElement;
    const prevBtn = container.querySelector('.carousel-nav.prev');
    const nextBtn = container.querySelector('.carousel-nav.next');
    const cardWidth = carousel.querySelector('.featured-card').offsetWidth + 20; // Include gap

    // Function to update arrow visibility
    function updateArrows() {
        const isAtStart = carousel.scrollLeft === 0;
        const isAtEnd = carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 1; // Small tolerance for rounding

        prevBtn.style.display = isAtStart ? 'none' : 'block';
        nextBtn.style.display = isAtEnd ? 'none' : 'block';
    }

    // Function to slide previous
    function slidePrev() {
        carousel.scrollLeft -= cardWidth;
        setTimeout(updateArrows, 300); // Delay to allow scroll to complete
    }

    // Function to slide next
    function slideNext() {
        carousel.scrollLeft += cardWidth;
        setTimeout(updateArrows, 300); // Delay to allow scroll to complete
    }

    // Attach click event listeners to the buttons
    prevBtn.addEventListener('click', slidePrev);
    nextBtn.addEventListener('click', slideNext);

    // Initialize arrows on load and on scroll
    updateArrows();
    carousel.addEventListener('scroll', updateArrows);
}

// Initialize each carousel (call this for each section)
initCarousel('featuredCarousel');      // For Featured Products
initCarousel('personalCareCarousel');  // For Personal Care Products