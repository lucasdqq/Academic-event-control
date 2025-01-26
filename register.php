<?php
include('db_connection.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $matricula = $_POST['matricula'];
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Verificar se o e-mail já está em uso
    $email_check_sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($email_check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "Erro: O e-mail já está em uso.";
    } else {
        // Verificar se a matrícula já está em uso
        $matricula_check_sql = "SELECT id FROM users WHERE matricula = ?";
        $stmt = $conn->prepare($matricula_check_sql);
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Erro: A matrícula já está em uso.";
        } else {
            // Inserir o novo usuário
            $stmt = $conn->prepare("INSERT INTO users (name, email, matricula, password, is_admin) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $email, $matricula, $password, $is_admin);

            if ($stmt->execute()) {
                $message = "Cadastro realizado com sucesso!";
            } else {
                $message = "Erro: " . $stmt->error;
            }
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-buttons {
            margin-top: 10px;
        }
        .message {
            color: #28a745; /* Green for success messages */
        }
        .error {
            color: #dc3545; /* Red for error messages */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cadastro</h2>
        <?php if (!empty($message)): ?>
            <p class="<?php echo strpos($message, 'Erro:') === 0 ? 'error' : 'message'; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="name">Nome:</label>
            <input type="text" id="name" name="name" required><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>
            <label for="matricula">Matrícula:</label>
            <input type="text" id="matricula" name="matricula" required><br>
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required><br>
            <label><input type="checkbox" name="is_admin"> Registrar como Administrador</label><br>
            <button type="submit">Cadastrar</button>
        </form>
        <div class="form-buttons">
            <a href="index.php">Voltar para Login</a>
        </div>
    </div>
</body>
</html>
