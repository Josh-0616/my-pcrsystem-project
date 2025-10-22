<?php
session_start();
include('db/connection.php'); 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $role = 'faculty'; // fixed role
    if (!empty($name) && !empty($email) && !empty($password)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();
        if ($checkResult->num_rows > 0) {
            echo "<script>alert('Email already registered. Please login.');</script>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
            if ($stmt->execute()) {
                echo "<script>alert('Registration successful! Redirecting to login...'); window.location='login.php';</script>";
                exit();
            } else {
                echo "<script>alert('Registration failed. Please try again.');</script>";
            }
        }
    } else {
        echo "<script>alert('Please fill in all fields.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    .register-panel {
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

    .register-form { 
      display: flex;
      flex-direction: column; 
    }

    .register-form label {
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
      font-size: clamp(14px, 1.2vw, 16px);
      pointer-events: none;
    }

    .input-wrapper input {
      padding: clamp(10px, 2vw, 12px) 38px;
      border: none; 
      border-radius: 10px;
      font-size: clamp(0.875rem, 1.2vw, 1rem);
      background: var(--input-light);
      color: var(--text-dark); 
      width: 100%;
      transition: all 0.3s ease;
    }

    .input-wrapper input:focus {
      outline: none;
      box-shadow: 0 0 0 2px var(--primary-color);
    }

    .eye-icon {
      position: absolute;
      right: 12px;
      cursor: pointer;
      width: 22px;
      height: 22px;
      background: transparent;
      border: none;
      padding: 0;
      transition: opacity 0.3s ease;
    }

    .eye-icon svg {
      width: 20px;
      height: 20px;
      fill: var(--icon-color);
      opacity: 0.7;
    }

    .eye-icon:hover svg {
      opacity: 1;
    }

    .btn-register {
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

    .btn-register:hover {
      background: #0056b3;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-register:active {
      transform: translateY(0);
    }

    .login-link {
      text-align: center; 
      margin-top: 15px; 
      font-size: clamp(0.75rem, 1vw, 0.8125rem);
    }

    .login-link a { 
      color: var(--text-light); 
      text-decoration: underline;
      transition: opacity 0.3s ease;
    }

    .login-link a:hover {
      opacity: 0.8;
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

      .register-panel {
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

      .register-panel {
        padding: 20px;
        margin-top: 10px;
      }

      .register-form label {
        margin-top: 12px;
      }

      .btn-register {
        margin-top: 15px;
      }
    }

    @media (max-width: 320px) {
      html {
        font-size: 12px;
      }

      .register-panel {
        padding: 15px;
      }

      .input-wrapper input {
        padding: 8px 38px;
      }
    }
  </style>
</head>
<body>
  <div class="overlay"></div>
  <h2 class="title">Office Performance and Commitment and Review for Philippine Countryville College,Inc</h2>
  <div class="register-panel">
    <form method="POST" action="" class="register-form">
      <label for="name">Full Name</label>
      <div class="input-wrapper">
        <i class="fas fa-user"></i>
        <input type="text" name="name" id="name" placeholder="Enter your full name" required>
      </div>
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
      <button type="submit" class="btn-register">Register</button>
      <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
    </form>
  </div>
  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const eyeOpen = document.getElementById('eyeOpen');
      const eyeClosed = document.getElementById('eyeClosed');
      
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeOpen.style.display = "none";
        eyeClosed.style.display = "inline";
      } else {
        passwordInput.type = "password";
        eyeOpen.style.display = "inline";
        eyeClosed.style.display = "none";
      }
    }
  </script>
</body>
</html>