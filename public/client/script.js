document.addEventListener('DOMContentLoaded', () => {
  const ws = new WebSocket('ws://localhost:2346');

  ws.onopen = function (event) {
    console.info('Соединение установлено: ', event);
  };

  const userList = document.querySelector('.aside__users');
  function generateUserElement(id, name) {
    return `
            <div class="aside__user" id="${id}">${name}</div>
        `;
  }

  const popupName = document.querySelector('.popup__wrap');
  const popupError = document.querySelector('.popup__error');
  const formName = document.querySelector('.popup__form');
  const formNameInput = document.querySelector('.popup__form-input');
  let userId;
  let userName;
  formName.addEventListener('submit', (e) => {
    e.preventDefault();
    userName = formNameInput.value;
    ws.send(JSON.stringify({ event: 'addUser', userId, userName }));
  });

  const messageList = document.querySelector('.messages');
  function generateMessageElement(from, name, text) {
    return `
            <div class="message__item ${from}">
                <span class="message__name">${name}</span>
                <p class="message">${text}</p>
            </div>
        `;
  }

  const formMessage = document.querySelector('.send__form');
  const formMessageInput = document.querySelector('.send__message');
  formMessage.addEventListener('submit', (e) => {
    e.preventDefault();
    const message = formMessageInput.value;
    const msg = generateMessageElement('my', userName, message); // my || his
    messageList.insertAdjacentHTML('beforeend', msg);
    formMessage.reset();
    ws.send(JSON.stringify({
      event: 'message', userId, userName, message,
    }));
  });

  ws.onmessage = (response) => {
    const data = JSON.parse(response.data);
    console.info('data: ', data);

    if (data.event === 'connection') {
      userId = data.userId;
    }

    if (data.event === 'errorUserName') {
      popupError.innerHTML = data.error;
    }

    if (data.event === 'users') {
      data.users.forEach((item) => {
        const user = generateUserElement(item.uid, item.name);
        userList.insertAdjacentHTML('beforeend', user);
      });
      const user = generateUserElement(userId, userName);
      userList.insertAdjacentHTML('beforeend', user);
      popupName.remove();
    }

    if (data.event === 'user') {
      const user = generateUserElement(data.userId, data.userName);
      userList.insertAdjacentHTML('beforeend', user);
    }

    if (data.event === 'message') {
      const msg = generateMessageElement('his', data.userName, data.message);
      messageList.insertAdjacentHTML('beforeend', msg);
    }

    if (data.event === 'messages' && data.messages) {
      data.messages.reverse().forEach((item) => {
        const msg = generateMessageElement('his', item.name, item.message);
        messageList.insertAdjacentHTML('beforeend', msg);
      });
    }

    if (data.event === 'removeUser') {
      document.getElementById(data.userId).remove();
    }
  };

  ws.onclose = function (event) {
    if (event.wasClean) {
      console.info(`Соединение закрыто чисто, код=${event.code} причина=${event.reason}`);
    } else {
      console.info('Соединение прервано: ', event);
      const connectionTerminated = `
                <div class="connection_wrap">
                    <div class="connection">
                        Соединение прервано…
                    </div>
                </div>
            `;
      document.querySelector('body').insertAdjacentHTML('beforeend', connectionTerminated);
      userList.innerHTML = '';
      messageList.innerHTML = '';
    }
  };

  ws.onerror = function (error) {
    console.error('error: ', error);
  };
}, false);
