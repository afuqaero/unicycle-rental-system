<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniCycle - Rent a Bike, Ride with Ease</title>
    <meta name="description"
        content="UniCycle makes campus mobility simpler, faster, and more accessible. Rent a bike with ease at UTHM.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="homepage.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="homepage-body">

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <div class="logo-icon">U</div>
                <span>UniCycle</span>
            </a>

            <div class="nav-links">
                <a href="#home" class="nav-link active">Home</a>
                <a href="#why-us" class="nav-link">Why Us</a>
                <a href="#how-it-works" class="nav-link">How it Works</a>
            </div>

            <div class="nav-buttons">
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>

            <button class="mobile-menu-btn" id="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">
                    Rent a bike.<br>
                    <span class="text-blue">Ride with ease.</span>
                </h1>

                <p class="hero-subtitle">Your convenience, our priority.</p>

                <div class="hero-buttons">
                    <a href="available-bikes.php" class="btn-primary">Book a Ride</a>
                    <a href="login.php" class="btn-secondary">Login / Register</a>
                </div>

                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Active Riders</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Bikes Available</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Service</span>
                    </div>
                </div>
            </div>

            <div class="hero-image">
                <div class="image-frame">
                    <img src="assets/hero-riders.png" alt="Students riding bikes on campus">
                </div>
            </div>
        </div>
    </section>

    <!-- Why Us Section -->
    <section class="why-us" id="why-us">
        <div class="why-us-container">
            <div class="why-us-card">
                <div class="why-us-image">
                    <img src="assets/why-us-image.png" alt="Students cycling on campus">
                </div>

                <div class="why-us-content">
                    <h2>Why Us?</h2>

                    <p>UniCycle is built to make campus mobility simpler, faster, and more accessible.</p>

                    <p>Powered by a passionate team of seven UTHM students, we're here to solve a real problem students
                        face daily — the need for reliable transportation across campus.</p>

                    <p>We believe convenience shouldn't be complicated, and every ride should feel effortless.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="how-container">
            <div class="how-header">
                <h2>How it works</h2>
                <p>Renting a luxury bike has never been easier. Our streamlined process makes it simple for you to book
                    and confirm your vehicle of choice online.</p>
            </div>

            <div class="how-card">
                <div class="how-steps">
                    <div class="step">
                        <div class="step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                        </div>
                        <div class="step-content">
                            <h3>Browse and select</h3>
                            <p>Choose from our wide range of premium bikes, select the pickup & return dates and
                                locations that suit you best.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="step-content">
                            <h3>Book and confirm</h3>
                            <p>Book your desired bike with just a few clicks and receive an instant confirmation via
                                email or SMS.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="m2 17 10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <div class="step-content">
                            <h3>Enjoy your ride</h3>
                            <p>Pick up your bike at the designated location and enjoy your premium riding experience
                                with our top-quality service.</p>
                        </div>
                    </div>
                </div>

                <div class="how-image">
                    <img src="assets/bike-closeup.png" alt="Premium bike close-up">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-logo">
                <div class="logo-icon">U</div>
                <span>UniCycle</span>
            </div>
            <p>&copy; 2026 UniCycle.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Active nav link on scroll
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const navLinksContainer = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenuBtn.classList.toggle('active');
            navLinksContainer.classList.toggle('active');
        });
    </script>
</body>

</html>