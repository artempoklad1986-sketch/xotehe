<?php
/**
 * SCEditor Configuration - PRODUCTION READY
 * Проверенная конфигурация с работающими CDN ссылками
 */

if (!defined('SCEDITOR_LOADED')) {
    define('SCEDITOR_LOADED', true);
}

function getSCEditorScript($textareaId = 'editor', $height = 400) {
    return <<<HTML
<!-- SCEditor CSS - проверенный CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sceditor@3.2.0/minified/themes/default.min.css">

<!-- SCEditor Core JS -->
<script src="https://cdn.jsdelivr.net/npm/sceditor@3.2.0/minified/sceditor.min.js"></script>

<!-- SCEditor BBCode Format -->
<script src="https://cdn.jsdelivr.net/npm/sceditor@3.2.0/minified/formats/bbcode.js"></script>

<!-- SCEditor Icons -->
<script src="https://cdn.jsdelivr.net/npm/sceditor@3.2.0/minified/icons/monocons.js"></script>

<script>
(function() {
    console.log('[SCEditor] Initializing...');

    // Ожидание загрузки библиотеки
    let checkCount = 0;
    const maxChecks = 100; // 10 секунд

    const checkSCEditor = setInterval(function() {
        checkCount++;

        if (typeof sceditor !== 'undefined') {
            clearInterval(checkSCEditor);
            console.log('[SCEditor] ✓ Library loaded successfully');
            initializeSCEditor();
        } else if (checkCount >= maxChecks) {
            clearInterval(checkSCEditor);
            console.error('[SCEditor] ✗ Failed to load after ' + (maxChecks * 100) + 'ms');
            showFallbackMessage();
        }
    }, 100);

    function showFallbackMessage() {
        const textarea = document.getElementById('{$textareaId}');
        if (textarea) {
            textarea.style.display = 'block';
            textarea.style.minHeight = '{$height}px';
            textarea.style.padding = '10px';
            textarea.style.border = '2px solid #ff0000';
        }

        const warning = document.createElement('div');
        warning.style.cssText = 'background:#fff3cd;border:1px solid #ffc107;padding:15px;margin:10px 0;border-radius:6px;color:#856404;';
        warning.innerHTML = '⚠️ <strong>Визуальный редактор не загрузился.</strong> Используйте BBCode напрямую в текстовом поле.<br><small>Возможные причины: блокировщик рекламы, медленное соединение, проблемы с CDN.</small>';

        if (textarea && textarea.parentNode) {
            textarea.parentNode.insertBefore(warning, textarea);
        }
    }

    function initializeSCEditor() {
        const textarea = document.getElementById('{$textareaId}');

        if (!textarea) {
            console.error('[SCEditor] Textarea #{$textareaId} not found');
            return;
        }

        try {
            // Создание редактора
            sceditor.create(textarea, {
                format: 'bbcode',
                icons: 'monocons',
                style: 'https://cdn.jsdelivr.net/npm/sceditor@3.2.0/minified/themes/content/default.min.css',

                height: {$height},
                width: '100%',

                resizeEnabled: true,
                resizeMinHeight: 200,
                resizeMaxHeight: 800,

                emoticonsEnabled: true,
                emoticonsCompat: true,

                autoExpand: false,
                autoUpdate: true,

                // Панель инструментов
                toolbar: 'bold,italic,underline,strike,subscript,superscript|' +
                         'left,center,right,justify|' +
                         'font,size,color,removeformat|' +
                         'bulletlist,orderedlist,indent,outdent|' +
                         'table|' +
                         'code,quote|' +
                         'horizontalrule|' +
                         'image,link,unlink|' +
                         'emoticon|' +
                         'youtube|' +
                         'source',

                // Дополнительные стили
                fonts: 'Arial,Arial Black,Comic Sans MS,Courier New,Georgia,Impact,Sans-serif,Serif,Times New Roman,Trebuchet MS,Verdana',

                colors: '#000000,#FFFFFF,#FF0000,#00FF00,#0000FF,#FFFF00,#FF00FF,#00FFFF,' +
                        '#800000,#008000,#000080,#808000,#800080,#008080,#C0C0C0,#808080,' +
                        '#FFA500,#A52A2A,#8B4513,#2F4F4F,#FF1493,#1E90FF,#FFD700,#ADFF2F',

                // Параметры BBCode
                bbcodeTrim: true,
                disableBlockRemove: false,

                parserOptions: {
                    breakBeforeBlock: true,
                    breakStartBlock: true,
                    breakEndBlock: true
                },

                // Локализация
                locale: 'ru',

                dateFormat: 'day.month.year'
            });

            console.log('[SCEditor] ✓ Editor initialized successfully for #{$textareaId}');

            // Глобальные функции для работы с редактором
            window.getSCEditorContent = function(editorId) {
                const elem = document.getElementById(editorId);
                const instance = sceditor.instance(elem);
                if (instance) {
                    return instance.val();
                }
                return elem ? elem.value : '';
            };

            window.setSCEditorContent = function(editorId, content) {
                const elem = document.getElementById(editorId);
                const instance = sceditor.instance(elem);
                if (instance) {
                    instance.val(content);
                    return true;
                }
                return false;
            };

            window.insertSCEditorContent = function(editorId, content) {
                const elem = document.getElementById(editorId);
                const instance = sceditor.instance(elem);
                if (instance) {
                    instance.insert(content);
                    return true;
                }
                return false;
            };

            console.log('[SCEditor] ✓ Helper functions registered');

        } catch(error) {
            console.error('[SCEditor] Initialization error:', error);
            showFallbackMessage();
        }
    }
})();
</script>
HTML;
}

function getSCEditorStyles() {
    return <<<CSS
<style>
/* SCEditor container customization */
.sceditor-container {
    border: 1px solid #CCCCCC !important;
    border-radius: 6px !important;
    overflow: hidden;
    font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif !important;
}

.sceditor-toolbar {
    background: #F5F5F5 !important;
    border-bottom: 1px solid #CCCCCC !important;
    padding: 5px !important;
}

.sceditor-group {
    background: transparent !important;
    border: none !important;
}

.sceditor-button {
    color: #333333 !important;
    background: transparent !important;
}

.sceditor-button:hover {
    background: #E5E5E5 !important;
    border-radius: 4px;
}

.sceditor-button.active,
.sceditor-button.hover {
    background: #D5D5D5 !important;
    border-radius: 4px;
}

.sceditor-button div {
    opacity: 0.8;
}

.sceditor-button:hover div,
.sceditor-button.active div {
    opacity: 1;
}

/* Dropdown menus */
.sceditor-dropdown {
    border: 1px solid #CCCCCC !important;
    border-radius: 6px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    background: white !important;
}

.sceditor-dropdown a {
    color: #333333 !important;
}

.sceditor-dropdown a:hover {
    background: #F5F5F5 !important;
}

/* Color picker */
.sceditor-color-option {
    border: 1px solid #E5E5E5 !important;
}

.sceditor-color-option:hover {
    border-color: #2C5F8D !important;
    transform: scale(1.1);
}

/* Editor content area */
.sceditor-container iframe,
.sceditor-container textarea {
    font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif !important;
    font-size: 11px !important;
    line-height: 1.6 !important;
    color: #000000 !important;
}

/* Source mode */
.sceditor-container textarea {
    padding: 10px !important;
    background: #FAFAFA !important;
}

/* Модальное окно для изображений */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
    display: flex;
}

.modal-image {
    max-width: 95vw;
    max-height: 95vh;
    object-fit: contain;
    animation: zoomIn 0.3s ease;
    border-radius: 8px;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10001;
    background: rgba(0, 0, 0, 0.5);
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    user-select: none;
}

.modal-close:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: rotate(90deg);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoomIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .sceditor-container {
        font-size: 13px !important;
    }

    .sceditor-toolbar {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .sceditor-group {
        white-space: nowrap;
    }

    .sceditor-button {
        min-width: 32px;
        min-height: 32px;
    }

    .modal-close {
        top: 10px;
        right: 10px;
        width: 40px;
        height: 40px;
        font-size: 30px;
    }
}

/* Дополнительные стили для контента */
.sceditor-container img {
    max-width: 640px;
    max-height: 480px;
    width: auto;
    height: auto;
    cursor: pointer;
}

.sceditor-container blockquote {
    border-left: 4px solid #2C5F8D;
    padding: 10px 15px;
    margin: 10px 0;
    background: #F9F9F9;
    border-radius: 6px;
}

.sceditor-container code {
    background: #2D2D2D;
    color: #F8F8F2;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

.sceditor-container pre {
    background: #2D2D2D;
    color: #F8F8F2;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    border: 1px solid #444;
}

.sceditor-container pre code {
    background: none;
    padding: 0;
}
</style>
CSS;
}

// Русская локализация
function getSCEditorLocale() {
    return <<<HTML
<script>
if (typeof sceditor !== 'undefined') {
    sceditor.locale['ru'] = {
        'Bold': 'Жирный',
        'Italic': 'Курсив',
        'Underline': 'Подчеркнутый',
        'Strikethrough': 'Зачеркнутый',
        'Left': 'По левому краю',
        'Center': 'По центру',
        'Right': 'По правому краю',
        'Justify': 'По ширине',
        'Font Name': 'Шрифт',
        'Font Size': 'Размер',
        'Font Color': 'Цвет текста',
        'Remove Formatting': 'Убрать форматирование',
        'Bullet list': 'Маркированный список',
        'Numbered list': 'Нумерованный список',
        'Undo': 'Отменить',
        'Redo': 'Повторить',
        'Maximize': 'Развернуть',
        'Image': 'Изображение',
        'Insert a Quote': 'Вставить цитату',
        'Code': 'Код',
        'Insert a Link': 'Вставить ссылку',
        'Unlink': 'Убрать ссылку',
        'Emoticon': 'Смайлик',
        'Insert': 'Вставить',
        'Cancel': 'Отмена',
        'Update': 'Обновить',
        'URL': 'URL',
        'Description': 'Описание',
        'Insert an image': 'Вставить изображение',
        'Insert an email': 'Вставить email',
        'Source': 'Исходный код',
        dateFormat: 'day.month.year'
    };
}
</script>
HTML;
}
?>
