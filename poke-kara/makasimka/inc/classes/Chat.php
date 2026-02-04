<?php

Class Chat {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if ($type) {

      $this->response =& $response;
      $this->userInfo = &$userInfo;

      // Проверяем, установлен ли 'type' в POST-запросе
      if (!isset($_POST['type'])) {
        $this->response['error'] = "Ошибка: отсутствует параметр 'type' в запросе.";
        return;
      }

      switch ($_POST['type']) {

        case 'startUser':
          if (!is_string($val) || empty($val)) {
            $this->response['error'] = "Ошибка: некорректный логин.";
            return;
          }
          
          $stmt = Work::$sql->prepare("SELECT id, user_group FROM users WHERE login = ?");
          if ($stmt) {
            $stmt->bind_param("s", $val);
            $stmt->execute();
            $data = $stmt->get_result();
            if ($data->num_rows > 0) {
              $user = $data->fetch_assoc();
              $this->response['response'] = [
                'id' => (int)$user['id'],
                'group' => (int)$user['user_group']
              ];
            } else {
              $this->response['error'] = "Ошибка: пользователь не найден.";
            }
            $stmt->close();
          } else {
            $this->response['error'] = "Ошибка: не удалось выполнить запрос.";
          }
        break;

        case 'createUser':
          if (!is_numeric($val) || $val <= 0) {
            $this->response['error'] = "Ошибка: некорректный ID пользователя.";
            return;
          }
          
          $stmt = Work::$sql->prepare("SELECT user_group, login FROM users WHERE id = ?");
          if ($stmt) {
            $stmt->bind_param("i", $val);
            $stmt->execute();
            $data = $stmt->get_result();
            if ($data->num_rows > 0) {
              $user = $data->fetch_assoc();
              $this->response['response'] = [
                'name' => htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8'),
                'group' => (int)$user['user_group']
              ];
            } else {
              $this->response['error'] = "Ошибка: пользователь не найден.";
            }
            $stmt->close();
          } else {
            $this->response['error'] = "Ошибка: не удалось выполнить запрос.";
          }
        break;

        case 'comand':
          if (!is_array($val) || empty($val) || !isset($val[0][0])) {
            $this->response['error'] = "Ошибка: неверный формат команды.";
            return;
          }

          switch ($val[0][0]) {
            case '%notice':
              if (isset($this->userInfo['user_group']) && (in_array($this->userInfo['user_group'], [1]) || ($this->userInfo['dolzn_panel'] ?? 0) == 1)) {
                unset($val[0][0]);
                $msg = implode(' ', $val[0]);

                if (!empty($msg)) {
                  $stmt = Work::$sql->prepare("INSERT INTO adminNotify (author, date, text) VALUES (?, ?, ?)");
                  if ($stmt) {
                    $time = time();
                    $stmt->bind_param("iis", $this->userInfo['id'], $time, $msg);
                    $stmt->execute();
                    $stmt->close();
                    $this->response['response'] = ['error' => 0, 'message' => "Уведомление успешно создано."];
                  } else {
                    $this->response['error'] = "Ошибка: не удалось выполнить запрос.";
                  }
                } else {
                  $this->response['error'] = "Ошибка: сообщение не может быть пустым.";
                }
              } else {
                $this->response['error'] = "Ошибка: у вас недостаточно прав.";
              }
            break;

            default:
              $this->response['error'] = "Ошибка: неизвестная команда.";
            break;
          }
        break;

        default:
          $this->response['error'] = "Ошибка: неизвестный тип запроса.";
        break;
      }
    } else {
      $this->response['error'] = "Ошибка: не передан тип запроса.";
    }
  }
}
