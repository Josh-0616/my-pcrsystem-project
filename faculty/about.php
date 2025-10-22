<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - PCR System | Philippine Countryville College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2c5282;
            --accent-color: #3182ce;
            --light-color: #ebf8ff;
            --dark-color: #1a202c;
            --success-color: #38a169;
            --info-color: #3182ce;
            --warning-color: #d69e2e;
            --danger-color: #e53e3e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(26, 54, 93, 0.9), rgba(44, 82, 130, 0.9)), url('../images/pcr.png');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-content p {
            font-size: 1.5rem;
            max-width: 800px;
            margin: 0 auto 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .hero-badge {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .hero-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Section Styling */
        .section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 50px;
            max-width: 700px;
        }

        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .feature-card.blue {
            border-top-color: var(--info-color);
        }

        .feature-card.green {
            border-top-color: var(--success-color);
        }

        .feature-card.orange {
            border-top-color: var(--warning-color);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background-color: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: var(--accent-color);
        }

        .feature-card.blue .feature-icon {
            color: var(--info-color);
        }

        .feature-card.green .feature-icon {
            color: var(--success-color);
        }

        .feature-card.orange .feature-icon {
            color: var(--warning-color);
        }

        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        /* Process Timeline */
        .process-timeline {
            position: relative;
            padding: 50px 0;
        }

        .process-timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            height: 100%;
            width: 4px;
            background: linear-gradient(to bottom, var(--accent-color), var(--info-color));
            border-radius: 4px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 50px;
        }

        .timeline-item:nth-child(odd) {
            text-align: right;
            padding-right: calc(50% + 50px);
        }

        .timeline-item:nth-child(even) {
            text-align: left;
            padding-left: calc(50% + 50px);
        }

        .timeline-dot {
            position: absolute;
            top: 0;
            width: 30px;
            height: 30px;
            background-color: white;
            border: 4px solid var(--accent-color);
            border-radius: 50%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
        }

        .timeline-content {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .timeline-content h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .timeline-number {
            position: absolute;
            top: -15px;
            width: 30px;
            height: 30px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .timeline-item:nth-child(odd) .timeline-number {
            right: -15px;
        }

        .timeline-item:nth-child(even) .timeline-number {
            left: -15px;
        }

        /* Importance Cards */
        .importance-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .importance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .importance-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .importance-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .importance-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        /* Developer Section */
        .developer-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .developer-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .developer-section::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .developer-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .developer-content h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background-color: white;
            border-radius: 2px;
        }

        .developer-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Footer */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 50px 0 30px;
        }

        .footer-content {
            text-align: center;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
        }

        .footer-text {
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .social-links a {
            color: white;
            font-size: 1.5rem;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-content h1 {
                font-size: 2.8rem;
            }

            .hero-content p {
                font-size: 1.2rem;
            }

            .section {
                padding: 60px 0;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .section {
                padding: 40px 0;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .timeline-item:nth-child(odd),
            .timeline-item:nth-child(even) {
                text-align: left;
                padding-left: calc(50% + 50px);
                padding-right: 0;
            }

            .process-timeline::before {
                left: 30px;
            }

            .timeline-dot {
                left: 30px;
            }

            .timeline-item:nth-child(odd) .timeline-number {
                right: auto;
                left: -15px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Web-Based Performance Commitment and Review for Regular Teaching Faculty and Administrative Staff of Philippine Countryville College, Inc.</h1>
                <p>A comprehensive digital solution for faculty performance evaluation at Philippine Countryville College, Inc.</p>
                <a href="#overview" class="hero-badge">Learn More</a>
            </div>
        </div>
    </section>

    <!-- System Overview -->
    <section id="overview" class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">System Overview</h2>
                <p class="section-subtitle">Transforming performance evaluation through digital innovation</p>
            </div>
            
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <p class="lead">The Performance Commitment and Review (PCR) System for Philippine Countryville College is a comprehensive web-based application designed to digitalize and streamline the faculty performance evaluation process. This system serves two primary user groups: faculty members who submit their performance commitments and reviews, and administrators who manage, review, and approve these submissions.</p>
                    <p>The PCR System replaces traditional paper-based evaluation methods with an efficient digital platform that automates the entire workflow from form submission to final approval, enhancing transparency, efficiency, and accuracy in the performance evaluation process.</p>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <img src="../images/pcr.png" alt="PCR System Interface" class="img-fluid rounded-4 shadow">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Objectives -->
    <section class="section bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">System Objectives</h2>
                <p class="section-subtitle">Our goals in developing the PCR System</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card blue">
                        <div class="feature-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h3>Digitalization</h3>
                        <p>Transform manual paper-based evaluation processes into a fully digital system, reducing paperwork and physical storage requirements.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card green">
                        <div class="feature-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h3>Efficiency</h3>
                        <p>Enhance evaluation process efficiency by automating workflows, reducing processing time, and eliminating manual data entry errors.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card orange">
                        <div class="feature-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Transparency</h3>
                        <p>Provide a transparent evaluation process where faculty can track submission status and receive timely feedback.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card blue">
                        <div class="feature-icon">
                            <i class="fas fa-universal-access"></i>
                        </div>
                        <h3>Accessibility</h3>
                        <p>Create a user-friendly platform accessible from any device with internet connectivity, enabling tasks from anywhere at any time.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card green">
                        <div class="feature-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3>Data Management</h3>
                        <p>Establish a centralized database for storing and managing performance evaluation data, facilitating easy retrieval and analysis.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Features -->
    <section class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">System Features</h2>
                <p class="section-subtitle">Comprehensive tools for seamless performance evaluation</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure Login System</h3>
                        <p>Role-based authentication ensuring only authorized users can access the system with appropriate permissions.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3>Dynamic PCR Form</h3>
                        <p>Interactive digital form with auto-save functionality for inputting performance commitments and accomplishments.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Submission & Review</h3>
                        <p>Streamlined workflow for submission and administrative review with approval or return functionality.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Notification System</h3>
                        <p>Real-time alerts for deadlines, status changes, and important system announcements.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <h3>PDF Generation</h3>
                        <p>Automatic generation of official PDF documents for approved PCR forms.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3>Analytics Dashboard</h3>
                        <p>Comprehensive dashboards with statistics, reports, and performance data visualization.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Flow -->
    <section class="section bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Process Flow</h2>
                <p class="section-subtitle">How the PCR System works from submission to approval</p>
            </div>
            
            <div class="process-timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-number">1</div>
                        <h4>Faculty Login</h4>
                        <p>Faculty members access the system using their unique credentials to begin the evaluation process.</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-number">2</div>
                        <h4>Form Completion</h4>
                        <p>Faculty fill out the digital PCR form with performance targets, accomplishments, challenges, and future commitments.</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-number">3</div>
                        <h4>Submission</h4>
                        <p>Completed forms are submitted for administrative review after system validation of required fields.</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-number">4</div>
                        <h4>Administrative Review</h4>
                        <p>Administrators review submissions, assess content, and provide feedback when necessary.</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-number">5</div>
                        <h4>Decision Making</h4>
                        <p>Administrators approve submissions meeting standards or return them for revision with specific comments.</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-number">6</div>
                        <h4>PDF Generation</h4>
                        <p>Approved submissions automatically generate official PDF documents for record-keeping purposes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Importance -->
    <section class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">System Importance</h2>
                <p class="section-subtitle">Why the PCR System matters for Philippine Countryville College, Inc.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="importance-card">
                        <div class="importance-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Operational Efficiency</h3>
                        <p>Reduces time and effort required by faculty and staff, allowing focus on core educational activities through process automation.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="importance-card">
                        <div class="importance-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h3>Data Accuracy</h3>
                        <p>Minimizes human errors in data entry and ensures evaluation data integrity through proper validation and security measures.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="importance-card">
                        <div class="importance-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Environmental Impact</h3>
                        <p>Contributes to sustainability initiatives by eliminating paper forms and reducing physical storage requirements.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="importance-card">
                        <div class="importance-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Historical Analysis</h3>
                        <p>Maintains comprehensive records for trend analysis and informed decision-making for institutional improvement.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="importance-card">
                        <div class="importance-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3>Standardization</h3>
                        <p>Ensures all evaluations follow a standardized format and process, promoting fairness and consistency across departments.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Developer Information -->
    <section class="developer-section">
        <div class="container">
            <div class="developer-content">
                <h2>Developer Information</h2>
                <p>The PCR System was developed as a capstone project by a student of Philippine Countryville College, Inc, demonstrating the practical application of knowledge and skills acquired through the Bachelor of Science in Information Technology (BSIT) program.</p>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="developer-card">
                            <p>This project showcases the student's ability to design, develop, and implement a comprehensive web-based solution that addresses a real-world institutional need. The development process involved extensive research, system analysis, database design, programming, testing, and implementation, following industry best practices and academic standards.</p>
                            <p>Through this capstone project, the developer has demonstrated proficiency in web development technologies, database management, user interface design, and system security, while also gaining valuable insights into the operational requirements of educational institutions.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="developer-card">
                            <h4>Technologies Used</h4>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> HTML5, CSS3, JavaScript</li>
                                <li><i class="fas fa-check text-success"></i> PHP 7.4+</li>
                                <li><i class="fas fa-check text-success"></i> MySQL Database</li>
                                <li><i class="fas fa-check text-success"></i> Bootstrap 5</li>
                                <li><i class="fas fa-check text-success"></i> Font Awesome Icons</li>
                                <li><i class="fas fa-check text-success"></i> Chart.js for Analytics</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    Web-Based Performance Commitment and Review for Regular Teaching Faculty and Administrative Staff of Philippine Countryville College, Inc.
                </div>
                <p class="footer-text">© 2025 Philippine Countryville College, Inc. All rights reserved.</p>
                <p class="footer-text">Bachelor of Science in Information Technology - Capstone Project</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/profile.php?id=61577314349831"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-x"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>