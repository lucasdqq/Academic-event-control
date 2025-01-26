<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

// Handle course enrollment request
if (isset($_POST['enroll_course'])) {
    $course_id = $_POST['course_id'];
    $check_enrollment_sql = "SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?";
    $stmt = $conn->prepare($check_enrollment_sql);
    $stmt->bind_param('ii', $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $enroll_sql = "INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($enroll_sql);
        $stmt->bind_param('ii', $user_id, $course_id);
        $stmt->execute();
        echo "<p>Você foi inscrito com sucesso no evento.</p>";
    } else {
        echo "<p>Você já está inscrito neste evento.</p>";
    }
}

// Handle event enrollment request
if (isset($_POST['enroll_event'])) {
    $event_id = $_POST['event_id'];
    
    // Get event details
    $get_event_sql = "SELECT course_id, date, start_time, end_time FROM events WHERE id = ?";
    $stmt = $conn->prepare($get_event_sql);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $stmt->bind_result($course_id, $event_date, $event_start_time, $event_end_time);
    $stmt->fetch();
    $stmt->close();

    // Check if the user is enrolled in the course
    $check_enrollment_sql = "SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?";
    $stmt = $conn->prepare($check_enrollment_sql);
    $stmt->bind_param('ii', $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Check if the user is already enrolled in the event
        $check_event_enrollment_sql = "SELECT * FROM event_participation WHERE user_id = ? AND event_id = ?";
        $stmt = $conn->prepare($check_event_enrollment_sql);
        $stmt->bind_param('ii', $user_id, $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Check if the user is already enrolled in another event on the same date with overlapping time
            $check_event_conflict_sql = "SELECT * FROM event_participation ep 
                                         JOIN events e ON ep.event_id = e.id
                                         WHERE ep.user_id = ? 
                                           AND e.date = ? 
                                           AND (e.start_time < ? AND e.end_time > ?)";
            $stmt = $conn->prepare($check_event_conflict_sql);
            $stmt->bind_param('isss', $user_id, $event_date, $event_end_time, $event_start_time);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Proceed with enrollment
                $enroll_sql = "INSERT INTO event_participation (user_id, event_id) VALUES (?, ?)";
                $stmt = $conn->prepare($enroll_sql);
                $stmt->bind_param('ii', $user_id, $event_id);
                $stmt->execute();
                echo "<p>Você foi inscrito com sucesso no curso.</p>";
            } else {
                echo "<p>Você já está inscrito em outro curso no mesmo horário deste.</p>";
            }
        } else {
            echo "<p>Você já está inscrito neste curso.</p>";
        }
    } else {
        echo "<p>Você deve estar matriculado no evento relacionado ao curso para se inscrever.</p>";
    }

    $stmt->close();
}

// Handle event unenrollment request
if (isset($_POST['unenroll_event'])) {
    $event_id = $_POST['event_id'];
    $unenroll_sql = "DELETE FROM event_participation WHERE user_id = ? AND event_id = ?";
    $stmt = $conn->prepare($unenroll_sql);
    $stmt->bind_param('ii', $user_id, $event_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "<p>Você foi removido com sucesso do curso.</p>";
    } else {
        echo "<p>Você não está inscrito neste curso.</p>";
    }
    $stmt->close();
}

// Fetch courses and events
$courses_sql = "SELECT id, title FROM courses";
$courses_result = $conn->query($courses_sql);

$events_sql = "SELECT e.id as event_id, e.title as event_title, e.description, e.date, e.start_time, e.end_time, c.title as course_title 
               FROM events e 
               JOIN courses c ON e.course_id = c.id";
$events_result = $conn->query($events_sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .btn-red {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        .btn-red:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>
<div class="container">
        <h1>Bem-vindo</h1>
        <a href="credits.php">Ver Créditos</a> | 
        <a href="edit_user.php">Editar Perfil</a> | 
        <a href="index.php">Deslogar</a> |
        <?php if ($is_admin): ?>
            <a href="event_management.php">Gerenciar Eventos</a> | 
            <a href="course_management.php">Gerenciar Cursos</a> | 
            <a href="reports.php">Relatórios</a> |
        <?php endif; ?>
        <a href="Sobre.php">Sobre</a>
        <h2>Eventos Disponíveis</h2>
        <ul>
            <?php while ($course = $courses_result->fetch_assoc()): ?>
                <li>
                    <?php echo htmlspecialchars($course['title']); ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <button type="submit" name="enroll_course">Inscrever-se</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>

        <h2>Próximos Cursos</h2>
        <ul>
            <?php while ($event = $events_result->fetch_assoc()): ?>
                <li>
                    <strong><?php echo htmlspecialchars($event['event_title']); ?></strong><br>
                    Evento: <?php echo htmlspecialchars($event['course_title']); ?><br>
                    Data: <?php echo htmlspecialchars($event['date']); ?><br>
                    Horário: 
                    <?php 
                        $start_time = isset($event['start_time']) ? date('H:i', strtotime($event['start_time'])) : 'Não definido'; 
                        $end_time = isset($event['end_time']) ? date('H:i', strtotime($event['end_time'])) : 'Não definido'; 
                    ?>
                    <?php echo htmlspecialchars($start_time); ?> - <?php echo htmlspecialchars($end_time); ?><br>
                    Descrição: <?php echo htmlspecialchars($event['description']); ?>
                    <br><form method="post" style="display:inline;">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" name="enroll_event">Inscrever-se</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" name="unenroll_event" class="btn-red">Sair do Curso</button>
                    </form>
                    <br><br><br><br><br>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</body>
</html>
