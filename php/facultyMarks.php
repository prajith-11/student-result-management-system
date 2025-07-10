<?php
// Connect to PostgreSQL
$connection = pg_connect("host=localhost dbname=postgres user=prajith password=charizard");

if (!$connection) {
    echo "Connection error.";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rowCount = $_POST['row_count'] ?? 0;

    for ($i = 1; $i <= $rowCount; $i++) {
        if (isset($_POST["save_" . $i])) {
            if (isset($_POST["sno_" . $i])) {
                $sno = $_POST["sno_" . $i];
                $name = $_POST["name_" . $i];

                $mid1_obj = isset($_POST["mid1_obj_" . $i]) && $_POST["mid1_obj_" . $i] !== '' ? round(floatval($_POST["mid1_obj_" . $i])) : null;
                $mid1_sub = isset($_POST["mid1_sub_" . $i]) && $_POST["mid1_sub_" . $i] !== '' ? round(floatval($_POST["mid1_sub_" . $i])) : null;
                $mid2_obj = isset($_POST["mid2_obj_" . $i]) && $_POST["mid2_obj_" . $i] !== '' ? round(floatval($_POST["mid2_obj_" . $i])) : null;
                $mid2_sub = isset($_POST["mid2_sub_" . $i]) && $_POST["mid2_sub_" . $i] !== '' ? round(floatval($_POST["mid2_sub_" . $i])) : null;
                $internal = isset($_POST["internal_" . $i]) && $_POST["internal_" . $i] !== '' ? round(floatval($_POST["internal_" . $i])) : null;

                $mid1_total = ($mid1_obj !== null && $mid1_sub !== null) ? $mid1_obj + $mid1_sub : null;
                $mid2_total = ($mid2_obj !== null && $mid2_sub !== null) ? $mid2_obj + $mid2_sub : null;

                $average = null;
                if ($mid1_total !== null && $mid2_total !== null) {
                    $average = round(($mid1_total + $mid2_total) / 2);
                }

                $total_grading = null;
                if ($average !== null && $internal !== null) {
                    $total_grading = $average + $internal;
                }

                if ($total_grading !== null) {
                    $subject_id = 1;

                    $mark_query = "
                        INSERT INTO marks (student_id, subject_id, mid1, mid2, marks_obtained)
                        VALUES ($1::int, $2::int, $3::int, $4::int, $5::int)
                        ON CONFLICT (student_id, subject_id) DO UPDATE
                        SET mid1 = EXCLUDED.mid1, mid2 = EXCLUDED.mid2, marks_obtained = EXCLUDED.marks_obtained;
                    ";
                    $res1 = pg_query_params($connection, $mark_query, [$sno, $subject_id, $mid1_total, $mid2_total, $total_grading]);

                    $sample_query = "
                        INSERT INTO sample (sno, name, mid1_obj, mid1_sub, mid2_obj, mid2_sub, internal, total_grading)
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
                        ON CONFLICT (sno) DO UPDATE
                        SET name = EXCLUDED.name, mid1_obj = EXCLUDED.mid1_obj, mid1_sub = EXCLUDED.mid1_sub,
                            mid2_obj = EXCLUDED.mid2_obj, mid2_sub = EXCLUDED.mid2_sub,
                            internal = EXCLUDED.internal, total_grading = EXCLUDED.total_grading;
                    ";
                    $res2 = pg_query_params($connection, $sample_query, [$sno, $name, $mid1_obj, $mid1_sub, $mid2_obj, $mid2_sub, $internal, $total_grading]);

                    if (!$res1 || !$res2) {
                        error_log("Database insert/update failed for student $sno");
                    }
                }
            }
        }

        if (isset($_POST["delete_" . $i])) {
            $sno = $_POST["sno_" . $i];
            pg_query_params($connection, "DELETE FROM sample WHERE sno = $1", [$sno]);
        }
    }
}

$students_query = pg_query($connection, "SELECT student_id, name FROM students ORDER BY student_id ASC");
$students = [];
while ($row = pg_fetch_assoc($students_query)) {
    $students[$row['student_id']] = $row['name'];
}

$sample_query = pg_query($connection, "SELECT * FROM sample");
$samples = [];
while ($row = pg_fetch_assoc($sample_query)) {
    $samples[$row['sno']] = $row;
}
?>

<head>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            width: 95%;
            border-collapse: collapse;
            margin: auto;
        }
        th, td {
            border: 1px solid #555;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #eee;
        }
        input[type=number] {
            width: 60px;
        }
        .table-wrapper {
            margin-top: 30px;
        }
    </style>
</head>

<div class="table-wrapper">
    <h3 style="text-align: center">Update Marks for Maths of Class A</h3>
    <form method="post" action="facultyMarks.php">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Roll No</th>
                    <th rowspan="2">Name</th>
                    <th colspan="3">Mid 1</th>
                    <th colspan="3">Mid 2</th>
                    <th rowspan="2">Average</th>
                    <th rowspan="2">Internal</th>
                    <th rowspan="2">Total Grading</th>
                    <th rowspan="2">Actions</th>
                </tr>
                <tr>
                    <th>Objective</th>
                    <th>Subjective</th>
                    <th>Total</th>
                    <th>Objective</th>
                    <th>Subjective</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($students as $sno => $name) {
                    $row_data = $samples[$sno] ?? [];

                    $mid1_obj = $row_data['mid1_obj'] ?? '';
                    $mid1_sub = $row_data['mid1_sub'] ?? '';
                    $mid2_obj = $row_data['mid2_obj'] ?? '';
                    $mid2_sub = $row_data['mid2_sub'] ?? '';
                    $internal = $row_data['internal'] ?? '';
                    $total_grading = $row_data['total_grading'] ?? '';

                    $mid1_total = ($mid1_obj !== '' && $mid1_sub !== '') ? ($mid1_obj + $mid1_sub) : '';
                    $mid2_total = ($mid2_obj !== '' && $mid2_sub !== '') ? ($mid2_obj + $mid2_sub) : '';
                    $average = ($mid1_total !== '' && $mid2_total !== '') ? round(($mid1_total + $mid2_total) / 2) : '';

                    echo "
                        <tr>
                            <td><input type='hidden' name='sno_{$i}' value='{$sno}' /><span>{$sno}</span></td>
                            <td><input type='hidden' name='name_{$i}' value='{$name}' /><span>{$name}</span></td>
                            <td><input type='number' name='mid1_obj_{$i}' value='{$mid1_obj}' min='0' max='10'></td>
                            <td><input type='number' name='mid1_sub_{$i}' value='{$mid1_sub}' min='0' max='20'></td>
                            <td>{$mid1_total}</td>
                            <td><input type='number' name='mid2_obj_{$i}' value='{$mid2_obj}' min='0' max='10'></td>
                            <td><input type='number' name='mid2_sub_{$i}' value='{$mid2_sub}' min='0' max='20'></td>
                            <td>{$mid2_total}</td>
                            <td>{$average}</td>
                            <td><input type='number' name='internal_{$i}' value='{$internal}' min='0' max='10'></td>
                            <td>{$total_grading}</td>
                            <td><input type='submit' name='save_{$i}' value='Save'></td>
                        </tr>
                    ";
                    $i++;
                }
                echo "<input type='hidden' name='row_count' value='" . count($students) . "' />";
                ?>
            </tbody>
        </table>
    </form>
</div>
