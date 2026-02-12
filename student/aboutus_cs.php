<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CCC</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #001f3f;
            --secondary-color: #0074cc;
            --accent-color: #ffcc00;
            --text-dark: #001f3f;
            --text-light: #666666;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 31, 63, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 31, 63, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #001f3f 0%, #0074cc 50%, #004080 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header-section {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease;
        }

        .main-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--bg-white);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
        }

        .content-card {
            background: var(--bg-white);
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .officials-section {
            padding: 3rem;
            text-align: center;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .officials-image {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin: 2rem 0;
            transition: transform 0.3s ease;
        }

        .officials-image:hover {
            transform: scale(1.02);
        }

        .info-section {
            padding: 3rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .info-card {
            background: var(--bg-white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .info-icon {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .info-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .info-content {
            color: var(--text-light);
            line-height: 1.6;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }

        .contact-item i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        .location-text {
            font-style: italic;
            color: var(--text-dark);
            font-weight: 500;
        }

        .footer {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 2rem;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        @media (max-width: 768px) {
            .main-title {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .officials-section,
            .info-section {
                padding: 2rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="about-container">
        <!-- Back Button -->
        <div class="mb-4">
            <a href="cs_studash.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Header Section -->
        <div class="header-section">
            <h1 class="main-title">About Us</h1>
            <p class="subtitle">Welcome To City College Of Calamba</p>
        </div>

        <!-- College Officials Section -->
        <div class="content-card">
            <div class="officials-section">
                <h2 class="section-title">College Officials</h2>
                <img src="co_final.png" alt="College Officials" class="officials-image">
                <p class="text-muted mt-3">SOURCE: https://www.ccc.edu.ph/</p>
            </div>
        </div>

        <!-- Information Section -->
        <div class="content-card">
            <div class="info-section">
                <h2 class="section-title">Get in Touch</h2>
                <div class="info-grid">
                    <!-- Location Card -->
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h3 class="info-title">Location</h3>
                        <div class="info-content">
                            <p class="location-text">
                                Old Municipal Site, Burgos St,<br>
                                Brgy. VII, Poblacion,<br>
                                Calamba City, Laguna, Philippines
                            </p>
                        </div>
                    </div>

                    <!-- Contact Card -->
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <h3 class="info-title">Contact Numbers</h3>
                        <div class="info-content">
                            <div class="contact-item">
                                <i class="bi bi-telephone"></i>
                                <span>(049) 559 8900</span>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-telephone"></i>
                                <span>(02) 8 539 5170</span>
                            </div>
                        </div>
                    </div>

                    <!-- Email Card (Optional) -->
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <h3 class="info-title">Email</h3>
                        <div class="info-content">
                            <div class="contact-item">
                                <i class="bi bi-envelope"></i>
                                <span>ccc.edu.ph</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2025 City College Of Calamba. All rights reserved.</p>
           
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>