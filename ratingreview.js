document.addEventListener('DOMContentLoaded', () => {
    const stars = document.querySelectorAll('#star-rating i');
    let currentRating = 0;

    // Star Click Interaction
    stars.forEach(star => {
        star.addEventListener('click', () => {
            currentRating = star.getAttribute('data-value');
            updateStars(currentRating);
        });

        // Hover effect
        star.addEventListener('mouseover', () => {
            updateStars(star.getAttribute('data-value'));
        });
    });

    // Reset stars to actual rating when mouse leaves the container
    document.getElementById('star-rating').addEventListener('mouseleave', () => {
        updateStars(currentRating);
    });

    function updateStars(rating) {
        stars.forEach(star => {
            if (star.getAttribute('data-value') <= rating) {
                star.classList.remove('fa-regular');
                star.classList.add('fa-solid', 'active');
            } else {
                star.classList.remove('fa-solid', 'active');
                star.classList.add('fa-regular');
            }
        });
    }

    // Submit Review Logic
    document.getElementById('submit-btn').addEventListener('click', () => {
        const review = document.getElementById('review-text').value.trim();
        
        if (currentRating === 0) {
            alert('Please select a star rating before submitting!');
            return;
        }

        // Show confirmation
        alert(`Thank you, James! You gave ${currentRating} star${currentRating > 1 ? 's' : ''}.\n${review ? 'Review: ' + review : 'No review text provided.'}`);
        
        // In a real application, you would send this data to the server
        console.log({
            orderId: 'ORD-2025-1234',
            restaurant: "Auntie Anne's — KL Sentral",
            rating: currentRating,
            review: review,
            user: 'James',
            date: new Date().toISOString()
        });

        // Redirect to order history or home page after submission
        setTimeout(() => {
            window.location.href = 'orderhistory.html'; // Or 'index.html'
        }, 1500);
    });
});

// Cancel Review Function
function cancelReview() {
    if (confirm('Are you sure you want to cancel? Your review will not be saved.')) {
        // Redirect back to order history or home
        window.location.href = 'orderhistory.html'; // Or 'index.html'
    }
}