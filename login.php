<?php 
session_start();
include('db/connection.php');
if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: dashboard/admin_dashboard.php");
    } elseif ($_SESSION['role'] == 'faculty') {
        header("Location: faculty/faculty_dashboard.php");
    } else {
        header("Location: dashboard/staff_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #007bff;
      --text-light: #ffffff;
      --overlay-dark: rgba(0, 0, 0, 0.35);
      --panel-light: rgba(255, 255, 255, 0.1);
      --input-light: rgba(255, 255, 255, 0.9);
      --text-dark: #000000;
      --icon-color: #444;
    }
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html {
      font-size: 16px;
    }
    body {
      font-family: 'Montserrat', sans-serif;
      background-image: url('assets/logo.jpeg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      position: relative;
      padding: 20px;
    }
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background: var(--overlay-dark);
      z-index: -1;
    }
    h2.title {
      font-size: clamp(1rem, 2.5vw, 1.375rem);
      margin: 20px 0;
      color: var(--text-light);
      text-align: center;
      text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.99);
      line-height: 1.4;
      max-width: 100%;
      padding: 0 15px;
    }
    .right-panel {
      background: var(--panel-light);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      padding: clamp(25px, 5vw, 40px);
      border-radius: 20px;
      width: 100%;
      max-width: 400px;
      color: var(--text-light);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
      margin-top: 20px;
    }
    .login-form {
      display: flex;
      flex-direction: column;
    }
    .login-form label {
      margin-top: 15px;
      margin-bottom: 5px;
      font-weight: 500;
      font-size: clamp(0.875rem, 1vw, 1rem);
    }
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrapper i {
      position: absolute;
      left: 12px;
      color: var(--icon-color);
      pointer-events: none;
      font-size: clamp(14px, 1.2vw, 16px);
    }
    .login-form input {
      padding: clamp(10px, 2vw, 12px) 38px;
      border: none;
      border-radius: 10px;
      font-size: clamp(0.875rem, 1.2vw, 1rem);
      background: var(--input-light);
      color: var(--text-dark);
      width: 100%;
      transition: all 0.3s ease;
    }
    .login-form input:focus {
      outline: none;
      box-shadow: 0 0 0 2px var(--primary-color);
    }
    .eye-icon {
      position: absolute;
      right: 12px;
      cursor: pointer;
      width: 22px;
      height: 22px;
      fill: var(--icon-color);
      opacity: 0.7;
      background: transparent;
      border: none;
      transition: opacity 0.3s ease;
    }
    .eye-icon:hover {
      opacity: 1;
    }
    .forgot {
      text-align: right;
      margin-top: 8px;
      font-size: clamp(0.75rem, 1vw, 0.8125rem);
    }
    .forgot a {
      color: var(--text-light);
      text-decoration: underline;
      transition: opacity 0.3s ease;
    }
    .forgot a:hover {
      opacity: 0.8;
    }
    .btn-primary {
      margin-top: 20px;
      padding: clamp(10px, 2vw, 12px);
      background: var(--primary-color);
      color: var(--text-light);
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: clamp(0.875rem, 1.2vw, 1rem);
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      background: #0056b3;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .btn-primary:active {
      transform: translateY(0);
    }
    .register-link {
      text-align: center;
      margin-top: 15px;
      font-size: clamp(0.75rem, 1vw, 0.8125rem);
    }
    .register-link a {
      color: var(--text-light);
      text-decoration: underline;
      transition: opacity 0.3s ease;
    }
    .register-link a:hover {
      opacity: 0.8;
    }
    .remember-me {
      display: flex;
      align-items: center;
      margin-top: 10px;
      font-size: clamp(0.75rem, 1vw, 0.8125rem);
    }
    .remember-me input {
      width: auto;
      margin-right: 8px;
      padding: 0;
      height: 16px;
      width: 16px;
    }
    .divider {
      display: flex;
      align-items: center;
      margin: 20px 0;
      color: var(--text-light);
    }
    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255, 255, 255, 0.3);
    }
    .divider span {
      padding: 0 10px;
      font-size: clamp(0.75rem, 1vw, 0.8125rem);
    }
    .google-login {
      margin-top: 15px;
      text-align: center;
    }
    .google-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      padding: 10px;
      background-color: #4285F4;
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 500;
      font-size: clamp(0.875rem, 1.2vw, 1rem);
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .google-btn:hover {
      background-color: #357ae8;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .google-btn i {
      margin-right: 10px;
      font-size: 1.2rem;
    }
    /* Media queries for different screen sizes */
    @media (max-width: 768px) {
      body {
        padding: 15px;
      }
      h2.title {
        margin: 15px 0;
        padding: 0 10px;
      }
      .right-panel {
        margin-top: 15px;
      }
    }
    @media (max-width: 480px) {
      html {
        font-size: 14px;
      }
      body {
        padding: 10px;
      }
      h2.title {
        margin: 10px 0;
        font-size: 0.9rem;
      }
      .right-panel {
        padding: 20px;
        margin-top: 10px;
      }
      .login-form label {
        margin-top: 12px;
      }
      .btn-primary {
        margin-top: 15px;
      }
    }
    @media (max-width: 320px) {
      html {
        font-size: 12px;
      }
      .right-panel {
        padding: 15px;
      }
      .login-form input {
        padding: 8px 38px;
      }
    }
  </style>
</head>
<body>
  <div class="overlay"></div>
  <h2 class="title">Office Performance for Commitment and Review for Philippine Countryville College,Inc</h2>
  <div class="right-panel">
    <form method="POST" action="login_check.php" class="login-form">
      <label for="email">Email</label>
      <div class="input-wrapper">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" id="email" placeholder="Enter your email" required>
      </div>
      <label for="password">Password</label>
      <div class="input-wrapper">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="password" placeholder="Enter your password" required>
        <button type="button" class="eye-icon" onclick="togglePassword()" aria-label="Toggle password visibility">
          <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M12 4.5C7.5 4.5 3.7 7.5 2 12c1.7 4.5 5.5 7.5 10 7.5s8.3-3 10-7.5c-1.7-4.5-5.5-7.5-10-7.5zm0 13c-3 0-5.5-2.5-5.5-5.5S9 6.5 12 6.5s5.5 2.5 5.5 5.5-2.5 5.5-5.5 5.5zm0-9a3.5 3.5 0 100 7 3.5 3.5 0 000-7z"/>
          </svg>
          
          <svg id="eyeClosed" style="display:none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M2 5.27L3.28 4 20 20.72 18.73 22l-2.18-2.18C14.79 20.06 13.42 20.5 12 20.5c-4.5 0-8.3-3-10-7.5a11.38 11.38 0 014.73-5.73L2 5.27zm9.36 6.95L8.5 9.35A3.5 3.5 0 0012 15.5c.61 0 1.17-.17 1.66-.45l-2.3-2.3zm7.61 2.15a11.47 11.47 0 002.03-2.87c-1.7-4.5-5.5-7.5-10-7.5a9.76 9.76 0 00-4.05.9l1.6 1.6a7.88 7.88 0 013.05-.6 8.49 8.49 0 017.37 4.5c-.36.73-.85 1.4-1.43 2.04l1.43 1.43z"/>
          </svg>
        </button>
      </div>
      <div class="remember-me">
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Remember me</label>
      </div>
      <div class="forgot"><a href="forgot_password.php">Forgot password?</a></div>
      <button type="submit" class="btn-primary">LOG IN</button>
    </form>
    
    <div class="divider">
      <span>OR</span>
    </div>
    
    <div class="google-login">
      <div id="gSignInWrapper">
        <div id="customBtn" class="google-btn">
          <i class="fab fa-google"></i>
          Sign in with Google
        </div>
      </div>
    </div>
    
    <div class="register-link">Are you new? <a href="register.php">Register an Account</a></div>
  </div>
  
  <script>
    function togglePassword() {
      const passwordField = document.getElementById("password");
      const eyeOpen = document.getElementById("eyeOpen");
      const eyeClosed = document.getElementById("eyeClosed");
      
      if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeOpen.style.display = "none";
        eyeClosed.style.display = "inline";
      } else {
        passwordField.type = "password";
        eyeOpen.style.display = "inline";
        eyeClosed.style.display = "none";
      }
    }
    
    // Google Sign-In
    document.getElementById('customBtn').addEventListener('click', function() {
      // Redirect to Google OAuth
      const clientId = 'YOUR_GOOGLE_CLIENT_ID'; // Replace with your Google Client ID
      const redirectUri = 'https://yourdomain.com/google_callback.php'; // Replace with your callback URL
      const scope = 'email profile';
      const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${clientId}&redirect_uri=${redirectUri}&scope=${scope}&response_type=code`;
      
      window.location.href = authUrl;
    });
  </script>
</body>
</html>