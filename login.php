<?php
require_once 'config/db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$success = '';
$active_panel = ''; // CSS class 'active' to keep sign-up open if signup failed

// 1. Process Sign In
if (isset($_POST['signin'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'inactive') {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// 2. Process Sign Up
if (isset($_POST['signup'])) {
    $active_panel = 'active'; // keep panel active for styling if signup fails
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if email or username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $error = 'Username or email already registered.';
            } else {
                // Hash password and insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
                $stmt->execute([$username, $email, $hashed_password]);

                $success = 'Registration successful! You can now log in.';
                $active_panel = ''; // toggle back to login panel
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container auth-wrapper">
    <div class="auth-container <?php echo $active_panel; ?>" id="auth-container">
        
        <!-- SIGN UP FORM -->
        <div class="form-container sign-up-container">
            <form action="login.php" method="POST" class="auth-form">
                <h1>Create Account</h1>
                
                <div class="social-container">
                    <a href="#" class="social-icon">G</a>
                    <a href="#" class="social-icon">F</a>
                    <a href="#" class="social-icon">Git</a>
                    <a href="#" class="social-icon">In</a>
                </div>
                <span>or use your email for registration</span>
                
                <?php if ($error && $active_panel === 'active'): ?>
                    <div class="alert alert-danger" style="padding: 8px 12px; margin-bottom: 10px; width: 100%; font-size: 0.85rem;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <input type="text" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) && $active_panel === 'active' ? htmlspecialchars($_POST['username']) : ''; ?>">
                <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) && $active_panel === 'active' ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" name="signup" class="btn btn-primary" style="margin-top: 15px; width: 100%;">Sign Up</button>
            </form>
        </div>

        <!-- SIGN IN FORM -->
        <div class="form-container sign-in-container">
            <form action="login.php" method="POST" class="auth-form">
                <h1>Sign In</h1>
                
                <div class="social-container">
                    <a href="#" class="social-icon">G</a>
                    <a href="#" class="social-icon">F</a>
                    <a href="#" class="social-icon">Git</a>
                    <a href="#" class="social-icon">In</a>
                </div>
                <span>or use your account email</span>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="padding: 8px 12px; margin-bottom: 10px; width: 100%; font-size: 0.85rem;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error && $active_panel === ''): ?>
                    <div class="alert alert-danger" style="padding: 8px 12px; margin-bottom: 10px; width: 100%; font-size: 0.85rem;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) && $active_panel === '' ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <a href="#">Forgot your password?</a>
                <button type="submit" name="signin" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </form>
        </div>

        <!-- OVERLAY CONTAINER -->
        <div class="overlay-container">
            <div class="overlay">
                
                <!-- LEFT OVERLAY PANEL (Reveals Sign In) -->
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="btn btn-ghost" id="signIn">Sign In</button>
                </div>
                
                <!-- RIGHT OVERLAY PANEL (Reveals Sign Up) -->
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to use all of app features</p>
                    <button class="btn btn-ghost" id="signUp">Sign Up</button>
                </div>
                
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
