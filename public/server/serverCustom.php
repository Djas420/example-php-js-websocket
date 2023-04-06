<?php
define('PORT', '2346');
define('IP', '0');
define('HOST', 'localhost');
define('SIZE', 1024);

require_once __DIR__ . '/SecFun.php';
require_once __DIR__ . '/../db/Database.php';

$DB = new Database();
$DB->initDB();
$DB->clearUsers();
$DB->clearMessages();

$secFun = new SecFun();

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, IP, PORT);

socket_listen($socket);

$readSocket = [$socket];
$writeSocket = [];
$exceptSocket = [];

while (true) {
    $newReadSocket = $readSocket;
    $newWriteSocket = $writeSocket;
    $newExceptSocket = $exceptSocket;
    socket_select($newReadSocket, $newWriteSocket, $newExceptSocket, 0, 10);

    if (in_array($socket, $newReadSocket)) {
        $newSocket = socket_accept($socket);
        $uid = time();
        $readSocket[$uid] = $newSocket;

        $header = socket_read($newSocket, SIZE);
        $secFun->sendHeaders($header, $newSocket, HOST, PORT);

        $eventConnection = $secFun->createMessage(['event' => 'connection', 'userId' => $uid]);
        $secFun->send($eventConnection, $newSocket);

        $newReadSocketIndex = array_search($socket, $newReadSocket);
        unset($newReadSocket[$newReadSocketIndex]);
    }

    foreach ($newReadSocket as $key => $newReadSocketResource) {
        echo "Key: $key". PHP_EOL;
        while (socket_recv($newReadSocketResource, $socketData, SIZE, 0) >= 1) {
            $socketMessage = $secFun->unserialize($socketData);
            $socketMessage = json_decode($socketMessage, true);

            $socketMessageCount = $socketMessage !== NULL;
            if ($socketMessageCount && $socketMessage['event'] === 'addUser') {
                $userName = $DB->getUsersName($socketMessage['userName']);
                if ($userName) {
                    $eventErrorUserName = $secFun->createMessage([
                        'event' => 'errorUserName',
                        'error' => 'A user with the same name already exists'
                    ]);
                    $secFun->send($eventErrorUserName, $newReadSocketResource);
                } else {
                    $users = $DB->getUsersAll();
                    $eventUsers = $secFun->createMessage([
                        'event' => 'users',
                        'users' => $users
                    ]);
                    $secFun->send($eventUsers, $newReadSocketResource);
                    $DB->addUser($socketMessage['userId'], $socketMessage['userName']);

                    $messages = $DB->getMessages();
                    $eventMessages = $secFun->createMessage([
                        'event' => 'messages',
                        'messages' => $messages
                    ]);
                    $secFun->send($eventMessages, $newReadSocketResource);

                    $eventUser = $secFun->createMessage([
                        'event' => 'user',
                        'userId' => $socketMessage['userId'],
                        'userName' => $socketMessage['userName']
                    ]);
                    $secFun->sendAllNotForMe($eventUser, $readSocket, $socketMessage['userId']);
                }
            }

            if($socketMessageCount && $socketMessage['event'] === 'message') {
                $DB->addMessage($socketMessage['userId'], $socketMessage['userName'], $socketMessage['message']);
                $eventMessage = $secFun->createMessage($socketMessage);
                $secFun->sendAllNotForMe($eventMessage, $readSocket, $socketMessage['userId']);
            }

            break 2;
        }

        $socketData = @socket_read($newReadSocketResource, SIZE, PHP_NORMAL_READ);
        if ((bool) $socketData === false) {
            $DB->deleteUser($key);
            $eventRemoveUser = $secFun->createMessage(['event' => 'removeUser', 'userId' => $key]);
            $secFun->sendAll($eventRemoveUser, $readSocket);

            $newReadSocketIndex = array_search($newReadSocketResource, $readSocket);
            unset($readSocket[$newReadSocketIndex]);
        }
    }
}

socket_close($socket);
