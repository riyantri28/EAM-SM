
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EAM-DPTM UNY</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4338ca;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .header {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--bg-tertiary);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .logo i {
            font-size: 1.75rem;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-btn {
            background: var(--primary-color);
            color: var(--text-primary);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-radius: var(--border-radius);
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--bg-tertiary);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, var(--text-primary), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .description {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
            max-width: 800px;
        }

        .workshop-image {
            width: 100%;
            border-radius: var(--border-radius);
            margin: 2rem 0;
            box-shadow: var(--shadow-md);
        }

        .footer {
            background: var(--bg-secondary);
            padding: 3rem 2rem;
            border-top: 1px solid var(--bg-tertiary);
        }

        .footer-brand {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .social-icon {
            color: var(--text-secondary);
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .social-icon:hover {
            color: var(--primary-color);
        }

        .footer-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        .footer-column h3 {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-column a {
            color: var(--text-secondary);
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .footer-column a:hover {
            color: var(--primary-color);
            transform: translateX(4px);
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .main-content {
                padding: 2rem 1rem;
            }

            .title {
                font-size: 2rem;
            }

            .hero-section {
                padding: 2rem 1rem;
            }
        }
                .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .login-modal {
            background: linear-gradient(145deg, var(--bg-secondary), var(--bg-tertiary));
            border-radius: var(--border-radius);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            position: relative;
            transform: translateY(-50px);
            opacity: 0;
            transition: transform 0.4s ease, opacity 0.4s ease;
            border: 1px solid var(--bg-tertiary);
            overflow: hidden;
        }

        .modal-overlay.active .login-modal {
            transform: translateY(0);
            opacity: 1;
        }

        .login-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .login-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--text-primary);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--bg-tertiary);
            border-radius: var(--border-radius);
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon .form-input {
            padding-left: 2.75rem;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .login-btn-modal {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: var(--text-primary);
            border: none;
            width: 100%;
            padding: 0.875rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .login-btn-modal:hover {
            background: linear-gradient(45deg, var(--primary-dark), var(--secondary-color));
            transform: translateY(-2px);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .login-spinner {
            display: none;
        }

        .login-spinner.active {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 3px solid var(--danger-color);
            color: var(--danger-color);
            padding: 0.75rem;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: none;
        }

        .login-error.active {
            display: block;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-cogs"></i>
            <span>EAM-DPTM UNY</span>
        </div>
        <button class="login-btn" onclick="openLoginModal()">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login</span>
        </button>
    </header>
    
    <main class="main-content">
        <div class="hero-section">
            <h1 class="title">Enterprise Asset Management System</h1>
            <p class="description">
                Sistem Enterprise Asset Management (EAM) ini dirancang untuk membantu bengkel pemesinan DPTM dalam memantau kondisi aset secara real-time, menjadwalkan perawatan rutin maupun perbaikan, serta mengelola data inventaris seperti lokasi, status, dan riwayat aset.
            </p>
            <button class="login-btn" onclick="openLoginModal()">
                <i class="fas fa-sign-in-alt"></i>
                <span>Akses Sistem</span>
            </button>
        </div>

        <img src="assets/img2.png" alt="Bengkel Pemesinan DPTM" class="workshop-image">
        
        <div class="hero-section">
            <h2 class="title">Bengkel Pemesinan DPTM</h2>
            <p class="description">
                Bengkel pemesinan merupakan unit kerja yang berfokus pada proses manufaktur berbasis pemotongan material, seperti pembubutan, frais, bor, dan gerinda, dengan menggunakan mesin-mesin presisi. Bengkel ini melayani berbagai kebutuhan pembuatan dan perbaikan komponen teknik, baik untuk keperluan produksi, riset, maupun perawatan alat industri.
            </p>
        </div>
    </main>
    
    <footer class="footer">
        <div class="footer-brand">
            <i class="fas fa-cogs"></i>
            EAM-DPTM
        </div>
        
        <div class="footer-links">
            <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
        </div>
        
        <div class="footer-nav">
            <div class="footer-column">
                <h3>Universitas</h3>
                <a href="#"><i class="fas fa-chevron-right"></i> FT UNY</a>
                <a href="#"><i class="fas fa-chevron-right"></i> DPTM</a>
            </div>
            
            <div class="footer-column">
                <h3>Program Studi</h3>
                <a href="#"><i class="fas fa-chevron-right"></i> Manufaktur</a>
                <a href="#"><i class="fas fa-chevron-right"></i> Pendidikan Teknik Mesin</a>
                <a href="#"><i class="fas fa-chevron-right"></i> S2 Teknik Mesin</a>
            </div>
        </div>
    </footer>
    
    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal">
        <div class="login-modal">
            <div class="login-header">
                <h2 class="login-title">
                    <i class="fas fa-user-lock"></i>
                    Login Sistem
                </h2>
                <button class="close-modal" onclick="closeLoginModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="login-error" id="loginError">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorMessage">Username atau password salah</span>
            </div>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
                    </div>
                </div>
                
                <button type="submit" class="login-btn-modal">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                    <i class="fas fa-spinner fa-spin login-spinner" id="loginSpinner"></i>
                </button>
            </form>
            
            <div class="login-footer">
                Enterprise Asset Management System DPTM UNY
            </div>
        </div>
    </div>

    <script>
        // Modal Login Functions
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
            document.getElementById('username').focus();
            // Prevent scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }
        
        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
            document.getElementById('loginForm').reset();
            document.getElementById('loginError').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close modal when clicking outside
        document.getElementById('loginModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeLoginModal();
            }
        });
        
        // Close modal on escape key press
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && document.getElementById('loginModal').classList.contains('active')) {
                closeLoginModal();
            }
        });
        
        // Handle Login Submission
        function handleLogin(event) {
            event.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const spinner = document.getElementById('loginSpinner');
            const errorBox = document.getElementById('loginError');
            const errorMessage = document.getElementById('errorMessage');
            
            spinner.classList.add('active');
            errorBox.classList.remove('active');
            
            // Simulate API request
            fetch('login_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                spinner.classList.remove('active');
                
                if (data.success) {
                    // Redirect to dashboard on successful login
                    window.location.href = data.redirect || 'dashboard.php';
                } else {
                    // Show error message
                    errorMessage.textContent = data.message || 'Username atau password salah';
                    errorBox.classList.add('active');
                }
            })
            .catch(error => {
                spinner.classList.remove('active');
                errorMessage.textContent = 'Terjadi kesalahan saat login. Silakan coba lagi.';
                errorBox.classList.add('active');
                console.error('Login error:', error);
            });
        }
    </script>
</body>
</html>
