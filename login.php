<?php
session_start();
require 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Prevent timing attacks using hash comparison
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['last_login'] = time();
        
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        header('Location: index.php');
        exit;
    } else {
        // Generic error message to prevent user enumeration
        $error = "Invalid credentials. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Climate Assessment Platform</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
        }
        
        body {
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            max-width: 440px;
            width: 100%;
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .auth-header {
            background: var(--secondary-color);
            padding: 2rem;
            text-align: center;
        }
        
        .brand-title {
            color: white;
            font-weight: 600;
            font-size: 1.75rem;
            margin: 0;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(42, 157, 143, 0.25);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card mx-auto">
            <div class="auth-header">
                <h1 class="brand-title"><i class="fas fa-earth-americas me-2"></i>Climate Analytics</h1>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3 position-relative">
                        <label class="form-label text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" 
                                   placeholder="Enter your username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4 position-relative">
                        <label class="form-label text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" 
                                   class="form-control" placeholder="••••••••" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-lg btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label text-secondary" for="remember">
                                Remember me
                            </label>
                        </div>
                        <a href="#forgot-password" class="text-decoration-none text-secondary">
                            Forgot Password?
                        </a>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-secondary">Or continue with</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-outline-secondary">
                            <i class="fab fa-google"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary">
                            <i class="fab fa-microsoft"></i>
                        </a>
                        <a href="#" class="btn btn-outline-secondary">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Form validation
        (() => {
          'use strict'
          const forms = document.querySelectorAll('.needs-validation')
          Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
              if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
              }
              form.classList.add('was-validated')
            }, false)
          })
        })()
    </script>
</body>
</html>