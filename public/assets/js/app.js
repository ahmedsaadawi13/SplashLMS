// FILE: /public/assets/js/app.js

/**
 * SplashLMS JavaScript Application
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Quiz timer
    const quizForm = document.getElementById('quiz-form');
    if (quizForm) {
        const startTime = Date.now();
        quizForm.addEventListener('submit', function() {
            const timeSpent = Math.floor((Date.now() - startTime) / 1000);
            const timeInput = document.createElement('input');
            timeInput.type = 'hidden';
            timeInput.name = 'time_spent';
            timeInput.value = timeSpent;
            this.appendChild(timeInput);
        });
    }

    // Toggle section visibility in course builder
    const sectionToggles = document.querySelectorAll('.section-toggle');
    sectionToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const sectionContent = this.nextElementSibling;
            sectionContent.style.display = sectionContent.style.display === 'none' ? 'block' : 'none';
        });
    });

});

// Mark lesson as complete
function markLessonComplete(lessonId, csrfToken) {
    fetch('/lesson/complete/' + lessonId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update progress bar
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = data.progress + '%';
                progressBar.textContent = Math.round(data.progress) + '%';
            }

            // Mark lesson as completed in sidebar
            const lessonItem = document.querySelector('[data-lesson-id="' + lessonId + '"]');
            if (lessonItem) {
                lessonItem.classList.add('completed');
            }

            // Show completion message if course is completed
            if (data.completed) {
                alert('Congratulations! You have completed this course. Your certificate is ready!');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// File upload preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Simple form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '#ddd';
        }
    });

    if (!isValid) {
        alert('Please fill in all required fields.');
    }

    return isValid;
}

// AJAX helper
function ajaxRequest(url, method, data, callback) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (method !== 'GET' && data) {
        options.body = JSON.stringify(data);
    }

    fetch(url, options)
        .then(response => response.json())
        .then(callback)
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}
