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

// Дописать и раскидать на все страницы (хедер)
require_once 'api/helpers/getUserType.php';

$userType = getUserType($DB);

if ($userType === 'tech') {
    echo "
        <li><a href='tech.php'>Обращения пользователя</a></li>
    ";
}
?>
            </ul>
            <a href="?do=logout" class="header__logout">Выйти</a>
        </div>
    </header>
    <main class="main">
        <section class="main__filters">
            <div class="container">
                <form action="" method="GET" class="main__form">
                    <select class="main__select" name="search_name" id="search_name">
                        <option value="name" <?php echo ($_GET['search_name'] ?? '') === 'name' ? 'selected' : ''; ?>>Поиск по имени</option>
                        <option value="email" <?php echo ($_GET['search_name'] ?? '') === 'email' ? 'selected' : ''; ?>>Поиск по почте</option>
                    </select>
                    <input <?php InputDefaultValue('search', ''); ?> class="main__input" type="text" id="search" name="search" placeholder="Александр">
                    <select class="main__select" name="sort" id="sort">
                        <option value="0" <?php echo ($_GET['sort'] ?? '') === '0' ? 'selected' : ''; ?>>По умолчанию</option>
                        <option value="1" <?php echo ($_GET['sort'] ?? '') === '1' ? 'selected' : ''; ?>>По возрастанию</option>
                        <option value="2" <?php echo ($_GET['sort'] ?? '') === '2' ? 'selected' : ''; ?>>По убыванию</option>
                    </select>
                    <button type="submit">Поиск</button>
                    <a href="?" class="main__reset">Сбросить</a>
                </form>
            </div>
        </section>
        <section class="main__clients">
            <div class="container">
                <h2 class="main__clients__title">Список клиентов</h2>
                <button class="main__clients__add" onclick="MicroModal.show('add-modal')"><i class="fa fa-plus-circle"></i></button>
                <?php
                    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $maxClients = 5;

                    $countClients = $DB->query("
                    SELECT COUNT(*) as count FROM clients")
                    ->fetchAll()[0]['count'];

                    $maxPage = ceil($countClients / $maxClients);
                    $minPage = 1;

                    // Build pagination URL with preserved search parameters
                    $searchParams = '';
                    if (isset($_GET['search_name'])) {
                        $searchParams .= '&search_name=' . urlencode($_GET['search_name']);
                    }
                    if (isset($_GET['search'])) {
                        $searchParams .= '&search=' . urlencode($_GET['search']);
                    }
                    if (isset($_GET['sort'])) {
                        $searchParams .= '&sort=' . urlencode($_GET['sort']);
                    }

                    // Normalize currentPage
                    if ($currentPage < $minPage || !is_numeric($currentPage)) {
                        $currentPage = $minPage;
                        header("Location: ?page=$currentPage" . $searchParams);
                        exit;
                    }
                    if ($currentPage > $maxPage) {
                        $currentPage = $maxPage;
                        header("Location: ?page=$currentPage" . $searchParams);
                        exit;
                    }
                    
                    // Wrap pagination in container
                    echo "<div class='pagination-container'>";
                    
                    // Always show prev button, but disable if on first page
                    $prevDisabled = ($currentPage <= $minPage) ? " disabled" : "";
                    $Prev = $currentPage - 1;
                    echo "<a href='?page=$Prev" . $searchParams . "'$prevDisabled><i class='fa fa-arrow-left' aria-hidden='true'></i></a>";

                    // Show numbered pagination buttons
                    echo "<div class='pagination'>";
                    for ($i = 1; $i <= $maxPage; $i++) {
                        $activeClass = ($i === $currentPage) ? " class='active'" : "";
                        echo "<a href='?page=$i" . $searchParams . "'$activeClass>$i</a>";
                    }
                    echo "</div>";

                    // Always show next button, but disable if on last page
                    $nextDisabled = ($currentPage >= $maxPage) ? " disabled" : "";
                    $Next = $currentPage + 1;
                    echo "<a href='?page=$Next" . $searchParams . "'$nextDisabled><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";

                    echo "</div>"; // Close pagination-container
                ?>
                <table>
                    <thead>
                        <th>ИД</th>
                        <th>ФИО</th>
                        <th>Почта</th>
                        <th>Телефон</th>
                        <th>День рождения</th>
                        <th>Дата создания</th>
                        <th>История заказов</th>
                        <th>Редактировать</th>
                        <th>Удалить</th>
                    </thead>
                    <tbody>
                        <?php
                            require 'api/DB.php';
                            require_once('api/clients/OutputClients.php');
                            require_once('api/clients/ClientsSearch.php');

                            $clients = ClientsSearch($_GET, $DB);
                        
                            OutputClients($clients);

                        ?>
                    </tbody>
            </table>
            </div>
        </section>
    </main>
    <div class="modal micromodal-slide" id="add-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
          <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
            <header class="modal__header">
              <h2 class="modal__title" id="modal-1-title">
                Добавить клиента
              </h2>
              <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
            </header>
            <main class="modal__content" id="modal-1-content">
                <form action="api/clients/AddClients.php" method="POST" class="modal__form">
                    <div class="modal__form-group">
                        <label for="fullname">ФИО</label>
                        <input type="text" id="fullname" name="fullname">
                    </div>
                    <div class="modal__form-group">
                        <label for="email">Почта</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="modal__form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="modal__form-group">
                        <label for="birthday">День рождения</label>
                        <input type="date" id="birthday" name="birthday">
                    </div>
                    <div class="modal__form-actions">
                        <button type="submit" class="modal__btn modal__btn-primary">Создать</button>
                        <button type="button" class="modal__btn modal__btn-secondary" data-micromodal-close>Отменить</button>
                    </div>
                </form>
            </main>
          </div>
        </div>
      </div>
      <div class="modal micromodal-slide" id="delete-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
          <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
            <header class="modal__header">
              <h2 class="modal__title" id="modal-1-title">
                Вы уверены, что хотите удалить клиента?
              </h2>
              <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
            </header>
            <main class="modal__content" id="modal-1-content">
                <button class="modal__btn danger">Удалить</button>
                <button class="modal__btn" data-micromodal-close>Отменить</button>
            </main>
          </div>
        </div>
      </div>
      <!-- 1.Редактировать клиента
       2.1.1 Пофиксить удаление параметров поиска
       3.Редактирование товара
       4.Редактирование заказа -->
       <div class="modal micromodal-slide 
    <?php 
        if(isset($_GET['edit-user']) && !empty($_GET['edit-user']) && isset($_SESSION['show_modal']) && $_SESSION['show_modal']) {
            echo 'open';
            unset($_SESSION['show_modal']); // Сбрасываем флаг после открытия
        }
    ?>" id="edit-modal" aria-hidden="true">
    
    <div class="modal__overlay" tabindex="-1" data-micromodal-close>
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
            <header class="modal__header">
                <h2 class="modal__title" id="modal-1-title">
                    Редактировать клиента
                </h2>
                <button class="modal__close" aria-label="Close modal" onclick="clearUrlAndClose()" data-micromodal-close></button>
            </header>
            <main class="modal__content">
            <?php
$fullname = $email = $phone = "";

// Подключение к БД
$host = "127.0.0.1"; // Или ваш хост
$dbname = "crm"; // Имя базы данных
$username = "root"; // Ваш логин
$password = ""; // Ваш пароль

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (isset($_GET['edit-user']) && !empty($_GET['edit-user'])) {
    $user_id = intval($_GET['edit-user']); // Приводим к числу для безопасности

    // SQL-запрос на получение данных пользователя
    $sql = "SELECT name, email, phone FROM clients WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Проверяем, найден ли пользователь
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fullname = $user['name'];
        $email = $user['email'];
        $phone = $user['phone'];
    }

    $stmt->close();
}

$conn->close();
?>
                
                <form class="modal__form" action="api/clients/EditClients.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_GET['edit-user']); ?>">
                    
                    <div class="modal__form-group">
                        <label for="fullname">ФИО</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                    </div>
                    
                    <div class="modal__form-group">
                        <label for="email">Почта</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="modal__form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                    </div>
                    
                    <div class="modal__form-actions">
                        <button type="submit" class="modal__btn modal__btn-primary">Сохранить</button>
                        <button type="button" class="modal__btn modal__btn-secondary" onclick="clearUrlAndClose()" data-micromodal-close>Отменить</button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</div>
      <div class="modal micromodal-slide" id="history-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        История покупок
                    </h2>
                    <small>Фамилия Имя Отчество</small>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="modal-1-content">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>ID заказа</th>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Цена</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Товар 1</td>
                                <td>2</td>
                                <td>1000₽</td>
                                <td>12.01.2024</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Товар 2</td>
                                <td>1</td>
                                <td>500₽</td>
                                <td>15.01.2024</td>
                            </tr>
                        </tbody>
                    </table>
                </main>
            </div>
        </div>
    </div>
    <div class="modal micromodal-slide" id="send-email-modal" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1" data-micromodal-close>
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
            <header class="modal__header">
                <h2 class="modal__title" id="modal-1-title">
                    Отправка письма
                </h2>   
                <button class="modal__close" aria-label="Закрыть" data-micromodal-close></button>
            </header>
            <main class="modal__content" id="modal-1-content">
                <form action="api/clients/SendEmail.php?email=<?php echo htmlspecialchars($_GET['send-email']); ?>" method="POST">
                    <div class="modal__form-group">
                        <label for="header">Обращение</label>
                        <input type="text" id="header" name="header" required>
                    </div>
                    <div class="modal__form-group">
                        <label for="main">Тело письма</label>
                        <textarea id="main" name="main" rows="5" required></textarea>
                    </div>
                    <div class="modal__form-group">
                        <label for="footer">Футер</label>
                        <input type="text" id="footer" name="footer">
                    </div>
                    <div class="modal__form-actions">
                        <button type="submit" class="modal__btn modal__btn-primary">Отправить</button>
                        <button type="button" class="modal__btn modal__btn-secondary" data-micromodal-close>Отменить</button>
                    </div>
                </form>

                <?php 
                if (isset($_GET['send-email']) && !empty($_GET['send-email'])) {
                    echo '<p>Email получателя: ' . htmlspecialchars($_GET['send-email']) . '</p>';
                }
                ?>
            </main>
        </div>
    </div>
</div>
<button class="support-btn"><i class="fa fa-question-circle fa-3x" aria-hidden="true"></i></button>
<div class="support-create-tickets">
    <button class="close-create-ticket">X</button>
        <form action="api/tickets/CreateTickets.php" method="POST">
                        <label for="type">Тип обращение</label>
                       <select name="type" id="type">
                        <option value="tech">Техническая неполадка</option>
                        <option value="crm">Проблема с crm</option>
                       </select>
                        <label for="message">Текст обращения</label>
                        <textarea name="message" id="message"></textarea>
                        <input type="file" id="files" name="files">
                        <button type="submit">Создать тикет</button>
        </form>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let params = new URLSearchParams(window.location.search);
    if (params.has("send-email")) {
        MicroModal.show("send-email-modal");
    }
});
</script>

    <script defer src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <script defer src="scripts/initClientsModal.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function() {
    let params = new URLSearchParams(window.location.search);
    if (params.has("edit-user")) {
        MicroModal.show("edit-modal");
    }
});

function clearUrlAndClose() {
    let newUrl = window.location.origin + window.location.pathname;
    window.history.pushState({}, document.title, newUrl);
    MicroModal.close("edit-modal");
}
</script>
</body>
</html>