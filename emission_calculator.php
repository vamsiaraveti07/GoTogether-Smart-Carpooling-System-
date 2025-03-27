<?php
session_start();
include 'includes/db.php'; // Database connection

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

$user_email = $_SESSION['email'];
$savings = [];
$passenger_savings = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $total_passengers = isset($_POST["total_passengers"]) ? intval($_POST["total_passengers"]) : 0;
    $mileage = isset($_POST["mileage"]) ? floatval($_POST["mileage"]) : 0;
    $fuel_type = $_POST["fuel_type"] ?? '';

    $distances = [];
    $total_distance = 0;

    for ($i = 1; $i <= $total_passengers; $i++) {
        if (isset($_POST["distance_$i"]) && is_numeric($_POST["distance_$i"])) {
            $distances[$i] = floatval($_POST["distance_$i"]);
        }
    }

    if (!empty($distances)) {
        $total_distance = array_sum($distances);
    }

    $fuel_used = ($mileage > 0) ? ($total_distance / $mileage) : 0;

    $emission_factors = [
        "petrol" => ["CO2" => 2392, "N2O" => 0.05, "CO" => 2.3, "PM2.5" => 0.02, "VOCs" => 0.3, "SO2" => 0.01],
        "diesel" => ["CO2" => 2640, "N2O" => 0.07, "CO" => 1.8, "PM2.5" => 0.04, "VOCs" => 0.2, "SO2" => 0.02],
        "cng" => ["CO2" => 1800, "N2O" => 0.03, "CO" => 1.5, "PM2.5" => 0.01, "VOCs" => 0.1, "SO2" => 0.005]
    ];

    if (isset($emission_factors[$fuel_type])) {
        foreach ($emission_factors[$fuel_type] as $gas => $factor) {
            $savings[$gas] = ($factor * $total_distance);
        }

        foreach ($distances as $i => $distance) {
            foreach ($emission_factors[$fuel_type] as $gas => $factor) {
                $passenger_savings[$i][$gas] = ($factor * $distance);
            }
        }
    }
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

<body>
    <h1>GO <span>TOGETHER</span></h1>
    <div class="container">
        <h2>Emission Reduction Calculator</h2>
        <form method="post">
            <label for="total_passengers">Number of Passengers:</label>
            <input type="number" name="total_passengers" id="total_passengers" required min="1" max="10">

            <div id="passenger_inputs"></div>

            <label for="mileage">Vehicle Mileage (km/l):</label>
            <input type="number" name="mileage" required>

            <label for="fuel_type">Fuel Type:</label>
            <select name="fuel_type" required>
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="cng">CNG</option>
            </select>

            <button type="submit">Calculate & Save</button>
        </form>

        <?php if (!empty($savings)): ?>
        <div class="result">
            <h3> Your Actual Emission Savings</h3>
            <ul>
                <?php foreach ($savings as $gas => $amount): ?>
                <li>
                    <strong><?php echo $gas; ?>:</strong>
                    <?php 
                        echo ($amount >= 1000) ? number_format($amount / 1000, 2) . " kg" : number_format($amount, 2) . " g";
                    ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="result">
            <h3>Emission Savings Per Passenger</h3>
            <table border="3" width="100%">
                <tr>
                    <th>Passenger</th>
                    <th>Distance (km)</th>
                    <th>CO₂</th>
                    <th>N₂O</th>
                    <th>CO</th>
                    <th>PM2.5</th>
                    <th>VOCs</th>
                    <th>SO₂</th>
                </tr>
                <?php foreach ($passenger_savings as $i => $savings): ?>
                <tr>
                    <td>Passenger <?php echo $i; ?></td>
                    <td><?php echo $distances[$i]; ?> km</td>
                    <?php foreach ($savings as $gas => $amount): ?>
                    <td>
                        <?php 
                            echo ($amount >= 1000) ? number_format($amount / 1000, 2) . " kg" : number_format($amount, 2) . " g";
                        ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="result">
            <h3>📌 Example Calculation (50 km Trip, Mileage: 15 km/l)</h3>
            <p><strong>Formula:</strong> (Distance × Emission Factor) ÷ Mileage</p>

            <div class="example-section">
                <?php foreach (["petrol", "diesel", "cng"] as $fuel): ?>
                <div class="example-box">
                    <h4>⛽ <?php echo ucfirst($fuel); ?> Example</h4>
                    <p><strong>Mileage Used:</strong> 15 km/l</p>
                    <p><strong>Fuel Used:</strong> 50 km ÷ 15 km/l = 3.33 liters</p>
                    <table border="1" width="100%">
                        <tr>
                            <th>Gas</th>
                            <th>Factor (g/km)</th>
                            <th>Emissions</th>
                        </tr>
                        <?php
                    $example_distance = 50;
                    foreach ($emission_factors[$fuel] as $gas => $factor) {
                        $emissions = $example_distance * $factor;
                        echo "<tr>
                            <td>{$gas}</td>
                            <td>{$factor} g/km</td>
                            <td>" . (($emissions >= 1000) ? number_format($emissions / 1000, 2) . " kg" : number_format($emissions, 2) . " g") . "</td>
                        </tr>";
                    }
                ?>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <script>
        document.getElementById('total_passengers').addEventListener('change', function() {
            let container = document.getElementById('passenger_inputs');
            container.innerHTML = '';
            for (let i = 1; i <= this.value; i++) {
                container.innerHTML += `<label>Distance for Passenger ${i} (km):</label>
                                        <input type="number" name="distance_${i}" required><br>`;
            }
        });
        </script>
</body>

</html>
