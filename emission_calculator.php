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
    $fuel_type = $_POST["fuel_type"];
    $vehicle_type = $_POST["vehicle_type"];

    // Emission factors (g/km)
     $emission_factors = [
        "petrol" => ["CO2" => 2.31, "N2O" => 0.05, "CO" => 2.3, "PM2.5" => 0.02, "VOCs" => 0.3, "SO2" => 0.01],
        "diesel" => ["CO2" => 2.68, "N2O" => 0.07, "CO" => 1.8, "PM2.5" => 0.04, "VOCs" => 0.2, "SO2" => 0.02],
        "cng" => ["CO2" => 2.18, "N2O" => 0.03, "CO" => 1.5, "PM2.5" => 0.01, "VOCs" => 0.1, "SO2" => 0.005]
    ];

    $savings = [];
    foreach ($emission_factors[$fuel_type] as $gas => $factor) {
        $savings[$gas] = $factor * $distance;
    }

    // Store savings in database
    $stmt = $conn->prepare("INSERT INTO emission_savings 
        (user_email, distance, fuel_type, vehicle_type, CO2, N2O, CO, PM2_5, VOCs, SO2) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssdddddd", $user_email, $distance, $fuel_type, $vehicle_type, 
        $savings['CO2'], $savings['N2O'], $savings['CO'], $savings['PM2.5'], $savings['VOCs'], $savings['SO2']);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emission Reduction Calculator</title>
    <link rel="stylesheet" href="assets\emssion_calculator.css">
</head>
<h1>GO <span>TOGETHER</span>
</h1>

<body>
    <div class="container">
        <h2>Emission Reduction Calculator</h2>
        <form method="post">
            <label for="distance">Distance Traveled (km):</label>
            <input type="number" name="distance" required>

            <label for="fuel_type">Fuel Type:</label>
            <select name="fuel_type" required>
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="cng">CNG</option>
            </select>

            <label for="vehicle_type">Vehicle Type:</label>
            <select name="vehicle_type" required>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
                <option value="bus">Bus</option>
            </select>

            <button type="submit">Calculate & Save</button>
        </form>

        <?php if (!empty($savings)): ?>
        <div class="result">
            <h3>Emission Savings (Stored in Your Account):</h3>
            <ul>
                <?php foreach ($savings as $gas => $amount): ?>
                <li><strong><?php echo $gas; ?>:</strong> <?php echo number_format($amount, 2); ?> g</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <div class="how">
            <h3>How It Works:</h3>
            <p>We calculate emission savings using:</p>
            <p><strong>Emission Saved (g) = Distance (km) × Emission Factor (g/km)</strong></p>
            <p>For example, if you travel 10 km using a petrol car:</p>
        </div>


        <div class="formula">
            <div>
                <h3>Petrol:</h3>
                <ul>
                    <li>CO₂: 10 × 239.2 = 2.392 kg</li>
                    <li>N₂O: 10 × 0.05 = 0.5 g</li>
                    <li>CO: 10 × 2.3 = 23 g</li>
                    <li>PM2.5: 10 × 0.02 = 0.2 g</li>
                    <li>VOCs: 10 × 0.3 = 3 g</li>
                    <li>SO₂: 10 × 0.01 = 0.1 g</li>
                </ul>
            </div>
            <div>
                <h3>Diesel:</h3>
                <ul>
                    <li>CO₂: 10 × 264 = 2.64 kg</li>
                    <li>N₂O: 10 × 0.07 = 0.7 g</li>
                    <li>CO: 10 × 1.8 = 18 g</li>
                    <li>PM2.5: 10 × 0.04 = 0.4 g</li>
                    <li>VOCs: 10 × 0.2 = 2 g</li>
                    <li>SO₂: 10 × 0.02 = 0.2 g</li>
                </ul>
            </div>
            <div>
                <h3>CNG:</h3>
                <ul>
                    <li>CO₂: 10 × 180 = 1.8 kg</li>
                    <li>N₂O: 10 × 0.03 = 0.3 g</li>
                    <li>CO: 10 × 1.5 = 15 g</li>
                    <li>PM2.5: 10 × 0.01 = 0.1 g</li>
                    <li>VOCs: 10 × 0.1 = 1 g</li>
                    <li>SO₂: 10 × 0.005 = 0.05 g</li>
                </ul>
            </div>
        </div>

    </div>
    </div>
</body>

</html>