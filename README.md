# Example php js websocket

## Description

An example of using webSocket in php and js.

Minimum required to install:
PHP, Node.js

Then

```shell
npm install
```

First option:
native php + native js

```shell
php public/server/serverCustom.php start
```

```shell
http-server public/client
```

Second option:
Using the library
<https://github.com/walkor/workerman>
Workerman php + native js

```shell
php public/server/serverWorkerman.php start
```

```shell
http-server public/client
```
