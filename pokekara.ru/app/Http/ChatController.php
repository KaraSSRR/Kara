<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Request;
use App\Support\Response;
use App\Support\Security;
use App\Domain\Chat\ChatRepository;
use App\Domain\Chat\ChatService;
use App\Domain\Auth\UserRepository;

final class ChatController {
    private ChatRepository $repo;
    private ChatService $service;
    private UserRepository $users;

    public function __construct() {
        $this->repo = new ChatRepository();
        $this->service = new ChatService($this->repo);
        $this->users = new UserRepository();
    }

    public function meta(Request $request): void {
        $uid = Security::requireAuth($request);
        $me = $this->users->findById($uid);
        $clanId = $this->repo->getClanIdForUser($uid);

        Response::apiOk($request, [
            'me' => [
                'id' => (int)$uid,
                'username' => (string)($me['username'] ?? ''),
            ],
            'clan' => $clanId ? ['id' => (int)$clanId] : null,
            'server_time' => date('c'),
        ]);
    }

    public function poll(Request $request): void {
        $uid = Security::requireAuth($request);

        $channel = strtolower((string)($request->query['channel'] ?? 'global'));
        $afterId = (int)($request->query['after_id'] ?? 0);
        $limit = (int)($request->query['limit'] ?? 50);

        if (!in_array($channel, ['global','trade','dm','clan'], true)) {
            Response::apiError($request, 'bad_request', 'Unknown channel', 400);
        }

        $rows = [];
        if ($channel === 'dm') {
            $peerId = (int)($request->query['peer_id'] ?? 0);
            if ($peerId < 0) Response::apiError($request, 'bad_request', 'peer_id required', 400);
            if ($peerId === 0) {
                $rows = $this->repo->pollBotDm($uid, $afterId, $limit);
            } else {
                $rows = $this->repo->pollDm($uid, $peerId, $afterId, $limit);
            }
        } elseif ($channel === 'clan') {
            $clanId = $this->repo->getClanIdForUser($uid);
            if (!$clanId) {
                Response::apiOk($request, [
                    'messages' => [],
                    'server_time' => date('c'),
                ]);
            }
            $rows = $this->repo->pollClan((int)$clanId, $afterId, $limit);
        } else {
            $rows = $this->repo->pollChannel($channel, $afterId, $limit);
        }

        $messages = [];
        foreach ($rows as $r) {
            $fromId = $r['from_user_id'] !== null ? (int)$r['from_user_id'] : null;
            $fromName = $r['from_username'] ?? null;
            $kind = (string)($r['kind'] ?? 'user');
            $botName = $r['bot_name'] ?? null;

            $sender = null;
            if ($kind === 'bot' || $kind === 'system') {
                $sender = [
                    'id' => null,
                    'username' => (string)($botName ?: 'System'),
                ];
            } else {
                $sender = [
                    'id' => $fromId,
                    'username' => (string)($fromName ?: ('#' . (string)$fromId)),
                ];
            }

            $messages[] = [
                'id' => (int)$r['id'],
                'channel' => (string)$r['channel'],
                'kind' => $kind,
                'from' => $sender,
                'to_user_id' => $r['to_user_id'] !== null ? (int)$r['to_user_id'] : null,
                'clan_id' => $r['clan_id'] !== null ? (int)$r['clan_id'] : null,
                'body' => (string)$r['body'],
                'created_at' => (string)$r['created_at'],
            ];
        }

        Response::apiOk($request, [
            'messages' => $messages,
            'server_time' => date('c'),
        ]);
    }

    public function send(Request $request): void {
        $uid = Security::requireAuth($request);
        Security::requireCsrf($request);

        $channel = (string)($request->post['channel'] ?? '');
        $message = (string)($request->post['message'] ?? '');
        $to = $request->post['to_username'] ?? null;

        $channelLower = strtolower(trim($channel));
        $peerId = null;
        $peerUsername = null;
        if ($channelLower === 'dm' && $to !== null) {
            $peerUsername = trim((string)$to);
            if ($peerUsername !== '') {
                $peerId = $this->repo->findUserIdByUsername($peerUsername);
            }
        }

        try {
            $id = $this->service->send($uid, $channel, $message, $to !== null ? (string)$to : null);
        } catch (\InvalidArgumentException $e) {
            $code = $e->getMessage();
            if ($code === 'empty') Response::apiError($request, 'validation', 'Сообщение пустое', 422, ['message' => 'required']);
            if ($code === 'channel') Response::apiError($request, 'validation', 'Неверный канал', 422, ['channel' => 'invalid']);
            if ($code === 'to') Response::apiError($request, 'validation', 'Неверный получатель', 422, ['to_username' => 'invalid']);
            if ($code === 'clan') Response::apiError($request, 'validation', 'Вы не в клане', 422, ['channel' => 'no_clan']);
            Response::apiError($request, 'validation', 'Ошибка валидации', 422);
        } catch (\RuntimeException $e) {
            $code = $e->getMessage();
            if ($code === 'rate_trade') Response::apiError($request, 'rate_limited', 'Лимит торгового чата: 1/30сек и 30/сутки', 429);
            if ($code === 'rate_global') Response::apiError($request, 'rate_limited', 'Слишком часто. Подожди немного.', 429);
            if ($code === 'rate_dm') Response::apiError($request, 'rate_limited', 'Слишком часто. Подожди немного.', 429);
            if ($code === 'rate_clan') Response::apiError($request, 'rate_limited', 'Слишком часто. Подожди немного.', 429);
            Response::apiError($request, 'rate_limited', 'Слишком часто. Подожди немного.', 429);
        }

        $payload = [
            'message_id' => (int)$id,
        ];
        if ($channelLower === 'dm' && $peerId) {
            $payload['peer_id'] = (int)$peerId;
            $payload['peer_username'] = (string)$peerUsername;
        }
        Response::apiOk($request, $payload);
    }

    public function dmPeers(Request $request): void {
        $uid = Security::requireAuth($request);
        $peers = $this->repo->dmPeers($uid, 30);

        Response::apiOk($request, [
            'peers' => $peers,
        ]);
    }
}
