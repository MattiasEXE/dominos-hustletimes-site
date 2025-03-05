<?php
include 'database.php';

$percentageThreshold = 95.0;
$iterator = 0;

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all drivers
$driversSQL = "SELECT * FROM drivers";
$driversResult = $conn->query($driversSQL);

if (!$driversResult) {
    die("Error fetching drivers: " . $conn->error);
}

$conn->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['csv_file'])) {
        $week = intval($_POST['week']);
        $year = intval($_POST['year']);
        $weekYear = sprintf("%02d%02d", $week, $year % 100);
        if ($week < 10) {
            $weekYear = sprintf("%d%02d", $week, $year % 100);
        }

        $tableName = "week_" . $weekYear;

        // Database connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Create new table
        $createTableSQL = "CREATE TABLE IF NOT EXISTS $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            time TIME NOT NULL,
            percentage FLOAT NOT NULL,
            points_this_week INT NOT NULL,
            total_points INT NOT NULL
        )";

        if (!$conn->query($createTableSQL)) {
            die("Error creating table: " . $conn->error);
        } else {
            echo "Table $tableName created successfully.<br>";
        }

        // Process CSV file
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $csvFile = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $data = [];

            // Read CSV and store data
            while (($row = fgetcsv($csvFile, 0, ';')) !== FALSE) {
                // Assuming CSV columns: name, time, percentage
                $name = isset($row[1]) ? $row[1] : 0;
                $time = isset($row[9]) ? $row[9] : "00:00";
                $percentage = isset($row[19]) ? floatval($row[19]) : 0.0;

                $data[] = [
                    'name' => $name,
                    'time' => $time,
                    'percentage' => $percentage
                ];
            }
            fclose($csvFile);

            // Sort by time (ascending order)
            usort($data, function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            });

            // Assign points to the top 5 fastest times
            $pointsAllocation = [5, 4, 3, 2, 1];
            foreach ($data as $index => $entry) {
                $pointsThisWeek = 0;
                
                // Check if this entry is in the top 5
                if ($index < 5 + $iterator && $entry['percentage'] >= $percentageThreshold) {
                    $pointsThisWeek = $pointsAllocation[$index - $iterator];
                } elseif ($index < 5 + $iterator && !($entry['percentage'] >= $percentageThreshold)) {
                    $iterator = $iterator + 1;
                }

                // Update total points in drivers table
                $pointQuery = "UPDATE drivers SET points = points + $pointsThisWeek WHERE id = '" . $conn->real_escape_string($entry['name']) . "'";
                if (!$conn->query($pointQuery)) {
                    die("Error updating points: " . $conn->error);
                }

                // Get total points
                $pointQuery = "SELECT points FROM drivers WHERE id = '" . $conn->real_escape_string($entry['name']) . "'";
                $pointResult = $conn->query($pointQuery);
                if (!$pointResult) {
                    die("Error retrieving total points: " . $conn->error);
                }

                if ($pointResult->num_rows > 0) {
                    $row = $pointResult->fetch_assoc();
                    $totalPoints = $row['points'];
                } else {
                    die("Driver with id " . $entry['name'] . " not found.");
                }

                // Insert data into current week table
                $insertSQL = "INSERT INTO $tableName (name, time, percentage, points_this_week, total_points) 
                              VALUES ('" . $conn->real_escape_string($entry['name']) . "', '" . $conn->real_escape_string($entry['time']) . "', " . $entry['percentage'] . ", $pointsThisWeek, $totalPoints)";
                if (!$conn->query($insertSQL)) {
                    die("Error inserting data: " . $conn->error);
                }
            }
            echo "CSV data successfully processed.";
        } else {
            echo "Error uploading CSV file.";
        }

        $conn->close();
    } elseif (isset($_POST['action'])) {
        // Database connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        if ($_POST['action'] == 'add_driver') {
            $driverId = intval($_POST['driver_id']);
            $driverName = $conn->real_escape_string($_POST['driver_name']);

            $insertDriverSQL = "INSERT INTO drivers (id, name, points) VALUES ($driverId, '$driverName', 0)";
            if (!$conn->query($insertDriverSQL)) {
                die("Error adding driver: " . $conn->error);
            }
            echo "Driver successfully added.";
            
        } elseif ($_POST['action'] == 'remove_driver') {
            $driverId = intval($_POST['remove_driver_id']);

            $deleteDriverSQL = "DELETE FROM drivers WHERE id = $driverId";
            if (!$conn->query($deleteDriverSQL)) {
                die("Error removing driver: " . $conn->error);
            }
            echo "Driver successfully removed.";

        } elseif ($_POST['action'] == 'reset_total_points') {
            $resetPointsSQL = "UPDATE drivers SET points=0 WHERE 1";
            if (!$conn->query($resetPointsSQL)) {
                die("Error resetting points: " . $conn->error);
            }
            echo "Total points successfully reset";
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Upload</title>
    <link rel="stylesheet" href="generate.css">
</head>
<body>
    <h2>Instructions for Using This Tool</h2>
    <p>
        To use this tool, please follow these steps: </br> 
        If something goes wrong, please contact your manager (they will know what to do or who to talk to).
    </p>

    <h3>Uploading a new week</h3>
    <ol>
        <li>
            Open 'rapporten' on chrome. Navigate to where you find the husstle times and percentages
        </li>
        <li>
            Copy (select from bottom-right to top left entry, ctrl+c)
        </li>
        <li>
            Paste the two tables next to each other in a new Excel sheet. 
            Ensure that the columns are aligned properly.
        </li>
        <li>
            Your table should look something like this:
            <pre>
            | Store ID | Driver ID | Monday | ... | Sunday | Week Avg | Store ID | Driver ID | Monday | ... | Sunday | Week Avg |
            </pre>
        </li>
        <li>
            After ensuring that the data is structured correctly, save the Excel file.
        </li>
        <li>
            Convert the Excel file to a CSV file:
            <ul>
                <li>In Excel, go to <strong>File</strong> > <strong>Save As</strong>.</li>
                <li>Choose the location where you want to save the file.</li>
                <li>In the <strong>Save as type</strong> dropdown, select <strong>CSV (Comma delimited) (*.csv)</strong>.</li>
                <li>Click <strong>Save</strong>.</li>
                <li>If prompted about features not compatible with CSV format, click <strong>Yes</strong>.</li>
            </ul>
        </li>
        <li>
            Enter the week number and year below.
        </li>
        <li>
            Select the CSV file you just created by clicking <strong>browse</strong> below.
        </li>
        <li>
            Hit upload, points are calculated automatically.
        </li>
        
    
    </ol>

    <h3>Note:</h3>
    <p>
        Ensure that all performance metrics are in numeric format. Entries with percentages below the threshold will not receive any points, so make sure to review the data accordingly.
    </p>


    <h1>Upload CSV and Input Week Number and Year</h1>
    <form action="generate.php" method="post" enctype="multipart/form-data">
        <label for="week">Week Number:</label>
        <input type="number" id="week" name="week" required>
        <br>
        <label for="year">Year:</label>
        <input type="number" id="year" name="year" required>
        <br>
        <label for="csv_file">CSV File:</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <br>
        <input type="submit" value="Upload">
    </form>

    <!-- Table of All Drivers -->
    <div class="table-wrapper">
        <h2>Driver List</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Driver ID</th>
                    <th>Driver Name</th>
                    <th>Total Points</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($driversResult->num_rows > 0) {
                    while ($row = $driversResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['points']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No drivers found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <h2>Add Driver</h2>
    <p>If a driver id is not known to the system, you can add it here</p>
    <ol>
        <li>
            Input the driver id that is not in the system yet.
        </li>
        <li>
            Input the corresponding name.
        </li>
        <li>
            Hit add.
        </li>
    </ol>
    <form action="generate.php" method="post">
        <input type="hidden" name="action" value="add_driver">
        <label for="driver_id">Driver ID:</label>
        <input type="number" id="driver_id" name="driver_id" required>
        <br>
        <label for="driver_name">Driver Name:</label>
        <input type="text" id="driver_name" name="driver_name" required>
        <br>
        <input type="submit" value="Add Driver">
    </form>

    <h2>Remove Driver</h2>
    <form action="generate.php" method="post">
        <input type="hidden" name="action" value="remove_driver">
        <label for="remove_driver_id">Driver ID:</label>
        <input type="number" id="remove_driver_id" name="remove_driver_id" required>
        <br>
        <input type="submit" value="Remove Driver">
    </form>
    
    <h2>RESET PUNTEN</h2>
    <p>If you want to reset the points, do this below. THIS ACTION IS NOT REVERSABLE!!!!<br> This resets the total points from now on, so the total points of previous weeks are still available.</p>
    <form action="generate.php" method="post">
        <input type="hidden" name="action" value="reset_total_points">
        <label for="reset">Reset total points: HANDLE WITH CARE</label>
        <input type="submit" value="reset" name="reset">
    </form>

</body>
</html>
