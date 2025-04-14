<?php
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
?>