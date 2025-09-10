<?php
    session_start();
    if (isset($_SESSION['user'])) {
        header("Location: dashboard.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="/styles/login.css" />
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">

  <title>Login</title>
</head>
<body>
  <form method="POST" action="login_process.php" data-tilt data-tilt-glare>
    <img src="assets/logo.svg" class="__logo">

    <h2 class="login__title">Admin Login</h2>

    <div class="login__field">
      <input type="text" id="username" name="username" class="login__input" placeholder=" " required>
      <label for="username" class="login__label">Username</label>
    </div>

    <div class="login__field">
      <input type="password" id="password" name="password" class="login__input" placeholder=" " required>
      <label for="password" class="login__label">Password</label>
    </div>

    <button type="submit">Login</button>
  </form>
</body>
<script type="text/javascript" src="scripts/vanilla-tilt.js"></script>
</html>
