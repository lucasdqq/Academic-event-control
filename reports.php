<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Fetch users
$users_sql = "SELECT id, name, email FROM users";
$users_result = $conn->query($users_sql);
if (!$users_result) {
    die("Erro na consulta de usuários: " . $conn->error);
}

// Fetch courses
$courses_sql = "SELECT id, title FROM courses";
$courses_result = $conn->query($courses_sql);
if (!$courses_result) {
    die("Erro na consulta de cursos: " . $conn->error);
}

// Fetch events
$events_sql = "SELECT id, title, date, start_time, end_time FROM events";
$events_result = $conn->query($events_sql);
if (!$events_result) {
    die("Erro na consulta de eventos: " . $conn->error);
}

// Fetch enrollments
$enrollments_sql = "SELECT u.name, c.title AS course_title FROM enrollments e
                    JOIN users u ON e.user_id = u.id
                    JOIN courses c ON e.course_id = c.id";
$enrollments_result = $conn->query($enrollments_sql);
if (!$enrollments_result) {
    die("Erro na consulta de inscrições: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Relatórios</h1>
        <a href="home.php">Voltar para a Página Inicial</a>
        <h2>Relatório de Usuários Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome de Usuário</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Relatório de Cursos Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($course = $courses_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['id']); ?></td>
                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Relatório de Eventos Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Horário</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($event = $events_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['id']); ?></td>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['date']); ?></td>
                        <td><?php echo htmlspecialchars(date('H:i', strtotime($event['start_time']))) . ' - ' . htmlspecialchars(date('H:i', strtotime($event['end_time']))); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Relatório de Inscrições</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome de Usuário</th>
                    <th>Evento</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($enrollment = $enrollments_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($enrollment['name']); ?></td>
                        <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
