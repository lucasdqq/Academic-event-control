<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Buscar cursos existentes
$courses_sql = "SELECT id, title FROM courses";
$courses_result = $conn->query($courses_sql);

// Buscar cursos existentes
$events_sql = "SELECT e.id, e.title, e.description, e.date, e.start_time, e.end_time, c.title as course_title 
               FROM events e 
               JOIN courses c ON e.course_id = c.id";
$events_result = $conn->query($events_sql);

// Criar curso
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_event'])) {
        $course_id = $_POST['course_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $stmt = $conn->prepare("INSERT INTO events (title, description, date, start_time, end_time, course_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $description, $date, $start_time, $end_time, $course_id);

        if ($stmt->execute()) {
            $message = "Curso criado com sucesso!";
        } else {
            $message = "Erro ao criar o curso: " . $stmt->error;
        }

        $stmt->close();
    }

    // Excluir curso
    if (isset($_POST['delete_event'])) {
        $event_id = $_POST['event_id'];

        // Iniciar uma transação
        $conn->begin_transaction();

        try {
            // remover todas as inscrições no curso
            $delete_participation_sql = "DELETE FROM event_participation WHERE event_id = ?";
            $stmt = $conn->prepare($delete_participation_sql);
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $stmt->close();

            // excluir o curso
            $delete_event_sql = "DELETE FROM events WHERE id = ?";
            $stmt = $conn->prepare($delete_event_sql);
            $stmt->bind_param("i", $event_id);

            if ($stmt->execute()) {
                $conn->commit();
                $message = "Curso excluído com sucesso!";
            } else {
                throw new Exception("Erro ao excluir o curso: " . $stmt->error);
            }

            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Não foi possível excluir o curso: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Cursos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Gerenciar Cursos</h2>
        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

        <h3>Criar Novo Curso</h3>
        <form method="POST">
            <label for="course_id">Escolha o evento que o curso será parte:</label>
            <select id="course_id" name="course_id" required>
                <?php while ($course = $courses_result->fetch_assoc()): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                <?php endwhile; ?>
            </select><br>
            <label for="title">Título do Curso:</label>
            <input type="text" id="title" name="title" required><br>
            <label for="description">Descrição:</label>
            <textarea id="description" name="description" required></textarea><br>
            <label for="date">Data:</label>
            <input type="date" id="date" name="date" required><br>
            <label for="start_time">Horário de Início:</label>
            <input type="time" id="start_time" name="start_time" required><br>
            <label for="end_time">Horário de Término:</label>
            <input type="time" id="end_time" name="end_time" required><br>
            <button type="submit" name="create_event">Criar Curso</button>
        </form>

        <h3>Excluir Curso</h3>
        <form method="POST">
            <label for="event_id">Selecione o Curso para Excluir:</label>
            <select id="event_id" name="event_id" required>
                <?php while ($event = $events_result->fetch_assoc()): ?>
                    <option value="<?php echo $event['id']; ?>">
                        <?php echo htmlspecialchars($event['title']); ?> - <?php echo htmlspecialchars($event['course_title']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
            <button type="submit" name="delete_event">Excluir Curso</button>
        </form>

        <a href="home.php">Voltar</a>
    </div>
</body>
</html>
