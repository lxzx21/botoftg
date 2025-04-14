<?php
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
?>