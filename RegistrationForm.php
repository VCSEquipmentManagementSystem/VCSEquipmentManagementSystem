<?php
// require 'ems_database.php'; // Database Connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and trim user input
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $employee_Id = trim($_POST['employeeId']);
    $emp_email = trim($_POST['email']);
    $contactNum = trim($_POST['contactNum']);
    $position = trim($_POST['position']);
    $password = trim($_POST['password']);

    // Ensure all fields are filled
    if ($firstName === "" || $lastName === "" || $employee_Id === "" || $emp_email === "" || $contactNum === "" || $position === "" || $password === "") {
        die("<script>alert('All fields are required!'); window.history.back();</script>");
    }

    // Check if Employee ID or Email already exists
    $check_stmt = $connection->prepare("SELECT user_id FROM user_tbl WHERE employee_id = ? OR emp_email = ?");
    if (!$check_stmt) {
        die("Prepare failed: " . $connection->error);
    }

    $check_stmt->bind_param("ss", $employee_Id, $emp_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        die("<script>alert('Email or Employee ID already exists!'); window.history.back();</script>");
    }

    // Secure Password Hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user data into database
    $stmt = $connection->prepare("INSERT INTO user_tbl (employee_id, emp_email, username, password, role, contactNum) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: " . $connection->error);
    }

    // Create username from firstName + lastName
    $username = strtolower($firstName . "." . $lastName);

    $stmt->bind_param("ssssss", $employee_Id, $emp_email, $username, $hashed_password, $position, $contactNum);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='http://localhost/latest_cdevkng/login-section/LoginPage.php';</script>";
    } else {
        echo "<script>alert('Error during registration!'); window.history.back();</script>";
    }

    $stmt->close();
    $check_stmt->close();
    $connection->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Create Account</title>
</head>

<style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", serif;
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

    body {
        background: url(bg.jpg);
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        display: block;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .card {
        justify-content: center;
        border-radius: 10px;
        background-color: #fff;
        max-width: 500px;
        margin: 50px auto;
        padding: 30px 20px;
        box-shadow: 2px 5px 10px rgba(0, 0, 0, 0.5);
    }

    .card-body input {
        border: none;
        border-bottom: 2px solid #f3f3f3;
        outline: none;
        font-size: 16px;
        width: 100%;
        padding: 5px 10px;
        box-sizing: border-box;
        transition: border-color 0.3s ease;
    }

    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .form-group label {
        font-weight: bold;
        width: 140px;
        text-align: left;
        margin-right: 15px;
        margin-bottom: 0;
    }

    .form-group .input-container {
        flex: 1;
    }

    .card-body form input:focus {
        border-bottom: 2px solid #0052a9;
    }

    h4 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
    }
</style>

<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center text-success">
                        <h4>Create Account</h4>
                    </div>
                    <div class="card-body">
                        <form action="confirm-password.php" method="post" id="form">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="firstName" placeholder="First Name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="lastName" placeholder="Last Name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="employeeid">Employee ID</label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="employeeid" placeholder="Employee ID" required maxlength="17">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="position">Position</label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="position" placeholder="Position" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <div class="input-container">
                                    <input type="email" class="form-control" id="email" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="contact-num">Contact Number</label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="contact-num" placeholder="Contact Number" maxlength="11" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mt-4">Next</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>