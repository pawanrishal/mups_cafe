<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mups Cafe - Footer</title>
  <!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <link rel="stylesheet" href="../assets/css/footer.css">

  <style>
    
  </style>
</head>
<body>
  

  <!-- Footer -->
  <footer>
    <div class="footer-container">
      <div class="footer-content">
        <!-- About Section -->
        <div class="footer-section footer-about">
          <div class="footer-logo">
            <div class="footer-logo-icon">☕</div>
            <span class="footer-logo-text">Mups Cafe</span>
          </div>
          <p>
            Experience the perfect blend of comfort and quality. We serve artisanal coffee, 
            delicious pastries, and create memorable moments in a cozy atmosphere.
          </p>
          <div class="footer-social">
            <a href="#" class="social-link" aria-label="Facebook"><i class="bi bi-facebook" style="color: #0866FF"></i></a>
            <a href="#" class="social-link" aria-label="Instagram"><i class="bi bi-instagram" style=" color: #E1306C;  "></i></a>
            <a href="#" class="social-link" aria-label="Tiktok"><i class="bi bi-tiktok" style="color:black"></i></a>
            <a href="#" class="social-link" aria-label="YouTube"><i class="bi bi-youtube" style="color: #e71313" ></i></a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-section">
          <h3>Quick Links</h3>
          <ul class="footer-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#menu">Menu</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#gallery">Gallery</a></li>
            <li><a href="#reservations">Reservations</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div class="footer-section">
          <h3>Contact Us</h3>
          <div class="footer-contact-item">
            <span class="icon"><i class="bi bi-geo-alt-fill"></i></span>
            <div>
              <strong>Address:</strong><br>
              Durbarmarg, Kathmandu<br>
              Nepal
            </div>
          </div>
          <div class="footer-contact-item">
            <span class="icon"><i class="bi bi-telephone-fill"></i></span>
            <div>
              <strong>Phone:</strong><br>
              +977 9703080105
            </div>
          </div>
          <div class="footer-contact-item">
            <span class="icon"><i class="bi bi-envelope-fill"></i></span>
            <div>
              <strong>Email:</strong><br>
              hello@mupscafe.com
            </div>
          </div>
        </div>

        <!-- Newsletter -->
        <div class="footer-section">
          <h3>Newsletter</h3>
          <p style="color: rgba(255, 255, 255, 0.8); font-size: 14px; margin-bottom: 16px;">
            Subscribe to get special offers, free giveaways, and updates.
          </p>
          <form class="newsletter-form" onsubmit="handleNewsletter(event)">
            <input 
              type="email" 
              class="newsletter-input" 
              placeholder="Enter your email" 
              required
            >
            <button type="submit" class="newsletter-btn">Subscribe</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <div class="footer-bottom-content">
        <p class="footer-copyright">
          &copy; 2026 Mups Cafe. All rights reserved.
        </p>
        <ul class="footer-bottom-links">
          <li><a href="#privacy">Privacy Policy</a></li>
          <li><a href="#terms">Terms of Service</a></li>
          <li><a href="#cookies">Cookie Policy</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <script>
    function handleNewsletter(event) {
      event.preventDefault();
      const email = event.target.querySelector('input').value;
      alert(`Thank you for subscribing with: ${email}`);
      event.target.reset();
    }
  </script>
</body>
</html>