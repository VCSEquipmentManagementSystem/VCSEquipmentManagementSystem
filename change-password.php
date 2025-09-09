<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Change Password</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");

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

        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            transition: border-color 0.3s;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="card">
            <div class="card-body d-flex justify-content-center align-items-center">
                <form action="">
                    <h3 class="text-center fw-bold mb-4">Change Password</h3>
                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label fw-bold">New Password</label>
                        <input type="password" class="form-control" id="password" placeholder="Enter new password" required>
                        <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3" id="togglePassword" style="cursor: pointer;"></i>
                        <label for="password" class="form-label fw-bold">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" name="" class="btn btn-primary w-100">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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