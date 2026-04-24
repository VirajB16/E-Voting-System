<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting System - College Elections</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/text-visibility-fix.css">
    <style>
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        
        .hero-content {
            max-width: 800px;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-out 0.2s both;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 1.2s ease-out 0.4s both;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
            animation: fadeIn 1.4s ease-out 0.6s both;
        }
        
        .feature-card {
            background: rgba(26, 31, 58, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 240, 255, 0.3);
            border-radius: var(--radius-xl);
            padding: 2rem;
            text-align: center;
            transition: all var(--transition-base);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
        }
        
        .feature-card:hover {
            background: rgba(26, 31, 58, 0.8);
            transform: translateY(-5px);
            border-color: var(--neon-cyan);
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.4);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.5rem;
        }
        
        .feature-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
            }
        }
        
        /* Fix Register button text visibility */
        .btn-outline.btn-lg {
            color: white !important;
        }
        
        .btn-outline.btn-lg:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
    <style>
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        
        .hero-content {
            max-width: 800px;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-out 0.2s both;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 1.2s ease-out 0.4s both;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
            animation: fadeIn 1.4s ease-out 0.6s both;
        }
        
        .feature-card {
            background: rgba(26, 31, 58, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 240, 255, 0.3);
            border-radius: var(--radius-xl);
            padding: 2rem;
            text-align: center;
            transition: all var(--transition-base);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
        }
        
        .feature-card:hover {
            background: rgba(26, 31, 58, 0.8);
            transform: translateY(-5px);
            border-color: var(--neon-cyan);
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.4);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.5rem;
        }
        
        .feature-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
            }
        }
        
        /* Fix Register button text visibility */
        .btn-outline.btn-lg {
            color: white !important;
        }
        
        .btn-outline.btn-lg:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-content">
            <h1 class="hero-title">🗳️ College E-Voting System</h1>
            <p class="hero-subtitle">
                Secure, Transparent, and Modern Online Voting Platform for Student Council Elections
            </p>
            
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary btn-lg" style="color: black !important;">
                    🔐 Login
                </a>
                <a href="register.php" class="btn btn-outline btn-lg" style="color: white; border-color: white;">
                    📝 Register
                </a>
                <a href="admin-login.php" class="btn btn-secondary btn-lg">
                    👨‍💼 Admin Login
                </a>
            </div>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3 class="feature-title">Secure Voting</h3>
                    <p class="feature-description">
                        OTP-based authentication ensures only authorized voters can participate
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">Real-Time Results</h3>
                    <p class="feature-description">
                        Live vote counting and instant result updates
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">✅</div>
                    <h3 class="feature-title">One Vote Per User</h3>
                    <p class="feature-description">
                        Advanced system prevents duplicate voting
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Transparent Process</h3>
                    <p class="feature-description">
                        Complete audit trail and voting analytics
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer" style="padding: 3rem 0; background: rgba(10, 14, 39, 0.9); border-top: 1px solid rgba(0, 240, 255, 0.1); width: 100%;">
        <div class="container" style="text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem;">🗳️ E-Voting System</div>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0;">© 2024 A.P. Shah Institute of Technology. All Rights Reserved.</p>
            <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-light);">Developed for secure and transparent campus elections</div>
        </div>
    </footer>
    
    <script src="js/main.js"></script>
</body>
</html>
