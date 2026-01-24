<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mups Cafe - Navbar</title>
    <link rel="stylesheet" href="../assets/css/nav.css">

</head>
<body>
  <nav>
    <div class="nav-container">
      <a href="../customers/customer_dashboard.php" class="logo" id="logoLink">
        <div class="logo-icon">☕</div>
        <span class="logo-text">MUPS Cafe</span>
      </a>

      <ul class="nav-links" id="navLinks">
        <li><a href="../customers/customer_dashboard.php" class="nav-link active" id="home">Home</a></li>
        <li><a href="../customers/menu.php" class="nav-link" id="menu">Menu</a></li>
        <li><a href="../customers/about.php" class="nav-link">About Us</a></li>
        <li><a href="../customers/cart.php" class="nav-link" id="cart">Cart</a></li>
        <li><a href="contact.php" class="nav-link">Contact</a></li>
        <div class="mobile-actions">
          <a href="../customers/menu.php" class="btn-primary">Order Now</a>
         <button class="btn-logout">Logout</button></a>
        </div>
      </ul>

      <div class="nav-cta">
        <a href="../customers/menu.php" class="btn-primary" onclick="sessionStorage.setItem('activeLink','menu')">Order Now</a>
        <a href="../auth/logout.php" onclick="return confirm('ARE YOU SURE?');"><button class="btn-logout">Logout</button></a>
      </div>

      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </nav>

  <script>
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');

    menuToggle.addEventListener('click', () => {
      menuToggle.classList.toggle('active');
      navLinks.classList.toggle('active');
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
        menuToggle.classList.remove('active');
        navLinks.classList.remove('active');
      }
    });

    // Close menu when clicking a link
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        menuToggle.classList.remove('active');
        navLinks.classList.remove('active');
      });
    });

    // Active link functionality - only on click
    const navLinksElements = document.querySelectorAll('.nav-link');
    
    navLinksElements.forEach(link => {
      link.addEventListener('click', function(e) {
        // Remove active class from all links
        navLinksElements.forEach(l => l.classList.remove('active'));
        // Add active class to clicked link
        this.classList.add('active');
        
        // Store the active link in sessionStorage
        sessionStorage.setItem('activeLink', this.id || this.getAttribute('href'));
      });
    });

    // Logo click functionality - set home as active
    const logoLink = document.getElementById('logoLink');
    if (logoLink) {
      logoLink.addEventListener('click', function() {
        // Set home as the active link
        sessionStorage.setItem('activeLink', 'home');
      });
    }

    // Restore active link on page load
    window.addEventListener('DOMContentLoaded', () => {
      const activeLink = sessionStorage.getItem('activeLink');
      if (activeLink) {
        navLinksElements.forEach(link => {
          link.classList.remove('active');
          if (link.id === activeLink || link.getAttribute('href') === activeLink) {
            link.classList.add('active');
          }
        });
      }
    });

    // // Logout functionality
    // function handleLogout() {
    //   if (confirm('Are you sure you want to logout?')) {
    //     // Add your logout logic here
    //     alert('Logging out...');
    //     // Example: window.location.href = '/logout';
    //   }
    // }

    // document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    // document.getElementById('logoutBtnMobile').addEventListener('click', () => {
    //   handleLogout();
    //   menuToggle.classList.remove('active');
    //   navLinks.classList.remove('active');
    // });
  </script>
</body>
</html>