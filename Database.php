<?php
/**
 * Файловая база данных на JSON + SQLite
 * Версия 11.0 - Добавлены методы для дня рождения и авторесайз фото
 */
class Database {
    private $dataDir;
    private $uploadsDir;
    private $backupDir;
    private $pdo = null;
    private $sqliteFile = 'database.sqlite';

    public function __construct() {
        // Устанавливаем UTF-8
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        $this->dataDir = dirname(__FILE__) . '/data/';
        $this->uploadsDir = dirname(__FILE__) . '/uploads/';
        $this->backupDir = dirname(__FILE__) . '/backups/';

        $this->createDirectories();
        $this->initFiles();
        $this->initSQLiteTables();
    }

    /**
     * Получить PDO подключение к SQLite
     */
    public function getPdo() {
        if ($this->pdo === null) {
            try {
                $dbPath = dirname(__FILE__) . '/' . $this->sqliteFile;
                $this->pdo = new PDO('sqlite:' . $dbPath);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new Exception('Ошибка подключения к SQLite: ' . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    /**
     * Инициализация таблиц SQLite
     */
    private function initSQLiteTables() {
        try {
            $pdo = $this->getPdo();

            // Таблица для истории настроек
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                data TEXT NOT NULL,
                timestamp INTEGER NOT NULL,
                description TEXT,
                user_id INTEGER
            )");

            // Таблица для объявлений о щенках
            $pdo->exec("CREATE TABLE IF NOT EXISTS puppies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                price REAL,
                images TEXT,
                created_at INTEGER NOT NULL,
                user_id INTEGER
            )");

        } catch (PDOException $e) {
            error_log("Ошибка инициализации таблиц SQLite: " . $e->getMessage());
        }
    }

    private function createDirectories() {
        $dirs = array($this->dataDir, $this->uploadsDir, $this->backupDir);

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }

        // Создаем папку для фото щенков
        $puppiesDir = $this->uploadsDir . 'puppies/';
        if (!is_dir($puppiesDir)) {
            @mkdir($puppiesDir, 0755, true);
        }

        // Создаем папку для форума
        $forumDir = $this->uploadsDir . 'forum/';
        if (!is_dir($forumDir)) {
            @mkdir($forumDir, 0755, true);
        }

        // Создаем папку для аватарок
        $avatarsDir = $this->uploadsDir . 'avatars/';
        if (!is_dir($avatarsDir)) {
            @mkdir($avatarsDir, 0755, true);
        }

        // Создаем папку для блога
        $blogDir = $this->uploadsDir . 'blog/';
        if (!is_dir($blogDir)) {
            @mkdir($blogDir, 0755, true);
        }

        // Создаем папку для галереи пользователей
        $galleryDir = $this->uploadsDir . 'gallery/';
        if (!is_dir($galleryDir)) {
            @mkdir($galleryDir, 0755, true);
        }
    }

    private function initFiles() {
        $files = array(
            'settings.json' => array(
                'site_title' => 'Российский Союз Владельцев Хотошо',
                'header_image' => '',
                'welcome_title' => 'Добро пожаловать на наш сайт!',
                'welcome_text' => 'Мы собираем для Вас воедино всё о породе Хотошо. Здесь Вы найдете самую полную информацию об уникальной и универсальной породе. Общайтесь, задавайте вопросы, делитесь своим опытом. Узнавайте больше о характере и особенностях Хотошо. Присоединяйтесь к нашему сообществу и откройте для себя мир собак породы Хотошо!',
                'phone' => '+7 (999) 123-45-67',
                'email' => 'info@hotosho.ru',
                'address' => 'Москва, Россия',
                'vk' => 'https://vk.com/hotosho',
                'instagram' => 'https://instagram.com/hotosho',
                'facebook' => 'https://facebook.com/hotosho',
                'footer_about' => 'Российский Союз Владельцев Хотошо - это сообщество любителей уникальной породы собак.',
                'footer_contacts' => 'Свяжитесь с нами для получения информации о породе Хотошо.',
                'enable_notifications' => true,
                'enable_search' => true,
                'forum_email_notifications' => true,
                'forum_moderation' => false,
                'slider_autoplay' => true,
                'slider_interval' => 5000,
                'slider_transition_speed' => 800,

                // Настройки размера шрифта
                'font_size_base' => 16,
                'font_size_min' => 12,
                'font_size_max' => 24,
                'font_size_step' => 1,
                'enable_font_resizer' => true,
                'font_scale_factor' => 1.0,

                // Настройки дня рождения
                'show_birthday_widget' => true,
                'show_birthday_age' => true,
                'birthday_required' => false
            ),
            'header_settings.json' => array(
                'mobile_breakpoint' => 768,
                'tablet_breakpoint' => 1024,
                'desktop_breakpoint' => 1280,
                'small_mobile_breakpoint' => 480,

                'header_height_desktop' => 400,
                'header_height_tablet' => 300,
                'header_height_mobile' => 200,
                'header_height_small' => 150,

                'mobile_menu_type' => 'burger',

                'show_logo' => true,
                'logo_image' => '',
                'logo_text' => 'ХОТОШО',
                'logo_position' => 'left',

                'show_search' => true,
                'show_social' => true,
                'show_phone' => true,

                'sticky_header' => true,
                'hide_on_scroll' => false,
                'transparent_header' => false,
                'header_bg_color' => '#ffffff',
                'header_text_color' => '#333333',
                'header_bg_color_scroll' => '#ffffff',
                'header_shadow' => true,

                'menu_items' => array(
                    array('title' => 'Главная', 'url' => '/index.php', 'order' => 1, 'icon' => '🏠'),
                    array('title' => 'О породе', 'url' => '/about.php', 'order' => 2, 'icon' => '📚'),
                    array('title' => 'Щенки', 'url' => '/puppies.php', 'order' => 3, 'icon' => '🐕'),
                    array('title' => 'Форум', 'url' => '/forum/', 'order' => 4, 'icon' => '💬'),
                    array('title' => 'Галерея', 'url' => '/gallery.php', 'order' => 5, 'icon' => '📷'),
                    array('title' => 'Контакты', 'url' => '/contacts.php', 'order' => 6, 'icon' => '📞')
                ),

                'menu_animation' => 'fade',
                'menu_animation_speed' => 300,

                'header_font_family' => 'system-ui, -apple-system, sans-serif',
                'header_font_size_desktop' => 16,
                'header_font_size_mobile' => 14,

                'updated_at' => date('Y-m-d H:i:s')
            ),
            'slider.json' => array(
                'slides' => array()
            ),
            'about.json' => array(
                'title' => 'Хотошо',
                'content' => "Шли за веками века, юга сменялась новой югой — не остановить ход времён. Неотвратимо вращается колесо жизни, от рождения к рождению. И понимали боги, что все более беззащитен становится человек на Земле.\n\nХотошо — это древняя и уникальная порода собак, которая на протяжении веков была верным спутником человека. Эти удивительные животные обладают не только красотой, но и исключительным умом, преданностью и храбростью.\n\nПорода формировалась в суровых условиях, что сделало её невероятно выносливой и приспособленной к различным климатическим условиям. Хотошо — это не просто собака, это член семьи, друг и защитник.",
                'image' => '',
                'author' => 'Терегулова М.В.'
            ),
            'puppies.json' => array(
                'items' => array()
            ),
            'parents.json' => array(
                'items' => array()
            ),
            'gallery.json' => array(
                'images' => array()
            ),
            'blog.json' => array(
                'posts' => array()
            ),
            'blog_comments.json' => array(
                'comments' => array()
            ),
            'blog_reactions.json' => array(
                'reactions' => array()
            ),
            'blog_categories.json' => array(
                'categories' => array(
                    array('id' => 1, 'name' => 'О породе', 'slug' => 'o-porode', 'order' => 1),
                    array('id' => 2, 'name' => 'Хотошо раскладываем по полочкам', 'slug' => 'hotosho-raskladyvaem', 'order' => 2),
                    array('id' => 3, 'name' => 'Хроника о Хотошо', 'slug' => 'hronika-hotosho', 'order' => 3),
                    array('id' => 4, 'name' => 'Стандарт и работа с ним', 'slug' => 'standart', 'order' => 4),
                    array('id' => 5, 'name' => 'Мы помним', 'slug' => 'my-pomnim', 'order' => 5),
                    array('id' => 6, 'name' => 'Воспитание и дрессировка', 'slug' => 'vospitanie', 'order' => 6),
                    array('id' => 7, 'name' => 'Условно схожие породы', 'slug' => 'uslovno-shozhie', 'order' => 7),
                    array('id' => 8, 'name' => 'Познавательные темы', 'slug' => 'poznavatelnye', 'order' => 8)
                )
            ),
            'users.json' => array(
                'users' => array(
                    array(
                        'id' => 1,
                        'username' => 'admin',
                        'password' => password_hash('admin123', PASSWORD_DEFAULT),
                        'email' => 'admin@hotosho.ru',
                        'role' => 'admin',
                        'display_name' => 'Администратор',
                        'created_at' => date('Y-m-d H:i:s'),
                        'active' => true,
                        'avatar' => '',
                        'bio' => '',
                        'location' => '',
                        'website' => '',
                        'birthday' => '',
                        'show_birthday_year' => true,
                        'reputation' => 0,
                        'topics_count' => 0,
                        'posts_count' => 0,
                        'likes_given' => 0,
                        'likes_received' => 0,
                        'email_notifications' => true,
                        'email_verified' => true
                    )
                )
            ),
            'breeds.json' => array(
                'breeds' => array()
            ),
            'feedback.json' => array(
                'messages' => array()
            ),
            'forum_topics.json' => array(
                'topics' => array()
            ),
            'forum_posts.json' => array(
                'posts' => array()
            ),
            'forum_subscriptions.json' => array(
                'subscriptions' => array()
            ),
            'forum_likes.json' => array(
                'likes' => array()
            ),
            'forum_notifications.json' => array(
                'notifications' => array()
            ),
            'forum_reports.json' => array(
                'reports' => array()
            ),
            'pages.json' => array(
                'pages' => array()
            ),
            'puppy_favorites.json' => array(
                'items' => array()
            ),
            'wpforo_topics.json' => array(
                'topics' => array()
            ),
            'wpforo_posts.json' => array(
                'posts' => array()
            ),
            'user_gallery.json' => array(
                'items' => array()
            ),
            'admin_messages.json' => array(
                'messages' => array()
            )
        );

        foreach ($files as $filename => $defaultData) {
            $filepath = $this->dataDir . $filename;
            if (!file_exists($filepath)) {
                $this->writeJsonFile($filepath, $defaultData);
            }
        }
    }

    public function get($filename) {
        $filepath = $this->dataDir . $filename . '.json';

        if (!file_exists($filepath)) {
            return null;
        }

        $content = @file_get_contents($filepath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error in {$filename}: " . json_last_error_msg());
            return null;
        }

        return $data;
    }

    public function save($filename, $data) {
        $filepath = $this->dataDir . $filename . '.json';

        if (file_exists($filepath)) {
            $backupPath = $this->backupDir . $filename . '_' . date('Y-m-d_H-i-s') . '.json';
            @copy($filepath, $backupPath);
        }

        return $this->writeJsonFile($filepath, $data);
    }

    private function writeJsonFile($filepath, $data) {
        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if (!defined('JSON_UNESCAPED_UNICODE')) {
            $json = json_encode($data);
        } else {
            $json = json_encode($data, $options);
        }

        if ($json === false) {
            error_log("JSON encode error: " . json_last_error_msg());
            return false;
        }

        if (function_exists('mb_convert_encoding')) {
            $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');
        }

        $result = @file_put_contents($filepath, $json, LOCK_EX);

        if ($result === false) {
            error_log("Failed to write file: {$filepath}");
            return false;
        }

        return $result;
    }

    // ========================================
    // 🖼️ ЗАГРУЗКА ФАЙЛОВ С АВТОМАТИЧЕСКИМ РЕСАЙЗОМ
    // ========================================

    /**
     * Загрузить файл с автоматическим ресайзом до 640x480
     */
    public function uploadFile($fileInput, $prefix = '', $maxWidth = 640, $maxHeight = 480) {
        if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'error' => 'Ошибка загрузки файла');
        }

        $file = $_FILES[$fileInput];

        $allowedTypes = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $allowedExts = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            return array('success' => false, 'error' => 'Недопустимый тип файла');
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            return array('success' => false, 'error' => 'Файл слишком большой (макс 10MB)');
        }

        $safeName = preg_replace('/[^a-z0-9_-]/i', '', $prefix);
        $filename = ($safeName ? $safeName . '_' : '') . uniqid() . '.jpg'; // Всегда сохраняем как JPG
        $uploadPath = $this->uploadsDir . $filename;

        // Обработка и ресайз изображения
        $resized = $this->resizeImage($file['tmp_name'], $uploadPath, $maxWidth, $maxHeight, 85);

        if ($resized) {
            @chmod($uploadPath, 0644);

            return array(
                'success' => true,
                'filename' => $filename,
                'path' => 'uploads/' . $filename,
                'fullpath' => '/uploads/' . $filename
            );
        }

        return array('success' => false, 'error' => 'Не удалось обработать изображение');
    }

    /**
     * Ресайз изображения с сохранением пропорций
     */
    private function resizeImage($source, $destination, $maxWidth, $maxHeight, $quality = 85) {
        // Получаем информацию об изображении
        $imageInfo = @getimagesize($source);
        if (!$imageInfo) {
            return false;
        }

        list($origWidth, $origHeight, $imageType) = $imageInfo;

        // Создаем изображение из источника
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($source);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = @imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Вычисляем новые размеры с сохранением пропорций
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);

        // Если изображение меньше максимальных размеров, не увеличиваем его
        if ($ratio > 1) {
            $ratio = 1;
        }

        $newWidth = round($origWidth * $ratio);
        $newHeight = round($origHeight * $ratio);

        // Создаем новое изображение
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Сохраняем прозрачность для PNG и GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Ресайз с высоким качеством
        imagecopyresampled(
            $newImage, 
            $sourceImage, 
            0, 0, 0, 0, 
            $newWidth, 
            $newHeight, 
            $origWidth, 
            $origHeight
        );

        // Сохраняем как JPEG
        $result = imagejpeg($newImage, $destination, $quality);

        // Освобождаем память
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $result;
    }

    /**
     * Загрузить несколько файлов с ресайзом
     */
    public function uploadMultipleFiles($fileInput, $prefix = '', $maxWidth = 640, $maxHeight = 480) {
        $uploaded = array();
        $errors = array();

        if (!isset($_FILES[$fileInput])) {
            return array('success' => false, 'error' => 'Файлы не выбраны', 'files' => array());
        }

        $files = $_FILES[$fileInput];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $allowedExts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts)) {
                $errors[] = "Файл {$files['name'][$i]}: недопустимый тип";
                continue;
            }

            if ($files['size'][$i] > 10 * 1024 * 1024) {
                $errors[] = "Файл {$files['name'][$i]}: слишком большой";
                continue;
            }

            $safeName = preg_replace('/[^a-z0-9_-]/i', '', $prefix);
            $filename = ($safeName ? $safeName . '_' : '') . uniqid() . '.jpg';
            $uploadPath = $this->uploadsDir . $filename;

            $resized = $this->resizeImage($files['tmp_name'][$i], $uploadPath, $maxWidth, $maxHeight, 85);

            if ($resized) {
                @chmod($uploadPath, 0644);
                $uploaded[] = array(
                    'filename' => $filename,
                    'path' => 'uploads/' . $filename,
                    'fullpath' => '/uploads/' . $filename
                );
            } else {
                $errors[] = "Файл {$files['name'][$i]}: не удалось сохранить";
            }
        }

        return array(
            'success' => count($uploaded) > 0,
            'files' => $uploaded,
            'errors' => $errors
        );
    }

    public function deleteFile($filename) {
        $basename = basename($filename);
        $filepath = $this->uploadsDir . $basename;

        if (file_exists($filepath)) {
            return @unlink($filepath);
        }

        return false;
    }

    public function getUploadedFiles() {
        $files = array();

        if (!is_dir($this->uploadsDir)) {
            return $files;
        }

        $dir = @opendir($this->uploadsDir);
        if ($dir === false) {
            return $files;
        }

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..' && is_file($this->uploadsDir . $file)) {
                $filepath = $this->uploadsDir . $file;
                $files[] = array(
                    'filename' => $file,
                    'path' => 'uploads/' . $file,
                    'size' => filesize($filepath),
                    'modified' => filemtime($filepath)
                );
            }
        }

        closedir($dir);

        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $files;
    }

    public function cleanOldBackups($keep = 10) {
        $files = glob($this->backupDir . '*.json');

        if (count($files) <= $keep) {
            return;
        }

        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $toDelete = array_slice($files, 0, count($files) - $keep);
        foreach ($toDelete as $file) {
            @unlink($file);
        }
    }

    public function getStats() {
        $dataFiles = glob($this->dataDir . '*.json');
        $uploadFiles = glob($this->uploadsDir . '*');
        $backupFiles = glob($this->backupDir . '*.json');

        $totalSize = 0;
        foreach (array_merge((array)$dataFiles, (array)$uploadFiles, (array)$backupFiles) as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        return array(
            'data_files' => count($dataFiles),
            'uploads' => count($uploadFiles),
            'backups' => count($backupFiles),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        );
    }

    public function getNextId($filename, $arrayKey) {
        $data = $this->get($filename);
        if (!$data || !isset($data[$arrayKey]) || empty($data[$arrayKey])) {
            return 1;
        }

        $maxId = 0;
        foreach ($data[$arrayKey] as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }

        return $maxId + 1;
    }

    public function getUserById($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return null;
        }

        foreach ($users['users'] as $user) {
            if ($user['id'] == $userId) {
                return $user;
            }
        }

        return null;
    }

    public function getUserByUsername($username) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return null;
        }

        foreach ($users['users'] as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }

        return null;
    }

    // ========================================
    // 🎂 МЕТОДЫ ДЛЯ ДНЯ РОЖДЕНИЯ
    // ========================================

    /**
     * Получить пользователей, у которых сегодня день рождения
     */
    public function getUsersBirthdayToday() {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return array();
        }

        $today = date('m-d'); // Текущая дата (месяц-день)
        $birthdays = array();

        foreach ($users['users'] as $user) {
            if (isset($user['birthday']) && !empty($user['birthday'])) {
                // Поддержка разных форматов даты
                $userBirthday = $user['birthday'];
                $timestamp = strtotime($userBirthday);

                if ($timestamp !== false) {
                    $userDate = date('m-d', $timestamp);

                    if ($userDate === $today) {
                        // Вычисляем возраст
                        $birthYear = date('Y', $timestamp);
                        $currentYear = date('Y');
                        $age = $currentYear - $birthYear;

                        $user['age'] = $age;
                        $user['birthday_formatted'] = date('d.m.Y', $timestamp);
                        $birthdays[] = $user;
                    }
                }
            }
        }

        return $birthdays;
    }

    /**
     * Получить ближайшие дни рождения (следующие N дней)
     */
    public function getUpcomingBirthdays($days = 7) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return array();
        }

        $upcomingBirthdays = array();
        $today = time();

        foreach ($users['users'] as $user) {
            if (isset($user['birthday']) && !empty($user['birthday'])) {
                $timestamp = strtotime($user['birthday']);

                if ($timestamp !== false) {
                    $birthMonth = date('m', $timestamp);
                    $birthDay = date('d', $timestamp);
                    $currentYear = date('Y');

                    // Создаем дату ДР в текущем году
                    $nextBirthday = strtotime("$currentYear-$birthMonth-$birthDay");

                    // Если ДР уже прошел в этом году, берем следующий год
                    if ($nextBirthday < $today) {
                        $nextBirthday = strtotime(($currentYear + 1) . "-$birthMonth-$birthDay");
                    }

                    $daysUntil = round(($nextBirthday - $today) / 86400);

                    if ($daysUntil >= 0 && $daysUntil <= $days) {
                        $birthYear = date('Y', $timestamp);
                        $age = $currentYear - $birthYear;
                        if ($nextBirthday < $today) {
                            $age++;
                        }

                        $user['days_until_birthday'] = $daysUntil;
                        $user['age_will_be'] = $age;
                        $user['birthday_formatted'] = date('d.m', $timestamp);
                        $upcomingBirthdays[] = $user;
                    }
                }
            }
        }

        // Сортируем по дням до ДР
        usort($upcomingBirthdays, function($a, $b) {
            return $a['days_until_birthday'] - $b['days_until_birthday'];
        });

        return $upcomingBirthdays;
    }

    /**
     * Обновить дату рождения пользователя
     */
    public function updateUserBirthday($userId, $birthday, $showYear = true) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                $users['users'][$index]['birthday'] = $birthday;
                $users['users'][$index]['show_birthday_year'] = $showYear;
                return $this->save('users', $users);
            }
        }

        return false;
    }

    /**
     * Получить настройки виджета дня рождения
     */
    public function getBirthdaySettings() {
        $settings = $this->get('settings');

        return array(
            'show_widget' => isset($settings['show_birthday_widget']) ? $settings['show_birthday_widget'] : true,
            'show_age' => isset($settings['show_birthday_age']) ? $settings['show_birthday_age'] : true,
            'required' => isset($settings['birthday_required']) ? $settings['birthday_required'] : false
        );
    }

    /**
     * Обновить настройки виджета дня рождения
     */
    public function updateBirthdaySettings($showWidget, $showAge, $required) {
        $settings = $this->get('settings');
        if (!$settings) {
            return false;
        }

        $settings['show_birthday_widget'] = (bool)$showWidget;
        $settings['show_birthday_age'] = (bool)$showAge;
        $settings['birthday_required'] = (bool)$required;

        return $this->save('settings', $settings);
    }

    // ========================================
    // МЕТОДЫ ДЛЯ УПРАВЛЕНИЯ РАЗМЕРОМ ШРИФТА
    // ========================================

    public function getFontSettings() {
        $settings = $this->get('settings');
        if (!$settings) {
            return array(
                'font_size_base' => 16,
                'font_size_min' => 12,
                'font_size_max' => 24,
                'font_size_step' => 1,
                'enable_font_resizer' => true,
                'font_scale_factor' => 1.0
            );
        }

        return array(
            'font_size_base' => isset($settings['font_size_base']) ? $settings['font_size_base'] : 16,
            'font_size_min' => isset($settings['font_size_min']) ? $settings['font_size_min'] : 12,
            'font_size_max' => isset($settings['font_size_max']) ? $settings['font_size_max'] : 24,
            'font_size_step' => isset($settings['font_size_step']) ? $settings['font_size_step'] : 1,
            'enable_font_resizer' => isset($settings['enable_font_resizer']) ? $settings['enable_font_resizer'] : true,
            'font_scale_factor' => isset($settings['font_scale_factor']) ? $settings['font_scale_factor'] : 1.0
        );
    }

    public function updateFontSettings($fontSettings) {
        $settings = $this->get('settings');
        if (!$settings) {
            return false;
        }

        $fontSize = isset($fontSettings['font_size_base']) ? intval($fontSettings['font_size_base']) : 16;
        $fontSize = max(12, min(24, $fontSize));

        $fontMin = isset($fontSettings['font_size_min']) ? intval($fontSettings['font_size_min']) : 12;
        $fontMin = max(10, min(18, $fontMin));

        $fontMax = isset($fontSettings['font_size_max']) ? intval($fontSettings['font_size_max']) : 24;
        $fontMax = max(16, min(32, $fontMax));

        $fontStep = isset($fontSettings['font_size_step']) ? intval($fontSettings['font_size_step']) : 1;
        $fontStep = max(1, min(4, $fontStep));

        $scaleFactor = isset($fontSettings['font_scale_factor']) ? floatval($fontSettings['font_scale_factor']) : 1.0;
        $scaleFactor = max(0.8, min(1.5, $scaleFactor));

        $settings['font_size_base'] = $fontSize;
        $settings['font_size_min'] = $fontMin;
        $settings['font_size_max'] = $fontMax;
        $settings['font_size_step'] = $fontStep;
        $settings['font_scale_factor'] = $scaleFactor;
        $settings['enable_font_resizer'] = isset($fontSettings['enable_font_resizer']) ? (bool)$fontSettings['enable_font_resizer'] : true;

        return $this->save('settings', $settings);
    }

    public function setBaseFontSize($size) {
        $size = intval($size);
        $size = max(12, min(24, $size));

        $settings = $this->get('settings');
        if (!$settings) {
            return false;
        }

        $settings['font_size_base'] = $size;
        return $this->save('settings', $settings);
    }

    public function increaseFontSize() {
        $fontSettings = $this->getFontSettings();
        $currentSize = $fontSettings['font_size_base'];
        $maxSize = $fontSettings['font_size_max'];
        $step = $fontSettings['font_size_step'];

        $newSize = min($currentSize + $step, $maxSize);

        if ($this->setBaseFontSize($newSize)) {
            return array(
                'success' => true,
                'size' => $newSize,
                'message' => 'Размер шрифта увеличен'
            );
        }

        return array(
            'success' => false,
            'size' => $currentSize,
            'message' => 'Ошибка изменения размера'
        );
    }

    public function decreaseFontSize() {
        $fontSettings = $this->getFontSettings();
        $currentSize = $fontSettings['font_size_base'];
        $minSize = $fontSettings['font_size_min'];
        $step = $fontSettings['font_size_step'];

        $newSize = max($currentSize - $step, $minSize);

        if ($this->setBaseFontSize($newSize)) {
            return array(
                'success' => true,
                'size' => $newSize,
                'message' => 'Размер шрифта уменьшен'
            );
        }

        return array(
            'success' => false,
            'size' => $currentSize,
            'message' => 'Ошибка изменения размера'
        );
    }

    public function resetFontSize() {
        if ($this->setBaseFontSize(16)) {
            return array(
                'success' => true,
                'size' => 16,
                'message' => 'Размер шрифта сброшен'
            );
        }

        return array(
            'success' => false,
            'message' => 'Ошибка сброса размера'
        );
    }

    public function setFontScaleFactor($factor) {
        $factor = floatval($factor);
        $factor = max(0.8, min(1.5, $factor));

        $settings = $this->get('settings');
        if (!$settings) {
            return false;
        }

        $settings['font_scale_factor'] = $factor;
        return $this->save('settings', $settings);
    }

    public function toggleFontResizer($enabled) {
        $settings = $this->get('settings');
        if (!$settings) {
            return false;
        }

        $settings['enable_font_resizer'] = (bool)$enabled;
        return $this->save('settings', $settings);
    }

    public function getFontSizeCSS() {
        $fontSettings = $this->getFontSettings();
        $baseSize = $fontSettings['font_size_base'];
        $scaleFactor = $fontSettings['font_scale_factor'];

        $actualSize = round($baseSize * $scaleFactor, 2);

        return ":root { 
            --font-size-base: {$actualSize}px; 
            --font-scale-factor: {$scaleFactor};
            --font-size-small: " . round($actualSize * 0.875, 2) . "px;
            --font-size-large: " . round($actualSize * 1.125, 2) . "px;
            --font-size-xlarge: " . round($actualSize * 1.25, 2) . "px;
        }
        body { font-size: var(--font-size-base); }";
    }

    // ========================================
    // МЕТОДЫ ДЛЯ ИСТОРИИ НАСТРОЕК (JSON)
    // ========================================

    public function saveSettingsHistory($historyData, $description = null, $userId = null) {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            $history = array();
            if (file_exists($historyFile)) {
                $content = file_get_contents($historyFile);
                $history = json_decode($content, true) ?: array();
            }

            $newEntry = array(
                'id' => count($history) + 1,
                'data' => $historyData,
                'timestamp' => time(),
                'description' => $description,
                'user_id' => $userId
            );

            array_unshift($history, $newEntry);
            $history = array_slice($history, 0, 20);

            return file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;

        } catch (Exception $e) {
            error_log("Ошибка сохранения истории настроек: " . $e->getMessage());
            return false;
        }
    }

    public function getSettingsHistory($id) {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            if (!file_exists($historyFile)) {
                return null;
            }

            $content = file_get_contents($historyFile);
            $history = json_decode($content, true);

            if (!$history) {
                return null;
            }

            foreach ($history as $entry) {
                if ($entry['id'] == $id) {
                    return $entry['data'];
                }
            }

            return null;

        } catch (Exception $e) {
            error_log("Ошибка получения истории настроек: " . $e->getMessage());
            return null;
        }
    }

    public function getSettingsHistoryList($limit = 10, $offset = 0) {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            if (!file_exists($historyFile)) {
                return array();
            }

            $content = file_get_contents($historyFile);
            $history = json_decode($content, true);

            if (!$history || !is_array($history)) {
                return array();
            }

            $result = array();
            foreach (array_slice($history, $offset, $limit) as $entry) {
                $result[] = array(
                    'id' => $entry['id'],
                    'timestamp' => $entry['timestamp'],
                    'description' => isset($entry['description']) ? $entry['description'] : null,
                    'user_id' => isset($entry['user_id']) ? $entry['user_id'] : null
                );
            }

            return $result;

        } catch (Exception $e) {
            error_log("Ошибка получения списка истории: " . $e->getMessage());
            return array();
        }
    }

    public function getFullSettingsHistory($id) {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            if (!file_exists($historyFile)) {
                return null;
            }

            $content = file_get_contents($historyFile);
            $history = json_decode($content, true);

            if (!$history) {
                return null;
            }

            foreach ($history as $entry) {
                if ($entry['id'] == $id) {
                    return $entry;
                }
            }

            return null;

        } catch (Exception $e) {
            error_log("Ошибка получения полной истории настроек: " . $e->getMessage());
            return null;
        }
    }

    public function deleteSettingsHistory($id) {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            if (!file_exists($historyFile)) {
                return false;
            }

            $content = file_get_contents($historyFile);
            $history = json_decode($content, true);

            if (!$history) {
                return false;
            }

            $newHistory = array();
            foreach ($history as $entry) {
                if ($entry['id'] != $id) {
                    $newHistory[] = $entry;
                }
            }

            return file_put_contents($historyFile, json_encode($newHistory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;

        } catch (Exception $e) {
            error_log("Ошибка удаления истории настроек: " . $e->getMessage());
            return false;
        }
    }

    public function cleanOldSettingsHistory($keep = 50) {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            if (!file_exists($historyFile)) {
                return true;
            }

            $content = file_get_contents($historyFile);
            $history = json_decode($content, true);

            if (!$history || !is_array($history)) {
                return true;
            }

            $history = array_slice($history, 0, $keep);

            return file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;

        } catch (Exception $e) {
            error_log("Ошибка очистки истории: " . $e->getMessage());
            return false;
        }
    }

    public function cleanupHistory($keepLast = 20) {
        return $this->cleanOldSettingsHistory($keepLast);
    }

    public function getSettingsHistoryCount() {
        try {
            $historyFile = $this->dataDir . 'settings_history.json';

            if (!file_exists($historyFile)) {
                return 0;
            }

            $content = file_get_contents($historyFile);
            $history = json_decode($content, true);

            return $history ? count($history) : 0;

        } catch (Exception $e) {
            error_log("Ошибка подсчета истории настроек: " . $e->getMessage());
            return 0;
        }
    }

    public function restoreSettingsFromHistory($historyId, $settingsType = 'settings') {
        try {
            $historyData = $this->getSettingsHistory($historyId);
            if (!$historyData) {
                return false;
            }

            $settings = is_string($historyData) ? json_decode($historyData, true) : $historyData;
            if (json_last_error() !== JSON_ERROR_NONE && is_string($historyData)) {
                error_log("Ошибка декодирования JSON истории: " . json_last_error_msg());
                return false;
            }

            $currentSettings = $this->get($settingsType);
            if ($currentSettings) {
                $this->saveSettingsHistory(
                    json_encode($currentSettings),
                    "Backup before restore from history #{$historyId}",
                    null
                );
            }

            return $this->save($settingsType, $settings);

        } catch (Exception $e) {
            error_log("Ошибка восстановления настроек из истории: " . $e->getMessage());
            return false;
        }
    }

    public function compareSettingsHistory($id1, $id2) {
        try {
            $data1 = $this->getSettingsHistory($id1);
            $data2 = $this->getSettingsHistory($id2);

            if (!$data1 || !$data2) {
                return null;
            }

            $settings1 = is_string($data1) ? json_decode($data1, true) : $data1;
            $settings2 = is_string($data2) ? json_decode($data2, true) : $data2;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $differences = array();
            $allKeys = array_unique(array_merge(array_keys($settings1), array_keys($settings2)));

            foreach ($allKeys as $key) {
                $value1 = isset($settings1[$key]) ? $settings1[$key] : null;
                $value2 = isset($settings2[$key]) ? $settings2[$key] : null;

                if ($value1 !== $value2) {
                    $differences[$key] = array(
                        'version1' => $value1,
                        'version2' => $value2
                    );
                }
            }

            return $differences;

        } catch (Exception $e) {
            error_log("Ошибка сравнения истории настроек: " . $e->getMessage());
            return null;
        }
    }

    // ========================================
    // МЕТОДЫ ДЛЯ НАСТРОЕК ШАПКИ
    // ========================================

    public function getHeaderSettings() {
        $settings = $this->get('header_settings');

        if (!$settings) {
            $settings = array(
                'mobile_breakpoint' => 768,
                'tablet_breakpoint' => 1024,
                'desktop_breakpoint' => 1280,
                'small_mobile_breakpoint' => 480,

                'header_height_desktop' => 400,
                'header_height_tablet' => 300,
                'header_height_mobile' => 200,
                'header_height_small' => 150,

                'mobile_menu_type' => 'burger',
                'show_logo' => true,
                'logo_image' => '',
                'logo_text' => 'ХОТОШО',
                'logo_position' => 'left',

                'show_search' => true,
                'show_social' => false,
                'show_phone' => false,

                'sticky_header' => true,
                'hide_on_scroll' => false,
                'transparent_header' => false,
                'header_shadow' => true,

                'header_bg_color' => '#4A2C2A',
                'header_text_color' => '#ffffff',
                'header_bg_color_scroll' => '#4A2C2A',

                'menu_animation' => 'fade',
                'menu_animation_speed' => 300,

                'header_font_family' => 'system-ui, -apple-system, sans-serif',
                'header_font_size_desktop' => 14,
                'header_font_size_mobile' => 14,

                'menu_items' => array(),
                'updated_at' => date('Y-m-d H:i:s')
            );
        }

        return $settings;
    }

    public function saveHeaderSettings($headerSettings, $saveHistory = true, $description = null, $userId = null) {
        $headerSettings['updated_at'] = date('Y-m-d H:i:s');

        if ($saveHistory) {
            $this->saveSettingsHistory(
                json_encode($headerSettings),
                $description ? $description : 'Header settings update',
                $userId
            );
        }

        return $this->save('header_settings', $headerSettings);
    }

    public function getAllFrontendSettings() {
        $settings = $this->get('settings');
        $headerSettings = $this->getHeaderSettings();

        return array_merge(
            $settings ? $settings : array(),
            array('header' => $headerSettings)
        );
    }

    public function updateHeaderSetting($key, $value) {
        $settings = $this->getHeaderSettings();
        if (!$settings) {
            return false;
        }

        $settings[$key] = $value;
        $settings['updated_at'] = date('Y-m-d H:i:s');

        return $this->saveHeaderSettings($settings);
    }

    public function addMenuItem($title, $url, $order = null, $icon = '') {
        $settings = $this->getHeaderSettings();
        if (!$settings) {
            return false;
        }

        if (!isset($settings['menu_items'])) {
            $settings['menu_items'] = array();
        }

        if ($order === null) {
            $order = count($settings['menu_items']) + 1;
        }

        $settings['menu_items'][] = array(
            'title' => $title,
            'url' => $url,
            'order' => $order,
            'icon' => $icon
        );

        return $this->saveHeaderSettings($settings);
    }

    public function removeMenuItem($index) {
        $settings = $this->getHeaderSettings();
        if (!$settings || !isset($settings['menu_items'][$index])) {
            return false;
        }

        array_splice($settings['menu_items'], $index, 1);

        return $this->saveHeaderSettings($settings);
    }

    public function updateMenuItem($index, $title, $url, $order, $icon = '') {
        $settings = $this->getHeaderSettings();
        if (!$settings || !isset($settings['menu_items'][$index])) {
            return false;
        }

        $settings['menu_items'][$index] = array(
            'title' => $title,
            'url' => $url,
            'order' => $order,
            'icon' => $icon
        );

        return $this->saveHeaderSettings($settings);
    }

    public function getHeaderHeightForDevice($device = 'desktop') {
        $settings = $this->getHeaderSettings();
        if (!$settings) {
            return 80;
        }

        switch ($device) {
            case 'small':
                return $settings['header_height_small'] ?? 150;
            case 'mobile':
                return $settings['header_height_mobile'] ?? 200;
            case 'tablet':
                return $settings['header_height_tablet'] ?? 300;
            case 'desktop':
            default:
                return $settings['header_height_desktop'] ?? 400;
        }
    }

    // ========================================
    // МЕТОДЫ ДЛЯ ФОРУМА (parents.json)
    // ========================================

    public function getCategoryById($categoryId) {
        $parents = $this->get('parents');
        if (!$parents || !isset($parents['items'])) {
            return null;
        }

        foreach ($parents['items'] as $parent) {
            if ($parent['id'] == $categoryId) {
                return $parent;
            }
        }

        return null;
    }

    public function getForumCategories() {
        $parents = $this->get('parents');
        if (!$parents || !isset($parents['items'])) {
            return array();
        }

        return $parents['items'];
    }

    public function getTopicById($topicId) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return null;
        }

        foreach ($topics['topics'] as $topic) {
            if ($topic['id'] == $topicId) {
                return $topic;
            }
        }

        return null;
    }

    /**
     * Увеличить счётчик просмотров темы
     */
    public function incrementTopicViews($topicId) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return false;
        }

        foreach ($topics['topics'] as $index => $topic) {
            if ($topic['id'] == $topicId) {
                if (!isset($topics['topics'][$index]['views'])) {
                    $topics['topics'][$index]['views'] = 0;
                }
                $topics['topics'][$index]['views']++;
                $this->save('forum_topics', $topics);
                return true;
            }
        }

        return false;
    }

    public function createForumTopic($categoryId, $userId, $title) {
        $topics = $this->get('forum_topics');
        if (!$topics) {
            $topics = array('topics' => array());
        }

        $topicId = time() + count($topics['topics']);

        $newTopic = array(
            'id' => $topicId,
            'category_id' => intval($categoryId),
            'user_id' => intval($userId),
            'title' => $title,
            'views' => 0,
            'pinned' => false,
            'locked' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $topics['topics'][] = $newTopic;

        if ($this->save('forum_topics', $topics)) {
            $this->incrementUserTopicsCount($userId);
            return $topicId;
        }

        return false;
    }

    public function createForumPost($topicId, $userId, $content, $images = array(), $videos = array()) {
        $posts = $this->get('forum_posts');
        if (!$posts) {
            $posts = array('posts' => array());
        }

        $postId = time() + count($posts['posts']);

        $newPost = array(
            'id' => $postId,
            'topic_id' => intval($topicId),
            'user_id' => intval($userId),
            'content' => $content,
            'images' => $images,
            'videos' => $videos,
            'created_at' => date('Y-m-d H:i:s'),
            'likes_count' => 0
        );

        $posts['posts'][] = $newPost;

        if ($this->save('forum_posts', $posts)) {
            $this->incrementUserPostsCount($userId);
            $this->updateTopicTimestamp($topicId);
            $this->notifyTopicSubscribers($topicId, $userId, $postId);
            return $postId;
        }

        return false;
    }

    public function incrementUserTopicsCount($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['topics_count'])) {
                    $users['users'][$index]['topics_count'] = 0;
                }
                $users['users'][$index]['topics_count']++;
                $this->save('users', $users);
                return true;
            }
        }

        return false;
    }

    public function incrementUserPostsCount($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['posts_count'])) {
                    $users['users'][$index]['posts_count'] = 0;
                }
                $users['users'][$index]['posts_count']++;
                $this->save('users', $users);
                return true;
            }
        }

        return false;
    }

    public function decrementUserPostsCount($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['posts_count'])) {
                    $users['users'][$index]['posts_count'] = 0;
                }
                if ($users['users'][$index]['posts_count'] > 0) {
                    $users['users'][$index]['posts_count']--;
                }
                $this->save('users', $users);
                return true;
            }
        }

        return false;
    }

    public function decrementUserTopicsCount($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['topics_count'])) {
                    $users['users'][$index]['topics_count'] = 0;
                }
                if ($users['users'][$index]['topics_count'] > 0) {
                    $users['users'][$index]['topics_count']--;
                }
                $this->save('users', $users);
                return true;
            }
        }

        return false;
    }

    public function updateTopicTimestamp($topicId) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return false;
        }

        foreach ($topics['topics'] as $index => $topic) {
            if ($topic['id'] == $topicId) {
                $topics['topics'][$index]['updated_at'] = date('Y-m-d H:i:s');
                $this->save('forum_topics', $topics);
                return true;
            }
        }

        return false;
    }

    public function updateForumPost($postId, $content, $images = null, $videos = null) {
        $posts = $this->get('forum_posts');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                $posts['posts'][$index]['content'] = $content;

                if ($images !== null) {
                    $posts['posts'][$index]['images'] = $images;
                }

                if ($videos !== null) {
                    $posts['posts'][$index]['videos'] = $videos;
                }

                $posts['posts'][$index]['updated_at'] = date('Y-m-d H:i:s');

                return $this->save('forum_posts', $posts);
            }
        }

        return false;
    }

    public function deleteForumPost($postId) {
        $post = $this->getForumPostById($postId);
        if (!$post) {
            return false;
        }

        $posts = $this->get('forum_posts');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        $updatedPosts = array('posts' => array());

        foreach ($posts['posts'] as $p) {
            if ($p['id'] != $postId) {
                $updatedPosts['posts'][] = $p;
            }
        }

        if ($this->save('forum_posts', $updatedPosts)) {
            $this->decrementUserPostsCount($post['user_id']);
            $this->deleteAllPostLikes($postId);
            return true;
        }

        return false;
    }

    public function deleteForumTopic($topicId) {
        $topic = $this->getForumTopicById($topicId);
        if (!$topic) {
            return false;
        }

        $posts = $this->get('forum_posts');
        if ($posts && isset($posts['posts'])) {
            $updatedPosts = array('posts' => array());
            $deletedPostsCount = 0;

            foreach ($posts['posts'] as $post) {
                if ($post['topic_id'] == $topicId) {
                    $deletedPostsCount++;
                    $this->decrementUserPostsCount($post['user_id']);
                    $this->deleteAllPostLikes($post['id']);
                } else {
                    $updatedPosts['posts'][] = $post;
                }
            }

            $this->save('forum_posts', $updatedPosts);
        }

        $this->unsubscribeAllFromTopic($topicId);

        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return false;
        }

        $updatedTopics = array('topics' => array());

        foreach ($topics['topics'] as $t) {
            if ($t['id'] != $topicId) {
                $updatedTopics['topics'][] = $t;
            }
        }

        if ($this->save('forum_topics', $updatedTopics)) {
            $this->decrementUserTopicsCount($topic['user_id']);
            return true;
        }

        return false;
    }

    public function getForumPostById($postId) {
        $posts = $this->get('forum_posts');
        if (!$posts || !isset($posts['posts'])) {
            return null;
        }

        foreach ($posts['posts'] as $post) {
            if ($post['id'] == $postId) {
                return $post;
            }
        }

        return null;
    }

    public function isPostAuthor($postId, $userId) {
        $post = $this->getForumPostById($postId);
        if (!$post) {
            return false;
        }

        return $post['user_id'] == $userId;
    }

    public function isTopicAuthor($topicId, $userId) {
        $topic = $this->getTopicById($topicId);
        if (!$topic) {
            return false;
        }

        return $topic['user_id'] == $userId;
    }

    // ========================================
    // ПОДПИСКИ НА ТЕМЫ
    // ========================================

    /**
     * Получить подписки пользователя
     */
    public function getUserSubscriptions($userId) {
        $allSubscriptions = $this->get('forum_subscriptions');
        if (!$allSubscriptions || !isset($allSubscriptions['subscriptions'])) {
            return array();
        }

        $userSubscriptions = array();
        foreach ($allSubscriptions['subscriptions'] as $subscription) {
            if ($subscription['user_id'] == $userId) {
                $userSubscriptions[] = $subscription;
            }
        }

        return $userSubscriptions;
    }

    /**
     * Подписаться на тему
     */
    public function subscribeToTopic($userId, $topicId) {
        $allSubscriptions = $this->get('forum_subscriptions');
        if (!$allSubscriptions) {
            $allSubscriptions = array('subscriptions' => array());
        }

        if (!isset($allSubscriptions['subscriptions'])) {
            $allSubscriptions['subscriptions'] = array();
        }

        // Проверка на существующую подписку
        foreach ($allSubscriptions['subscriptions'] as $subscription) {
            if ($subscription['user_id'] == $userId && $subscription['topic_id'] == $topicId) {
                return true; // Уже подписан
            }
        }

        $newSubscription = array(
            'user_id' => intval($userId),
            'topic_id' => intval($topicId),
            'created_at' => date('Y-m-d H:i:s')
        );

        $allSubscriptions['subscriptions'][] = $newSubscription;
        return $this->save('forum_subscriptions', $allSubscriptions);
    }

    /**
     * Отписаться от темы
     */
    public function unsubscribeFromTopic($userId, $topicId) {
        $allSubscriptions = $this->get('forum_subscriptions');
        if (!$allSubscriptions || !isset($allSubscriptions['subscriptions'])) {
            return false;
        }

        $allSubscriptions['subscriptions'] = array_filter($allSubscriptions['subscriptions'], function($subscription) use ($userId, $topicId) {
            return !($subscription['user_id'] == $userId && $subscription['topic_id'] == $topicId);
        });

        $allSubscriptions['subscriptions'] = array_values($allSubscriptions['subscriptions']);
        return $this->save('forum_subscriptions', $allSubscriptions);
    }

    /**
     * Отписаться от всех тем
     */
    public function unsubscribeFromAllTopics($userId) {
        $allSubscriptions = $this->get('forum_subscriptions');
        if (!$allSubscriptions || !isset($allSubscriptions['subscriptions'])) {
            return false;
        }

        $allSubscriptions['subscriptions'] = array_filter($allSubscriptions['subscriptions'], function($subscription) use ($userId) {
            return $subscription['user_id'] != $userId;
        });

        $allSubscriptions['subscriptions'] = array_values($allSubscriptions['subscriptions']);
        return $this->save('forum_subscriptions', $allSubscriptions);
    }

    public function unsubscribeAllFromTopic($topicId) {
        $subscriptions = $this->get('forum_subscriptions');
        if (!$subscriptions || !isset($subscriptions['subscriptions'])) {
            return false;
        }

        $updatedSubs = array('subscriptions' => array());

        foreach ($subscriptions['subscriptions'] as $sub) {
            if ($sub['topic_id'] != $topicId) {
                $updatedSubs['subscriptions'][] = $sub;
            }
        }

        return $this->save('forum_subscriptions', $updatedSubs);
    }

    public function isSubscribedToTopic($userId, $topicId) {
        $subscriptions = $this->get('forum_subscriptions');
        if (!$subscriptions || !isset($subscriptions['subscriptions'])) {
            return false;
        }

        foreach ($subscriptions['subscriptions'] as $sub) {
            if ($sub['user_id'] == $userId && $sub['topic_id'] == $topicId) {
                return true;
            }
        }

        return false;
    }

    public function getTopicSubscribers($topicId) {
        $subscriptions = $this->get('forum_subscriptions');
        if (!$subscriptions || !isset($subscriptions['subscriptions'])) {
            return array();
        }

        $subscribers = array();
        foreach ($subscriptions['subscriptions'] as $sub) {
            if ($sub['topic_id'] == $topicId) {
                $subscribers[] = $sub['user_id'];
            }
        }

        return $subscribers;
    }

    public function toggleTopicSubscription($topicId, $userId) {
        $subscriptions = $this->get('forum_subscriptions');
        if (!$subscriptions) {
            $subscriptions = array('subscriptions' => array());
        }

        $subExists = false;
        $subIndex = -1;

        foreach ($subscriptions['subscriptions'] as $index => $sub) {
            if ($sub['topic_id'] == $topicId && $sub['user_id'] == $userId) {
                $subExists = true;
                $subIndex = $index;
                break;
            }
        }

        if ($subExists) {
            unset($subscriptions['subscriptions'][$subIndex]);
            $subscriptions['subscriptions'] = array_values($subscriptions['subscriptions']);
            $this->save('forum_subscriptions', $subscriptions);
            return array('action' => 'unsubscribed');
        } else {
            $subscriptions['subscriptions'][] = array(
                'id' => time() + count($subscriptions['subscriptions']),
                'topic_id' => intval($topicId),
                'user_id' => intval($userId),
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->save('forum_subscriptions', $subscriptions);
            return array('action' => 'subscribed');
        }
    }

    public function isUserSubscribedToTopic($topicId, $userId) {
        return $this->isSubscribedToTopic($userId, $topicId);
    }

    // ========================================
    // УВЕДОМЛЕНИЯ
    // ========================================

    /**
     * Создать уведомление (универсальный метод)
     */
    public function createNotification($userId, $type, $messageOrRelatedId, $dataOrLink = null, $link = null) {
        $allNotifications = $this->get('forum_notifications');
        if (!$allNotifications) {
            $allNotifications = array('notifications' => array());
        }

        if (!isset($allNotifications['notifications'])) {
            $allNotifications['notifications'] = array();
        }

        // Генерация ID
        $maxId = 0;
        foreach ($allNotifications['notifications'] as $notification) {
            if (isset($notification['id']) && $notification['id'] > $maxId) {
                $maxId = $notification['id'];
            }
        }

        // Поддержка старого формата: createNotification($userId, $type, $message, $data)
        // И нового формата: createNotification($userId, $type, $relatedId, $message, $link)
        if (is_array($dataOrLink)) {
            // Старый формат
            $newNotification = array(
                'id' => $maxId + 1,
                'user_id' => intval($userId),
                'type' => $type,
                'message' => $messageOrRelatedId,
                'data' => $dataOrLink,
                'related_id' => isset($dataOrLink['related_id']) ? $dataOrLink['related_id'] : 0,
                'link' => isset($dataOrLink['link']) ? $dataOrLink['link'] : '',
                'content' => $messageOrRelatedId,
                'is_read' => false,
                'read' => false,
                'created_at' => date('Y-m-d H:i:s')
            );
        } else if (is_numeric($messageOrRelatedId)) {
            // Новый формат с related_id
            $newNotification = array(
                'id' => $maxId + 1,
                'user_id' => intval($userId),
                'type' => $type,
                'related_id' => intval($messageOrRelatedId),
                'message' => $dataOrLink ? $dataOrLink : '',
                'link' => $link ? $link : '',
                'content' => $dataOrLink ? $dataOrLink : '',
                'data' => array(),
                'is_read' => false,
                'read' => false,
                'created_at' => date('Y-m-d H:i:s')
            );
        } else {
            // Формат с message и link
            $newNotification = array(
                'id' => $maxId + 1,
                'user_id' => intval($userId),
                'type' => $type,
                'content' => $messageOrRelatedId,
                'message' => $messageOrRelatedId,
                'link' => $dataOrLink ? $dataOrLink : '',
                'related_id' => 0,
                'data' => array(),
                'is_read' => false,
                'read' => false,
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $allNotifications['notifications'][] = $newNotification;
        return $this->save('forum_notifications', $allNotifications);
    }

    /**
     * Получить уведомления пользователя
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 50) {
        $allNotifications = $this->get('forum_notifications');
        if (!$allNotifications || !isset($allNotifications['notifications'])) {
            return array();
        }

        $userNotifications = array();
        foreach ($allNotifications['notifications'] as $notification) {
            if ($notification['user_id'] == $userId) {
                $isRead = isset($notification['is_read']) ? $notification['is_read'] : (isset($notification['read']) ? $notification['read'] : false);

                if ($unreadOnly && $isRead) {
                    continue;
                }

                $userNotifications[] = $notification;
            }
        }

        // Сортировка по дате (новые первыми)
        usort($userNotifications, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $limit ? array_slice($userNotifications, 0, $limit) : $userNotifications;
    }

    public function getUnreadNotifications($userId) {
        return $this->getUserNotifications($userId, true);
    }

    /**
     * Отметить уведомление как прочитанное
     */
    public function markNotificationAsRead($notificationId, $userId = null) {
        $allNotifications = $this->get('forum_notifications');
        if (!$allNotifications || !isset($allNotifications['notifications'])) {
            return false;
        }

        foreach ($allNotifications['notifications'] as $key => $notification) {
            if ($notification['id'] == $notificationId) {
                // Если указан userId, проверяем принадлежность
                if ($userId !== null && $notification['user_id'] != $userId) {
                    return false;
                }

                $allNotifications['notifications'][$key]['is_read'] = true;
                $allNotifications['notifications'][$key]['read'] = true;
                return $this->save('forum_notifications', $allNotifications);
            }
        }

        return false;
    }

    /**
     * Отметить все уведомления как прочитанные
     */
    public function markAllNotificationsAsRead($userId) {
        $allNotifications = $this->get('forum_notifications');
        if (!$allNotifications || !isset($allNotifications['notifications'])) {
            return false;
        }

        $updated = false;
        foreach ($allNotifications['notifications'] as $key => $notification) {
            if ($notification['user_id'] == $userId) {
                $isRead = isset($notification['is_read']) ? $notification['is_read'] : (isset($notification['read']) ? $notification['read'] : false);

                if (!$isRead) {
                    $allNotifications['notifications'][$key]['is_read'] = true;
                    $allNotifications['notifications'][$key]['read'] = true;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            return $this->save('forum_notifications', $allNotifications);
        }

        return false;
    }

    public function getUnreadNotificationsCount($userId) {
        $notifications = $this->get('forum_notifications');
        if (!$notifications || !isset($notifications['notifications'])) {
            return 0;
        }

        $count = 0;
        foreach ($notifications['notifications'] as $notification) {
            if ($notification['user_id'] == $userId) {
                $isRead = isset($notification['is_read']) ? $notification['is_read'] : (isset($notification['read']) ? $notification['read'] : false);
                if (!$isRead) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Удалить уведомление
     */
    public function deleteNotification($notificationId, $userId = null) {
        $allNotifications = $this->get('forum_notifications');
        if (!$allNotifications || !isset($allNotifications['notifications'])) {
            return false;
        }

        $found = false;
        foreach ($allNotifications['notifications'] as $key => $notification) {
            if ($notification['id'] == $notificationId) {
                // Если указан userId, проверяем принадлежность
                if ($userId !== null && $notification['user_id'] != $userId) {
                    return false;
                }

                unset($allNotifications['notifications'][$key]);
                $allNotifications['notifications'] = array_values($allNotifications['notifications']);
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->save('forum_notifications', $allNotifications);
        }

        return false;
    }

    /**
     * Удалить все уведомления пользователя
     */
    public function deleteAllNotifications($userId) {
        $allNotifications = $this->get('forum_notifications');
        if (!$allNotifications || !isset($allNotifications['notifications'])) {
            return false;
        }

        $allNotifications['notifications'] = array_filter($allNotifications['notifications'], function($notification) use ($userId) {
            return $notification['user_id'] != $userId;
        });

        $allNotifications['notifications'] = array_values($allNotifications['notifications']);
        return $this->save('forum_notifications', $allNotifications);
    }

    public function deleteAllUserNotifications($userId) {
        return $this->deleteAllNotifications($userId);
    }

    public function notifyTopicSubscribers($topicId, $authorId, $postId) {
        $subscribers = $this->getTopicSubscribers($topicId);
        $topic = $this->getForumTopicById($topicId);
        $author = $this->getUserById($authorId);

        if (!$topic || !$author) {
            return false;
        }

        $authorName = isset($author['display_name']) ? $author['display_name'] : $author['username'];

        foreach ($subscribers as $subscriberId) {
            if ($subscriberId == $authorId) {
                continue;
            }

            $message = "Новый ответ от {$authorName} в теме \"{$topic['title']}\"";
            $link = "/forum/topic.php?id={$topicId}#post-{$postId}";

            $this->createNotification($subscriberId, 'new_reply', $postId, $message, $link);

            $subscriber = $this->getUserById($subscriberId);
            if ($subscriber && isset($subscriber['email_notifications']) && $subscriber['email_notifications']) {
                $this->sendEmailNotification($subscriber['email'], $message, $link);
            }
        }

        return true;
    }

    public function sendEmailNotification($email, $message, $link = '') {
        $settings = $this->get('settings');

        if (!$settings || !isset($settings['forum_email_notifications']) || !$settings['forum_email_notifications']) {
            return false;
        }

        $siteTitle = isset($settings['site_title']) ? $settings['site_title'] : 'Форум';
        $subject = "{$siteTitle} - Новое уведомление";

        $fullLink = '';
        if ($link) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $fullLink = $protocol . '://' . $host . $link;
        }

        $body = "{$message}\n\n";
        if ($fullLink) {
            $body .= "Перейти: {$fullLink}\n\n";
        }
        $body .= "---\n{$siteTitle}";

        $headers = "From: noreply@" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return @mail($email, $subject, $body, $headers);
    }

    // ========================================
    // ❤️ СИСТЕМА ЛАЙКОВ ДЛЯ ПОСТОВ ФОРУМА
    // ========================================

    public function likePost($userId, $postId) {
        $likes = $this->get('forum_likes');
        if (!$likes) {
            $likes = array('likes' => array());
        }

        foreach ($likes['likes'] as $like) {
            if ($like['user_id'] == $userId && $like['post_id'] == $postId) {
                return array(
                    'success' => false,
                    'message' => 'Вы уже лайкнули этот пост'
                );
            }
        }

        $likes['likes'][] = array(
            'user_id' => intval($userId),
            'post_id' => intval($postId),
            'created_at' => date('Y-m-d H:i:s')
        );

        if ($this->save('forum_likes', $likes)) {
            $this->updatePostLikesCount($postId);
            $this->incrementUserLikesGiven($userId);

            $post = $this->getForumPostById($postId);
            if ($post) {
                $this->incrementUserLikesReceived($post['user_id']);

                if ($post['user_id'] != $userId) {
                    $liker = $this->getUserById($userId);
                    $likerName = isset($liker['display_name']) ? $liker['display_name'] : $liker['username'];

                    $message = "{$likerName} оценил ваше сообщение";
                    $link = "/forum/topic.php?id={$post['topic_id']}#post-{$postId}";

                    $this->createNotification($post['user_id'], 'like', $postId, $message, $link);
                }
            }

            $likesCount = $this->getPostLikesCount($postId);
            $likers = $this->getPostLikersList($postId);

            return array(
                'success' => true,
                'message' => 'Лайк добавлен',
                'likes_count' => $likesCount,
                'likers' => $likers
            );
        }

        return array(
            'success' => false,
            'message' => 'Ошибка добавления лайка'
        );
    }

    public function unlikePost($userId, $postId) {
        $likes = $this->get('forum_likes');
        if (!$likes || !isset($likes['likes'])) {
            return array(
                'success' => false,
                'message' => 'Лайк не найден'
            );
        }

        $found = false;
        $updatedLikes = array('likes' => array());

        foreach ($likes['likes'] as $like) {
            if ($like['user_id'] == $userId && $like['post_id'] == $postId) {
                $found = true;
            } else {
                $updatedLikes['likes'][] = $like;
            }
        }

        if (!$found) {
            return array(
                'success' => false,
                'message' => 'Лайк не найден'
            );
        }

        if ($this->save('forum_likes', $updatedLikes)) {
            $this->updatePostLikesCount($postId);
            $this->decrementUserLikesGiven($userId);

            $post = $this->getForumPostById($postId);
            if ($post) {
                $this->decrementUserLikesReceived($post['user_id']);
            }

            $likesCount = $this->getPostLikesCount($postId);
            $likers = $this->getPostLikersList($postId);

            return array(
                'success' => true,
                'message' => 'Лайк удален',
                'likes_count' => $likesCount,
                'likers' => $likers
            );
        }

        return array(
            'success' => false,
            'message' => 'Ошибка удаления лайка'
        );
    }

    public function togglePostLike($postId, $userId) {
        $likes = $this->get('forum_likes');
        if (!$likes) {
            $likes = array('likes' => array());
        }

        $likeExists = false;
        $likeIndex = -1;

        foreach ($likes['likes'] as $index => $like) {
            if ($like['post_id'] == $postId && $like['user_id'] == $userId) {
                $likeExists = true;
                $likeIndex = $index;
                break;
            }
        }

        if ($likeExists) {
            unset($likes['likes'][$likeIndex]);
            $likes['likes'] = array_values($likes['likes']);
            $this->save('forum_likes', $likes);

            $this->updatePostLikesCount($postId);
            $this->decrementUserLikesGiven($userId);

            $post = $this->getForumPostById($postId);
            if ($post) {
                $this->decrementUserLikesReceived($post['user_id']);
            }

            return array(
                'action' => 'unliked',
                'count' => $this->countPostLikes($postId)
            );
        } else {
            $likes['likes'][] = array(
                'id' => time() + count($likes['likes']),
                'post_id' => intval($postId),
                'user_id' => intval($userId),
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->save('forum_likes', $likes);

            $this->updatePostLikesCount($postId);
            $this->incrementUserLikesGiven($userId);

            $post = $this->getForumPostById($postId);
            if ($post) {
                $this->incrementUserLikesReceived($post['user_id']);

                if ($post['user_id'] != $userId) {
                    $liker = $this->getUserById($userId);
                    $likerName = isset($liker['display_name']) ? $liker['display_name'] : $liker['username'];

                    $message = "{$likerName} оценил ваше сообщение";
                    $link = "/forum/topic.php?id={$post['topic_id']}#post-{$postId}";

                    $this->createNotification($post['user_id'], 'like', $postId, $message, $link);
                }
            }

            return array(
                'action' => 'liked',
                'count' => $this->countPostLikes($postId)
            );
        }
    }

    public function hasUserLikedPost($userId, $postId = null) {
        if ($postId === null) {
            $postId = $userId;
            $userId = null;
        }

        $likes = $this->get('forum_likes');
        if (!$likes || !isset($likes['likes'])) {
            return false;
        }

        foreach ($likes['likes'] as $like) {
            if ($like['user_id'] == $userId && $like['post_id'] == $postId) {
                return true;
            }
        }

        return false;
    }

    public function countPostLikes($postId) {
        return $this->getPostLikesCount($postId);
    }

    public function getPostLikesCount($postId) {
        $likes = $this->get('forum_likes');
        if (!$likes || !isset($likes['likes'])) {
            return 0;
        }

        $count = 0;
        foreach ($likes['likes'] as $like) {
            if ($like['post_id'] == $postId) {
                $count++;
            }
        }

        return $count;
    }

    public function getPostLikers($postId) {
        $likes = $this->get('forum_likes');
        $users = $this->get('users');

        if (!$likes || !isset($likes['likes']) || !$users || !isset($users['users'])) {
            return array();
        }

        $likers = array();
        foreach ($likes['likes'] as $like) {
            if ($like['post_id'] == $postId) {
                foreach ($users['users'] as $user) {
                    if ($user['id'] == $like['user_id']) {
                        $likers[] = array(
                            'user_id' => $user['id'],
                            'username' => isset($user['display_name']) && !empty($user['display_name']) ? $user['display_name'] : $user['username'],
                            'created_at' => $like['created_at']
                        );
                        break;
                    }
                }
            }
        }

        return $likers;
    }

    public function getPostLikersList($postId) {
        $likes = $this->get('forum_likes');
        if (!$likes || !isset($likes['likes'])) {
            return array();
        }

        $likerIds = array();
        foreach ($likes['likes'] as $like) {
            if ($like['post_id'] == $postId) {
                $likerIds[] = $like['user_id'];
            }
        }

        $likers = array();
        foreach ($likerIds as $userId) {
            $user = $this->getUserById($userId);
            if ($user) {
                $likers[] = array(
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'display_name' => isset($user['display_name']) ? $user['display_name'] : $user['username'],
                    'avatar' => isset($user['avatar']) ? $user['avatar'] : ''
                );
            }
        }

        return $likers;
    }

    private function updatePostLikesCount($postId) {
        $posts = $this->get('forum_posts');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        $likesCount = $this->getPostLikesCount($postId);

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                $posts['posts'][$index]['likes_count'] = $likesCount;
                return $this->save('forum_posts', $posts);
            }
        }

        return false;
    }

    private function incrementUserLikesGiven($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['likes_given'])) {
                    $users['users'][$index]['likes_given'] = 0;
                }
                $users['users'][$index]['likes_given']++;
                return $this->save('users', $users);
            }
        }

        return false;
    }

    private function decrementUserLikesGiven($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['likes_given'])) {
                    $users['users'][$index]['likes_given'] = 0;
                }
                if ($users['users'][$index]['likes_given'] > 0) {
                    $users['users'][$index]['likes_given']--;
                }
                return $this->save('users', $users);
            }
        }

        return false;
    }

    private function incrementUserLikesReceived($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['likes_received'])) {
                    $users['users'][$index]['likes_received'] = 0;
                }
                $users['users'][$index]['likes_received']++;
                return $this->save('users', $users);
            }
        }

        return false;
    }

    private function decrementUserLikesReceived($userId) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return false;
        }

        foreach ($users['users'] as $index => $user) {
            if ($user['id'] == $userId) {
                if (!isset($users['users'][$index]['likes_received'])) {
                    $users['users'][$index]['likes_received'] = 0;
                }
                if ($users['users'][$index]['likes_received'] > 0) {
                    $users['users'][$index]['likes_received']--;
                }
                return $this->save('users', $users);
            }
        }

        return false;
    }

    private function deleteAllPostLikes($postId) {
        $likes = $this->get('forum_likes');
        if (!$likes || !isset($likes['likes'])) {
            return false;
        }

        $updatedLikes = array('likes' => array());

        foreach ($likes['likes'] as $like) {
            if ($like['post_id'] == $postId) {
                $this->decrementUserLikesGiven($like['user_id']);

                $post = $this->getForumPostById($postId);
                if ($post) {
                    $this->decrementUserLikesReceived($post['user_id']);
                }
            } else {
                $updatedLikes['likes'][] = $like;
            }
        }

        return $this->save('forum_likes', $updatedLikes);
    }

    public function getTopLikedPosts($limit = 10, $categoryId = null) {
        $posts = $this->get('forum_posts');
        if (!$posts || !isset($posts['posts'])) {
            return array();
        }

        $postsWithLikes = array();

        foreach ($posts['posts'] as $post) {
            if ($categoryId !== null) {
                $topic = $this->getForumTopicById($post['topic_id']);
                if (!$topic || $topic['category_id'] != $categoryId) {
                    continue;
                }
            }

            $likesCount = $this->getPostLikesCount($post['id']);
            if ($likesCount > 0) {
                $post['likes_count'] = $likesCount;
                $postsWithLikes[] = $post;
            }
        }

        usort($postsWithLikes, function($a, $b) {
            return $b['likes_count'] - $a['likes_count'];
        });

        return array_slice($postsWithLikes, 0, $limit);
    }

    // ========================================
    // ЖАЛОБЫ / РЕПОРТЫ
    // ========================================

    public function reportPost($userId, $postId, $reason) {
        $reports = $this->get('forum_reports');
        if (!$reports) {
            $reports = array('reports' => array());
        }

        $reportId = time() + count($reports['reports']);

        $reports['reports'][] = array(
            'id' => $reportId,
            'user_id' => intval($userId),
            'post_id' => intval($postId),
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'resolved_at' => null,
            'resolved_by' => null
        );

        return $this->save('forum_reports', $reports);
    }

    public function getAllReports($status = null) {
        $reports = $this->get('forum_reports');
        if (!$reports || !isset($reports['reports'])) {
            return array();
        }

        if ($status === null) {
            return $reports['reports'];
        }

        $filtered = array();
        foreach ($reports['reports'] as $report) {
            if ($report['status'] === $status) {
                $filtered[] = $report;
            }
        }

        return $filtered;
    }

    public function updateReportStatus($reportId, $status, $resolvedBy = null) {
        $reports = $this->get('forum_reports');
        if (!$reports || !isset($reports['reports'])) {
            return false;
        }

        foreach ($reports['reports'] as $index => $report) {
            if ($report['id'] == $reportId) {
                $reports['reports'][$index]['status'] = $status;
                if ($status !== 'pending') {
                    $reports['reports'][$index]['resolved_at'] = date('Y-m-d H:i:s');
                    $reports['reports'][$index]['resolved_by'] = $resolvedBy;
                }
                return $this->save('forum_reports', $reports);
            }
        }

        return false;
    }

    // ========================================
    // ЗАКРЕПЛЕНИЕ И БЛОКИРОВКА ТЕМ
    // ========================================

    public function toggleTopicPin($topicId) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return false;
        }

        foreach ($topics['topics'] as $index => $topic) {
            if ($topic['id'] == $topicId) {
                $topics['topics'][$index]['pinned'] = !$topic['pinned'];
                return $this->save('forum_topics', $topics);
            }
        }

        return false;
    }

    public function toggleTopicLock($topicId) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return false;
        }

        foreach ($topics['topics'] as $index => $topic) {
            if ($topic['id'] == $topicId) {
                $topics['topics'][$index]['locked'] = !$topic['locked'];
                return $this->save('forum_topics', $topics);
            }
        }

        return false;
    }

    public function isTopicLocked($topicId) {
        $topic = $this->getForumTopicById($topicId);
        return $topic && isset($topic['locked']) && $topic['locked'];
    }

    // ========================================
    // ПОИСК И СТАТИСТИКА
    // ========================================

    public function searchForum($query, $categoryId = null) {
        $query = mb_strtolower(trim($query));
        if (empty($query)) {
            return array('topics' => array(), 'posts' => array());
        }

        $results = array('topics' => array(), 'posts' => array());

        $topics = $this->get('forum_topics');
        if ($topics && isset($topics['topics'])) {
            foreach ($topics['topics'] as $topic) {
                if ($categoryId && $topic['category_id'] != $categoryId) {
                    continue;
                }

                $title = mb_strtolower($topic['title']);
                if (strpos($title, $query) !== false) {
                    $results['topics'][] = $topic;
                }
            }
        }

        $posts = $this->get('forum_posts');
        if ($posts && isset($posts['posts'])) {
            foreach ($posts['posts'] as $post) {
                $content = mb_strtolower($post['content']);
                if (strpos($content, $query) !== false) {
                    if ($categoryId) {
                        $topic = $this->getForumTopicById($post['topic_id']);
                        if ($topic && $topic['category_id'] == $categoryId) {
                            $results['posts'][] = $post;
                        }
                    } else {
                        $results['posts'][] = $post;
                    }
                }
            }
        }

        return $results;
    }

    public function getLatestTopics($limit = 10, $categoryId = null) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return array();
        }

        $filtered = array();
        foreach ($topics['topics'] as $topic) {
            if ($categoryId === null || $topic['category_id'] == $categoryId) {
                $filtered[] = $topic;
            }
        }

        usort($filtered, function($a, $b) {
            $timeA = isset($a['updated_at']) ? $a['updated_at'] : $a['created_at'];
            $timeB = isset($b['updated_at']) ? $b['updated_at'] : $b['created_at'];
            return strtotime($timeB) - strtotime($timeA);
        });

        return array_slice($filtered, 0, $limit);
    }

    public function getActiveForumUsers($limit = 10) {
        $users = $this->get('users');
        if (!$users || !isset($users['users'])) {
            return array();
        }

        $activeUsers = array();
        foreach ($users['users'] as $user) {
            if (!isset($user['posts_count'])) {
                $user['posts_count'] = 0;
            }
            if (!isset($user['topics_count'])) {
                $user['topics_count'] = 0;
            }
            $user['activity'] = $user['posts_count'] + ($user['topics_count'] * 2);
            $activeUsers[] = $user;
        }

        usort($activeUsers, function($a, $b) {
            return $b['activity'] - $a['activity'];
        });

        return array_slice($activeUsers, 0, $limit);
    }

    /**
     * Получить статистику форума
     */
    public function getForumStats() {
        $topicsData = $this->get('forum_topics');
        $postsData = $this->get('forum_posts');
        $usersData = $this->get('users');

        $stats = array(
            'categories' => 0,
            'topics' => 0,
            'posts' => 0,
            'users' => 0
        );

        $categories = $this->getForumCategories();
        $stats['categories'] = count($categories);

        if ($topicsData && isset($topicsData['topics'])) {
            $stats['topics'] = count($topicsData['topics']);
        }

        if ($postsData && isset($postsData['posts'])) {
            $stats['posts'] = count($postsData['posts']);
        }

        if ($usersData && isset($usersData['users'])) {
            $stats['users'] = count($usersData['users']);
        }

        return $stats;
    }

    public function getForumCategoryById($categoryId) {
        return $this->getCategoryById($categoryId);
    }

    public function getForumCategoryTopics($categoryId, $limit = 50) {
        $topics = $this->get('forum_topics');
        if (!$topics || !isset($topics['topics'])) {
            return array();
        }

        $result = array();
        foreach ($topics['topics'] as $topic) {
            if ($topic['category_id'] == $categoryId) {
                $result[] = $topic;
            }
        }

        usort($result, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($result, 0, $limit);
    }

    public function getForumTopicById($topicId) {
        return $this->getTopicById($topicId);
    }

    public function getForumTopicPosts($topicId) {
        $posts = $this->get('forum_posts');
        if (!$posts || !isset($posts['posts'])) {
            return array();
        }

        $result = array();
        foreach ($posts['posts'] as $post) {
            if ($post['topic_id'] == $topicId) {
                $result[] = $post;
            }
        }

        usort($result, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        return $result;
    }

    // ============================================
    // 📝 БЛОГ - ПОСТЫ
    // ============================================

    /**
     * Генерация slug из заголовка
     */
    private function generateSlug($title) {
        $slug = mb_strtolower(trim($title));

        $translitMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
        ];

        $slug = strtr($slug, $translitMap);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug ?: 'post-' . time();
    }

    /**
     * Создать новый пост блога
     */
    public function createBlogPost($data) {
        $posts = $this->get('blog') ?: ['posts' => []];

        $postId = !empty($posts['posts']) ? max(array_column($posts['posts'], 'id')) + 1 : 1;

        $post = [
            'id' => $postId,
            'title' => $data['title'] ?? '',
            'slug' => $data['slug'] ?? $this->generateSlug($data['title'] ?? ''),
            'content' => $data['content'] ?? '',
            'excerpt' => $data['excerpt'] ?? '',
            'author' => $data['author'] ?? '',
            'author_id' => $data['author_id'] ?? null,
            'featured_image' => $data['featured_image'] ?? '',
            'category' => $data['category'] ?? 'Без категории',
            'category_slug' => $data['category_slug'] ?? 'bez-kategorii',
            'tags' => $data['tags'] ?? [],
            'status' => $data['status'] ?? 'draft',
            'media' => $data['media'] ?? [],
            'meta_title' => $data['meta_title'] ?? $data['title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'og_image' => $data['og_image'] ?? $data['featured_image'] ?? '',
            'likes_count' => 0,
            'views' => 0,
            'comments_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'published_at' => $data['status'] === 'published' ? date('Y-m-d H:i:s') : null
        ];

        $posts['posts'][] = $post;

        if ($this->save('blog', $posts)) {
            return $postId;
        }

        return false;
    }

    /**
     * Обновить пост блога
     */
    public function updateBlogPost($postId, $data) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                foreach ($data as $key => $value) {
                    if ($key !== 'id') {
                        $posts['posts'][$index][$key] = $value;
                    }
                }

                $posts['posts'][$index]['updated_at'] = date('Y-m-d H:i:s');

                if (isset($data['status']) && $data['status'] === 'published' && empty($posts['posts'][$index]['published_at'])) {
                    $posts['posts'][$index]['published_at'] = date('Y-m-d H:i:s');
                }

                return $this->save('blog', $posts);
            }
        }

        return false;
    }

    /**
     * Удалить пост блога
     */
    public function deleteBlogPost($postId) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        $this->deleteAllPostComments($postId);
        $this->deleteAllPostReactions($postId);

        $posts['posts'] = array_filter($posts['posts'], function($p) use ($postId) {
            return $p['id'] != $postId;
        });
        $posts['posts'] = array_values($posts['posts']);

        return $this->save('blog', $posts);
    }

    /**
     * Получить пост блога по ID
     */
    public function getBlogPostById($postId) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return null;
        }

        foreach ($posts['posts'] as $post) {
            if ($post['id'] == $postId) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Получить пост по slug
     */
    public function getBlogPostBySlug($slug) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return null;
        }

        foreach ($posts['posts'] as $post) {
            if ($post['slug'] === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Получить все посты блога с фильтрацией
     */
    public function getBlogPosts($filters = []) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return [];
        }

        $result = $posts['posts'];

        // Фильтр по статусу
        if (isset($filters['status'])) {
            $result = array_filter($result, function($p) use ($filters) {
                return isset($p['status']) && $p['status'] === $filters['status'];
            });
        }

        // Фильтр по категории
        if (isset($filters['category_slug']) && !empty($filters['category_slug'])) {
            $result = array_filter($result, function($p) use ($filters) {
                return isset($p['category_slug']) && $p['category_slug'] === $filters['category_slug'];
            });
        }

        // Фильтр по автору
        if (isset($filters['author_id'])) {
            $result = array_filter($result, function($p) use ($filters) {
                return isset($p['author_id']) && $p['author_id'] == $filters['author_id'];
            });
        }

        // Поиск
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $result = array_filter($result, function($p) use ($search) {
                $title = isset($p['title']) ? mb_strtolower($p['title']) : '';
                $excerpt = isset($p['excerpt']) ? mb_strtolower($p['excerpt']) : '';
                $content = isset($p['content']) ? mb_strtolower($p['content']) : '';

                return stripos($title, $search) !== false || 
                       stripos($excerpt, $search) !== false ||
                       stripos($content, $search) !== false;
            });
        }

        // Сортировка
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        usort($result, function($a, $b) use ($sortBy, $sortOrder) {
            if ($sortBy === 'created_at' || $sortBy === 'published_at') {
                $defaultDate = '2000-01-01 00:00:00';

                $timeA = 0;
                $timeB = 0;

                if ($sortBy === 'published_at') {
                    $timeA = strtotime($a['published_at'] ?? $a['created_at'] ?? $defaultDate);
                    $timeB = strtotime($b['published_at'] ?? $b['created_at'] ?? $defaultDate);
                } else {
                    $timeA = strtotime($a['created_at'] ?? $defaultDate);
                    $timeB = strtotime($b['created_at'] ?? $defaultDate);
                }

                return $sortOrder === 'asc' ? $timeA - $timeB : $timeB - $timeA;
            }

            if ($sortBy === 'views' || $sortBy === 'likes_count') {
                $valA = isset($a[$sortBy]) ? intval($a[$sortBy]) : 0;
                $valB = isset($b[$sortBy]) ? intval($b[$sortBy]) : 0;
                return $sortOrder === 'asc' ? $valA - $valB : $valB - $valA;
            }

            return 0;
        });

        return array_values($result);
    }

    /**
     * Увеличить счётчик просмотров
     */
    public function incrementPostViews($postId) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                if (!isset($posts['posts'][$index]['views'])) {
                    $posts['posts'][$index]['views'] = 0;
                }
                $posts['posts'][$index]['views']++;
                return $this->save('blog', $posts);
            }
        }

        return false;
    }

    // ============================================
    // 📝 БЛОГ - КОММЕНТАРИИ
    // ============================================

    /**
     * Создать комментарий к посту
     */
    public function createBlogComment($postId, $userId, $content, $parentId = null) {
        $comments = $this->get('blog_comments') ?: ['comments' => []];

        $commentId = !empty($comments['comments']) ? max(array_column($comments['comments'], 'id')) + 1 : 1;

        $comment = [
            'id' => $commentId,
            'post_id' => intval($postId),
            'user_id' => intval($userId),
            'parent_id' => $parentId ? intval($parentId) : null,
            'content' => $content,
            'status' => 'approved',
            'likes_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $comments['comments'][] = $comment;

        if ($this->save('blog_comments', $comments)) {
            $this->incrementPostCommentsCount($postId);
            return $commentId;
        }

        return false;
    }

    /**
     * Получить комментарии поста
     */
    public function getBlogPostComments($postId) {
        $comments = $this->get('blog_comments');
        if (!$comments || !isset($comments['comments'])) {
            return [];
        }

        $result = [];
        foreach ($comments['comments'] as $comment) {
            if ($comment['post_id'] == $postId && $comment['status'] === 'approved') {
                $result[] = $comment;
            }
        }

        usort($result, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $result;
    }

    /**
     * Удалить комментарий
     */
    public function deleteBlogComment($commentId) {
        $comments = $this->get('blog_comments');
        if (!$comments || !isset($comments['comments'])) {
            return false;
        }

        $comment = null;
        foreach ($comments['comments'] as $c) {
            if ($c['id'] == $commentId) {
                $comment = $c;
                break;
            }
        }

        if (!$comment) return false;

        $comments['comments'] = array_filter($comments['comments'], function($c) use ($commentId) {
            return $c['id'] != $commentId && $c['parent_id'] != $commentId;
        });
        $comments['comments'] = array_values($comments['comments']);

        if ($this->save('blog_comments', $comments)) {
            $this->decrementPostCommentsCount($comment['post_id']);
            return true;
        }

        return false;
    }

    /**
     * Удалить все комментарии поста
     */
    private function deleteAllPostComments($postId) {
        $comments = $this->get('blog_comments');
        if (!$comments || !isset($comments['comments'])) {
            return false;
        }

        $comments['comments'] = array_filter($comments['comments'], function($c) use ($postId) {
            return $c['post_id'] != $postId;
        });
        $comments['comments'] = array_values($comments['comments']);

        return $this->save('blog_comments', $comments);
    }

    /**
     * Увеличить счётчик комментариев
     */
    private function incrementPostCommentsCount($postId) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                if (!isset($posts['posts'][$index]['comments_count'])) {
                    $posts['posts'][$index]['comments_count'] = 0;
                }
                $posts['posts'][$index]['comments_count']++;
                return $this->save('blog', $posts);
            }
        }

        return false;
    }

    /**
     * Уменьшить счётчик комментариев
     */
    private function decrementPostCommentsCount($postId) {
        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                if (!isset($posts['posts'][$index]['comments_count'])) {
                    $posts['posts'][$index]['comments_count'] = 0;
                }
                if ($posts['posts'][$index]['comments_count'] > 0) {
                    $posts['posts'][$index]['comments_count']--;
                }
                return $this->save('blog', $posts);
            }
        }

        return false;
    }

    // ============================================
    // ❤️ БЛОГ - РЕАКЦИИ (ЭМОДЖИ)
    // ============================================

    /**
     * Добавить реакцию к посту
     */
    public function addBlogReaction($postId, $userId, $emoji) {
        $reactions = $this->get('blog_reactions') ?: ['reactions' => []];

        foreach ($reactions['reactions'] as $index => $reaction) {
            if ($reaction['post_id'] == $postId && $reaction['user_id'] == $userId) {
                if ($reaction['emoji'] === $emoji) {
                    return ['success' => false, 'message' => 'Вы уже поставили эту реакцию'];
                }
                $reactions['reactions'][$index]['emoji'] = $emoji;
                $reactions['reactions'][$index]['updated_at'] = date('Y-m-d H:i:s');
                return $this->save('blog_reactions', $reactions) ? 
                    ['success' => true, 'message' => 'Реакция изменена'] : 
                    ['success' => false, 'message' => 'Ошибка'];
            }
        }

        $reactions['reactions'][] = [
            'id' => time() + count($reactions['reactions']),
            'post_id' => intval($postId),
            'user_id' => intval($userId),
            'emoji' => $emoji,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($this->save('blog_reactions', $reactions)) {
            $this->updatePostLikesCountFromReactions($postId);
            return ['success' => true, 'message' => 'Реакция добавлена'];
        }

        return ['success' => false, 'message' => 'Ошибка'];
    }

    /**
     * Удалить реакцию
     */
    public function removeBlogReaction($postId, $userId) {
        $reactions = $this->get('blog_reactions');
        if (!$reactions || !isset($reactions['reactions'])) {
            return false;
        }

        $reactions['reactions'] = array_filter($reactions['reactions'], function($r) use ($postId, $userId) {
            return !($r['post_id'] == $postId && $r['user_id'] == $userId);
        });
        $reactions['reactions'] = array_values($reactions['reactions']);

        if ($this->save('blog_reactions', $reactions)) {
            $this->updatePostLikesCountFromReactions($postId);
            return true;
        }

        return false;
    }

    /**
     * Получить реакции поста
     */
    public function getBlogPostReactions($postId) {
        $reactions = $this->get('blog_reactions');
        if (!$reactions || !isset($reactions['reactions'])) {
            return [];
        }

        $result = [];
        foreach ($reactions['reactions'] as $reaction) {
            if ($reaction['post_id'] == $postId) {
                $result[] = $reaction;
            }
        }

        return $result;
    }

    /**
     * Удалить все реакции поста
     */
    private function deleteAllPostReactions($postId) {
        $reactions = $this->get('blog_reactions');
        if (!$reactions || !isset($reactions['reactions'])) {
            return false;
        }

        $reactions['reactions'] = array_filter($reactions['reactions'], function($r) use ($postId) {
            return $r['post_id'] != $postId;
        });
        $reactions['reactions'] = array_values($reactions['reactions']);

        return $this->save('blog_reactions', $reactions);
    }

    /**
     * Обновить счётчик лайков из реакций
     */
    private function updatePostLikesCountFromReactions($postId) {
        $reactions = $this->getBlogPostReactions($postId);
        $likesCount = count($reactions);

        $posts = $this->get('blog');
        if (!$posts || !isset($posts['posts'])) {
            return false;
        }

        foreach ($posts['posts'] as $index => $post) {
            if ($post['id'] == $postId) {
                $posts['posts'][$index]['likes_count'] = $likesCount;
                return $this->save('blog', $posts);
            }
        }

        return false;
    }

    // ============================================
    // КАТЕГОРИИ БЛОГА
    // ============================================

    /**
     * Получить категории блога
     */
    public function getBlogCategories() {
        $categories = $this->get('blog_categories');
        if (!$categories || !isset($categories['categories'])) {
            return [];
        }

        usort($categories['categories'], function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });

        return $categories['categories'];
    }

    /**
     * Создать категорию блога
     */
    public function createBlogCategory($name, $slug, $order = null) {
        $categories = $this->get('blog_categories') ?: ['categories' => []];

        $categoryId = !empty($categories['categories']) ? max(array_column($categories['categories'], 'id')) + 1 : 1;

        if ($order === null) {
            $order = count($categories['categories']) + 1;
        }

        $categories['categories'][] = [
            'id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'order' => $order,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->save('blog_categories', $categories) ? $categoryId : false;
    }

    /**
     * Обновить категорию блога
     */
    public function updateBlogCategory($categoryId, $name, $slug, $order) {
        $categories = $this->get('blog_categories');
        if (!$categories || !isset($categories['categories'])) {
            return false;
        }

        foreach ($categories['categories'] as $index => $category) {
            if ($category['id'] == $categoryId) {
                $categories['categories'][$index]['name'] = $name;
                $categories['categories'][$index]['slug'] = $slug;
                $categories['categories'][$index]['order'] = $order;
                return $this->save('blog_categories', $categories);
            }
        }

        return false;
    }

    /**
     * Удалить категорию блога
     */
    public function deleteBlogCategory($categoryId) {
        $categories = $this->get('blog_categories');
        if (!$categories || !isset($categories['categories'])) {
            return false;
        }

        $categories['categories'] = array_filter($categories['categories'], function($c) use ($categoryId) {
            return $c['id'] != $categoryId;
        });
        $categories['categories'] = array_values($categories['categories']);

        return $this->save('blog_categories', $categories);
    }
}
?>
