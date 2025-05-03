//  Menu Toggle Functionality
const menuToggle = document.querySelector('.menu-toggle');
const menu = document.querySelector('.menu');

if (menuToggle && menu) {
  menuToggle.addEventListener('click', () => {
    menu.classList.toggle('active');
    
    // Animate the hamburger menu
    const spans = menuToggle.querySelectorAll('span');
    spans.forEach(span => {
      span.classList.toggle('active');
    });
  });
}

//  Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    e.preventDefault();
    
    const targetId = this.getAttribute('href');
    // Make sure targetId is not just "#" before trying to select
    if (targetId && targetId !== "#") {
      const targetElement = document.querySelector(targetId);
      
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 100,
          behavior: 'smooth'
        });
      }
    }
  });
});
 

// Simple form validation function to be used across forms
function validateForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll('[required]');
  
  requiredFields.forEach(field => {
    if (!field.value.trim()) {
      isValid = false;
      
      // Add error class
      field.classList.add('error');
      
      // Create error message if it doesn't exist
      let errorMessage = field.nextElementSibling;
      if (!errorMessage || !errorMessage.classList.contains('error-message')) {
        errorMessage = document.createElement('span');
        errorMessage.classList.add('error-message');
        errorMessage.textContent = 'This field is required';
        field.parentNode.insertBefore(errorMessage, field.nextSibling);
      }
    } else {
      // Remove error class
      field.classList.remove('error');
      
      // Remove error message if it exists
      const errorMessage = field.nextElementSibling;
      if (errorMessage && errorMessage.classList.contains('error-message')) {
        errorMessage.remove();
      }
    }
  });
  
  return isValid;
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
  // Add any initialization code here
  console.log('Sage platform initialized');
});
 