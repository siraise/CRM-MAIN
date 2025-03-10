<?php
require_once '../DB.php'; // Подключение к БД

// Проверяем, существует ли соединение с БД
if (!isset($DB)) {
    die("Ошибка: соединение с базой данных не установлено.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем данные из формы
    $id = $_POST['user_id'] ?? null;
    $fullname = trim($_POST['fullname'] ?? '');  // Поле для ФИО
    $email = trim($_POST['email'] ?? '');  // Поле для почты
    $phone = trim($_POST['phone'] ?? '');  // Поле для телефона
    
    // Проверяем, что все поля заполнены
    if ($id && $fullname && $email && $phone) {
        try {
            // Подготовленный запрос для обновления данных
            $stmt = $DB->prepare("UPDATE clients SET name = :fullname, email = :email, phone = :phone WHERE id = :id");
            $stmt->execute([
                ':fullname' => $fullname,
                ':email' => $email,
                ':phone' => $phone,
                ':id' => $id
            ]);

            // Проверяем, были ли обновлены данные
            if ($stmt->rowCount() > 0) {
                // Если обновление успешно, редиректим на clients.php
                header("Location: ../../clients.php"); 
                exit; // Завершаем выполнение скрипта
            } else {
                echo "Ошибка обновления или данные не изменились.";
            }
        } catch (PDOException $e) {
            echo "Ошибка при обновлении данных: " . $e->getMessage();
        }
    } else {
        echo "Ошибка: все поля должны быть заполнены.";
    }
} else {
    echo "Ошибка: некорректный метод запроса.";
}
?>
