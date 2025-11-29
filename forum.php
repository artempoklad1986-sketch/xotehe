<?php
/**
 * Forum v3.1 - Fixed Version
 * Правильно использует структуру из forum_categories.json
 * С поддержкой BBCode, иерархии категорий и всех функций
 */

// ========================================
// БАЗОВАЯ КОНФИГУРАЦИЯ
// ========================================

header('Content-Type: text/html; charset=utf-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// CSRF ЗАЩИТА
// ========================================

if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
    }
}

// ========================================
// ПОДКЛЮЧЕНИЕ БАЗЫ ДАННЫХ
// ========================================

try {
    $dbPath = __DIR__ . '/Database.php';

    if (!file_exists($dbPath)) {
        throw new Exception('Database.php not found');
    }

    require_once $dbPath;
    $db = new Database();

} catch (Exception $e) {
    die('<h1 style="text-align:center;margin-top:100px;">Ошибка подключения к базе данных</h1>');
}

// ========================================
// ЗАГРУЗКА НАСТРОЕК
// ========================================

try {
    $settings = $db->get('settings');

    if (!is_array($settings)) {
        $settings = [];
    }

    $defaults_settings = [
        'site_title' => 'Хотошо - Питомник',
        'header_image' => '',
        'forum_enabled' => true,
        'forum_allow_guest_view' => true,
        'phone' => '',
        'email' => '',
        'vk' => '',
        'footer_about' => 'О питомнике'
    ];

    $settings = array_merge($defaults_settings, $settings);

    if (empty($settings['forum_enabled'])) {
        die('<h1 style="text-align:center;margin-top:100px;">Форум временно недоступен</h1>');
    }

} catch (Exception $e) {
    $settings = $defaults_settings;
}

// ========================================
// АВТОРИЗАЦИЯ И РОЛИ
// ========================================

$currentUser = null;
$isGuest = true;
$isModerator = false;
$isAdmin = false;

try {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $currentUser = $db->getUserById($_SESSION['user_id']);

        if ($currentUser && is_array($currentUser)) {
            $isGuest = false;
            $userRole = isset($currentUser['role']) ? strtolower($currentUser['role']) : 'user';
            $isModerator = in_array($userRole, ['moderator', 'admin']);
            $isAdmin = ($userRole === 'admin');
        } else {
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
        }
    }
} catch (Exception $e) {
    $isGuest = true;
}

if ($isGuest && empty($settings['forum_allow_guest_view'])) {
    header('Location: login.php?redirect=forum.php');
    exit;
}

// ========================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ========================================

function safe_htmlspecialchars($string) {
    if (!is_string($string)) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function getImagePath($path) {
    if (empty($path) || !is_string($path)) {
        return '';
    }

    if (strpos($path, 'http') === 0) {
        return $path;
    }

    if (strpos($path, '/') === 0) {
        return $path;
    }

    return '/' . $path;
}

function formatDate($datetime) {
    if (empty($datetime)) {
        return '';
    }

    try {
        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return $datetime;
        }

        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'только что';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' мин. назад';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' ч. назад';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' дн. назад';
        } else {
            return date('d.m.Y в H:i', $timestamp);
        }

    } catch (Exception $e) {
        return $datetime;
    }
}

function getUserName($userId, $db) {
    try {
        $user = $db->getUserById($userId);

        if (!$user || !is_array($user)) {
            return 'Гость';
        }

        if (!empty($user['display_name'])) {
            return $user['display_name'];
        }

        if (!empty($user['username'])) {
            return $user['username'];
        }

        return 'Гость';

    } catch (Exception $e) {
        return 'Гость';
    }
}

function getUserAvatar($userId, $db) {
    try {
        $user = $db->getUserById($userId);

        if ($user && is_array($user) && isset($user['avatar']) && !empty($user['avatar'])) {
            return getImagePath($user['avatar']);
        }

        return null;

    } catch (Exception $e) {
        return null;
    }
}

function getUserInitials($userId, $db) {
    try {
        $name = getUserName($userId, $db);

        if (empty($name) || $name === 'Гость') {
            return '?';
        }

        $words = explode(' ', $name);

        if (count($words) >= 2) {
            $first = mb_substr($words[0], 0, 1, 'UTF-8');
            $second = mb_substr($words[1], 0, 1, 'UTF-8');
            return mb_strtoupper($first . $second, 'UTF-8');
        }

        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');

    } catch (Exception $e) {
        return '?';
    }
}

// ========================================
// 🎨 КОНВЕРТЕР BBCODE → HTML
// ========================================

function convertBBCodeToHTML($content) {
    if (!is_string($content)) {
        return '';
    }

    try {
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        $content = preg_replace('/$$b$$(.*?)$$\/b$$/is', '<strong>$1</strong>', $content);
        $content = preg_replace('/$$i$$(.*?)$$\/i$$/is', '<em>$1</em>', $content);
        $content = preg_replace('/$$u$$(.*?)$$\/u$$/is', '<u>$1</u>', $content);
        $content = preg_replace('/$$s$$(.*?)$$\/s$$/is', '<s>$1</s>', $content);

        $content = preg_replace_callback('/$$color=(#?[a-fA-F0-9]{6}|#?[a-fA-F0-9]{3}|red|blue|green|yellow|orange|purple|pink|black|white|gray)$$(.*?)$$\/color$$/is', function($matches) {
            $color = $matches[1];
            if (strpos($color, '#') !== 0 && preg_match('/^[a-fA-F0-9]{3,6}$/', $color)) {
                $color = '#' . $color;
            }
            return '<span style="color:' . $color . '">' . $matches[2] . '</span>';
        }, $content);

        $content = preg_replace_callback('/$$size=(\d+)$$(.*?)$$\/size$$/is', function($matches) {
            $size = intval($matches[1]);
            $size = max(10, min(36, $size));
            return '<span style="font-size:' . $size . 'px">' . $matches[2] . '</span>';
        }, $content);

        $content = preg_replace_callback('/$$url=([^$$]+)\](.*?)$$\/url$$/is', function($matches) {
            $url = trim($matches[1]);
            if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
                return $matches[2];
            }
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color:#2C5F8D;text-decoration:underline;">' . $matches[2] . '</a>';
        }, $content);

        $content = preg_replace_callback('/$$url$$(.*?)$$\/url$$/is', function($matches) {
            $url = trim($matches[1]);
            if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
                return $url;
            }
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color:#2C5F8D;text-decoration:underline;">' . $url . '</a>';
        }, $content);

        $content = preg_replace_callback('/$$img(?:=(\d+)x(\d+))?$$(.*?)$$\/img$$/is', function($matches) {
            $url = trim($matches[3]);
            $width = !empty($matches[1]) ? intval($matches[1]) : 640;
            $height = !empty($matches[2]) ? intval($matches[2]) : 480;

            if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
                return '';
            }

            return sprintf(
                '<img src="%s" alt="Изображение" style="max-width:%dpx;max-height:%dpx;width:auto;height:auto;border-radius:8px;cursor:pointer;display:block;margin:10px 0;" loading="lazy" class="forum-image">',
                $url,
                $width,
                $height
            );
        }, $content);

        $content = preg_replace_callback('/$$video$$(.*?)$$\/video$$/is', function($matches) {
            $url = trim($matches[1]);

            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $match)) {
                return sprintf(
                    '<div class="video-container" style="position:relative;padding-bottom:56.25%%;height:0;margin:15px 0;max-width:640px;">
                        <iframe src="https://www.youtube.com/embed/%s" style="position:absolute;top:0;left:0;width:100%%;height:100%%;border:none;border-radius:8px;" allowfullscreen></iframe>
                    </div>',
                    $match[1]
                );
            }

            if (preg_match('/vk\.com\/video(-?\d+_\d+)/', $url, $match)) {
                $parts = explode('_', $match[1]);
                if (count($parts) === 2) {
                    return sprintf(
                        '<div class="video-container" style="position:relative;padding-bottom:56.25%%;height:0;margin:15px 0;max-width:640px;">
                            <iframe src="https://vk.com/video_ext.php?oid=%s&id=%s" style="position:absolute;top:0;left:0;width:100%%;height:100%%;border:none;border-radius:8px;" allowfullscreen></iframe>
                        </div>',
                        $parts[0],
                        $parts[1]
                    );
                }
            }

            return '[Неподдерживаемый формат видео]';
        }, $content);

        $content = preg_replace_callback('/$$quote(?:=([^$$]+))?\](.*?)$$\/quote$$/is', function($matches) {
            $author = !empty($matches[1]) ? '<strong>' . $matches[1] . ' написал(а):</strong><br>' : '';
            return sprintf(
                '<blockquote style="border-left:4px solid #2C5F8D;padding:10px 15px;margin:10px 0;background:#F9F9F9;border-radius:6px;color:#333;">%s%s</blockquote>',
                $author,
                $matches[2]
            );
        }, $content);

        $content = preg_replace_callback('/$$code$$(.*?)$$\/code$$/is', function($matches) {
            return sprintf(
                '<pre style="background:#2D2D2D;color:#F8F8F2;padding:15px;border-radius:6px;overflow-x:auto;border:1px solid #444;font-family:monospace;font-size:13px;line-height:1.5;margin:10px 0;"><code>%s</code></pre>',
                $matches[1]
            );
        }, $content);

        $content = preg_replace('/$$list$$(.*?)$$\/list$$/is', '<ul style="margin:10px 0;padding-left:30px;">$1</ul>', $content);
        $content = preg_replace('/$$\*$$(.*?)(?=$$\*$$|$$\/list$$|$)/is', '<li style="margin:5px 0;">$1</li>', $content);

        $content = preg_replace_callback('/$$spoiler(?:=([^$$]+))?\](.*?)$$\/spoiler$$/is', function($matches) {
            $title = !empty($matches[1]) ? $matches[1] : 'Спойлер';
            $id = 'spoiler_' . md5($matches[2]);
            return sprintf(
                '<div class="spoiler" style="margin:10px 0;border:1px solid #E5DDD7;border-radius:6px;overflow:hidden;">
                    <div class="spoiler-header" onclick="toggleSpoiler(\'%s\')" style="background:#F5F5F5;padding:10px 15px;cursor:pointer;user-select:none;font-weight:bold;">
                        <span id="%s-icon">▶</span> %s
                    </div>
                    <div id="%s" class="spoiler-content" style="display:none;padding:15px;background:#FAFAFA;">%s</div>
                </div>',
                $id,
                $id,
                $title,
                $id,
                $matches[2]
            );
        }, $content);

        $content = nl2br($content);

        return $content;

    } catch (Exception $e) {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }
}

// ========================================
// ЗАГРУЗКА СТРУКТУРЫ ФОРУМА
// ========================================

$forumStructure = [];
try {
    $forumCategoriesData = $db->get('forum_categories');
    if (is_array($forumCategoriesData)) {
        $forumStructure = $forumCategoriesData;
    }
} catch (Exception $e) {
    $forumStructure = [];
}

$forums = isset($forumStructure['forums']) ? $forumStructure['forums'] : [];
$categoryGroups = isset($forumStructure['category_groups']) ? $forumStructure['category_groups'] : [];

// Темы
$topics = [];
try {
    $topicsData = $db->get('forum_topics');
    if (is_array($topicsData) && isset($topicsData['topics'])) {
        $topics = $topicsData['topics'];
    }
} catch (Exception $e) {
    $topics = [];
}

// Сообщения
$posts = [];
try {
    $postsData = $db->get('forum_posts');
    if (is_array($postsData) && isset($postsData['posts'])) {
        $posts = $postsData['posts'];
    }
} catch (Exception $e) {
    $posts = [];
}

// ========================================
// ОНЛАЙН ПОЛЬЗОВАТЕЛИ
// ========================================

$onlineUsers = 0;
$onlineGuests = 0;
$onlineUsernames = [];

try {
    $onlineData = $db->get('forum_online');

    if (!is_array($onlineData)) {
        $onlineData = ['users' => []];
    }

    if (!isset($onlineData['users']) || !is_array($onlineData['users'])) {
        $onlineData['users'] = [];
    }

    $currentTime = time();
    $timeout = 15 * 60;

    $onlineData['users'] = array_filter($onlineData['users'], function($user) use ($currentTime, $timeout) {
        return isset($user['last_seen']) && ($currentTime - $user['last_seen']) <= $timeout;
    });

    $sessionId = session_id();
    $found = false;

    foreach ($onlineData['users'] as $key => $user) {
        if (isset($user['session_id']) && $user['session_id'] === $sessionId) {
            $onlineData['users'][$key]['last_seen'] = $currentTime;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $onlineData['users'][] = [
            'session_id' => $sessionId,
            'user_id' => $isGuest ? null : $_SESSION['user_id'],
            'username' => $isGuest ? null : (isset($_SESSION['username']) ? $_SESSION['username'] : 'User'),
            'last_seen' => $currentTime,
            'page' => 'forum'
        ];
    }

    $db->save('forum_online', $onlineData);

    foreach ($onlineData['users'] as $user) {
        if (isset($user['user_id']) && !empty($user['user_id'])) {
            $onlineUsers++;
            if (isset($user['username'])) {
                $onlineUsernames[] = $user['username'];
            }
        } else {
            $onlineGuests++;
        }
    }

} catch (Exception $e) {
    // Игнорируем
}

// ========================================
// СТАТИСТИКА
// ========================================

$stats = $db->getForumStats();

$totalTopics = isset($stats['topics']) ? $stats['topics'] : count($topics);
$totalPosts = isset($stats['posts']) ? $stats['posts'] : count($posts);
$totalUsers = isset($stats['users']) ? $stats['users'] : 0;

// Последняя активность
$lastPost = null;
$lastTopic = null;
$lastUser = null;

if (!empty($posts)) {
    $postsCopy = $posts;
    usort($postsCopy, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $lastPost = $postsCopy[0];
}

if (!empty($topics)) {
    $topicsCopy = $topics;
    usort($topicsCopy, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $lastTopic = $topicsCopy[0];
}

try {
    $usersData = $db->get('users');
    if (is_array($usersData) && isset($usersData['users']) && !empty($usersData['users'])) {
        $users = $usersData['users'];
        usort($users, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $lastUser = $users[0];
    }
} catch (Exception $e) {
    $lastUser = null;
}

// ========================================
// УВЕДОМЛЕНИЯ
// ========================================

$unreadNotifications = 0;

if (!$isGuest) {
    $unreadNotifications = $db->getUnreadNotificationsCount($_SESSION['user_id']);
}

// ========================================
// ГРУППИРОВКА ФОРУМОВ ПО КАТЕГОРИЯМ
// ========================================

$parentCategories = [];
$childForums = [];

foreach ($forums as $forum) {
    if (isset($forum['is_cat']) && $forum['is_cat'] == '1') {
        $parentCategories[$forum['forumid']] = $forum;
    } else {
        $parentId = isset($forum['parentid']) ? $forum['parentid'] : '0';
        if (!isset($childForums[$parentId])) {
            $childForums[$parentId] = [];
        }
        $childForums[$parentId][] = $forum;
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Форум питомника <?php echo safe_htmlspecialchars($settings['site_title']); ?>">
    <title>Форум - <?php echo safe_htmlspecialchars($settings['site_title']); ?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background: #e8e8e8;
        }

        body {
            font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #000000;
            background: #FFFCF8;
            max-width: 1200px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow-x: hidden;
        }

        .header-image {
            width: 100%;
            display: block;
            height: 400px;
            object-fit: cover;
            object-position: center;
        }

        .nav {
            background: #3E3936;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .burger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 10px;
            z-index: 1001;
            position: absolute;
            left: 20px;
        }

        .burger span {
            width: 25px;
            height: 3px;
            background: #FFFFFF;
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 3px;
        }

        .burger.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }
        .burger.active span:nth-child(2) {
            opacity: 0;
        }
        .burger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li a,
        nav ul li .user-info {
            display: flex;
            align-items: center;
            color: #FFFFFF;
            text-decoration: none;
            padding: 12px 16px;
            font-size: 11px;
            font-weight: normal;
            transition: 0.3s;
            border-right: 1px solid #2D2825;
        }

        nav ul li:last-child a,
        nav ul li:last-child .user-info {
            border-right: none;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background: rgba(0,0,0,0.2);
        }

        .user-info {
            gap: 8px;
            cursor: pointer;
        }

        .user-info .avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
        }

        .nav-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 999;
        }

        .nav-overlay.active {
            display: block;
        }

        main.container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .mini-nav {
            background: #EFEFEF;
            padding: 15px 30px;
            border-bottom: 1px solid #E5DDD7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            border-radius: 8px 8px 0 0;
        }

        .mini-nav-left, .mini-nav-right {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .mini-nav a {
            color: #2C5F8D;
            text-decoration: none;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .mini-nav a:hover {
            background: #DDDDDD;
            color: #1A4A6D;
        }

        .notification-badge {
            background: #3E3936;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            margin-left: 4px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #E5DDD7;
            border-radius: 20px;
            padding: 5px 15px;
            min-width: 200px;
            width: 100%;
            max-width: 300px;
        }

        .search-bar input {
            border: none;
            outline: none;
            background: transparent;
            padding: 5px;
            flex: 1;
            color: #333333;
            font-size: 11px;
            font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
            width: 100%;
        }

        .search-bar input::placeholder {
            color: #999999;
        }

        .category-section {
            background: #FFFFFF;
            border: 1px solid #CCCCCC;
            border-radius: 0;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .category-header {
            background: #E8E3DC;
            border-bottom: 1px solid #CCCCCC;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .category-header:hover {
            background: #DED9D2;
        }

        .category-title {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex: 1;
            min-width: 0;
        }

        .category-title strong {
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            word-wrap: break-word;
        }

        .category-description {
            font-size: 11px;
            color: #666666;
            font-weight: normal;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .category-header-right {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        .forum-count {
            font-size: 11px;
            color: #666666;
            font-weight: normal;
            white-space: nowrap;
        }

        .toggle-icon {
            font-size: 1em;
            transition: transform 0.3s ease;
            color: #666666;
            font-weight: normal;
        }

        .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }

        .subforum-list {
            padding: 0;
            display: block;
            max-height: 3000px;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }

        .subforum-list.collapsed {
            max-height: 0;
        }

        .subforum-item {
            display: grid;
            grid-template-columns: 50px 1fr 150px 250px;
            border-bottom: 1px solid #E5DDD7;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #FFFFFF;
        }

        .subforum-item:hover {
            background: #F5F5F5;
        }

        .subforum-item:last-child {
            border-bottom: none;
        }

        .subforum-item > * {
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .subforum-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .subforum-icon::before {
            content: '';
            width: 30px;
            height: 22px;
            background: linear-gradient(135deg, #F5F1E8 0%, #E8DDD0 100%);
            border: 1.5px solid #C4A777;
            border-radius: 3px;
            position: relative;
            display: block;
            box-shadow:
                0 1px 3px rgba(196, 167, 119, 0.3),
                inset 0 1px 0 rgba(255,255,255,0.5);
        }

        .subforum-icon::after {
            content: '';
            width: 10px;
            height: 3px;
            background: linear-gradient(135deg, #F5F1E8 0%, #E8DDD0 100%);
            border: 1.5px solid #C4A777;
            border-bottom: none;
            border-radius: 2px 2px 0 0;
            position: absolute;
            top: 9px;
            left: 13px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.3);
        }

        .subforum-item.has-new .subforum-icon::before {
            background: linear-gradient(135deg, #FFF8E7 0%, #F0E5D0 100%);
            border-color: #D4B068;
            box-shadow:
                0 1px 4px rgba(212, 176, 104, 0.4),
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 0 8px rgba(212, 176, 104, 0.2);
        }

        .subforum-item.has-new .subforum-icon::after {
            background: linear-gradient(135deg, #FFF8E7 0%, #F0E5D0 100%);
            border-color: #D4B068;
        }

        .subforum-info {
            min-width: 0;
            overflow: hidden;
        }

        .subforum-name {
            font-weight: bold;
            color: #003366;
            font-size: 11px;
            margin-bottom: 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .subforum-description {
            font-size: 11px;
            color: #666666;
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .subforum-stats {
            text-align: center;
            font-size: 11px;
            color: #000000;
            font-weight: normal;
        }

        .subforum-last-activity {
            font-size: 11px;
            color: #000000;
            display: flex;
            flex-direction: column;
            gap: 3px;
            line-height: 1.4;
            min-width: 0;
            overflow: hidden;
        }

        .subforum-last-activity a {
            color: #003366;
            text-decoration: none;
            font-weight: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .subforum-last-activity a:hover {
            text-decoration: underline;
        }

        .subforum-last-activity .meta {
            color: #666666;
            font-size: 11px;
            word-wrap: break-word;
        }

        .info-center {
            background: #FFFFFF;
            border: 1px solid #CCCCCC;
            border-radius: 0;
            margin: 30px 0;
            overflow: hidden;
        }

        .info-center-header {
            background: #E8E3DC;
            border-bottom: 1px solid #CCCCCC;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-row {
            background: #F9F9F9;
            border-bottom: 1px solid #E5DDD7;
            padding: 20px 25px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-box {
            text-align: center;
            flex: 1;
            min-width: 120px;
        }

        .stat-box .icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat-box .number {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            margin-bottom: 4px;
            word-wrap: break-word;
        }

        .stat-box .label {
            font-size: 11px;
            color: #666666;
        }

        .info-blocks {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid #E5DDD7;
        }

        .info-block {
            padding: 15px 20px;
            border-right: 1px solid #E5DDD7;
            background: #FFFFFF;
        }

        .info-block:last-child {
            border-right: none;
        }

        .info-block-title {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-block-content {
            font-size: 11px;
            color: #000000;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .info-block-content a {
            color: #003366;
            font-weight: normal;
            text-decoration: none;
            word-wrap: break-word;
        }

        .info-block-content a:hover {
            text-decoration: underline;
        }

        .info-block-meta {
            font-size: 11px;
            color: #666666;
            margin-top: 5px;
        }

        .online-info {
            padding: 15px 20px;
            background: #FFFFFF;
        }

        .online-title {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .online-stats {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #E5DDD7;
            flex-wrap: wrap;
        }

        .online-stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #000000;
        }

        .online-stat-item .icon {
            font-size: 16px;
        }

        .online-stat-item .count {
            font-weight: bold;
            color: #003366;
            font-size: 11px;
        }

        .online-users-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .online-user-tag {
            background: #3E3936;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: normal;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .online-user-tag .status-dot {
            width: 6px;
            height: 6px;
            background: #4CAF50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .detailed-stats {
            background: #F9F9F9;
            padding: 12px 20px;
            text-align: center;
        }

        .detailed-stats a {
            color: #003366;
            text-decoration: none;
            font-size: 11px;
            font-weight: normal;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .detailed-stats a:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666666;
            background: #FFFFFF;
            border: 1px solid #E5DDD7;
            border-radius: 10px;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .footer {
            background: #3E3936;
            color: white;
            padding: 50px 20px 30px;
            margin-top: 80px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            color: #DED5CF;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .footer-column a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 10px 0;
            transition: 0.3s;
            font-size: 11px;
        }

        .footer-column a:hover {
            color: #DED5CF;
            padding-left: 10px;
        }

        .footer-column p {
            color: rgba(255,255,255,0.9);
            line-height: 1.7;
            font-size: 11px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
            font-size: 11px;
        }

        @media (max-width: 1024px) {
            .header-image {
                height: 300px;
            }
        }

        @media (max-width: 768px) {
            body {
                font-size: 12px;
                box-shadow: none;
            }

            .header-image {
                height: 200px;
            }

            .burger {
                display: flex;
                left: 15px;
                padding: 15px 10px;
            }

            nav ul {
                position: fixed;
                top: 0;
                right: -100%;
                width: 85%;
                max-width: 300px;
                height: 100vh;
                background: #3E3936;
                flex-direction: column;
                padding: 70px 0 20px;
                transition: right 0.3s ease;
                box-shadow: -5px 0 20px rgba(0,0,0,0.3);
                z-index: 1000;
                overflow-y: auto;
                align-items: stretch;
            }

            nav ul.active {
                right: 0;
            }

            nav ul li {
                width: 100%;
                border-bottom: 1px solid #2D2825;
            }

            nav ul li a,
            nav ul li .user-info {
                padding: 18px 20px;
                width: 100%;
                border-right: none;
                font-size: 13px;
            }

            main.container {
                margin: 10px auto;
                padding: 0 10px;
            }

            .mini-nav {
                padding: 12px 15px;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                margin-top: 10px;
                border-radius: 8px 8px 0 0;
            }

            .mini-nav-left,
            .mini-nav-right {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }

            .mini-nav a {
                padding: 12px 15px;
                font-size: 12px;
                width: 100%;
                justify-content: center;
                background: white;
                border-radius: 6px;
            }

            .search-bar {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding: 10px 15px;
            }

            .category-header {
                padding: 12px 15px;
                flex-wrap: wrap;
                gap: 8px;
            }

            .category-header-right {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                gap: 10px;
            }

            .subforum-item {
                grid-template-columns: 1fr;
                gap: 0;
                padding: 0;
            }

            .subforum-icon {
                display: none;
            }

            .subforum-item > * {
                padding: 14px 15px;
                border-right: none;
                border-bottom: 1px solid rgba(229, 221, 215, 0.3);
            }

            .subforum-item > *:last-child {
                border-bottom: none;
            }

            .info-blocks {
                grid-template-columns: 1fr;
            }

            .info-block {
                padding: 12px 15px;
                border-right: none;
                border-bottom: 1px solid #E5DDD7;
            }

            .info-block:last-child {
                border-bottom: none;
            }

            .footer {
                padding: 30px 15px 20px;
                margin-top: 40px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }

        @media (max-width: 480px) {
            body {
                font-size: 11px;
            }

            .header-image {
                height: 120px;
            }
        }
    </style>
</head>
<body>

    <?php
    $headerPath = getImagePath($settings['header_image']);
    if (!empty($headerPath)):
    ?>
    <img src="<?php echo safe_htmlspecialchars($headerPath); ?>" alt="Шапка сайта" class="header-image">
    <?php endif; ?>

    <nav class="nav" id="mainNav">
        <div class="nav-container">
            <div class="burger" id="burger">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <ul id="navMenu">
                <li><a href="index.php">Главная</a></li>
                <li><a href="blog.php">Блог</a></li>
                <li><a href="forum.php" class="active">Форум</a></li>
                <li><a href="puppies.php">Щенки</a></li>
                <li><a href="gallery.php">Галерея</a></li>
                <li><a href="dogs_online.php">Каталог собак</a></li>
                <li><a href="feedback.php">Связь</a></li>

                <?php if ($isAdmin): ?>
                <li><a href="admin.php">Админка</a></li>
                <?php endif; ?>

                <?php if ($isModerator): ?>
                <li><a href="forum_moderate.php">Модерация</a></li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="user-menu">
                    <div class="user-info">
                        <div class="avatar">
                            <?php echo isset($_SESSION['username']) ? mb_substr($_SESSION['username'], 0, 1, 'UTF-8') : '?'; ?>
                        </div>
                        <span><?php echo isset($_SESSION['username']) ? safe_htmlspecialchars($_SESSION['username']) : 'User'; ?></span>
                    </div>
                </li>
                <li><a href="logout.php">Выйти</a></li>
                <?php else: ?>
                <li class="user-menu"><a href="login.php">Войти</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="nav-overlay" id="navOverlay"></div>
    </nav>

    <main class="container">

        <div class="mini-nav">
            <div class="mini-nav-left">
                <a href="forum.php">
                    🏠 Главная
                </a>
                <a href="forum.php?view=new">
                    ✨ Новое
                </a>
                <?php if (!$isGuest): ?>
                <a href="forum_notifications.php">
                    🔔 Уведомления
                    <?php if ($unreadNotifications > 0): ?>
                    <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
            <div class="mini-nav-right">
                <form method="GET" action="forum_search.php" class="search-bar">
                    🔍
                    <input type="text" name="q" placeholder="Поиск..." minlength="2" required>
                </form>
            </div>
        </div>

        <?php if (!empty($parentCategories)): ?>
            <?php
            uasort($parentCategories, function($a, $b) {
                $orderA = isset($a['order']) ? intval($a['order']) : 999;
                $orderB = isset($b['order']) ? intval($b['order']) : 999;
                return $orderA - $orderB;
            });

            foreach ($parentCategories as $parentId => $parentCat):
                $children = isset($childForums[$parentId]) ? $childForums[$parentId] : [];
                if (empty($children)) continue;
            ?>
                <section class="category-section">
                    <header class="category-header" onclick="toggleCategory(this)">
                        <div class="category-title">
                            <strong><?php echo safe_htmlspecialchars($parentCat['title']); ?></strong>
                            <?php if (!empty($parentCat['description'])): ?>
                                <span class="category-description"><?php echo strip_tags($parentCat['description']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="category-header-right">
                            <span class="forum-count">Разделов: <?php echo count($children); ?></span>
                            <span class="toggle-icon">▼</span>
                        </div>
                    </header>
                    <div class="subforum-list">
                        <?php foreach ($children as $forum):
                            $forumId = $forum['forumid'];

                            $forumTopics = array_filter($topics, function($topic) use ($forumId) {
                                return isset($topic['category_id']) && $topic['category_id'] == $forumId;
                            });
                            $topicsCount = count($forumTopics);

                            $postsCount = 0;
                            foreach ($forumTopics as $topic) {
                                $topicPosts = array_filter($posts, function($post) use ($topic) {
                                    return isset($post['topic_id']) && $post['topic_id'] == $topic['id'];
                                });
                                $postsCount += count($topicPosts);
                            }

                            $lastPost = null;
                            $lastPostTopic = null;

                            foreach ($forumTopics as $topic) {
                                $topicPosts = array_filter($posts, function($post) use ($topic) {
                                    return isset($post['topic_id']) && $post['topic_id'] == $topic['id'];
                                });

                                if (!empty($topicPosts)) {
                                    usort($topicPosts, function($a, $b) {
                                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                                    });

                                    $tempPost = $topicPosts[0];
                                    if (!$lastPost || strtotime($tempPost['created_at']) > strtotime($lastPost['created_at'])) {
                                        $lastPost = $tempPost;
                                        $lastPostTopic = $topic;
                                    }
                                }
                            }

                            $hasNew = false;
                            if ($lastPost && strtotime($lastPost['created_at']) > strtotime('-24 hours')) {
                                $hasNew = true;
                            }
                        ?>
                            <article class="subforum-item <?php echo $hasNew ? 'has-new' : ''; ?>" onclick="window.location.href='forum_category.php?id=<?php echo $forumId; ?>'">
                                <div class="subforum-icon"></div>

                                <div class="subforum-info">
                                    <h3 class="subforum-name"><?php echo safe_htmlspecialchars($forum['title']); ?></h3>
                                    <?php if (!empty($forum['description'])): ?>
                                        <div class="subforum-description"><?php echo strip_tags($forum['description']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="subforum-stats">
                                    <div><?php echo $postsCount; ?> Сообщений</div>
                                    <div><?php echo $topicsCount; ?> Тем</div>
                                </div>

                                <div class="subforum-last-activity">
                                    <?php if ($lastPost): ?>
                                        <div>
                                            Последний ответ от
                                            <a href="forum_user_profile.php?id=<?php echo $lastPost['user_id']; ?>" onclick="event.stopPropagation()">
                                                <?php echo safe_htmlspecialchars(getUserName($lastPost['user_id'], $db)); ?>
                                            </a>
                                        </div>
                                        <?php if ($lastPostTopic): ?>
                                        <div>
                                            <a href="forum_topic.php?id=<?php echo $lastPostTopic['id']; ?>" onclick="event.stopPropagation()" title="<?php echo safe_htmlspecialchars($lastPostTopic['title']); ?>">
                                                <?php
                                                $topicTitle = $lastPostTopic['title'];
                                                if (mb_strlen($topicTitle, 'UTF-8') > 30) {
                                                    $topicTitle = mb_substr($topicTitle, 0, 30, 'UTF-8') . '...';
                                                }
                                                echo safe_htmlspecialchars($topicTitle);
                                                ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                        <div class="meta">
                                            <?php echo formatDate($lastPost['created_at']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #999999; text-align: center;">Нет сообщений</div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div>Форум пока пуст</div>
                <?php if ($isAdmin): ?>
                <div style="margin-top: 15px;">
                    <a href="admin.php?page=forum_settings" style="color: #2C5F8D; text-decoration: none;">
                        → Настроить форум в админке
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <section class="info-center">
            <header class="info-center-header">
                📊 Информ-блок
            </header>

            <div class="stats-row">
                <article class="stat-box">
                    <div class="icon">💬</div>
                    <div class="number"><?php echo $totalTopics; ?></div>
                    <div class="label">Тем</div>
                </article>
                <article class="stat-box">
                    <div class="icon">📝</div>
                    <div class="number"><?php echo $totalPosts; ?></div>
                    <div class="label">Сообщений</div>
                </article>
                <article class="stat-box">
                    <div class="icon">👥</div>
                    <div class="number"><?php echo $totalUsers; ?></div>
                    <div class="label">Участников</div>
                </article>
                <article class="stat-box">
                    <div class="icon">🆕</div>
                    <div class="number"><?php echo $lastUser ? safe_htmlspecialchars(getUserName($lastUser['id'], $db)) : 'Нет'; ?></div>
                    <div class="label">Последний участник</div>
                </article>
            </div>

            <div class="info-blocks">
                <article class="info-block">
                    <h4 class="info-block-title">
                        📌 Последнее сообщение
                    </h4>
                    <div class="info-block-content">
                        <?php if ($lastPost): ?>
                            <?php
                            $postContentHTML = convertBBCodeToHTML($lastPost['content']);
                            $postPreview = strip_tags($postContentHTML);
                            if (mb_strlen($postPreview, 'UTF-8') > 100) {
                                $postPreview = mb_substr($postPreview, 0, 100, 'UTF-8') . '...';
                            }

                            $postTopic = null;
                            foreach ($topics as $topic) {
                                if ($topic['id'] == $lastPost['topic_id']) {
                                    $postTopic = $topic;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($postTopic): ?>
                            <div style="margin-bottom: 6px;">
                                В теме: <a href="forum_topic.php?id=<?php echo $postTopic['id']; ?>">
                                    <?php echo safe_htmlspecialchars($postTopic['title']); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            <div style="color: #666666; font-size: 11px; line-height: 1.4;">
                                <?php echo safe_htmlspecialchars($postPreview); ?>
                            </div>
                            <div class="info-block-meta">
                                Автор: <strong><?php echo safe_htmlspecialchars(getUserName($lastPost['user_id'], $db)); ?></strong>
                                • <?php echo formatDate($lastPost['created_at']); ?>
                            </div>
                        <?php else: ?>
                            <div style="color: #666666;">Сообщений пока нет</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="info-block">
                    <h4 class="info-block-title">
                        🔥 Последняя тема
                    </h4>
                    <div class="info-block-content">
                        <?php if ($lastTopic): ?>
                            <div style="margin-bottom: 6px;">
                                <a href="forum_topic.php?id=<?php echo $lastTopic['id']; ?>">
                                    <?php echo safe_htmlspecialchars($lastTopic['title']); ?>
                                </a>
                            </div>
                            <div class="info-block-meta">
                                Автор: <strong><?php echo safe_htmlspecialchars(getUserName($lastTopic['user_id'], $db)); ?></strong>
                                • <?php echo formatDate($lastTopic['created_at']); ?>
                            </div>
                        <?php else: ?>
                            <div style="color: #666666;">Тем пока нет</div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

            <div class="online-info">
                <h4 class="online-title">
                    🌐 Пользователи Online
                </h4>
                <div class="online-stats">
                    <div class="online-stat-item">
                        <span class="icon">👤</span>
                        <span>Участников: <span class="count"><?php echo $onlineUsers; ?></span></span>
                    </div>
                    <div class="online-stat-item">
                        <span class="icon">👁️</span>
                        <span>Гостей: <span class="count"><?php echo $onlineGuests; ?></span></span>
                    </div>
                    <div class="online-stat-item">
                        <span class="icon">🌐</span>
                        <span>Всего онлайн: <span class="count"><?php echo $onlineUsers + $onlineGuests; ?></span></span>
                    </div>
                </div>
                <?php if (!empty($onlineUsernames)): ?>
                <div class="online-users-list">
                    <?php foreach ($onlineUsernames as $username): ?>
                    <div class="online-user-tag">
                        <span class="status-dot"></span>
                        <?php echo safe_htmlspecialchars($username); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; color: #666666; font-size: 11px; padding: 10px;">
                    Зарегистрированных пользователей онлайн нет
                </div>
                <?php endif; ?>
            </div>

            <div class="detailed-stats">
                <a href="forum_stats.php">
                    📈 Подробная статистика форума →
                </a>
            </div>
        </section>

    </main>

    <?php if (file_exists(__DIR__ . '/includes/footer.php')): ?>
        <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php else: ?>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-column">
                <h3>О питомнике</h3>
                <p><?php echo safe_htmlspecialchars($settings['footer_about']); ?></p>
            </div>
            <div class="footer-column">
                <h3>Навигация</h3>
                <a href="index.php">Главная</a>
                <a href="blog.php">Блог</a>
                <a href="forum.php">Форум</a>
                <a href="puppies.php">Щенки</a>
                <a href="gallery.php">Галерея</a>
            </div>
            <div class="footer-column">
                <h3>Контакты</h3>
                <?php if (!empty($settings['phone'])): ?>
                <p>📞 <?php echo safe_htmlspecialchars($settings['phone']); ?></p>
                <?php endif; ?>
                <?php if (!empty($settings['email'])): ?>
                <p>📧 <?php echo safe_htmlspecialchars($settings['email']); ?></p>
                <?php endif; ?>
                <?php if (!empty($settings['vk'])): ?>
                <a href="<?php echo safe_htmlspecialchars($settings['vk']); ?>" target="_blank">VK</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> <?php echo safe_htmlspecialchars($settings['site_title']); ?>. Все права защищены.
        </div>
    </footer>
    <?php endif; ?>

    <script>
        const burger = document.getElementById('burger');
        const navMenu = document.getElementById('navMenu');
        const navOverlay = document.getElementById('navOverlay');

        if (burger && navMenu && navOverlay) {
            burger.addEventListener('click', function(e) {
                e.stopPropagation();
                burger.classList.toggle('active');
                navMenu.classList.toggle('active');
                navOverlay.classList.toggle('active');
            });

            navOverlay.addEventListener('click', function() {
                burger.classList.remove('active');
                navMenu.classList.remove('active');
                navOverlay.classList.remove('active');
            });
        }

        function toggleCategory(header) {
            const icon = header.querySelector('.toggle-icon');
            const list = header.nextElementSibling;

            if (icon && list) {
                icon.classList.toggle('collapsed');
                list.classList.toggle('collapsed');
            }
        }

        function toggleSpoiler(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');

            if (content && icon) {
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.textContent = '▼';
                } else {
                    content.style.display = 'none';
                    icon.textContent = '▶';
                }
            }
        }
    </script>

</body>
</html>
