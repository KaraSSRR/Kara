<?php

class Referal {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array $userInfo = [], array $response = []) {
    $this->userInfo = $userInfo;
    $this->response = $response;

    if ($type) {
      switch ($type) {

        case 'reward':
          $this->handleReward($val);
          break;

        case 'open':
          $this->handleOpen();
          break;

        case 'generate':
          $this->handleGenerateCode();
          break;

        // Добавляйте другие кейсы при необходимости

      }
    }
  }

  private function handleReward($val) {
    $userId = $this->userInfo['id'];

    $stmt = Work::$sql->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $val);
    $stmt->execute();
    $data = $stmt->get_result();
    $referal = $data->fetch_assoc();

    if (!$referal) {
      $this->setError('Реферал не найден.');
      return;
    }

    $stmtrm = Work::$sql->prepare("SELECT * FROM referals WHERE user1 = ? AND user2 = ?");
    $stmtrm->bind_param("ii", $userId, $referal['id']);
    $stmtrm->execute();
    $datarm = $stmtrm->get_result();
    $referalMy = $datarm->fetch_assoc();

    if (!$referalMy) {
      $this->setError('Данный реферал принадлежит не вам.');
      return;
    }

    $pve = $referal["population"];
    if ($referal['battleCount'] < 10 || $pve < 700) {
      $this->setError('Не выполнены условия получения награды за реферала. Ваш реферал должен быть не ниже ранга Известный и провести хотя бы 10 боев между другими тренерами.');
      return;
    }

    if ($referalMy['status'] == 0) {
      $text = 'Получена награда за вашего реферала.';
      $error = 'success';
      $plus = '<img src="/img/world/items/little/43.png" class="item"> Жемчуг x3';
      itemAdd(43, 3);
      $a = 1;
      $stmt = Work::$sql->prepare("UPDATE referals SET status = ? WHERE user2 = ?");
      $stmt->bind_param("ii", $a, $referal['id']);
      $stmt->execute();
    } else {
      $this->setError('За данного реферала уже получена награда.');
      return;
    }

    $this->response['response'] = [
      'text' => $text,
      'error' => $error,
      'plus' => (isset($plus) ? $plus : 0)
    ];
  }

  private function handleOpen() {
    $userId = $this->userInfo['id'];
    $stmt = Work::$sql->prepare("SELECT * FROM referals WHERE user1 = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $referals = $stmt->get_result();
    $referalsList = [];
    while ($referal = $referals->fetch_assoc()) {
      $referalsList[$referal['id']] = [
        'User' => Info::getMainUser(['id', $referal['user2']]),
        'Status' => $referal['status']
      ];
    }
    $this->response['response'] = [
      'referals' => $referalsList
    ];
  }

  // Новый кейс: генерация реферального кода
  private function handleGenerateCode() {
    $userId = $this->userInfo['id'];
    // Проверяем, есть ли уже код
    $stmt = Work::$sql->prepare("SELECT referal FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data = $stmt->get_result();
    $user = $data->fetch_assoc();

    if (!empty($user['referal'])) {
      $this->response['response'] = [
        'referal' => $user['referal'],
        'message' => 'Ваш реферальный код уже создан.'
      ];
      return;
    }

    // Генерируем код (например: RFL-<рандом>)
    $referalCode = 'RFL-' . substr(md5(uniqid($userId, true)), 0, 8);

    $stmt = Work::$sql->prepare("UPDATE users SET referal = ? WHERE id = ?");
    $stmt->bind_param("si", $referalCode, $userId);
    $stmt->execute();

    $this->response['response'] = [
      'referal' => $referalCode,
      'message' => 'Реферальный код успешно сгенерирован.'
    ];
  }

  private function setError($text) {
    $this->response['response'] = [
      'text' => $text,
      'error' => 'error',
      'plus' => 0
    ];
  }

  public function getResponse() {
    return $this->response;
  }
}