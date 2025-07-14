<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroConnect - Empowering Farmers</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <nav class="navbar">
            <div class="logo">
               
                <span class="logo-text"><i class="fas fa-leaf"></i>Agro<span>Connect</span></span>
            </div>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#weather">Weather</a></li>
                <li><a href="#contact">Contact</a></li>
            
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="welcome-container">
            <h1>Welcome to AgroConnect</h1>
            <p class="subtitle">Empowering farmers with real-time market prices, weather information, and seamless transportation solutions</p>

            <div class="welcome-options">
                <div class="option-card">
                    <h3>Are you a Farmer/User?</h3>
                    <p>Get instant access to market prices across regions and book reliable transportation for your produce with just a few clicks.</p>
                    <a href="user_login.php" class="btn btn-secondary">Farmer Login</a>
                    <a href="user_register.php" class="btn btn-primary">Farmer Register</a>
                </div>

                <div class="option-card">
                    <h3>Are you a Vehicle Driver?</h3>
                    <p>Connect with farmers who need your services. Grow your business by offering reliable transportation solutions.</p>
                    <a href="driver_login.php" class="btn btn-secondary">Driver Login</a>
                    <a href="driver_register.php" class="btn btn-primary">Driver Register</a>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="about-section" id="about">
            <div class="about-container">
                <div class="about-image">
                    <img src="https://i.pinimg.com/1200x/43/3b/99/433b99d1a55d9e92f0ece2750250a7d7.jpg" alt="Farmer in field">
                </div>
                <div class="about-content">
                    <h2>About AgroConnect</h2>
                    <p>AgroConnect is a revolutionary platform designed to empower farmers by eliminating middlemen and providing direct access to essential agricultural services.</p>
                    <p>Our mission is to bridge the gap between farmers and markets by providing real-time price information, weather forecasts, and efficient transportation solutions.</p>
                    
                    <div class="about-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Market Prices</h4>
                                <p>Access real-time crop prices from multiple markets to make informed selling decisions.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Direct Transport</h4>
                                <p>Connect directly with verified drivers to transport your produce without intermediaries.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-cloud-sun"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Weather Forecasts</h4>
                                <p>Get accurate weather predictions to plan your farming activities effectively.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission Section -->
        <section class="mission-section animate__animated animate__fadeIn">
            <h2>Our Mission</h2>
            <p>AgroConnect bridges the gap between farmers and markets by providing real-time price information and efficient transportation solutions. We're committed to helping farmers maximize their profits while minimizing logistical challenges in agricultural trade. Our platform eliminates the need for middlemen, allowing farmers to connect directly with transport providers and access market data that helps them get the best prices for their produce.</p>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="feature-card animate__animated animate__fadeInLeft">
                <div class="feature-icon-main"><i class="fas fa-chart-pie"></i></div>
                <h3>Real-time Market Data</h3>
                <p>Access up-to-date prices from multiple markets to make informed selling decisions.</p>
            </div>
            
            <div class="feature-card animate__animated animate__fadeInUp">
                <div class="feature-icon-main"><i class="fas fa-truck-moving"></i></div>
                <h3>Reliable Transport</h3>
                <p>Find verified drivers and vehicles to transport your produce safely and affordably.</p>
            </div>
            
            <div class="feature-card animate__animated animate__fadeInRight">
                <div class="feature-icon-main"><i class="fas fa-cloud-sun-rain"></i></div>
                <h3>Weather Updates</h3>
                <p>Get accurate weather forecasts to plan your farming and transportation activities.</p>
            </div>
        </section>

        <!-- Weather Section -->
        <section class="weather-section" id="weather">
            <div class="weather-container">
                <h2>Weather Information</h2>
                <p>Get accurate weather forecasts to plan your farming activities and transportation schedules effectively.</p>
                
                <div class="weather-cards">
                    <div class="weather-card">
                        <div class="weather-icon"><i class="fas fa-sun"></i></div>
                        <h3>Current Weather</h3>
                        <p>Get real-time weather updates for your location to make immediate farming decisions.</p>
                    </div>
                    
                    <div class="weather-card">
                        <div class="weather-icon"><i class="fas fa-cloud-sun"></i></div>
                        <h3>3-Day Forecast</h3>
                        <p>Plan your week with accurate 3-day weather predictions for your area.</p>
                    </div>
                    
                    <div class="weather-card">
                        <div class="weather-icon"><i class="fas fa-umbrella"></i></div>
                        <h3>Rain Alerts</h3>
                        <p>Receive notifications about upcoming rain to protect your crops and schedule transport.</p>
                    </div>
                    
                </div><br><br>
                <a href="weather.html"><button class="btn btn-secondary">CHECK WEATHER</button></a>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section" id="contact">
            <div class="contact-container">
                <h2>Contact Us</h2>
                
                <div class="contact-content">
                    <div class="contact-info">
                        <h3>Get in Touch</h3>
                        <p>Have questions or need assistance? Our team is here to help you with any inquiries about our services.</p>
                        
                        <div class="contact-details">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <h4>Address</h4>
                                    <p>123 Farm Road, Agricultural Zone, City</p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-text">
                                    <h4>Phone</h4>
                                    <p><a href="tel:+1234567890">+1 (234) 567-890</a></p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-text">
                                    <h4>Email</h4>
                                    <p><a href="mailto:info@agroconnect.com">info@agroconnect.com</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-form">
                        <form id="contactForm">
                            <div class="form-group">
                                <label for="name">Your Name</label>
                                <input type="text" id="name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" class="form-control" required></textarea>
                            </div>
                            
                            <button type="submit" class="submit-btn">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Section -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h3>AgroConnect</h3>
                <p>Empowering farmers with real-time market prices, weather information, and seamless transportation solutions.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#weather">Weather</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="user_login.php">User Login</a></li>
                    <li><a href="driver_login.php">Driver Login</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Services</h3>
                <ul class="footer-links">
                    <li><a href="#">Market Prices</a></li>
                    <li><a href="#">Transport Booking</a></li>
                    <li><a href="#">Weather Forecast</a></li>
                    <li><a href="#">Crop Advisory</a></li>
                </ul>
            </div>
            <script src="index.js"></script>
            <div class="footer-col">
                <h3>Contact Info</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> 123 Farm Road, City</a></li>
                    <li><a href="tel:+1234567890"><i class="fas fa-phone-alt"></i> +1 (234) 567-890</a></li>
                    <li><a href="mailto:info@agroconnect.com"><i class="fas fa-envelope"></i> info@agroconnect.com</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2023 AgroConnect. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>