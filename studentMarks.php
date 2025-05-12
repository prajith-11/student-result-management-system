<?php
// Connect to PostgreSQL
$connection = pg_connect("host=localhost dbname=postgres user=prajith password=charizard");

if (!$connection) {
    echo "Connection error.";
    exit;
}

$result = pg_query($connection, "
    SELECT s.subject_name, m.mid1, m.mid2, m.marks_obtained
    FROM marks m
    JOIN subjects s ON s.subject_id = m.subject_id
    WHERE m.student_id = 1;  -- Add a placeholder for the student_id, or use a specific ID value
");

if (!$result) {
    echo "Error retrieving data";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sample Table</title>
    <style>
        /* Basic CSS to add borders, bold headers, center align data, and set header background color */
        table {
            width: 50%;
            border-collapse: collapse;
            margin: 20px auto;
        }

        th, td {
            border: 1px solid black; /* Add border to every cell */
            padding: 8px;
            text-align: center; /* Center-align data */
        }

        th {
            font-weight: bold; /* Make table headers bold */
            background-color: #f2f2f2; /* Light grey background for headers */
        }
    </style>
</head>
<body>
<h2 style="text-align:center">  Your Marks </h2>
<table>
    <thead>
        <tr>
            <th>Subject</th>
            <th>Mid 1</th>
            <th>Mid 2</th>
            <th>Total Marks</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch and display rows
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mid1']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mid2']) . "</td>";
            echo "<td>" . htmlspecialchars($row['marks_obtained']) . "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>
