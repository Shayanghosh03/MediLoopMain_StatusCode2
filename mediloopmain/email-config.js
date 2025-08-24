// Email Configuration for MediLoop
// Replace these values with your actual email service credentials

const EMAIL_CONFIG = {
    // Your email address where you want to receive messages
    recipientEmail: 'mainaksutradhar0@gmail.com',
    
    // Email service configuration
    service: 'gmail', // or 'outlook', 'yahoo', etc.
    
    // EmailJS configuration (if using EmailJS)
    emailjs: {
        serviceId: 'service_ih8i1ov', // Replace with your EmailJS service ID
        templateId: 'template_t2n83zr', // Replace with your EmailJS template ID
        userId: 'TqTTGi3QR_rcD6xMv' // Replace with your EmailJS user ID
    },
    
    // Alternative: Formspree configuration
    formspree: {
        endpoint: 'https://formspree.io/f/your-form-id'
    },
    
    // Alternative: Netlify Forms configuration
    netlify: {
        formName: 'contact-form'
    }
};

// Email template for contact form
const EMAIL_TEMPLATE = {
    subject: 'New Message from MediLoop Contact Form',
    body: `
        Name: {name}
        Email: {email}
        Subject: {subject}
        Message: {message}
        
        ---
        This message was sent from the MediLoop contact form.
    `
};

// Export configuration
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { EMAIL_CONFIG, EMAIL_TEMPLATE };
}
