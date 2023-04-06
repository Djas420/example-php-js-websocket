<?php

class SecFun
{
    public function sendHeaders($headersText, $newSocket, $host, $port)
    {
        $headers = [];
        $tmpLine = preg_split('/\r\n/', $headersText);
        foreach ($tmpLine as $line) {
            $line = trim($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $key = $headers['Sec-WebSocket-Key'];
        $sKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $strHeader = "HTTP/1.1 101 Switching Protocols \r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host\r\n" .
            "WebSocket-Location: ws://$host:$port\r\n" .
            "Sec-WebSocket-Accept:$sKey\r\n\r\n";

        socket_write($newSocket, $strHeader, strlen($strHeader));
    }

    public function createMessage($data)
    {
        return $this->serialize(json_encode($data));
    }

    public function disconnectMessage()
    {
        $userId = time();
        $data = json_encode(['event' => 'removeUser', 'userId' => $userId]);
        $dataACK = $this->serialize($data);
        return $dataACK;
    }

    public function serialize($data)
    {
        $b1 = 0x81;
        $length = strlen($data);
        $dataToSend = '';

        if ($length <= 125) {
            $dataToSend = pack('CC', $b1, $length);
        } else if ($length > 125 && $length <= 65535) {
            $dataToSend = pack('CCn', $b1, 126, $length);
        } else if ($length > 65535) {
            $dataToSend = pack('CCNN', $b1, 127, $length);
        }
        return $dataToSend . $data;
    }

    public function unserialize($dataToBeProcessed)
    {
        $length = ord($dataToBeProcessed[1]) & 127;

        if ($length === 126) {
            $mask = substr($dataToBeProcessed, 4, 4);
            $data = substr($dataToBeProcessed, 8);
        } else if ($length === 127) {
            $mask = substr($dataToBeProcessed, 10, 4);
            $data = substr($dataToBeProcessed, 14);
        } else {
            $mask = substr($dataToBeProcessed, 2, 4);
            $data = substr($dataToBeProcessed, 6);
        }

        $socketStr = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $socketStr .= $data[$i] ^ $mask[$i % 4];
        }
        return $socketStr;
    }

    public function send($data, $socket)
    {
        $dataLength = strlen($data);
        @socket_write($socket, $data, $dataLength);
        return true;
    }

    public function sendAllNotForMe($data, $sockets, $uid)
    {
        $dataLength = strlen($data);

        foreach ($sockets as $key => $socket) {
            if($key === $uid) {
                continue;
            }
            @socket_write($socket, $data, $dataLength);
        }
        return true;
    }

    public function sendAll($data, $sockets)
    {
        $dataLength = strlen($data);

        foreach ($sockets as $socket) {
            @socket_write($socket, $data, $dataLength);
        }
        return true;
    }
}
