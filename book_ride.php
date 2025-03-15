<?php
session_start();
include 'includes/db.php';
require 'includes/mailer.php'; // Include mailer function

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

$user_email = $_SESSION['email'];

// Handle ride booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_ride'])) {
    $ride_id = 1; // Default ride_id set to 1
    $pickup = $_POST['pickup'];
    $dropoff = $_POST['dropoff'];
    $pickup_date = $_POST['pickup_date'];
    $required_seats = $_POST['required_seats'];
    $pickup_time = $_POST['pickup_time'];
    $dropoff_time = $_POST['dropoff_time'];

    // Insert booking details
    $stmt = $conn->prepare("INSERT INTO bookings 
        (ride_id, user_email, pickup, dropoff, pickup_date, required_seats, status, pickup_time, dropoff_time) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
    $stmt->bind_param("issssiss", $ride_id, $user_email, $pickup, $dropoff, $pickup_date, $required_seats, $pickup_time, $dropoff_time);

    if ($stmt->execute()) {
        // Send email notification to user
        $subject = "Booking Confirmation - GoTogether";
        $message = "<h3>Your ride booking request has been submitted.</h3>
                    <p>Pickup: $pickup</p>
                    <p>Dropoff: $dropoff</p>
                    <p>Date: $pickup_date</p>
                    <p>Seats: $required_seats</p>
                    <p>Status: Pending Approval</p>
                    <br><p>Thank you for using GoTogether!</p>";

        sendEmail($user_email, $subject, $message);

        echo "<script>alert('Booking successful! Waiting for approval.'); window.location.href='home.php';</script>";
    } else {
        echo "<script>alert('Error booking the ride. Please try again.');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Book a Ride</title>
    <link rel="stylesheet" href="assets/book_ride.css">
</head>

<body>
    <h1>Book a Ride</h1>
    <form method="POST">
        <input type="text" name="pickup" placeholder="Pickup Location" required>
        <input type="text" name="dropoff" placeholder="Dropoff Location" required>
        <input type="date" name="pickup_date" required>
        <input type="time" name="pickup_time" required>
        <input type="time" name="dropoff_time" required>
        <input type="number" name="required_seats" placeholder="Seats Required" required>
        <button type="submit" name="book_ride">Book Ride</button>
    </form>
</body>

</html>