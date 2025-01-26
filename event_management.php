<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Criar Evento
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_course'])) {
        $course_name = $_POST['course_name'];

        $stmt = $conn->prepare("INSERT INTO courses (title) VALUES (?)");
        $stmt->bind_param("s", $course_name);

        if ($stmt->execute()) {
            $message = "Evento criado com sucesso!";
        } else {
            $message = "Erro ao criar o evento: " . $stmt->error;
        }

        $stmt->close();
    }

    // Excluir Evento
    if (isset($_POST['delete_course'])) {
        $course_id = $_POST['course_id'];

        // Verificar se há eventos associados ao evento
        $check_events_sql = "SELECT COUNT(*) FROM events WHERE course_id = ?";
        $stmt = $conn->prepare($check_events_sql);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $stmt->bind_result($event_count);
        $stmt->fetch();
        $stmt->close();

        if ($event_count > 0) {
            $message = "Não foi possível excluir o evento, pois há cursos dependentes dele.";
        } else {
        // Excluir inscrições associadas ao curso
        $delete_enrollments_sql = "DELETE FROM enrollments WHERE course_id = ?";
        $stmt = $conn->prepare($delete_enrollments_sql);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $stmt->close();

        // Excluir o curso após excluir as inscrições
        $delete_course_sql = "DELETE FROM courses WHERE id = ?";
        $stmt = $conn->prepare($delete_course_sql);
        $stmt->bind_param("i", $course_id);

        if ($stmt->execute()) {
            $message = "Evento excluído com sucesso!";
        } else {
            $message = "Erro ao excluir o evento: " . $stmt->error;
        }

        $stmt->close();
        }
    }
}

// Buscar Eventos
$courses_sql = "SELECT id, title FROM courses";
$courses_result = $conn->query($courses_sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Eventos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Gerenciar Eventos</h2>
        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>
        
        <h3>Criar Novo Evento</h3>
        <form method="POST">
            <label for="course_name">Nome do Evento:</label>
            <input type="text" id="course_name" name="course_name" required><br>
            <button type="submit" name="create_course">Criar Evento</button>
        </form>
        
        <h3>Excluir Evento</h3>
        <form method="POST">
            <label for="course_id">Selecione o Evento para Excluir:</label>
            <select id="course_id" name="course_id" required>
                <?php while ($course = $courses_result->fetch_assoc()): ?>
                    <option value="<?php echo $course['id']; ?>">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
            <button type="submit" name="delete_course">Excluir Evento</button>
        </form>

        <a href="home.php">Voltar</a>
    </div>
</body>
</html>
