<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Mups Cafe</title>
    <link rel="stylesheet" href="../assets/css/about.css">
</head>
<body>
    <?php include '../Partials/nav.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-content">
            <h1>About Mups Cafe</h1>
            <p>Where every cup tells a story and every meal feels like home</p>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="our-story">
        <div class="container">
            <div class="story-content">
                <div class="story-text">
                    <span class="section-badge">Our Story</span>
                    <h2>A Passion for Great Food & Coffee</h2>
                    <p>
                        Founded in 2020, Mups Cafe began as a dream to create a welcoming space where people 
                        could enjoy exceptional food and coffee while feeling right at home. What started as a 
                        small neighborhood cafe has grown into a beloved community gathering place.
                    </p>
                    <p>
                        We believe in the power of good food to bring people together. Every dish we serve is 
                        crafted with care using fresh, quality ingredients. Our passionate team works tirelessly 
                        to ensure every visit to Mups Cafe is a memorable experience.
                    </p>
                    <p>
                        From our signature breakfast dishes to our carefully curated dinner menu, we're committed 
                        to serving meals that not only satisfy your hunger but also warm your heart.
                    </p>
                </div>
                <div class="story-image">
                    <img src="../uploads/image/cappuccino.jpg" alt="" class="image-placeholder">
                    
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="our-values">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Our Values</span>
                <h2>What We Stand For</h2>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">⭐</div>
                    <h3>Quality First</h3>
                    <p>We use only the freshest ingredients and prepare everything with attention to detail.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">❤️</div>
                    <h3>Made with Love</h3>
                    <p>Every dish is prepared with passion and served with genuine care for our guests.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">🤝</div>
                    <h3>Community Focus</h3>
                    <p>We're more than a cafe - we're a gathering place for friends, families, and neighbors.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">♻️</div>
                    <h3>Sustainability</h3>
                    <p>We're committed to eco-friendly practices and supporting local suppliers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="our-team">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Our Team</span>
                <h2>Meet the People Behind Mups Cafe</h2>
                <p>Passionate individuals dedicated to making your experience exceptional</p>
            </div>

            <div class="team-grid">
                <div class="team-member">
                    <img src="../uploads/image/head_chef.jpg" alt="" class="member-image">   
                    <h3>John Smith</h3>
                    <p class="member-role">Head Chef</p>
                    <p class="member-bio">With 15 years of culinary experience, John brings creativity and passion to every dish.</p>
                </div>

                <div class="team-member">
                    <img src="../uploads/image/manager.jpg" alt="" class="member-image">
                    <h3>Sarah Johnson</h3>
                    <p class="member-role">Cafe Manager</p>
                    <p class="member-bio">Sarah ensures everything runs smoothly and every guest feels welcome.</p>
                </div>

                <div class="team-member">
                    <img src="../uploads/image/chef.jpg" alt="" class="member-image">
                    <h3>Mike Brown</h3>
                    <p class="member-role">Barista Lead</p>
                    <p class="member-bio">Mike is our coffee expert, crafting the perfect cup every single time.</p>
                </div>

                <div class="team-member">
                    <img src="../uploads/image/emily.jpg" alt="" class="member-image">
                    <h3>Emily Davis</h3>
                    <p class="member-role">Pastry Chef</p>
                    <p class="member-bio">Emily creates our delicious pastries and desserts fresh every morning.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-choose-us">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Why Choose Us</span>
                <h2>What Makes Us Special</h2>
            </div>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🍳</div>
                    <h3>Fresh Daily</h3>
                    <p>Everything prepared fresh each day using quality ingredients</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <h3>Quick Service</h3>
                    <p>Fast, friendly service without compromising on quality</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🏠</div>
                    <h3>Cozy Atmosphere</h3>
                    <p>Comfortable seating and welcoming ambiance for all occasions</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <h3>Great Value</h3>
                    <p>Delicious food at prices that won't break the bank</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🌱</div>
                    <h3>Healthy Options</h3>
                    <p>Nutritious choices for health-conscious diners</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📱</div>
                    <h3>Easy Ordering</h3>
                    <p>Convenient online ordering system for dine-in service</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Experience Mups Cafe?</h2>
                <p>Join us for a meal and discover why we're the neighborhood favorite</p>
                <div class="cta-buttons">
                    <a href="menu.php" class="btn-primary" style="align-content:center" onclick="sessionStorage.setItem('activeLink','menu')">View Our Menu</a>
                    <a href="contact.php" class="btn-secondary" onclick="sessionStorage.setItem('activeLink','contact')">Visit Us Today</a>
                </div>
            </div>
        </div>
    </section>

    <?php include '../Partials/footer.php'; ?>
</body>
</html>