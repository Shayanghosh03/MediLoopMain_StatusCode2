# MediLoop - Medication Sharing & Donation Platform

<img width="1920" height="1080" alt="Image" src="https://github.com/user-attachments/assets/9da699d5-acc4-404f-b2dd-71cf0aa045c3" />

# Live Link
https://mediloop.wuaze.com

## New Features Added

### 1. Email Functionality
- Contact form now sends emails to your specified email address
- Enhanced form with subject field and better validation
- Configurable email service integration

### 2. Deliver Button
- Interactive deliver button in the features section
- Shows notification when clicked
- Simulates delivery service initiation

### 3. Improved "Get in Touch" UI
- Enhanced contact section with better visual design
- Added more contact information (office address, support hours)
- Improved form layout with labels and icons
- Better responsive design for mobile devices

### 4. Enhanced Footer Design
- Added social media links
- More comprehensive contact information
- Additional service links
- Better visual hierarchy and spacing
- Privacy policy and terms links

### 5. Logo Navigation
- Clicking the MediLoop logo on any page redirects to the home page
- Consistent navigation across all pages



### Option 3: Netlify Forms
1. Deploy to Netlify
2. Update `email-config.js`:
   ```javascript
   const EMAIL_CONFIG = {
       recipientEmail: 'your-email@example.com',
       netlify: {
           formName: 'contact-form'
       }
   };
   ```

## File Structure
```
Status Code 2/
├── index.html          # Main homepage with enhanced features
├── login.html          # Login page with logo navigation
├── signup.html         # Signup page with logo navigation
├── donate.html         # Donate page with logo navigation
├── find.html           # Find page with logo navigation
├── verify.html         # Verify page with logo navigation
├── styles.css          # Enhanced CSS with new styling
├── script.js           # JavaScript with email and deliver functionality
├── email-config.js     # Email configuration file
└── README.md           # This file
```

## Features Overview

### Contact Form
- Name, email, subject, and message fields
- Real-time validation
- Email sending functionality
- Success/error notifications

### Deliver Button
- Located in the features section
- Interactive hover effects
- Notification system integration

### Navigation
- Logo click redirects to home page
- Consistent across all pages
- Mobile-responsive design

### Footer
- Social media integration
- Comprehensive contact information
- Service links
- Legal links

## Browser Compatibility
- Chrome (recommended)
- Firefox
- Safari
- Edge

## Setup Instructions
1. Update `email-config.js` with your email service credentials
2. Test the contact form functionality
3. Customize colors and styling in `styles.css` if needed
4. Deploy to your web server

## Support
For technical support or questions about the implementation, please refer to the email configuration file or contact the development team.

