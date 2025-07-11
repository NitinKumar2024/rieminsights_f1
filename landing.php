<?php
require_once 'config.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect(SITE_URL . '/index.php');
}

// Set page title
$page_title = 'Welcome to RiemInsights';
?>

<?php
// Handle contact form submission
if ($_POST['action'] == 'contact') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    if ($name && $email && $message) {
        $to = "info@riemInsights.com";
        $subject = "New Contact Form Submission - RiemInsights";
        $body = "Name: $name\n";
        $body .= "Email: $email\n";
        $body .= "Company: $company\n";
        $body .= "Message:\n$message";
        
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        if (mail($to, $subject, $body, $headers)) {
            $success_message = "Thank you for your message! We'll get back to you soon.";
        } else {
            $error_message = "Sorry, there was an error sending your message. Please try again.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Handle Get Started redirect
if (isset($_GET['action']) && $_GET['action'] == 'get_started') {
    header("Location: ./auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiemInsights - AI-Powered Data Analytics for Everyone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #667eea;
        }

        .hero {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 2rem 0;
            padding: 4rem 2rem;
            text-align: center;
            color: white;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }

        .features {
            background: white;
            margin: 2rem 0;
            padding: 4rem 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .features h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 2rem;
            border-radius: 15px;
            color: white;
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .contact-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 2rem 0;
            padding: 4rem 2rem;
            border-radius: 20px;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        footer {
            background: rgba(0, 0, 0, 0.1);
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="#" class="logo">
                <img src="assets/images/logo.png" alt="RiemInsights Logo">
                RiemInsights
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="?action=get_started">Get Started for Free</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="hero">
            <h1>AI-Powered Data Analytics</h1>
            <p>Transform your spreadsheets into insights with natural language. No technical expertise required - just ask questions and get answers instantly.</p>
            <div class="cta-buttons">
                <a href="?action=get_started" class="btn btn-primary">Get Started for Free</a>
                <a href="#contact" class="btn btn-secondary">Contact Us</a>
            </div>
            <p style="margin-top: 1rem; opacity: 0.8; font-size: 0.9rem;">No credit card required</p>
        </section>

        <section id="features" class="features">
            <h2>Why Choose RiemInsights?</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>File Upload</h3>
                    <p>Upload CSV or Excel files instantly. Our system automatically cleans your data, removes blank rows, and detects data types for optimal analysis.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>AI Assistant</h3>
                    <p>Ask natural language questions like "Which product had the highest sales?" and get instant answers powered by advanced AI technology.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💎</div>
                    <h3>Token-Based Access</h3>
                    <p>Fair and transparent pricing with monthly token allowances. Pay only for what you use, making data analysis affordable for everyone.</p>
                </div>
            </div>
        </section>

        <section id="contact" class="contact-section">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #333;">Get in Touch</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="contact-form">
                <input type="hidden" name="action" value="contact">
                
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="company">Company</label>
                    <input type="text" id="company" name="company">
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" placeholder="Tell us about your data analysis needs..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
            </form>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 RiemInsights. All rights reserved. | Making data analysis accessible for everyone.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
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

        // Form validation
        document.querySelector('.contact-form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (!name || !email || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });
    </script>
</body>
</html>