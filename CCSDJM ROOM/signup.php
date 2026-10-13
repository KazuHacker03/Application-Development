<?php
session_start();

/*
|--------------------------------------------------------------------------
| CCSJDM SYSTEM - SIGN UP
|--------------------------------------------------------------------------
| Creates users in:
|   ccsjdm_system.users
|
| Fields:
|   full_name
|   student_id
|   password_hash
|--------------------------------------------------------------------------
*/

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ccsjdm_system";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . htmlspecialchars($conn->connect_error));
}

$conn->set_charset("utf8mb4");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    /* Validate fields */
    if ($full_name === "" || $student_id === "" || $password === "" || $confirm_password === "") {
        $error = "Please complete all fields.";

    } elseif (strlen($full_name) > 100) {
        $error = "Full name is too long.";

    } elseif (strlen($student_id) > 50) {
        $error = "Student/Professor ID is too long.";

    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";

    } else {

        /* Check whether Student ID already exists */
        $check = $conn->prepare(
            "SELECT id FROM users WHERE student_id = ? LIMIT 1"
        );

        if (!$check) {
            $error = "Unable to process the registration.";
        } else {

            $check->bind_param("s", $student_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "That Student/Professor ID is already registered.";
            } else {

                /* Secure password hash */
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (full_name, student_id, password_hash)
                    VALUES (?, ?, ?)"
                );

                if (!$stmt) {
                    $error = "Unable to create the account.";
                } else {

                    $stmt->bind_param(
                        "sss",
                        $full_name,
                        $student_id,
                        $password_hash
                    );

                    if ($stmt->execute()) {

                        /* Record registration */
                        $log = $conn->prepare(
                            "INSERT INTO audit_logs (username, action)
                             VALUES (?, ?)"
                        );

                        if ($log) {
                            $action = "Created a new user account";
                            $log->bind_param(
                                "ss",
                                $student_id,
                                $action
                            );
                            $log->execute();
                            $log->close();
                        }

                        $success =
                            "Account created successfully! You can now log in.";

                        /* Clear submitted fields after successful signup */
                        $full_name = "";
                        $student_id = "";

                    } else {
                        $error = "Unable to create the account.";
                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sign Up | CCSJDM System</title>

<style>
:root {
    --bg: #090118;
    --bg2: #1e023d;
    --card: rgba(28, 11, 54, .94);
    --purple: #7011ce;
    --purple2: #8b12f6;
    --pink: #d946ef;
    --blue: #3b82f6;
    --green: #22c55e;
    --red: #ef4444;
    --text: #fff;
    --muted: #aaa;
    --border: rgba(255,255,255,.12);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 25px 0;
    color: var(--text);
    background:
        radial-gradient(circle at 15% 15%, rgba(112,17,206,.28), transparent 30%),
        radial-gradient(circle at 85% 85%, rgba(217,70,239,.20), transparent 30%),
        linear-gradient(135deg, var(--bg), var(--bg2), #120124);
}

.signup-wrapper {
    width: min(92%, 450px);
}

.signup-card {
    position: relative;
    padding: 35px;
    border: 1px solid var(--border);
    border-radius: 20px;
    background: var(--card);
    backdrop-filter: blur(18px);
    box-shadow:
        0 25px 70px rgba(0,0,0,.55),
        0 0 35px rgba(112,17,206,.18);
}

.fb-corner {
    position: absolute;
    top: 14px;
    left: 14px;
    width: 36px;
    height: 36px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    background: rgba(59, 130, 246, .15);
    border: 1px solid rgba(59, 130, 246, .35);
    color: #60a5fa;
    transition: .2s;
    z-index: 2;
}

.fb-corner:hover {
    color: white;
    background: #1877f2;
    border-color: #1877f2;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24, 119, 242, .35);
}

.fb-corner svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.logo {
    width: 65px;
    height: 65px;
    margin: 0 auto 16px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 17px;
    font-weight: 800;
    font-size: 1.25rem;
    background: linear-gradient(135deg, var(--purple), var(--pink));
    box-shadow: 0 0 28px rgba(217,70,239,.35);
}

.title {
    text-align: center;
    font-size: 1.75rem;
    font-weight: 800;
    background: linear-gradient(90deg, #d946ef, #8b5cf6, #3b82f6);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.subtitle {
    margin: 8px 0 25px;
    color: var(--muted);
    text-align: center;
    font-size: .88rem;
}

.message {
    margin-bottom: 18px;
    padding: 11px 13px;
    border-radius: 8px;
    font-size: .87rem;
}

.error {
    border: 1px solid rgba(239,68,68,.55);
    color: #fecaca;
    background: rgba(239,68,68,.12);
}

.success {
    border: 1px solid rgba(34,197,94,.55);
    color: #86efac;
    background: rgba(34,197,94,.12);
}

.form-group {
    margin-bottom: 17px;
}

label {
    display: block;
    margin-bottom: 7px;
    color: #ddd;
    font-size: .86rem;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 12px 13px;
    border: 1px solid var(--border);
    border-radius: 8px;
    outline: none;
    color: white;
    background: rgba(255,255,255,.055);
    transition: .2s;
}

input::placeholder {
    color: #777;
}

input:focus {
    border-color: var(--pink);
    box-shadow: 0 0 0 3px rgba(217,70,239,.10);
}

.password-box {
    position: relative;
}

.password-box input {
    padding-right: 65px;
}

.show-password {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
    padding: 5px;
    border: 0;
    color: #aaa;
    background: transparent;
    cursor: pointer;
}

.show-password:hover {
    color: white;
}

.signup-btn {
    width: 100%;
    margin-top: 4px;
    padding: 13px;
    border: 0;
    border-radius: 9px;
    color: white;
    background: linear-gradient(135deg, var(--purple), var(--purple2));
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.signup-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(217,70,239,.23);
}

.login-link {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
    text-align: center;
    color: var(--muted);
    font-size: .85rem;
}

.login-link a {
    color: #c084fc;
    text-decoration: none;
    font-weight: 700;
}

.login-link a:hover {
    color: white;
}

.password-note {
    margin-top: 6px;
    color: #777;
    font-size: .72rem;
}

.logo-img {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 16px;
    width: 100%;
}

.logo-img img {
    display: block;
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 50%;
}

@media(max-width: 480px) {
    .signup-card {
        padding: 27px 21px;
    }
}
</style>
</head>

<body>

<main class="signup-wrapper">

    <section class="signup-card">

        <a href="" class="fb-corner" target="_blank" rel="noopener noreferrer" title="Facebook Profile">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.88v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z"/>
            </svg>
        </a>

        <div class="logo-img">
    <img src="Backgroundcc.jpg" alt="CCSJDM Logo" width="100">
</div>

        <h1 class="title">
            Create Account
        </h1>

        <p class="subtitle">
            City College of San Jose Del Monte
        </p>

        <?php if ($error !== ""): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" autocomplete="on">

            <div class="form-group">
                <label for="full_name">Full Name</label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    maxlength="100"
                    autocomplete="name"
                    value="<?php echo htmlspecialchars($full_name ?? ""); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="student_id">
                    Professor ID
                </label>

                <input
                    type="text"
                    id="student_id"
                    name="student_id"
                    placeholder="Enter your ID"
                    maxlength="50"
                    autocomplete="username"
                    value="<?php echo htmlspecialchars($student_id ?? ""); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="password-box">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="show-password"
                        onclick="togglePassword('password', this)"
                    >
                        Show
                    </button>
                </div>

                <div class="password-note">
                    Password must contain at least 8 characters.
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    Confirm Password
                </label>

                <div class="password-box">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat your password"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="show-password"
                        onclick="togglePassword('confirm_password', this)"
                    >
                        Show
                    </button>
                </div>
            </div>

            <button type="submit" class="signup-btn">
                Create Account
            </button>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="index.php">Login here</a>
        </div>

    </section>

</main>

<script>
function togglePassword(id, button) {

    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        button.textContent = "Hide";
    } else {
        input.type = "password";
        button.textContent = "Show";
    }
}
</script>

</body>
</html>