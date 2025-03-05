<?php
include 'database.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SHOW TABLES LIKE 'week_%'";
$weeksResult = $conn->query($sql);

$max_week_id = 0;
if ($weeksResult->num_rows > 0) {
    while ($row = $weeksResult->fetch_array()) {
        $table_name = $row[0];
        $table_week_id = intval(str_replace('week_', '', $table_name));
        if ($table_week_id > $max_week_id && substr($table_week_id, -2) >= substr($max_week_id, -2)) {
            $max_week_id = $table_week_id;
        }
    }
}

$week_id = isset($_GET['week_id']) ? intval($_GET['week_id']) : $max_week_id;
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'points_this_week';
$sort_direction = isset($_GET['sort_direction']) ? $_GET['sort_direction'] : 'desc';

// Validate sort column and direction
$valid_columns = ['name', 'time', 'percentage', 'points_this_week', 'total_points'];
if (!in_array($sort_column, $valid_columns)) {
    $sort_column = 'time';
}
if ($sort_direction !== 'asc' && $sort_direction !== 'desc') {
    $sort_direction = 'asc';
}

// Toggle sorting direction
$new_sort_direction = $sort_direction === 'asc' ? 'desc' : 'asc';

$sql = "SELECT d.name, w.time, w.percentage, w.points_this_week, w.total_points
        FROM week_$week_id w
        JOIN drivers d ON w.name = d.id
        ORDER BY $sort_column $sort_direction";


$stmt = $conn->prepare($sql);

$stmt->execute();
$result = $stmt->get_result();


$leaderboard = [];
while ($row = $result->fetch_assoc()) {
    $leaderboard[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="style_dark.css">
</head>
<body>
    <h1>Hustle Leaderboard Week <?= substr($week_id, 0, 2) ?>, 20<?= substr($week_id, -2)?></h1>

    <!-- Dropdown to select the week -->
    <form method="GET" action="index.php">
        <label for="week_id">Select Week:</label>
        <select name="week_id" id="week_id" onchange="this.form.submit()">
            <?php 
                $sql = "SHOW TABLES LIKE 'week_%'";
                $conn = new mysqli($servername, $username, $password, $dbname);
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_array()) {
                        $table_name = $row[0];
                        $table_week_id = str_replace('week_', '', $table_name);
                        $week_number = substr($table_week_id, 0, 2);
                        if (substr($table_name, -4, -3) == '_') {
                            $week_number = substr($table_week_id, 0, 1);
                        }
                        $year = substr($table_week_id, -2);
                        echo "<option value='$table_week_id' " . ($week_id == $table_week_id ? 'selected' : '') . ">Week $week_number, 20$year</option>";
                    }
                }
            ?>
        </select>
        <input type="hidden" name="sort_column" value="<?= $sort_column ?>">
        <input type="hidden" name="sort_direction" value="<?= $sort_direction ?>">
    </form>


    <!-- Display the leaderboard -->
    <table>
        <tbody>
            <tr>
                <th>
                    <a href="?week_id=<?= $week_id ?>&sort_column=name&sort_direction=<?= ($sort_column == 'name') ? $new_sort_direction : 'asc' ?>">
                        Name <?= $sort_column == 'name' ? ($sort_direction == 'asc' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?week_id=<?= $week_id ?>&sort_column=time&sort_direction=<?= ($sort_column == 'time') ? $new_sort_direction : 'asc' ?>">
                        Time (min) <?= $sort_column == 'time' ? ($sort_direction == 'asc' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?week_id=<?= $week_id ?>&sort_column=percentage&sort_direction=<?= ($sort_column == 'percentage') ? $new_sort_direction : 'asc' ?>">
                        Percentage <?= $sort_column == 'percentage' ? ($sort_direction == 'asc' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?week_id=<?= $week_id ?>&sort_column=points_this_week&sort_direction=<?= ($sort_column == 'points_this_week') ? $new_sort_direction : 'asc' ?>">
                        Points This Week <?= $sort_column == 'points_this_week' ? ($sort_direction == 'asc' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?week_id=<?= $week_id ?>&sort_column=total_points&sort_direction=<?= ($sort_column == 'total_points') ? $new_sort_direction : 'asc' ?>">
                        Total Points <?= $sort_column == 'total_points' ? ($sort_direction == 'asc' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
            </tr>
            <tr>
                <?php foreach ($leaderboard as $row): ?>
                    <tr>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['time'] ?></td>
                        <td><?= $row['percentage'] ?></td>
                        <td><?= $row['points_this_week'] ?></td>
                        <td><?= $row['total_points'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
</body>
</html>