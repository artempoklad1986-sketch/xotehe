<?php
/**
 * Страница авторизации
 */

session_start();

// Если уже авторизован - редирект
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

require_once 'Database.php';

$error = '';
$debug = ''; // Для отладки

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        try {
            $db = new Database();
            $usersData = $db->get('users');

            // Отладка
            $debug .= "Users data exists: " . (($usersData && isset($usersData['users'])) ? 'YES' : 'NO') . "<br>";
            if ($usersData && isset($usersData['users'])) {
                $debug .= "Total users: " . count($usersData['users']) . "<br>";
            }

            if ($usersData && isset($usersData['users'])) {
                $userFound = false;
                foreach ($usersData['users'] as $user) {
                    // Отладка
                    $debug .= "Checking user: " . $user['username'] . " (role: " . ($user['role'] ?? 'NO ROLE') . ")<br>";

                    if ($user['username'] === $username) {
                        $userFound = true;
                        $debug .= "User found! Checking password...<br>";

                        if (password_verify($password, $user['password'])) {
                            $debug .= "Password OK!<br>";

                            // Успешная авторизация
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['user_role'] = $user['role'] ?? 'user'; // Важно!
                            $_SESSION['display_name'] = $user['display_name'] ?? $user['username'];

                            $debug .= "Session set: role = " . $_SESSION['user_role'] . "<br>";

                            // Обновляем last_login
                            foreach ($usersData['users'] as &$u) {
                                if ($u['id'] === $user['id']) {
                                    $u['last_login'] = date('Y-m-d H:i:s');
                                    break;
                                }
                            }
                            $db->save('users', $usersData);

                            // Редирект
                            if ($_SESSION['user_role'] === 'admin') {
                                header('Location: admin.php');
                            } else {
                                header('Location: index.php');
                            }
                            exit;
                        } else {
                            $debug .= "Password verification failed!<br>";
                            $error = 'Неверный пароль';
                        }
                        break;
                    }
                }

                if (!$userFound) {
                    $error = 'Пользователь не найден';
                }

            } else {
                $error = 'База пользователей не найдена. Запустите миграции!';
            }
        } catch (Exception $e) {
            $error = 'Ошибка БД: ' . $e->getMessage();
            $debug .= "Exception: " . $e->getMessage() . "<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Хотошо</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }

        .debug-message {
            background: #fff3cd;
            color: #856404;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
            font-size: 12px;
            font-family: monospace;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .demo-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
            font-size: 13px;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 8px;
        }

        code {
            background: rgba(0,0,0,0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>🐕 Хотошо</h1>
        <p>Авторизация в системе</p>
    </div>

    <?php if ($error): ?>
        <div class="error-message">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($debug && isset($_POST['username'])): ?>
        <div class="debug-message">
            <strong>🔍 Отладка:</strong><br>
            <?php echo $debug; ?>
        </div>
    <?php endif; ?>

    <div class="demo-info">
        <strong>💡 Данные для входа:</strong>
        Логин: <code>admin</code><br>
        Пароль: <code>admin123</code>
    </div>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username">Логин</label>
            <input type="text" id="username" name="username" placeholder="Введите логин" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" placeholder="Введите пароль" required>
        </div>

        <button type="submit" class="btn-login">Войти</button>
    </form>

    <div class="login-footer">
        <a href="index.php">← Вернуться на главную</a>
    </div>
</div>

</body>
</html>
