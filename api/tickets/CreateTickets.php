<?php
require_once '../DB.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST['type'] ?? '';
    $message = $_POST['message'] ?? '';

    if (!empty($type) && !empty($message)) {
        $stmt = $DB->prepare("
            INSERT INTO tickets (type, message, created_at) 
            VALUES (?, ?, NOW())
        ");
        $success = $stmt->execute([$type, $message]);

        if ($success) {
            header("Location: ../../clients.php?success=1"); // Укажите правильный путь к вашей странице
            exit();
        } else {
            header("Location: ../../clients.php?error=Ошибка при создании тикета");
            exit();
        }
    } else {
        header("Location: ../../clients.php?error=Заполните все поля");
        exit();
    }
} else {
    header("Location: ../../clients.php?error=Неверный метод запроса");
    exit();
}
