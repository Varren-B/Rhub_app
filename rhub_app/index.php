<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHUB - RHIMBS Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon">RH</div>
            <span class="logo-text">RHUB</span>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="#home">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="login.php" class="btn-login">Login</a></li>
        </ul>
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Hero Section with Slider -->
    <section class="hero" id="home">
        <div class="slider-container">
            <div class="slide slide-1 active">
                <div class="slide-overlay">
                    <div class="floating-elements">
                        <div class="floating-icon icon-1">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="floating-icon icon-2">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="floating-icon icon-3">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slide slide-2">
                <div class="slide-overlay">
                    <div class="floating-elements">
                        <div class="floating-icon icon-1">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <div class="floating-icon icon-2">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="floating-icon icon-3">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slide slide-3">
                <div class="slide-overlay">
                    <div class="floating-elements">
                        <div class="floating-icon icon-1">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="floating-icon icon-2">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="floating-icon icon-3">
                            <i class="fas fa-rocket"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-content">
            <h1>Welcome to <span>RHIMBS</span> Higher Institute</h1>
            <p>Your gateway to excellence in education. Access fee payments, marketplace, and all student services in one convenient platform.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Get Started
                </a>
                <a href="#features" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
        </div>

        <!-- Floating Icons on the Right -->
        <div class="hero-floating-icons">
            <div class="float-icon" style="animation-delay: 0s;">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="float-icon" style="animation-delay: 0.5s;">
                <i class="fas fa-laptop"></i>
            </div>
            <div class="float-icon" style="animation-delay: 1s;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="float-icon" style="animation-delay: 1.5s;">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <div class="slider-nav">
            <span class="slider-dot active" data-slide="0"></span>
            <span class="slider-dot" data-slide="1"></span>
            <span class="slider-dot" data-slide="2"></span>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Our Platform Features</h2>
            <p>Everything you need to manage your academic journey at RHIMBS in one place</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>Online Fee Payment</h3>
                <p>Pay your tuition fees securely using MTN Mobile Money or Orange Money. Track your payment history and outstanding balances in real-time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h3>Student Marketplace</h3>
                <p>Buy and sell items with fellow students. From textbooks to electronics, find great deals or sell items you no longer need.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Peer-to-Peer Messaging</h3>
                <p>Communicate directly with buyers and sellers through our secure messaging system. Negotiate prices and arrange meetups easily.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Transactions</h3>
                <p>All payments are processed through secure channels with MTN and Orange Cameroon. Your financial data is always protected.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Friendly</h3>
                <p>Access all features from any device. Our responsive design ensures a seamless experience on phones, tablets, and computers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Real-time Notifications</h3>
                <p>Stay updated with payment confirmations, new marketplace listings, and messages from other students instantly.</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="about-container">
            <div class="about-content">
                <h2>About RHIMBS Higher Institute</h2>
                <p>RHIMBS (Recognized Higher Institute of Management and Business Studies) is a premier educational institution in Cameroon dedicated to nurturing future business leaders and professionals.</p>
                <p>Established with a vision to provide world-class education, RHIMBS offers a wide range of programs in Business Administration, Computer Science, Accounting, Marketing, and more.</p>
                <div class="about-stats">
                    <div class="stat-item">
                        <h3>2000+</h3>
                        <p>Active Students</p>
                    </div>
                    <div class="stat-item">
                        <h3>15+</h3>
                        <p>Departments</p>
                    </div>
                    <div class="stat-item">
                        <h3>50+</h3>
                        <p>Expert Faculty</p>
                    </div>
                    <div class="stat-item">
                        <h3>95%</h3>
                        <p>Graduate Success</p>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <div class="about-image-card">
                    <h4><i class="fas fa-graduation-cap"></i> Academic Excellence</h4>
                    <p>Our curriculum is designed to meet international standards while addressing local industry needs.</p>
                </div>
                <div class="about-image-card">
                    <h4><i class="fas fa-users"></i> Student Community</h4>
                    <p>Join a vibrant community of learners and build lasting connections for your professional career.</p>
                </div>
                <div class="about-image-card">
                    <h4><i class="fas fa-laptop"></i> Modern Facilities</h4>
                    <p>State-of-the-art computer labs, libraries, and learning resources at your fingertips.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="section-header">
            <h2>Our Services</h2>
            <p>RHIMBS provides comprehensive services to support your academic journey</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-image">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="service-content">
                    <h3>Fee Management</h3>
                    <p>Easy online fee payment with multiple payment options. Track your payment status and download receipts anytime.</p>
                </div>
            </div>
            <div class="service-card">
                <div class="service-image">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="service-content">
                    <h3>Campus Marketplace</h3>
                    <p>Buy and sell textbooks, electronics, and other items within the RHIMBS student community safely.</p>
                </div>
            </div>
            <div class="service-card">
                <div class="service-image">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="service-content">
                    <h3>Student ID Services</h3>
                    <p>Access your digital student ID and verify your enrollment status online.</p>
                </div>
            </div>
            <div class="service-card">
                <div class="service-image">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="service-content">
                    <h3>24/7 Support</h3>
                    <p>Our support team is always ready to help you with any questions or issues you may have.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="section-header">
            <h2>What Students Say</h2>
            <p>Hear from our students about their experience with RHUB</p>
        </div>
        <div class="testimonial-slider">
            <div class="testimonial-track" id="testimonialTrack">
                <div class="testimonial-card">
                    <p class="quote">The fee payment system is incredibly convenient. I can pay my fees from anywhere using MTN Mobile Money without standing in long queues.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">JN</div>
                        <div class="author-info">
                            <h4>Jean Nkeng</h4>
                            <p>Computer Science, Level 300</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="quote">I sold my old textbooks through the marketplace and earned enough to buy new ones for this semester. Great platform!</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AM</div>
                        <div class="author-info">
                            <h4>Adeline Mbong</h4>
                            <p>Business Administration, Level 200</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="quote">The messaging feature made it easy to communicate with sellers. I found exactly what I needed at a great price.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">PT</div>
                        <div class="author-info">
                            <h4>Patrick Tanyi</h4>
                            <p>Accounting, Level 400</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-nav">
                <button id="prevTestimonial"><i class="fas fa-chevron-left"></i></button>
                <button id="nextTestimonial"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Ready to Get Started?</h2>
        <p>Join thousands of RHIMBS students already using our platform</p>
        <a href="register.php" class="btn btn-primary">
            <i class="fas fa-rocket"></i> Create Your Account
        </a>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>RHUB Portal</h3>
                <p>The official student portal for RHIMBS Higher Institute. Manage your fees, buy and sell items, and connect with fellow students.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="login.php">Student Login</a></li>
                    <li><a href="register.php">Create Account</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#about">About RHIMBS</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> Douala, Cameroon</p>
                <p><i class="fas fa-phone"></i> +237 6XX XXX XXX</p>
                <p><i class="fas fa-envelope"></i> info@rhimbs.edu</p>
            </div>
            <div class="footer-section">
                <h3>Payment Partners</h3>
                <p style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem;">
                    <span style="background: #FFCC00; color: #000; padding: 0.5rem 1rem; border-radius: 5px; font-weight: 700;">MTN MoMo</span>
                    <span style="background: #FF6600; color: #fff; padding: 0.5rem 1rem; border-radius: 5px; font-weight: 700;">Orange Money</span>
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 RHUB - RHIMBS Student Portal. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
