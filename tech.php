<?php session_start();

if (isset($_GET['do']) && $_GET['do'] === 'logout') {
    require_once 'api/auth/LogoutUser.php';
    require_once 'api/DB.php';

    LogoutUser('login.php', $DB, $_SESSION['token']);

    exit;
}

require_once 'api/auth/AuthCheck.php';
require_once 'api/helpers/InputDefaultValue.php';
require_once 'api/clients/ClientsSearch.php';

AuthCheck('', 'login.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/clients.css">
    <link rel="stylesheet" href="styles/modules/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="styles/modules/micromodal.css">
    <title>CRM | Клиенты</title>
    <style>
        .ticket-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
            display: none; /* Скрываем все карточки по умолчанию */
        }
        .ticket-card.active {
            display: block; /* Показываем только активную карточку */
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination button {
            margin: 0 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <p class="header__admin">
                <?php 
                    require 'api/DB.php';
                    require_once 'api/clients/AdminName.php';

                    echo AdminName($_SESSION['token'], $DB);
                ?>
            </p>
            <ul class="header__links">
                <li><a href="clients.php">Клиенты</a></li>
                <li><a href="product.php">Товары</a></li>
                <li><a href="orders.php">Заказы</a></li>
                <?php
                require_once 'api/helpers/getUserType.php';

                $userType = getUserType($DB);

                if ($userType === 'tech') {
                    echo "<li><a href='tech.php'>Обращения пользователя</a></li>";
                }
                ?>
            </ul>
            <a href="?do=logout" class="header__logout">Выйти</a>
        </div>
    </header>

    <div id="ticket-container"></div>
    <div class="pagination">
        <button id="prev-ticket">← Назад</button>
        <span id="ticket-info"></span>
        <button id="next-ticket">Вперед →</button>
    </div>

    <script>
        // Получаем данные о карточках из PHP
        const ticketsData = <?php
            require_once 'api/DB.php';

            // Запрос для получения всех обращений
            $query = "
                SELECT 
                    t.id AS ticket_id,
                    t.type AS ticket_type,
                    t.message AS ticket_message,
                    t.created_at AS ticket_created_at,
                    CASE 
                        WHEN t.client = 0 THEN 'Клиент не указан'
                        ELSE CONCAT(u.name, ' ', u.surname)
                    END AS client_full_name,
                    CASE 
                        WHEN t.admin IS NULL THEN 'Админ не привязан'
                        ELSE CONCAT(a.name, ' ', a.surname)
                    END AS admin_full_name
                FROM tickets t
                LEFT JOIN users u ON t.client = u.id
                LEFT JOIN users a ON t.admin = a.id
                ORDER BY t.created_at DESC
            ";

            // Выполняем запрос
            try {
                $tickets = $DB->query($query);
                $ticketsArray = [];
                while ($ticket = $tickets->fetch(PDO::FETCH_ASSOC)) {
                    $ticketsArray[] = [
                        'id' => $ticket['ticket_id'],
                        'type' => $ticket['ticket_type'],
                        'message' => $ticket['ticket_message'],
                        'client' => $ticket['client_full_name'],
                        'admin' => $ticket['admin_full_name'],
                        'created_at' => $ticket['ticket_created_at']
                    ];
                }
                echo json_encode($ticketsArray);
            } catch (PDOException $e) {
                echo json_encode([]); // Возвращаем пустой массив в случае ошибки
            }
        ?>;

        let currentTicketIndex = 0;

        function renderTicket(index) {
            const ticketContainer = document.getElementById('ticket-container');
            ticketContainer.innerHTML = ''; // Очищаем контейнер

            if (ticketsData.length === 0) {
                ticketContainer.innerHTML = '<p>Нет доступных карточек</p>';
                return;
            }

            // Проверяем, находится ли индекс в допустимых пределах
            if (index < 0 || index >= ticketsData.length) {
                return;
            }

            const ticket = ticketsData[index];
            const ticketHTML = `
                <div class="ticket-card active">
                    <p><strong>ID:</strong> ${ticket.id}</p>
                    <p><strong>Тип:</strong> ${ticket.type === 'tech' ? 'Технические неполадки' : 'Проблема с CRM'}</p>
                    <p><strong>Текст ошибки:</strong> ${ticket.message}</p>
                    <p><strong>ФИО клиента:</strong> ${ticket.client}</p>
                    <p><strong>ФИО администратора:</strong> ${ticket.admin}</p>
                    <p><strong>Дата создания:</strong> ${ticket.created_at}</p>
                    <p><strong>Статус:</strong> Ожидает</p>
                </div>
            `;

            ticketContainer.innerHTML = ticketHTML;
            document.getElementById('ticket-info').textContent = `Карточка ${index + 1} из ${ticketsData.length}`;
        }

        document.getElementById('prev-ticket').addEventListener('click', () => {
            if (currentTicketIndex > 0) {
                currentTicketIndex--;
                renderTicket(currentTicketIndex);
            }
        });

        document.getElementById('next-ticket').addEventListener('click', () => {
            if (currentTicketIndex < ticketsData.length - 1) {
                currentTicketIndex++;
                renderTicket(currentTicketIndex);
            }
        });

        // Инициализация первой карточки
        renderTicket(currentTicketIndex);
    </script>
</body>
</html>