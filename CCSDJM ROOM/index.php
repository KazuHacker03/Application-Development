<?php
session_start();

/* ================================
   DATABASE CONNECTION
================================ */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ccsjdm_system";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* ================================
   IF ALREADY LOGGED IN
================================ */
if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: main.php");
    exit;
}


/* ================================
   LOGIN PROCESS
================================ */
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = trim($_POST["student_id"] ?? "");
    $password   = $_POST["password"] ?? "";

    if ($student_id === "" || $password === "") {

        $error = "Please enter your ID and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, student_id, password_hash
             FROM users
             WHERE student_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $student_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $userData = $result->fetch_assoc();

            if (password_verify($password, $userData["password_hash"])) {

                /* Create session */
                $_SESSION["logged_in"] = true;
                $_SESSION["user_id"]   = $userData["id"];
                $_SESSION["username"]  = $userData["student_id"];
                $_SESSION["full_name"] = $userData["full_name"];

                /* Audit log */
                $action = "User logged in";
                $log = $conn->prepare(
                    "INSERT INTO audit_logs (username, action)
                     VALUES (?, ?)"
                );

                $log->bind_param(
                    "ss",
                    $userData["student_id"],
                    $action
                );

                $log->execute();
                $log->close();

                header("Location: main.php");
                exit;

            } else {

                $error = "Incorrect ID or password.";

            }

        } else {

            $error = "Incorrect ID or password.";

        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>CCSJDM | Login</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;

            /* Base color sa likod ng logo */
            background:
                linear-gradient(
                    135deg,
                    #16002b,
                    #2b0645,
                    #090014
                );

            color: white;
            padding: 20px;

            position: relative;
            overflow-x: hidden;
        }

        /* ================================
           BACKGROUND GAMIT ANG LOGO
           (palitan ang "cc_logo.png" kung
           iba ang pangalan/lokasyon ng file)
        ================================ */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;

            background-image: url("backgroundcc.jpg");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: min(95vh, 95vw);

            /* Lakas ng logo sa background: 0.10 (mahina) - 0.35 (malakas) */
            opacity: 0.22;

            pointer-events: none;
        }

        /* Madilim na glow para mabasa pa rin ang card */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;

            background:
                radial-gradient(
                    circle at center,
                    rgba(22,0,43,0.15) 0%,
                    rgba(9,0,20,0.75) 100%
                );

            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 1;

            width: 100%;
            max-width: 430px;
        }

        .login-card {
            position: relative;

            background: rgba(20,5,35,0.55);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            border: 1px solid rgba(255,255,255,0.15);

            border-radius: 24px;

            padding: 40px 35px;

            box-shadow:
                0 25px 60px rgba(0,0,0,0.45);

            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================================
           FACEBOOK BUTTON (kaliwang itaas)
        ================================ */
        .fb-corner {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 2;

            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(59, 130, 246, .15);
            border: 1px solid rgba(59, 130, 246, .35);
            color: #60a5fa;

            transition: 0.2s;
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
            width: 100px;
            height: 100px;

            margin: 0 auto 20px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff;

            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(147,51,234,0.45);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        h1 {
            text-align: center;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #d8cbe5;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #eee;
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            padding: 14px 45px 14px 15px;

            border-radius: 12px;

            border: 1px solid rgba(255,255,255,0.15);

            background: rgba(0,0,0,0.25);

            color: white;

            outline: none;

            font-size: 15px;

            transition: 0.3s;
        }

        input::placeholder {
            color: #a99eb2;
        }

        input:focus {
            border-color: #c026d3;

            box-shadow:
                0 0 0 3px rgba(192,38,211,0.15);
        }

        .show-password {
            position: absolute;

            right: 13px;
            top: 50%;

            transform: translateY(-50%);

            cursor: pointer;

            color: #c9b9d3;

            font-size: 13px;
        }

        .show-password:hover {
            color: white;
        }

        .error {
            background: rgba(239,68,68,0.15);

            border: 1px solid rgba(239,68,68,0.35);

            color: #ffb4b4;

            padding: 12px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            text-align: center;
        }

        .login-btn {
            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #9333ea,
                    #ec4899
                );

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;

            box-shadow:
                0 8px 25px rgba(147,51,234,0.35);
        }

        .login-btn:hover {
            transform: translateY(-2px);

            box-shadow:
                0 12px 30px rgba(236,72,153,0.35);
        }

        /* SIGN UP AREA */

        .signup-area {
            text-align: center;

            margin-top: 25px;

            padding-top: 22px;

            border-top:
                1px solid
                rgba(255,255,255,0.12);

            color: #cfc2d8;

            font-size: 14px;
        }

        .signup-link {
            display: inline-block;

            margin-top: 8px;

            color: #f0abfc;

            font-weight: bold;

            text-decoration: none;

            transition: 0.3s;
        }

        .signup-link:hover {
            color: white;

            text-decoration: underline;
        }

        .footer {
            text-align: center;

            margin-top: 20px;

            font-size: 12px;

            color: #cbbbd6;
        }

        @media (max-width: 480px) {

            .login-card {
                padding: 30px 22px;
            }

            h1 {
                font-size: 23px;
            }

        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <!-- FACEBOOK BUTTON -->
        <a href="https://www.facebook.com/HaroldCachoAnimator"
           class="fb-corner"
           target="_blank"
           rel="noopener noreferrer"
           title="Facebook Profile">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.88v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z"/>
            </svg>
        </a>

        <div class="logo">
            <img src="backgroundcc.jpg" alt="CCSJDM Logo">
        </div>

        <h1>CCSJDM</h1>

        <p class="subtitle">
            Scheduling & Room Plotting
        </p>


        <?php if ($error !== ""): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="index.php">

            <div class="input-group">

                <label for="student_id">
                    Professor ID
                </label>

                <input
                    type="text"
                    id="student_id"
                    name="student_id"
                    placeholder="Enter your ID"
                    maxlength="50"
                    required
                    autocomplete="username"
                >

            </div>


            <div class="input-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >

                    <span
                        class="show-password"
                        onclick="togglePassword()"
                        id="toggleText"
                    >
                        Show
                    </span>

                </div>

            </div>


            <button
                type="submit"
                class="login-btn"
            >
                Login
            </button>

        </form>


        <!-- SIGN UP LINK -->

        <div class="signup-area">

            <div>
                Don't have an account?
            </div>

            <a
                href="signup.php"
                class="signup-link"
            >
                Sign up
            </a>
             

            <div>
                <br>
                Developer: Harold Cacho
                <br>
            </div>

        </div>

    </div>

    <div class="footer">
        City College of San Jose Del Monte
    </div>

</div>
    <br>
        </br>
<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const toggleText =
        document.getElementById("toggleText");

    if (password.type === "password") {

        password.type = "text";

        toggleText.textContent = "Hide";

    } else {

        password.type = "password";

        toggleText.textContent = "Show";

    }

}

</script>

</body>

</html>