<?php session_start();

if (isset($_GET['do']) && $_GET['do'] === 'logout') {
    require_once 'api/auth/LogoutUser.php';
    require_once 'api/DB.php';

    LogoutUser('login.php', $DB, $_SESSION['token']);

    exit;
}

require_once 'api/auth/AuthCheck.php';

AuthCheck('', 'login.php');

require_once 'api/helpers/InputDefaultValue.php';

// Обработка состояния статуса заказов
if (isset($_GET["search_status"])) {
    $_SESSION["search_status"] = $_GET["search_status"];
} else if (!isset($_SESSION["search_status"])) {
    $_SESSION["search_status"] = "all"; // По умолчанию показываем все заказы
}

// Обработка кнопки сброса
if (isset($_GET['reset'])) {
    $_SESSION["search_status"] = "all"; // По умолчанию показываем все заказы
    header("Location: orders.php");
    exit;
}

// Добавляем параметры в URL пагинации
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
if (isset($_SESSION['search_status'])) {
    $searchParams .= '&search_status=' . urlencode($_SESSION['search_status']);
}

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
    <title>CRM | Заказы</title>
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
            </ul>
            <a href="?do=logout" class="header__logout">Выйти</a>
        </div>
    </header>
    <main class="main">
        <section class="main__filters">
            <div class="container">
                <form action="" class="main__form">
                    <label class="main__label" for="search">Поиск по заказу</label>
                    <input <?php InputDefaultValue('search', ''); ?> class="main__input" type="text" id="search" name="search" placeholder="Поиск...">
                    <select class="main__select" name="search_name" id="search_name">
                        <option value="client.name" <?php echo ($_GET['search_name'] ?? '') === 'client.name' ? 'selected' : ''; ?>>По клиенту</option>
                        <option value="orders.id" <?php echo ($_GET['search_name'] ?? '') === 'orders.id' ? 'selected' : ''; ?>>По ID</option>
                        <option value="orders.order_date" <?php echo ($_GET['search_name'] ?? '') === 'orders.order_date' ? 'selected' : ''; ?>>По дате</option>
                        <option value="orders.total" <?php echo ($_GET['search_name'] ?? '') === 'orders.total' ? 'selected' : ''; ?>>По сумме</option>
                        <option value="orders.status" <?php echo ($_GET['search_name'] ?? '') === 'orders.status' ? 'selected' : ''; ?>>По статусу</option>
                    </select>
                    <select class="main__select" name="sort" id="sort">
                        <option value="0" <?php echo ($_GET['sort'] ?? '') === '0' ? 'selected' : ''; ?>>По умолчанию</option>
                        <option value="1" <?php echo ($_GET['sort'] ?? '') === '1' ? 'selected' : ''; ?>>По возрастанию</option>
                        <option value="2" <?php echo ($_GET['sort'] ?? '') === '2' ? 'selected' : ''; ?>>По убыванию</option>
                    </select>
                    <div class="filter-controls">
                        <label>
                            <select class="main__select" name="search_status" id="search_status">
                                <option value="all" <?php echo ($_SESSION["search_status"] === "all" ? "selected" : ""); ?>>Все заказы</option>
                                <option value="1" <?php echo ($_SESSION["search_status"] === "1" ? "selected" : ""); ?>>Активные заказы</option>
                                <option value="0" <?php echo ($_SESSION["search_status"] === "0" ? "selected" : ""); ?>>Неактивные заказы</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit">Поиск</button>
                    <a href="?" class="main__reset" onclick="' . session_unset() . '">Сбросить</a>
                </form>
            </div>
        </section>
        <section class="main__clients">
            <div class="container">
                <h2 class="main__clients__title">Список заказов</h2>
                <div class="main__clients__controls">
                    <button class="main__clients__add" onclick="MicroModal.show('add-modal')"><i class="fa fa-plus-circle"></i></button>
                </div>
                <table>
                    <thead>
                        <th>ИД</th>
                        <th>Менеджер</th>
                        <th>ФИО</th>
                        <th>Дата заказа</th>
                        <th>Общая сумма</th>
                        <th>Состав заказа</th>
                        <th>Статус</th>
                        <th>Чек</th>
                        <th>Редактировать</th>
                        <th>Удалить</th>
                    </thead>
                    <tbody>
                        <?php
                            require 'api/DB.php';
                            require_once 'api/orders/OutputOrders.php';
                            require_once 'api/orders/OrdersSearch.php';

                            // Подсчет общего количества записей с учетом фильтров
                            $search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                            $whereClause = "";
                            if (!empty($search)) {
                                $whereClause = "WHERE (LOWER(clients.name) LIKE '%$search%' OR LOWER(products.name) LIKE '%$search%')";
                            }

                            // Добавляем условие статуса для подсчета
                            if ($_SESSION["search_status"] == '1') {
                                $whereClause = $whereClause ? $whereClause . " AND orders.status = '1'" : "WHERE orders.status = 1";
                            } elseif ($_SESSION["search_status"] == '0') {
                                $whereClause = $whereClause ? $whereClause . " AND orders.status = '0'" : "WHERE orders.status = 0";
                            }

                            $countQuery = "SELECT COUNT(DISTINCT orders.id) as count 
                                           FROM orders 
                                           JOIN clients ON orders.client_id = clients.id 
                                           JOIN order_items ON orders.id = order_items.order_id 
                                           JOIN products ON order_items.product_id = products.id 
                                           $whereClause";

                            $countOrders = $DB->query($countQuery)->fetchAll()[0]['count'];

                            $per_page = 5; // Количество записей на странице
                            $maxPage = ceil($countOrders / $per_page);
                            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

                            // Проверка и корректировка текущей страницы
                            if ($currentPage < 1) {
                                $currentPage = 1;
                            } elseif ($currentPage > $maxPage) {
                                $currentPage = $maxPage;
                            }

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

                            // Wrap pagination in container
                            echo "<div class='pagination-container'>";
                            
                            // Кнопка "Предыдущая"
                            $prevDisabled = ($currentPage <= 1) ? " disabled" : "";
                            $prevPage = $currentPage - 1;
                            echo "<a href='?page=$prevPage$searchParams'$prevDisabled><i class='fa fa-arrow-left' aria-hidden='true'></i></a>";

                            // Номера страниц
                            echo "<div class='pagination'>";
                            for ($i = 1; $i <= $maxPage; $i++) {
                                $activeClass = ($i === $currentPage) ? " class='active'" : "";
                                echo "<a href='?page=$i$searchParams'$activeClass>$i</a>";
                            }
                            echo "</div>";

                            // Кнопка "Следующая"
                            $nextDisabled = ($currentPage >= $maxPage) ? " disabled" : "";
                            $nextPage = $currentPage + 1;
                            echo "<a href='?page=$nextPage$searchParams'$nextDisabled><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";

                            echo "</div>";

                            $orders = OrdersSearch($_GET, $DB);
                            OutputOrders($orders);
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Модальное окно для редактирования статуса заказа -->
    <div class="modal micromodal-slide 
        <?php 
            if(isset($_GET['edit-order']) && !empty($_GET['edit-order']) && isset($_SESSION['show_modal']) && $_SESSION['show_modal']) {
                echo 'open';
                unset($_SESSION['show_modal']); // Сбрасываем флаг после открытия
            }
        ?>" id="edit-order-modal" aria-hidden="true">
        
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="modal-1-title">
                        Редактировать статус заказа
                    </h2>
                    <button class="modal__close" aria-label="Close modal" onclick="clearUrlAndClose()" data-micromodal-close></button>
                </header>
                <main class="modal__content">
                <?php
                $status = "";

                // Подключение к БД
                $host = "127.0.0.1"; // Или ваш хост
                $dbname = "crm"; // Имя базы данных
                $username = "root"; // Ваш логин
                $password = ""; // Ваш пароль

                $conn = new mysqli($host, $username, $password, $dbname);
                if ($conn->connect_error) {
                    die("Ошибка подключения: " . $conn->connect_error);
                }

                if (isset($_GET['edit-order']) && !empty($_GET['edit-order'])) {
                    $order_id = intval($_GET['edit-order']); // Приводим к числу для безопасности

                    // SQL-запрос на получение статуса заказа
                    $sql = "SELECT status FROM orders WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // Проверяем, найден ли заказ
                    if ($result->num_rows > 0) {
                        $order = $result->fetch_assoc();
                        $status = $order['status'];
                    }
                    $stmt->close();
                }
                $conn->close();
                ?>
                <form class="modal__form" action="api/orders/EditOrders.php" method="POST">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($_GET['edit-order']); ?>">
                    
                    <div class="modal__form-group">
                        <label for="status">Статус</label>
                        <select id="status" name="status" required>
                            <option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>Активный</option>
                            <option value="0" <?php echo ($status == 0) ? 'selected' : ''; ?>>Неактивный</option>
                        </select>
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
    <script defer src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <script defer src="scripts/orders.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let params = new URLSearchParams(window.location.search);
            if (params.has("edit-order")) {
                MicroModal.show("edit-order-modal");
            }
        });

        function clearUrlAndClose() {
            let newUrl = window.location.origin + window.location.pathname;
            window.history.pushState({}, document.title, newUrl);
            MicroModal.close("edit-order-modal");
        }
    </script>
</body>
</html>