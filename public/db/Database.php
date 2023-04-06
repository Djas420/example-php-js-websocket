<?php

class Database
{
    public $db;

    public function initDB()
    {
        $file = __DIR__ . '/db.sqlite';
        $this->db = new SQLite3($file);
    }

    public function closeDB()
    {
        $this->db->close();
    }

    public function openDB($name)
    {
        $this->db->open("./$name.sqlite3");
    }

    public function unsetDB()
    {
        $this->db->close();
        unset($this->db);
    }

    public function addUser($uid, $name)
    {
        $uid = $this->db->escapeString($uid);
        $name = $this->db->escapeString($name);
        $sql = "INSERT INTO Users (uid, name) VALUES ('$uid', '$name')";
        $result = $this->db->exec($sql);
        return $result;
    }

    public function getUsersAll()
    {
        $sql = "SELECT uid, name FROM Users";
        $results = $this->db->query($sql);
        if ($results) {
            $result = [];
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                $result[] = $row;
            }
            return $result;
        }
        return false;
    }

    public function getUsersName($name)
    {
        $name = $this->db->escapeString($name);
        $sql = "SELECT name FROM Users WHERE name = '$name'";
        $result = $this->db->query($sql);
        $res = $result->fetchArray();
        return $res;
    }

    public function deleteUser($uid)
    {
        $uid = $this->db->escapeString($uid);
        $sql = "DELETE FROM Users WHERE uid = $uid";
        return $this->db->exec($sql);
    }

    public function addMessage($uid, $name, $message)
    {
        $uid = $this->db->escapeString($uid);
        $name = $this->db->escapeString($name);
        $message = $this->db->escapeString($message);
        $sql = "INSERT INTO Messages (uid, name, message) VALUES ('$uid', '$name', '$message')";
        $result = $this->db->exec($sql);
        return $result;
    }

    public function getMessages()
    {
        $sql = "SELECT name, message FROM Messages ORDER BY id DESC LIMIT 3";
        $results = $this->db->query($sql);
        $result = [];
        if ($results) {
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function clearUsers()
    {
        $sql = "DELETE FROM Users";
        return $this->db->exec($sql);
    }

    public function clearMessages()
    {
        $sql = "DELETE FROM Messages";
        return $this->db->exec($sql);
    }
}
