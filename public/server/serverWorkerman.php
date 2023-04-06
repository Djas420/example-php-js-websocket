<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/Database.php';

use Workerman\Worker;

$DB = new Database();
$DB->initDB();
$DB->clearUsers();
$DB->clearMessages();


// Create a Websocket server
$wsWorker = new Worker('websocket://0.0.0.0:2346');

// Set the process count of the worker instance.
$wsWorker->count = 4;

// Emitted when new connection come
$wsWorker->onConnect = function ($connection) {
    $userId = time();
    $connection->uid = $userId;
    $connection->send(json_encode(['event' => 'connection', 'userId' => $userId]));
    echo "ID client - " . $userId . "\n";
    unset($userId);
};

// Emitted when data received
$wsWorker->onMessage = function ($connection, $data) use ($wsWorker) {
    global $DB;

    $data = json_decode($data, true);

    if ($data['event'] === 'addUser') {
        $userName = $DB->getUsersName($data['userName']);
        if ($userName) {
            $connection->send(json_encode(['event' => 'errorUserName', 'error' => 'A user with the same name already exists']));
        } else {
            $users = $DB->getUsersAll();
            $connection->send(json_encode(['event' => 'users', 'users' => $users]));
            $DB->addUser($data['userId'], $data['userName']);

            $messages = $DB->getMessages();
            $connection->send(json_encode(['event' => 'messages', 'messages' => $messages]));

            foreach ($wsWorker->connections as $clientConnection) {
                if ($clientConnection->uid === $data['userId']) {
                    continue;
                }
                $clientConnection->send(json_encode(['event' => 'user', 'userId' => $data['userId'], 'userName' => $data['userName']]));
            }
        }
    }

    if ($data['event'] === 'message') {
        $DB->addMessage($data['userId'], $data['userName'], $data['message']);

        foreach ($wsWorker->connections as $clientConnection) {
            if ($clientConnection->uid === $data['userId']) {
                continue;
            }
            $clientConnection->send(json_encode($data));
        }
    }
};

// Emitted when connection closed
$wsWorker->onClose = function ($connection) use ($wsWorker) {
    global $DB;
    $DB->deleteUser($connection->uid);
    foreach ($wsWorker->connections as $clientConnection) {
        $clientConnection->send(json_encode(['event' => 'removeUser', 'userId' => $connection->uid]));
    }
    // echo "Connection closed\n";
};

// Run worker
Worker::runAll();
