<?php
// PostgreSQL connection settings
$host = "localhost";
$dbname = "postgres";
$user = "prajith";
$password = "charizard";

// Create connection
$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// SQL query to get student marks for subject_id = 1
$query = "
    SELECT 
        s.name, 
        m.marks_obtained, 
        m.mid1, 
        m.mid2
    FROM marks m
    JOIN students s ON m.student_id = s.student_id
    JOIN sample sm ON sm.sno = s.student_id
    WHERE m.subject_id = 1
    ORDER BY s.name;
";

$result = pg_query($conn, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Variables to calculate averages
$mid1_total = 0;
$mid2_total = 0;
$marks_total = 0;
$num_students = 0;

while ($row = pg_fetch_assoc($result)) {
    $mid1_total += (float)$row['mid1'];
    $mid2_total += (float)$row['mid2'];
    $marks_total += (float)$row['marks_obtained']; // Use marks_obtained for the total
    $num_students++;
}

// Calculate averages
$mid1_avg = $mid1_total / $num_students;
$mid2_avg = $mid2_total / $num_students;
$marks_avg = $marks_total / $num_students; // Average of marks_obtained

// Reset the result pointer to fetch the students again for the table
pg_result_seek($result, 0);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Marks</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: auto;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px 12px;
            text-align: center;
        }
        th {
            background-color: #ddd;
        }
        h2 {
            text-align: center;
        }
        .highlight-yellow {
            background-color: #fff8b3;
        }
        .highlight-red {
            background-color: #f8b3b3;
        }
        #chart-container {
            width: 80%;
            margin: auto;
            padding-top: 30px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Marks of Students of Class A for Maths</h2>
    <table>
        <thead>
            <tr>
                <th>S. No</th>
                <th>Name</th>
                <th>Mid 1</th>
                <th>Mid 2</th>
                <th>Average</th>
                <th>Total Marks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sno = 1;
            while ($row = pg_fetch_assoc($result)) {
                $mid1 = (float)$row['mid1'];
                $mid2 = (float)$row['mid2'];
                $marks_obtained = (float)$row['marks_obtained']; // Use marks_obtained for the total
                $average = intdiv($mid1 + $mid2, 2);

                // Shading logic based on Mid 1 and Mid 2 scores
                $highlightClass = '';
                if ($average < 10 || ($mid1 < 10 && $mid2 < 10)) {
                    $highlightClass = 'highlight-red'; // Red for scores less than 10
                } elseif ($average < 20 || ($mid1 < 20 && $mid2 < 20)) {
                    $highlightClass = 'highlight-yellow'; // Yellow for scores less than 20
                }

                echo "<tr>";
                echo "<td>" . $sno++ . "</td>";
                echo "<td class='$highlightClass'>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($mid1) . "</td>";
                echo "<td>" . htmlspecialchars($mid2) . "</td>";
                echo "<td>" . htmlspecialchars($average) . "</td>";
                echo "<td>" . htmlspecialchars($marks_obtained) . "</td>"; // Display the marks_obtained value
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <div id="chart-container">
        <canvas id="marksChart"></canvas>
    </div>

    <script>
        // Data for the new chart
        var ctx = document.getElementById('marksChart').getContext('2d');
        var marksChart = new Chart(ctx, {
            type: 'bar',  // Bar chart
            data: {
                labels: ['Mid 1', 'Mid 2', 'Total'], // Labels for the 3 datasets
                datasets: [{
                    label: 'Class Average Marks',
                    data: [<?php echo number_format($mid1_avg, 2); ?>, <?php echo number_format($mid2_avg, 2); ?>, <?php echo number_format($marks_avg, 2); ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
// Close connection
pg_close($conn);
?>
