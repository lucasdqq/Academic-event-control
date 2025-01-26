<?php
include('db_connection.php');

$user_id = 1; // Replace with the logged-in user's ID

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $event_id = $_POST['event_id'];

    // Check for conflicting enrollments
    $conflict_check_sql = "SELECT * FROM enrollments 
                           JOIN events ON enrollments.event_id = events.id
                           WHERE enrollments.user_id = '$user_id' 
                           AND events.date = (SELECT date FROM events WHERE id = '$event_id')
                           AND events.time = (SELECT time FROM events WHERE id = '$event_id')";

    $conflict_check_result = $conn->query($conflict_check_sql);

    if ($conflict_check_result->num_rows > 0) {
        echo "Cannot enroll: Event conflicts with another enrolled event.";
    } else {
        $enroll_sql = "INSERT INTO enrollments (user_id, course_id, event_id) VALUES ('$user_id', '$course_id', '$event_id')";
        if ($conn->query($enroll_sql) === TRUE) {
            echo "Enrollment successful!";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Fetch courses and associated events
$courses_sql = "SELECT * FROM courses";
$courses_result = $conn->query($courses_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enroll</title>
</head>
<body>
    <h1>Enroll in a Course</h1>
    <form method="POST">
        Course: 
        <select name="course_id" required>
            <?php while($course = $courses_result->fetch_assoc()): ?>
                <option value="<?php echo $course['id']; ?>"><?php echo $course['title']; ?></option>
            <?php endwhile; ?>
        </select><br>
        Event:
        <select name="event_id" required>
            <?php 
            $events_sql = "SELECT * FROM events";
            $events_result = $conn->query($events_sql);
            while($event = $events_result->fetch_assoc()): ?>
                <option value="<?php echo $event['id']; ?>"><?php echo $event['title']; ?></option>
            <?php endwhile; ?>
        </select><br>
        <button type="submit">Enroll</button>
    </form>
</body>
</html>
