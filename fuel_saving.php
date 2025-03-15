<?php
session_start();
include 'includes/db.php'; // Database connection

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

$user_email = $_SESSION['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $distance = floatval($_POST["distance"]);
    $mileage = floatval($_POST["mileage"]);
    $fuel_rate = floatval($_POST["fuel_rate"]);
    $vehicle_type = $_POST["vehicle_type"];
    $fuel_type = $_POST["fuel_type"];

    // Fuel wasted without carpool
    $fuel_wasted = $distance / $mileage;
    $cost_wasted = $fuel_wasted * $fuel_rate;

    // Fuel saved if carpool (75% saved assuming 4 people share)
    $fuel_saved = $fuel_wasted * 0.75;
    $cost_saved = $cost_wasted * 0.75;

    // Save to database
    $stmt = $conn->prepare("INSERT INTO fuel_savings 
        (user_email, distance, mileage, fuel_rate, vehicle_type, fuel_type, fuel_wasted, cost_wasted, fuel_saved, cost_saved) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddsssdddd", $user_email, $distance, $mileage, $fuel_rate, $vehicle_type, $fuel_type, $fuel_wasted, $cost_wasted, $fuel_saved, $cost_saved);
    $stmt->execute();
    $stmt->close();

    // Messages
    $message_wasted = "Without using GoTogether, you will waste <strong>" . number_format($fuel_wasted, 2) . " L</strong> of fuel and spend <strong>₹" . number_format($cost_wasted, 2) . "</strong>.";
    $message_saved = "By using GoTogether, you can save <strong>" . number_format($fuel_saved, 2) . " L</strong> of fuel and <strong>₹" . number_format($cost_saved, 2) . "</strong>!";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel & Cost Saving Calculator</title>
    <link rel="stylesheet" href="assets/fuel_saving.css">
</head>

<body>
    <div class="container">
        <h1>Go<span> Together</span></h1>
        <h2>Fuel & Cost Saving Calculator</h2>
        <form method="post">
            <label>Distance (km):</label>
            <input type="number" step="0.1" name="distance" required>

            <label>Vehicle Mileage (km/l):</label>
            <input type="number" step="0.1" name="mileage" required>

            <label>Fuel Rate (₹/L):</label>
            <input type="number" step="0.1" name="fuel_rate" required>

            <label>Vehicle Type:</label>
            <select name="vehicle_type" required>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
                <option value="bus">Bus</option>
            </select>

            <label>Fuel Type:</label>
            <select name="fuel_type" required>
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="cng">CNG</option>
            </select>

            <button type="submit">Calculate Savings</button>
        </form>

        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <div class="alert">
            <h3>🚨 Fuel & Money Wasted</h3>
            <p><?php echo $message_wasted; ?></p>
        </div>

        <div class="result">
            <h3>✅ Savings with GoTogether</h3>
            <p><?php echo $message_saved; ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>

</html>