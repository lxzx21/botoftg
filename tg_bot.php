<?php
// Telegram Bot API 配置
class TelegramConfig
{
    public static $botToken = '7598295826:AAF95RxL4K3qjTdRM7z3X1coQahIhiJsW3E';
    public static $apiUrl = "https://api.telegram.org/bot" . self::$botToken . "/";
}

// 日志记录类
class Logger
{
    public static function log($message)
    {
        date_default_timezone_set('Asia/Shanghai');
        $timestamp = date('Y-m-d H:i:s');

        if (!is_dir('log')) {
            mkdir('log', 0777, true);
        }

        file_put_contents('log/telegram_bot.log', "[$timestamp] $message\n", FILE_APPEND);
    }
}

/**
 * 发送Telegram消息
 * @param int $chatId 聊天ID
 * @param string $text 消息内容
 * @param int|null $replyToMessageId 回复的消息ID(可选)
 */
function sendMessage($chatId, $text, $replyToMessageId = null)
{
    global $apiUrl;

    $params = [
        'chat_id' => $chatId,
        'text' => $text
    ];

    if ($replyToMessageId !== null) {
        $params['reply_to_message_id'] = $replyToMessageId;
    }

    $query = http_build_query($params);
    $response = file_get_contents($apiUrl . "sendMessage?$query");
    $responseData = json_decode($response, true);
    Logger::log("发送消息到聊天ID {$chatId}: {$text}");
    return $responseData['result']['message_id'];
}

/**
 * 删除Telegram消息
 * @param int $chatId 聊天ID
 * @param int $messageId 要删除的消息ID
 */
function deleteMessage($chatId, $messageId)
{
    global $apiUrl;

    $params = [
        'chat_id' => $chatId,
        'message_id' => $messageId
    ];

    $query = http_build_query($params);
    file_get_contents($apiUrl . "deleteMessage?$query");
    Logger::log("删除聊天ID {$chatId}中的消息ID {$messageId}");
}

// 数据库配置类
class DatabaseConfig
{
    public static $host = 'localhost';
    public static $user = 'root';
    public static $pass = 'your_password';
    public static $name = 'telegram_bot';
}

// 用户表操作类
class UserModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // 添加用户
    public function addUser($userId, $username, $firstName, $lastName = null)
    {
        $stmt = $this->db->prepare("INSERT INTO users (user_id, username, first_name, last_name) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $username, $firstName, $lastName]);
    }

    // 获取用户
    public function getUser($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 更新用户
    public function updateUser($userId, $username, $firstName, $lastName = null)
    {
        $stmt = $this->db->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ? WHERE user_id = ?");
        return $stmt->execute([$username, $firstName, $lastName, $userId]);
    }

    // 删除用户
    public function deleteUser($userId)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}

// 连接数据库
try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 创建用户表
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL UNIQUE,
        username VARCHAR(255),
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    file_put_contents('/var/log/telegram_bot.log', $e->getMessage(), FILE_APPEND);
    exit;
}

// Webhook处理函数
function processUpdate($update)
{
    global $apiUrl;

    if (!empty($update)) {
        // 处理/start命令
        if (isset($update['message']['text']) && strpos($update['message']['text'], '/start') !== false) {
            $chatId = $update['message']['chat']['id'];
            $messageId = $update['message']['message_id'];
            $text = '你好！我是一个机器人，可以处理命令和监测群聊内容。';
            $isPrivate = $update['message']['chat']['type'] === 'private';
            $msg = sendMessage($chatId, $text, $isPrivate ? null : $messageId);
            deleteMessage($chatId, $messageId);
            sleep(5);
            deleteMessage($chatId, $msg);
        }

        // 处理/bind命令
        if (isset($update['message']['text']) && strpos($update['message']['text'], '/bind') !== false) {
            $chatId = $update['message']['chat']['id'];
            $messageId = $update['message']['message_id'];
            $text = '绑定功能正在开发中，请稍后再试。';
            $isPrivate = $update['message']['chat']['type'] === 'private';
            $msg = sendMessage($chatId, $text, $isPrivate ? null : $messageId);
            deleteMessage($chatId, $messageId);
            sleep(5);
            deleteMessage($chatId, $msg);
        }

        // 消息处理
        if (isset($update['message']['text'])) {
            $text = strtolower($update['message']['text']);
            $chatId = $update['message']['chat']['id'];
            $isPrivate = $update['message']['chat']['type'] === 'private';

            // 检测关键词
            if (strpos($text, '关键词') !== false) {
                $messageId = $update['message']['message_id'];
                sendMessage($chatId, '检测到特定关键词！', $isPrivate ? null : $messageId);
            }

            // 检测回复消息内容为+数字
            if (isset($update['message']['reply_to_message']) && isset($update['message']['from']) && isset($update['message']['reply_to_message']['from']) && preg_match('/^\+\d+$/', $text)) {
                $replier = $update['message']['from'];
                $replied = $update['message']['reply_to_message']['from'];

                $response = "回复者: {$replier['first_name']} ({$replier['id']})\n"
                    . "被回复者: {$replied['first_name']} ({$replied['id']})\n"
                    . "金额: " . preg_replace('/\D/', '', $text);

                $messageId = $update['message']['message_id'];
                sendMessage($chatId, $response, $isPrivate ? null : $messageId);
            }
        }
    }
}

// 设置Webhook
function setWebhook($url)
{
    global $apiUrl;

    $params = ['url' => $url];
    $query = http_build_query($params);
    $response = file_get_contents($apiUrl . "setWebhook?$query");
    Logger::log("设置Webhook到: $url");
    return json_decode($response, true);
}

// 验证Webhook请求
function verifyWebhook($secretToken, $inputToken)
{
    return hash_equals($secretToken, $inputToken);
}

// 主入口
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// 验证Webhook请求


processUpdate($update);


// 处理/start命令
// 假设使用 Telegram Bot SDK 中的系统命令注册方式不正确，通常应注册具体的命令类，这里简单用闭包处理
// 处理/start命令
if (isset($update['message']['text']) && strpos($update['message']['text'], '/start') !== false) {
    $chatId = $update['message']['chat']['id'];
    $messageId = $update['message']['message_id'];
    $text = '你好！我是一个机器人，可以处理命令和监测群聊内容。';
    $isPrivate = $update['message']['chat']['type'] === 'private';
    $msg = sendMessage($chatId, $text, $isPrivate ? null : $messageId);
    deleteMessage($chatId, $messageId);
    sleep(5);
    deleteMessage($chatId, $msg);
}

// 处理/bind命令
if (isset($update['message']['text']) && strpos($update['message']['text'], '/bind') !== false) {
    $chatId = $update['message']['chat']['id'];
    $messageId = $update['message']['message_id'];
    $isPrivate = $update['message']['chat']['type'] === 'private';

    $userModel = new UserModel($db);
    $user = $update['message']['from'];

    // 检查用户是否已存在
    $existingUser = $userModel->getUser($user['id']);

    if ($existingUser) {
        $text = '您已经绑定过账号了！';
    } else {
        // 添加新用户
        $userModel->addUser(
            $user['id'],
            $user['username'] ?? '',
            $user['first_name'],
            $user['last_name'] ?? null
        );
        $text = '账号绑定成功！';
    }

    $msg = sendMessage($chatId, $text, $isPrivate ? null : $messageId);
    deleteMessage($chatId, $messageId);
    sleep(5);
    deleteMessage($chatId, $msg);
}

// 消息处理
if (isset($update['message']['text'])) {
    $text = strtolower($update['message']['text']);
    $chatId = $update['message']['chat']['id'];
    $isPrivate = $update['message']['chat']['type'] === 'private';

    // 检测关键词
    if (strpos($text, '关键词') !== false) {
        $messageId = $update['message']['message_id'];
        sendMessage($chatId, '检测到特定关键词！', $isPrivate ? null : $messageId);
    }

    // 检测回复消息内容为+数字
    if (isset($update['message']['reply_to_message']) && isset($update['message']['from']) && isset($update['message']['reply_to_message']['from']) && preg_match('/^\+\d+$/', $text)) {
        $replier = $update['message']['from'];
        $replied = $update['message']['reply_to_message']['from'];

        $response = "回复者: {$replier['first_name']} ({$replier['id']})\n"
            . "被回复者: {$replied['first_name']} ({$replied['id']})\n"
            . "金额: " . preg_replace('/\D/', '', $text);

        $messageId = $update['message']['message_id'];
        sendMessage($chatId, $response, $isPrivate ? null : $messageId);
    }
}
