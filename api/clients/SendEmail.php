<?php
// Подключение к базе данных
$host = '127.0.0.1';
$dbname = 'crm';
$username = 'root'; // Измените при необходимости
$password = ''; // Измените при необходимости

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Функция для получения имени пользователя по email
function getUserNameByEmail($email, $pdo) {
    $stmt = $pdo->prepare("SELECT name FROM clients WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['name'] : '';
}

// Проверяем существование файла изображения
$imagePath = '../../img/hoh.jpg';
if (file_exists($imagePath)) {
    $backgroundImage = 'url("' . $imagePath . '")';
} else {
    echo "Ошибка: Файл не найден по пути: " . $imagePath;
    $backgroundImage = 'none';
}
// Почта пользователя
$email = $_GET['email'] ?? '';

// Получение имени пользователя из БД, если оно не передано через POST
$userName = $_POST['userName'] ?? getUserNameByEmail($email, $pdo);

// Элементы страницы
$header = $_POST['header'] ?? 'Дорогие коллеги!';
$main = $_POST['main'] ?? 'Описание отсутствует';
$footer = $_POST['footer'] ?? 'СИБИРЬ БЛИЖЕ, ЧЕМ ВЫ ДУМАЕТЕ...';
// 

// Разметка страницы
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/phpmailer/phpmailer/src/Exception.php';
require '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../../vendor/phpmailer/phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = 'smtp.mail.ru';
$mail->SMTPAuth = true;
$mail->Username = 'dima.haunov@mail.ru';
$mail->Password = 'ikW5x1urvtS6bnm7afNp';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
// Почта отправителя
$mail->setFrom('dima.haunov@mail.ru', 'Daniyar');
// Почта получателя
$mail->addAddress('matviei.maksimov@bk.ru', 'Matviei Maksimov');
$mail->isHTML(true);
$mail->Subject = 'Сообщение';
$mail->CharSet = 'UTF-8';
$html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Сибирский гостинец</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background-image:$backgroundImage !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(249, 249, 249, 0.95);
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            color: #4a2c2c;
        }
        .content {
            line-height: 1.6;
            color: #333;
            text-align: center;
            max-width: 80%;
            margin: 0 auto;
            padding: 20px 0;
        }
        .contact-info {
            margin-top: 30px;
            color: #666;
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #333;
        }
        h2 {
            color: #4a2c2c;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='logo'>
            <h1>Сибирский гостинец</h1>
        </div>
        <div class='content'>
        <h2>Здравстуйте , $userName</h2>
            <h2>$header</h2>
            <p>$main</p>
            <p>Больше полезной информации о нашей компании и продукте вы найдете в презентации во вложении (либо <a href='#'>по ссылке</a>).</p>
        </div>
        <div class='contact-info'>
            <p>(3462) 77-40-59</p>
            <p>info@sg-trade.ru</p>
            <p>628406, РФ, ХМАО-Югра,</p>
            <p>г. Сургут, ул. Университетская, 4</p>
        </div>
        <div class='footer'>
            <h2>$footer</h2>
            <h2>СИБИРЬ БЛИЖЕ, ЧЕМ ВЫ ДУМАЕТЕ...</h2>
        </div>
    </div>
</body>
</html>";
$mail->Body = $html;
$mail->send();
header('Location: ../../clients.php');
?>

