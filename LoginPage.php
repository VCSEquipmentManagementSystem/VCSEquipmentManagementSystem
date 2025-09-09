<?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
    <div class="alert alert-danger text-center" role="alert">
        Incorrect Employee ID or Password.
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <title>LogIn</title>
</head>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

    body {
        font-family: "Poppins", serif;
        background: url(./Pictures/bg.jpg);
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        display: block;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f5f5f5;
    }

    body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        z-index: -1;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .left-section .overlay {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        background-color: rgba(0, 128, 0, 0.8);
        color: #fff;
        height: 50vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
    }

    .left-section .logo {
        float: left;
        width: 80px;
        margin-bottom: 20px;
    }

    .left-section h1 {
        font-size: 30px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .left-section h2 {
        font-size: 25px;
        color: #f7b81b;
        margin-top: 10px;
    }

    form label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    form input {
        border: none;
        border-bottom: 2px solid #f3f3f3;
        outline: none;
        font-size: 16px;
        width: 350px;
        padding: 5px 10px;
        box-sizing: border-box;
        transition: border-color 0.3s ease;
    }

    form .btn {
        width: 350px;
    }

    form .btn:hover {
        background-color: #1c7d33;
    }

    .form-control {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.10);
    }

    @media (max-width: 768px) {
        .container {
            flex-direction: column;
            width: 100%;
            height: auto;
        }

        .left-section,
        .right-section {
            flex: 1;
            width: 100%;
            height: auto;
        }

        .left-section .overlay {
            padding: 10px;
        }

        .right-section {
            padding: 20px;
        }

        form input {
            width: 100%;
        }

        .password-container input {
            width: 100%;
        }
    }
</style>

<body>
    <div class="container">
        <div class="left-section">
            <div class="overlay">
                <img src="./Pictures/LOGO.png" alt="Company Logo" class="logo">
                <h1>VIKING CONSTRUCTION & SUPPLIES</h1>
                <h2>EQUIPMENT MANAGEMENT SYSTEM</h2>
            </div>
        </div>
        <div class="right-section bg-light h-50 p-5 rounded-end shadow-sm">
            <!-- Header -->
            <h1 class="text-center text-success">Welcome!</h1>
            <!-- Inputs -->
            <form action="loginProcess.php" method="post">
                <label for="employee-id">Employee ID</label>
                <input type="text" class="form-control" id="employeeId" name="company_emp_id" placeholder="Enter ID" required maxlength="17">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                    <!-- <i class="bi bi-eye-slash" id="togglePassword"></i> -->
                </div>
                <!-- Remember me -->
                <div class="form-check mt-2 d-flex ">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                    <div class="vr mx-2" style="height:20px;"></div>
                    <!-- Forgot pass -->
                    <div class="forgot-pass justify-content-end">
                        <a href="change-password.php" class="text-secondary-emphasis text-decoration-underline">Forgot Password?</a>
                    </div>
                </div>
                <div class=" d-flex justify-content-center mt-2">
                    <button type="submit" class="btn btn-success mb-4 d-flex justify-content-center">Log In</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById("togglePassword").addEventListener("click", function() {
            let passwordInput = document.getElementById("password");
            let icon = this;

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye-fill");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("bi-eye-fill");
                icon.classList.add("bi-eye-slash");
            }
        });
    </script>
</body>

</html>