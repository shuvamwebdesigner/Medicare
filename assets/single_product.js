function changeImage(src) {
    document.getElementById('mainImage').src = src;
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.src.includes(src.split('/').pop())) {
            thumb.classList.add('active');
        }
    });
}

function scrollThumbnails(direction) {
    const slider = document.getElementById('thumbnailSlider');
    const scrollAmount = 100; // Adjust scroll distance as needed
    slider.scrollLeft += direction * scrollAmount;
}

// Optional: Auto-scroll to active thumbnail
function scrollToActiveThumbnail() {
    const activeThumb = document.querySelector('.thumbnail.active');
    if (activeThumb) {
        activeThumb.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        });
    }
}

// Enhanced changeImage function with auto-scroll
function changeImage(src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.src.includes(src.split('/').pop())) {
            thumb.classList.add('active');
            scrollToActiveThumbnail();
        }
    });
}

// Tabs navigation
document.querySelectorAll('.tabs-nav li').forEach((tab, idx) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tabs-nav li').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.querySelectorAll('.tab-pane')[idx].classList.add('active');
    });
});

// FAQ toggle (if needed)
document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
        const ans = q.nextElementSibling;
        if (ans.style.display === 'block') {
            ans.style.display = 'none';
        } else {
            ans.style.display = 'block';
        }
        q.classList.toggle('expanded');
    });
});


// Zoom functionality
document.addEventListener('DOMContentLoaded', function() {
    const img = document.getElementById('mainImage');
    const zoomArea = document.getElementById('zoomArea');
    const zoomLevel = 2; // Adjust zoom level as needed
    
    img.addEventListener('mouseenter', function() {
        zoomArea.style.display = 'block';
        zoomArea.style.backgroundImage = `url(${img.src})`;
        zoomArea.style.backgroundSize = `${img.offsetWidth * zoomLevel}px ${img.offsetHeight * zoomLevel}px`;
        zoomArea.style.backgroundRepeat = 'no-repeat';
    });
    
    img.addEventListener('mousemove', function(e) {
        const rect = img.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Calculate background position for zoom (centering the mouse position in the zoom area)
        const bgX = - (x / rect.width) * (img.offsetWidth * zoomLevel - zoomArea.offsetWidth);
        const bgY = - (y / rect.height) * (img.offsetHeight * zoomLevel - zoomArea.offsetHeight);

        zoomArea.style.backgroundPosition = `${bgX}px ${bgY}px`;
    });

    img.addEventListener('mouseleave', function() {
        zoomArea.style.display = 'none';
    });
});