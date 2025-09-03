<?php // If you plan to use PHP, keep this tag. If not, rename the file to index.html. ?>
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
        /* Floating Contact Button Styles */
        /* Contact Section Styles */
.contact-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.contact-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.contact-container h2 {
    text-align: center;
    margin-bottom: 40px;
    color: #2c3e50;
    font-size: 2.5rem;
}

.contact-content {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
}

.contact-info {
    flex: 1;
    min-width: 300px;
}

.contact-info h3 {
    color: #28a745;
    margin-bottom: 20px;
    font-size: 1.8rem;
}

.contact-info p {
    margin-bottom: 30px;
    line-height: 1.6;
    color: #555;
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.contact-text h4 {
    margin-bottom: 5px;
    color: #2c3e50;
}

.contact-text a {
    color: #555;
    text-decoration: none;
    transition: color 0.3s;
}

.contact-text a:hover {
    color: #28a745;
}

.contact-form {
    flex: 1;
    min-width: 300px;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #28a745;
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.submit-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s;
}

.submit-btn:hover {
    background: #218838;
}

/* Responsive styles for contact section */
@media (max-width: 768px) {
    .contact-content {
        flex-direction: column;
    }
    
    .contact-info, .contact-form {
        width: 100%;
    }
    
    .contact-container h2 {
        font-size: 2rem;
    }
}
        .contact-float-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            background: #28a745;
            color: #fff;
            border: none;
            outline: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: background-color 0.2s;
        }
        .contact-float-btn:hover {
            background: #218838;
        }
        /* Modal Styles */
        .contact-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .contact-modal-content {
            background: #fff;
            margin: auto;
            padding: 30px 20px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
        }
        @media (max-width: 600px) {
            .contact-modal-content {
                padding: 15px 5px;
            }
            .contact-float-btn {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
                bottom: 16px;
                right: 16px;
            }
        }
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
            
            <ul class="nav-links" id="navLinks" style="list-style: none; padding-left: 0; margin: 0;">
                <li><a href="#welcome-container" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="#about"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="#weather"><i class="fas fa-cloud-sun"></i> Weather</a></li>
            </ul>
           
           
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="welcome-container" id="welcome-container">
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
        <section class="mission-section parallax-mission">
            <div class="mission-content-wrapper">
                <div class="mission-heading">
                <h2>
                    <span class="our-static">Our</span><br>
                    <span id="typedMission"></span>
                </h2>
            </div>
            <div class="mission-text">
                <p>
                AgroConnect bridges the gap between farmers and markets by providing real-time price information and efficient transportation solutions.<br><br>
                We're committed to helping farmers maximize their profits while minimizing logistical challenges in agricultural trade.<br><br>
                Our platform eliminates the need for middlemen, allowing farmers to connect directly with transport providers and access market data that helps them get the best prices for their produce.
                </p>
            </div>
            
            </div>
        </section>
        <style>
        .parallax-mission {
            background-image: url('https://media.istockphoto.com/id/479554988/photo/senior-farmer-holding-a-green-young-plant.jpg?s=612x612&w=0&k=20&c=vkCBesmud2gHAw9Q6-PmUFBzs81WV256X1cE12MRkyA=');
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            position: relative;
            min-height: 400px;
            display: flex;
            align-items: center;
            z-index: 1;
        }
        .parallax-mission::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.90);
            z-index: 2;
        }
        .mission-content-wrapper {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 90%;
            gap: 30px;
        }
        .mission-text {
            flex: 1.2;
            font-size: 1.2rem;
            color: #222;
            background: rgba(255,255,255,0.95);
            padding: 32px 28px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(40,167,69,0.08);
            font-family: 'Roboto', 'Poppins', sans-serif;
            line-height: 1.7;
        }
        .mission-heading {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .mission-heading h2 {
            font-size: 5rem;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            /* border removed */
            padding: 32px 28px;
            border-radius: 18px;
            background: rgba(40,167,69,0.10);
            text-shadow: 0 4px 16px #28a745, 0 2px 8px #218838;
            box-shadow: 0 8px 32px rgba(40,167,69,0.12);
            letter-spacing: 2px;
            text-align: right;
            min-width: 420px;
            white-space: nowrap;
            overflow: hidden;
            border: none;
        }
        .mission-heading .our-static {
            display: block;
            font-size: 1em;
            color: #fff;
            text-shadow: 0 4px 16px #28a745, 0 2px 8px #218838;
        }
        #typedMission {
            display: block;
            margin-top: 0.2em;
        }
        @media (max-width: 900px) {
            .mission-content-wrapper {
            flex-direction: column;
            gap: 24px;
            align-items: stretch;
            }
            .mission-heading {
            justify-content: center;
            }
            .mission-heading h2 {
            font-size: 2.8rem;
            min-width: 0;
            width: 100%;
            text-align: center;
            padding: 10px 10px;
            }
        }
        </style>
        <script>
        // Typewriter effect for "Mission"
        window.addEventListener('DOMContentLoaded', function() {
            const text = "Mission";
            const target = document.getElementById('typedMission');
            let i = 0;
            function typeWriter() {
                if (i < text.length) {
                    target.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 120);
                }
            }
            target.innerHTML = "";
            typeWriter();
        });

        // Adjust gap and alignment between "Our" and "Mission"
        document.addEventListener('DOMContentLoaded', function() {
            const ourStatic = document.querySelector('.mission-heading .our-static');
            const typedMission = document.getElementById('typedMission');
            if (ourStatic && typedMission) {
                // Reduce margin between "Our" and "Mission"
                typedMission.style.marginTop = "0.05em";
                // Center align both lines
                ourStatic.style.textAlign = "center";
                typedMission.style.textAlign = "center";
                // Remove extra spacing if any
                typedMission.style.display = "block";
            }
        });
        </script>
         

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
                        <h3>5-Day Forecast</h3>
                        <p>Plan your week with accurate 5-day weather predictions for your area.</p>
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
        <!-- Contact Button (Floating) -->
        <button id="contactBtn" class="contact-float-btn" aria-label="Contact Us">
            <i class="fas fa-envelope"></i>
        </button>

        <!-- Contact Modal -->
        <div id="contactModal" class="contact-modal">
            <div class="contact-modal-content">
            <span class="close-modal" id="closeContactModal">&times;</span>
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
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Phone</h4>
                            <p><a href="tel:+1234567890">+91 9121350982</a></p>
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
                    <form id="contactFormModal">
                        <div class="form-group">
                        <label for="modalName">Your Name</label>
                        <input type="text" id="modalName" class="form-control" required>
                        </div>
                        <div class="form-group">
                        <label for="modalEmail">Email Address</label>
                        <input type="email" id="modalEmail" class="form-control" required>
                        </div>
                        <div class="form-group">
                        <label for="modalSubject">Subject</label>
                        <input type="text" id="modalSubject" class="form-control" required>
                        </div>
                        <div class="form-group">
                        <label for="modalMessage">Message</label>
                        <textarea id="modalMessage" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                    </div>
                </div>
                </div>
            </section>
            </div>
        </div>

       

        <script>
        // Modal open/close logic
        document.addEventListener('DOMContentLoaded', function() {
            var contactBtn = document.getElementById('contactBtn');
            var contactModal = document.getElementById('contactModal');
            var closeModal = document.getElementById('closeContactModal');

            contactBtn.onclick = function() {
            contactModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            };
            closeModal.onclick = function() {
            contactModal.style.display = 'none';
            document.body.style.overflow = '';
            };
            window.onclick = function(event) {
            if (event.target === contactModal) {
                contactModal.style.display = 'none';
                document.body.style.overflow = '';
            }
            };

            // Save contact form data to server-side (not public)
            document.getElementById('contactFormModal').onsubmit = function(e) {
                e.preventDefault();

                var name = document.getElementById('modalName').value;
                var email = document.getElementById('modalEmail').value;
                var subject = document.getElementById('modalSubject').value;
                var message = document.getElementById('modalMessage').value;

                var formData = new FormData();
                formData.append('name', name);
                formData.append('email', email);
                formData.append('subject', subject);
                formData.append('message', message);

                fetch('save_contact.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Thank you for contacting us! Your message has been saved.');
                        contactModal.style.display = 'none';
                        document.body.style.overflow = '';
                        document.getElementById('contactFormModal').reset();
                    } else {
                        alert('There was an error saving your message. Please try again.');
                    }
                })
                .catch(() => {
                    alert('There was an error saving your message. Please try again.');
                });
            };
        });
        </script>
    </main>

    <!-- Footer Section -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h3>AgroConnect</h3>
                <p>Empowering farmers with real-time market prices, weather information, and seamless transportation solutions.</p>
            <!-- <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>-->
            </div>
            
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="welcome-container">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#weather">Weather</a></li>
                    <li><a href="#contactBtn">Contact</a></li>
                   
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Services</h3>
                <ul class="footer-links">
                    <li>Market Prices</a>
                    <li>Transport Booking</a>
                    <li>Weather Forecast</a>
                    <li>Crop Advisory</a>
                </ul>
            </div>
            <script src="index.js"></script>
            <div class="footer-col">
                <h3>Contact Info</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> Kavali,Nellore,AP</a></li>
                    <li><a href="tel:+1234567890"><i class="fas fa-phone-alt"></i>+91 9121350982</a></li>
                    <li><a href="mailto:info@agroconnect.com"><i class="fas fa-envelope"></i> agroconnect@gmail.com</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2023 AgroConnect. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>