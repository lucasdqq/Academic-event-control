<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$credits_sql = "SELECT e.title as event_title, e.date, 10 as credits 
                FROM event_participation ep 
                JOIN events e ON ep.event_id = e.id 
                WHERE ep.user_id = ?";
$stmt = $conn->prepare($credits_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$credits_result = $stmt->get_result();

$total_credits = 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meus Créditos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Meus Créditos</h1>
        <ul>
            <?php while ($credit = $credits_result->fetch_assoc()): 
                $total_credits += $credit['credits'];
            ?>
                <li>
                    Evento: <?php echo htmlspecialchars($credit['event_title']); ?><br>
                    Data: <?php echo htmlspecialchars($credit['date']); ?><br>
                    Créditos: <?php echo $credit['credits']; ?>
                </li>
            <?php endwhile; ?>
        </ul>
        <p>Total de Créditos: <?php echo $total_credits; ?></p>
        <a href="home.php">Voltar</a>
    </div>
</body>
</html>
