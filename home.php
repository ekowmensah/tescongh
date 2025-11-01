<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TESCON Ghana - Tertiary Students Confederacy of NPP</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CoreUI Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }
        
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --primary-red: #dc2626;
            --secondary-red: #ef4444;
            --white: #ffffff;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1e3a8a 50%, var(--primary-red) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            animation: fadeInUp 1s ease;
        }
        
        .hero p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .hero-buttons {
            animation: fadeInUp 1s ease 0.4s both;
        }
        
        .hero-image-container {
            position: relative;
            animation: fadeInRight 1s ease 0.6s both;
        }
        
        .hero-image {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 5px solid rgba(255,255,255,0.2);
            transition: transform 0.3s ease;
        }
        
        .hero-image:hover {
            transform: scale(1.05);
        }
        
        .btn-hero {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            border: none;
            margin: 0.5rem;
        }
        
        .btn-hero-primary {
            background: var(--white);
            color: var(--primary-blue);
        }
        
        .btn-hero-primary:hover {
            background: var(--light-blue);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-hero-outline {
            background: transparent;
            color: var(--white);
            border: 3px solid var(--white);
        }
        
        .btn-hero-outline:hover {
            background: var(--white);
            color: var(--primary-blue);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        /* Features Section */
        .features {
            padding: 6rem 0;
            background: var(--white);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title h2 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        
        .section-title p {
            font-size: 1.2rem;
            color: #6b7280;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            height: 100%;
            border: 2px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(30, 64, 175, 0.15);
            border-color: var(--secondary-blue);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: #6b7280;
            line-height: 1.8;
        }
        
        /* Stats Section */
        .stats {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-red) 100%);
            padding: 5rem 0;
            color: white;
        }
        
        .stat-item {
            text-align: center;
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .stat-label {
            font-size: 1.2rem;
            opacity: 0.95;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* CTA Section */
        .cta {
            background: var(--light-blue);
            padding: 5rem 0;
        }
        
        .cta-content {
            text-align: center;
        }
        
        .cta h2 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
        }
        
        .cta p {
            font-size: 1.3rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #111827 0%, var(--primary-blue) 100%);
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .footer-section {
            flex: 1;
            min-width: 250px;
            margin-bottom: 2rem;
        }
        
        .footer-section h4 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--white);
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section ul li a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: var(--white);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.2rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 hero-content">
                    <h1>TESCON GHANA</h1>
                    <p>Tertiary Students Confederacy of the New Patriotic Party</p>
                    <p class="mb-4">Empowering student leaders. Building Ghana's future.</p>
                    <div class="hero-buttons">
                        <a href="login.php" class="btn btn-hero btn-hero-primary">Member Login</a>
                        <a href="register.php" class="btn btn-hero btn-hero-outline">Join TESCON</a>
                    </div>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <div class="hero-image-container">
                        <img src="assets/images/leadership/team.jpg" alt="TESCON Leadership Team" class="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>What We Offer</h2>
                <p>Comprehensive platform for TESCON members across Ghana</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="cil-people"></i>
                        </div>
                        <h3>Member Management</h3>
                        <p>Centralized database of all TESCON members across tertiary institutions in Ghana with comprehensive profiles and records.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="cil-calendar"></i>
                        </div>
                        <h3>Events & Activities</h3>
                        <p>Stay updated with upcoming events, meetings, and activities. Track attendance and participation seamlessly.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="cil-comment-square"></i>
                        </div>
                        <h3>Communication</h3>
                        <p>Instant SMS notifications and announcements to keep all members informed and connected.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="cil-dollar"></i>
                        </div>
                        <h3>Dues Management</h3>
                        <p>Easy tracking and payment of membership dues with integrated payment systems and receipts.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="cil-chart-line"></i>
                        </div>
                        <h3>Analytics & Reports</h3>
                        <p>Comprehensive reports and analytics on membership, activities, and organizational performance.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="cil-star"></i>
                        </div>
                        <h3>Executive Portal</h3>
                        <p>Dedicated portal for campus executives to manage their chapters and coordinate activities.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Campuses</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">10K+</div>
                        <div class="stat-label">Members</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">16</div>
                        <div class="stat-label">Regions</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Events/Year</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of student leaders making a difference</p>
                <a href="login.php" class="btn btn-hero btn-hero-primary">Access Portal</a>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>TESCON Ghana</h4>
                    <p>Tertiary Students Confederacy of the New Patriotic Party</p>
                    <p>Building leaders. Shaping Ghana's future.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="login.php">Member Login</a></li>
                        <li><a href="register.php">Join TESCON</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="#">Constitution</a></li>
                        <li><a href="#">Guidelines</a></li>
                        <li><a href="#">Events</a></li>
                        <li><a href="#">News</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <ul>
                        <li>Email: info@tesconghana.org</li>
                        <li>Phone: +233 XX XXX XXXX</li>
                        <li>Address: Accra, Ghana</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> TESCON Ghana. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
