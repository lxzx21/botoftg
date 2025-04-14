<?php
// Telegram Bot API 配置
class TelegramConfig
{
    public static $botToken = '7598295826:AAF95RxL4K3qjTdRM7z3X1coQahIhiJsW3E';
    public static $apiUrl = "https://api.telegram.org/bot" . self::$botToken . "/";
}

// 数据库配置类
class DatabaseConfig
{
    public static $host = 'localhost';
    public static $user = 'root';
    public static $pass = 'your_password';
    public static $name = 'telegram_bot';
}
?>