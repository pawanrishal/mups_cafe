<?php
session_start();

// Handle contact form submission
if(isset($_POST['send_message'])) {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // Validate
    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['error'] = "Please fill in all fields!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address!";
    } else {
        // Here you can send email or save to database
        // For now, we'll just show success message
        $_SESSION['success'] = "Thank you for contacting us! We'll get back to you soon.";
        
        // Clear form by redirecting
        header("Location: contact.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/contact.css">
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="hero-content">
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you! Reach out for reservations, feedback, or just to say hello.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-content">
                <!-- Contact Form -->
                <div class="contact-form-container">
                    <h2>Send Us a Message</h2>
                    <p class="form-subtitle">Have a question or feedback? Fill out the form below and we'll respond as soon as possible.</p>

                    <!-- Success Message -->
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="success-message show">
                            <?php 
                                echo $_SESSION['success']; 
                                unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Error Message -->
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="error-message show">
                            <?php 
                                echo $_SESSION['error']; 
                                unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="contact-form">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" placeholder="pawan rishal" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="What is this regarding?" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" placeholder="Write your message here..." required></textarea>
                        </div>

                        <button type="submit" name="send_message" class="submit-btn">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div class="contact-info">
                    <h2>Contact Information</h2>
                    <p class="info-subtitle">You can also reach us through any of these channels</p>

                    <div class="info-cards">
                        <div class="info-card">
                            <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                            <h3>Visit Us</h3>
                            <p>123 Coffee Street<br>Kathmandu, Nepal<br>Postal Code: 44600</p>
                        </div>

                        <div class="info-card">
                            <div class="info-icon"><i class="bi bi-telephone"></i></div>
                            <h3>Call Us</h3>
                            <p>+977 123-456-7890<br>+977 098-765-4321</p>
                            <span class="timing">Mon-Sun: 7:00 AM - 10:00 PM</span>
                        </div>

                        <div class="info-card">
                            <div class="info-icon"><i class="bi bi-envelope"></i></div>
                            <h3>Email Us</h3>
                            <p>info@mupscafe.com<br>support@mupscafe.com</p>
                            <span class="timing">Response within 24 hours</span>
                        </div>

                        <div class="info-card">
                            <div class="info-icon"><i class="bi bi-clock"></i></div>
                            <h3>Opening Hours</h3>
                            <p>
                                Monday - Friday: 7:00 AM - 10:00 PM<br>
                                Saturday - Sunday: 8:00 AM - 11:00 PM
                            </p>
                        </div>
                    </div>

                    
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2>Find Us on the Map</h2>
            <div class="map-container">
                <div class="map-wrapper">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.123456789012!2d85.3240!3d27.7172!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1b1234567890%3A0x1234567890abcdef!2sDurbarmarg%2C%20Kathmandu%2044600%2C%20Nepal!5e0!3m2!1sen!2s!4v1234567890123!5m2!1sen!2s"
                        width="100%"
                        height="400"
                        style="border:0; border-radius: 12px;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                    <div class="map-overlay" onclick="openInMaps()">
                        <div class="map-overlay-content">
                        </div>
                    </div>
                </div>
                <div class="map-info">
                    <h3>Mups Cafe</h3>
                    <p><i class="bi bi-geo-alt"></i> Durbarmarg, Kathmandu, Nepal</p>
                    <p><i class="bi bi-clock"></i> Open Daily: 7:00 AM - 9:00 PM</p>
                    <p><i class="bi bi-telephone"></i> +977-1-234567</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers to common questions</p>
            </div>

            <div class="faq-grid">
                <div class="faq-item">
                    <h3>Do you take reservations?</h3>
                    <p>Yes! While walk-ins are welcome, we recommend reserving a table during peak hours (lunch and dinner) to ensure seating.</p>
                </div>

                <div class="faq-item">
                    <h3>Do you offer takeout or delivery?</h3>
                    <p>Currently, we offer dine-in service with our convenient table ordering system. Takeout options are coming soon!</p>
                </div>

                <div class="faq-item">
                    <h3>Is WiFi available?</h3>
                    <p>Yes! Free high-speed WiFi is available for all our customers. Perfect for working or studying while enjoying your meal.</p>
                </div>

                <div class="faq-item">
                    <h3>Do you accommodate dietary restrictions?</h3>
                    <p>Absolutely! We have vegetarian, vegan, and gluten-free options. Please inform our staff of any allergies or dietary needs.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include '../Partials/footer.php'; ?>

    <script>
        // Auto-hide messages
        setTimeout(() => {
            const successMsg = document.querySelector('.success-message');
            const errorMsg = document.querySelector('.error-message');
            
            if(successMsg) {
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 300);
            }
            
            if(errorMsg) {
                errorMsg.style.opacity = '0';
                setTimeout(() => errorMsg.remove(), 300);
            }
        }, 5000);

        // Open location in Google Maps
        function openInMaps() {
            // Durbarmarg coordinates
            const latitude = 27.7172;
            const longitude = 85.3240;
            
            // Try to open in Google Maps app (mobile) or web version
            if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                // Mobile - try to open in Google Maps app
                window.location.href = `geo:${latitude},${longitude}?q=${latitude},${longitude}(Mups+Cafe+Durbarmarg)`;
            } else {
                // Desktop - open in Google Maps web
                window.open(`https://www.google.com/maps/search/?api=1&query=${latitude},${longitude}`, '_blank');
            }
        }
    </script>
</body>
</html>