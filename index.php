<?php
 $pageTitle = "About - PCR System | Philippine Countryville College, Inc.";
 $pageDescription = "Learn about the Web-Based Performance Commitment and Review System for PCC faculty and staff";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-teal: #f7c200ff;
            --primary-blue: #60a5fa;
            --dark-blue: #1e3a8a;
            --light-blue: #dbeafe;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        /* School Image Background */
        .school-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: url('https://z-cdn-media.chatglm.cn/files/027152b5-e70c-4262-b1e7-17c896bdbeca_1logo.jpeg?auth_key=1792361655-ef31624d542047699161fd4fb7e92839-0-7d13e3be3865636835fe647e52462253') center/cover no-repeat;
        }

        .school-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.85), rgba(59, 130, 246, 0.75));
            z-index: 1;
        }

        /* Technology Animation Canvas */
        #tech-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.15) 0%, rgba(96, 165, 250, 0) 70%);
            pointer-events: none;
            animation: float 20s infinite ease-in-out;
            z-index: 3;
        }

        .floating-element:nth-child(1) {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 5%;
            animation-duration: 25s;
        }

        .floating-element:nth-child(2) {
            width: 200px;
            height: 200px;
            top: 60%;
            right: 10%;
            animation-duration: 30s;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 250px;
            height: 250px;
            bottom: 10%;
            left: 15%;
            animation-duration: 35s;
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) scale(1);
            }
            25% {
                transform: translateY(-20px) translateX(10px) scale(1.05);
            }
            50% {
                transform: translateY(10px) translateX(-10px) scale(0.95);
            }
            75% {
                transform: translateY(-10px) translateX(5px) scale(1.02);
            }
        }

        /* Header Styles */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 15px 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        header.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .logo span {
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
        }

        header.scrolled .logo span {
            color: var(--dark-blue);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--white);
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        header.scrolled .nav-links a {
            color: var(--text-dark);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-teal);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 14px;
            display: inline-block;
        }

        .btn-outline {
            background: transparent;
            color: var(--white);
            border: 1px solid var(--white);
        }

        header.scrolled .btn-outline {
            color: var(--dark-blue);
            border-color: var(--dark-blue);
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--dark-blue);
        }

        .btn-primary {
            background: var(--primary-teal);
            color: var(--dark-blue);
        }

        .btn-primary:hover {
            background: #0339ffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(110, 231, 183, 0.4);
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            cursor: pointer;
        }

        .menu-toggle span {
            height: 3px;
            width: 100%;
            background: var(--white);
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        header.scrolled .menu-toggle span {
            background: var(--dark-blue);
        }

        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100vh;
            background: rgb(6 7 51 / 95%);
            backdrop-filter: blur(10px);
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            padding: 80px 20px 20px;
            transition: right 0.3s ease;
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-menu-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--dark-blue);
        }

        .mobile-menu-links {
            list-style: none;
            margin-bottom: 30px;
        }

        .mobile-menu-links li {
            margin-bottom: 15px;
        }

        .mobile-menu-links a {
            text-decoration: none;
            color: var(--dark-blue);
            font-weight: 500;
            font-size: 18px;
            display: block;
            transition: all 0.3s ease;
        }

        .mobile-menu-links a:hover {
            color: var(--primary-teal);
            padding-left: 10px;
        }

        .mobile-menu-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .mobile-menu-buttons .btn {
            text-align: center;
        }

        /* Hero Section */
        .hero-section {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 50px;
            text-align: center;
            color: var(--white);
            position: relative;
        }

        .hero-content {
            max-width: 900px;
            z-index: 4;
            position: relative;
        }

        .hero-content h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease;
        }

        .hero-content p {
            font-size: clamp(1rem, 2vw, 1.2rem);
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.8s ease 0.2s;
            animation-fill-mode: both;
        }

        .hero-badge {
            display: inline-block;
            background: var(--primary-teal);
            color: var(--dark-blue);
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: clamp(0.9rem, 1.5vw, 1.1rem);
            box-shadow: 0 4px 15px rgba(110, 231, 183, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            animation: fadeInUp 0.8s ease 0.4s;
            animation-fill-mode: both;
        }

        .hero-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(110, 231, 183, 0.6);
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

        /* Responsive Design */
        @media (max-width: 992px) {
            .nav-links, .nav-buttons {
                display: none;
            }

            .menu-toggle {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            nav {
                padding: 0 15px;
            }

            .logo img {
                height: 35px;
            }

            .logo span {
                font-size: 18px;
            }

            .hero-section {
                min-height: 60vh;
                padding: 80px 15px 40px;
            }
        }

        @media (max-width: 576px) {
            .logo span {
                display: none;
            }

            .hero-content h1 {
                font-size: clamp(1.8rem, 5vw, 2.5rem);
            }

            .hero-content p {
                font-size: 1rem;
            }

            .hero-badge {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                min-height: 50vh;
                padding: 70px 15px 30px;
            }

            .hero-content h1 {
                font-size: clamp(1.5rem, 5vw, 2rem);
            }

            .hero-content p {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }

            .hero-badge {
                padding: 8px 16px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 320px) {
            .logo img {
                height: 30px;
            }

            .hero-section {
                min-height: 40vh;
                padding: 60px 10px 20px;
            }

            .hero-content h1 {
                font-size: 1.3rem;
            }

            .hero-content p {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- School Image Background with Technology Animation -->
    <div class="school-background">
        <canvas id="tech-canvas"></canvas>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>

    <!-- Header -->
    <header id="header">
        <nav>
            <div class="logo">
                <img src="../images/pcc1.png" alt="PCC Logo">
                <span>Philippine Countryville College, Inc.</span>
            </div>

            <div class="nav-buttons">
                <a href="login.php" class="btn btn-outline">Sign In</a>
                <a href="register.php" class="btn btn-primary">Create Account</a>
            </div>
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </div>
        <ul class="mobile-menu-links">
            <li><a href="#">Home</a></li>
            <li><a href="#">About</a></li>
            <li><a href="#">Features</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
        <div class="mobile-menu-buttons">
            <a href="login.php" class="btn btn-outline">Sign In</a>
            <a href="register.php" class="btn btn-primary">Create Account</a>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Web-Based Performance Commitment and Review for Regular Teaching Faculty and Administrative Staff</h1>
            <p>A comprehensive digital solution for faculty performance evaluation at Philippine Countryville College, Inc.</p>
            <a href="LearnMore.php" class="hero-badge">Learn More</a>
        </div>
    </section>

  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Technology Animation - Circuit Board Effect
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('tech-canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size
            function resizeCanvas() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            
            // Circuit node class
            class CircuitNode {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 3 + 1;
                    this.speedX = (Math.random() - 0.5) * 0.5;
                    this.speedY = (Math.random() - 0.5) * 0.5;
                    this.pulseSize = 0;
                    this.pulseSpeed = Math.random() * 0.05 + 0.02;
                    this.pulseDirection = 1;
                }
                
                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    
                    // Pulse effect
                    this.pulseSize += this.pulseSpeed * this.pulseDirection;
                    if (this.pulseSize > 2 || this.pulseSize < 0) {
                        this.pulseDirection *= -1;
                    }
                    
                    // Bounce off edges
                    if (this.x < 0 || this.x > canvas.width) {
                        this.speedX = -this.speedX;
                    }
                    
                    if (this.y < 0 || this.y > canvas.height) {
                        this.speedY = -this.speedY;
                    }
                }
                
                draw() {
                    // Draw node
                    ctx.fillStyle = 'rgba(96, 165, 250, 0.8)';
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size + this.pulseSize, 0, Math.PI * 2);
                    ctx.fill();
                    
                    // Draw glow
                    const gradient = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, this.size + this.pulseSize + 5);
                    gradient.addColorStop(0, 'rgba(96, 165, 250, 0.3)');
                    gradient.addColorStop(1, 'rgba(96, 165, 250, 0)');
                    ctx.fillStyle = gradient;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size + this.pulseSize + 5, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
            
            // Create nodes
            const nodes = [];
            const nodeCount = 40;
            
            for (let i = 0; i < nodeCount; i++) {
                nodes.push(new CircuitNode());
            }
            
            // Draw connections between nodes
            function drawConnections() {
                for (let i = 0; i < nodes.length; i++) {
                    for (let j = i + 1; j < nodes.length; j++) {
                        const dx = nodes[i].x - nodes[j].x;
                        const dy = nodes[i].y - nodes[j].y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        
                        if (distance < 150) {
                            const opacity = 1 - distance / 150;
                            ctx.strokeStyle = `rgba(96, 165, 250, ${opacity * 0.4})`;
                            ctx.lineWidth = 1;
                            ctx.beginPath();
                            ctx.moveTo(nodes[i].x, nodes[i].y);
                            ctx.lineTo(nodes[j].x, nodes[j].y);
                            ctx.stroke();
                            
                            // Draw data packet animation
                            if (Math.random() < 0.005) {
                                drawDataPacket(nodes[i], nodes[j]);
                            }
                        }
                    }
                }
            }
            
            // Draw data packet animation
            function drawDataPacket(node1, node2) {
                const progress = Math.random();
                const x = node1.x + (node2.x - node1.x) * progress;
                const y = node1.y + (node2.y - node1.y) * progress;
                
                ctx.fillStyle = 'rgba(37, 99, 235, 0.8)';
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fill();
                
                // Draw packet glow
                const gradient = ctx.createRadialGradient(x, y, 0, x, y, 8);
                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.6)');
                gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(x, y, 8, 0, Math.PI * 2);
                ctx.fill();
            }
            
            // Animation loop
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Update and draw nodes
                nodes.forEach(node => {
                    node.update();
                    node.draw();
                });
                
                // Draw connections
                drawConnections();
                
                requestAnimationFrame(animate);
            }
            
            animate();
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
        });
        
        mobileMenuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
        });
        
        // Close mobile menu when clicking on a link
        const mobileMenuLinks = document.querySelectorAll('.mobile-menu-links a');
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
            });
        });
    </script>
</body>
</html>
