<?php
// PostgreSQL connection settings
$host = "localhost";
$port = "5432";
$dbname = "postgres";
$user = "prajith";
$password = "charizard";

// Connect
$connString = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_connect($connString);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// Fetch subject names
$subjectNames = [];
$subjectQuery = "SELECT subject_name FROM subjects";
$subjectResult = pg_query($conn, $subjectQuery);

if ($subjectResult) {
    while ($row = pg_fetch_assoc($subjectResult)) {
        $subjectNames[] = $row['subject_name'];
    }
} else {
    die("Error fetching subjects: " . pg_last_error());
}

$students = [];
$studentQuery = "
    SELECT s.student_id as roll_no,
           s.name as student_name, 
           sub.subject_name, 
           m.mid1,
           m.mid2,
           m.marks_obtained
    FROM students s
    JOIN marks m ON s.student_id = m.student_id
    JOIN subjects sub ON m.subject_id = sub.subject_id
    ORDER BY s.name, sub.subject_name
";
$studentResult = pg_query($conn, $studentQuery);

if ($studentResult) {
    while ($row = pg_fetch_assoc($studentResult)) {
        $rollNo = $row['roll_no'];
        $studentName = $row['student_name'];
        $subject = $row['subject_name'];
        $mark = $row['marks_obtained'];
        $mid1 = $row['mid1'];
        $mid2 = $row['mid2'];

        // Store marks, mid1, and mid2 for each student
        $students[$studentName]['roll_no'] = $rollNo;
        $students[$studentName]['marks'][$subject] = $mark;
        $students[$studentName]['mid1'][$subject] = $mid1;
        $students[$studentName]['mid2'][$subject] = $mid2;
    }
} else {
    die("Error fetching student marks: " . pg_last_error());
}

pg_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Marks Table</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        td:first-child {
            text-align: left;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center">Student Marks Table</h2>
    <table>
        <tr>
            <th rowspan="2">Roll No</th>
            <th rowspan="2">Student Name</th>
            <?php foreach ($subjectNames as $subject): ?>
                <th colspan="3"><?php echo htmlspecialchars($subject); ?></th>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($subjectNames as $subject): ?>
                <th>Mid 1</th>
                <th>Mid 2</th>
                <th>Marks Obtained</th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($students as $studentName => $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                <td><?php echo htmlspecialchars($studentName); ?></td>
                <?php foreach ($subjectNames as $subject): ?>
                    <td><?php echo isset($student['mid1'][$subject]) ? htmlspecialchars($student['mid1'][$subject]) : 'N/A'; ?></td>
                    <td><?php echo isset($student['mid2'][$subject]) ? htmlspecialchars($student['mid2'][$subject]) : 'N/A'; ?></td>
                    <td><?php echo isset($student['marks'][$subject]) ? htmlspecialchars($student['marks'][$subject]) : 'N/A'; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Select Subject to View Graph</h2>
    <select id="subjectSelect" onchange="updateGraph()">
        <option value="">--Select a Subject--</option>
        <?php foreach ($subjectNames as $subject): ?>
            <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
        <?php endforeach; ?>
    </select>

    <h2>Class Performance for Selected Subject</h2>
    <canvas id="marksChart" width="400" height="200"></canvas>

    <script>
        const studentNames = <?php echo json_encode(array_keys($students)); ?>;
        const subjectNames = <?php echo json_encode($subjectNames); ?>;
        const studentMarks = <?php 
            $marks = [];
            foreach ($students as $studentName => $student) {
                foreach ($subjectNames as $subject) {
                    $marks[$studentName][$subject] = isset($student['marks'][$subject]) ? $student['marks'][$subject] : 0;
                }
            }
            echo json_encode($marks);
        ?>;

        const studentMid1 = <?php 
            $mid1 = [];
            foreach ($students as $studentName => $student) {
                foreach ($subjectNames as $subject) {
                    $mid1[$studentName][$subject] = isset($student['mid1'][$subject]) ? $student['mid1'][$subject] : 0;
                }
            }
            echo json_encode($mid1);
        ?>;

        const studentMid2 = <?php 
            $mid2 = [];
            foreach ($students as $studentName => $student) {
                foreach ($subjectNames as $subject) {
                    $mid2[$studentName][$subject] = isset($student['mid2'][$subject]) ? $student['mid2'][$subject] : 0;
                }
            }
            echo json_encode($mid2);
        ?>;

        let chart; // global chart variable

        function updateGraph() {
            const selectedSubject = document.getElementById('subjectSelect').value;
            
            // If no subject is selected, hide the graph
            if (!selectedSubject) {
                document.getElementById('marksChart').style.display = 'none';
                return;
            }

            const dataMid1 = studentNames.map(studentName => studentMid1[studentName][selectedSubject]);
            const dataMid2 = studentNames.map(studentName => studentMid2[studentName][selectedSubject]);
            const dataMarks = studentNames.map(studentName => studentMarks[studentName][selectedSubject]);
            
            const ctx = document.getElementById('marksChart').getContext('2d');
            if (chart) {
                chart.destroy(); // Destroy the previous chart before creating a new one
            }

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: studentNames,
                    datasets: [
                        {
                            label: `Mid 1`,
                            data: dataMid1,
                            backgroundColor: 'rgba(255, 99, 132, 0.6)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        },
                        {
                            label: `Mid 2`,
                            data: dataMid2,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: `Total Marks`,
                            data: dataMarks,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
