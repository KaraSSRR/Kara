<?php
declare(strict_types=1);

namespace App\Domain\Chat;

use App\Support\Security;

final class ChatService {
    private ChatRepository $repo;

    public function __construct(ChatRepository $repo) {
        $this->repo = $repo;
    }

    public function send(int $userId, string $channel, string $message, ?string $toUsername = null): int {
        $channel = strtolower(trim($channel));
        $message = $this->normalizeMessage($message);

        if ($message === '') {
            throw new \InvalidArgumentException('empty');
        }
        if (mb_strlen($message, 'UTF-8') > 400) {
            $message = mb_substr($message, 0, 400, 'UTF-8');
        }

        if (!in_array($channel, ['global','trade','dm','clan'], true)) {
            throw new \InvalidArgumentException('channel');
        }

        // Rate limits (window + daily)
        $key = (string)$userId;
        if ($channel === 'trade') {
            if (!Security::rateLimit('chat:trade', $key, 1, 30) || !Security::rateLimit('chat:trade:day', $key, 30, 86400)) {
                $this->maybeSendTradeBotDm($userId);
                throw new \RuntimeException('rate_trade');
            }
        } elseif ($channel === 'global') {
            if (!Security::rateLimit('chat:global', $key, 6, 12) || !Security::rateLimit('chat:global:day', $key, 200, 86400)) {
                throw new \RuntimeException('rate_global');
            }
        } elseif ($channel === 'dm') {
            if (!Security::rateLimit('chat:dm', $key, 10, 30) || !Security::rateLimit('chat:dm:day', $key, 300, 86400)) {
                throw new \RuntimeException('rate_dm');
            }
        } elseif ($channel === 'clan') {
            if (!Security::rateLimit('chat:clan', $key, 6, 12) || !Security::rateLimit('chat:clan:day', $key, 250, 86400)) {
                throw new \RuntimeException('rate_clan');
            }
        }

        $toUserId = null;
        $clanId = null;

        if ($channel === 'dm') {
            $toUsername = trim((string)$toUsername);
            if ($toUsername === '') throw new \InvalidArgumentException('to');
            $toUserId = $this->repo->findUserIdByUsername($toUsername);
            if (!$toUserId) throw new \InvalidArgumentException('to');
            if ($toUserId === $userId) throw new \InvalidArgumentException('to');
        }

        if ($channel === 'clan') {
            $clanId = $this->repo->getClanIdForUser($userId);
            if (!$clanId) throw new \InvalidArgumentException('clan');
        }

        return $this->repo->insertMessage($channel, 'user', $userId, $toUserId, $clanId, $message, null);
    }

    private function normalizeMessage(string $s): string {
        $s = trim($s);
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        // Single-line only (UI becomes cleaner)
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return trim($s);
    }

    private function maybeSendTradeBotDm(int $userId): void {
        // Avoid spamming bot DMs: 1 per 5 minutes
        if (!Security::rateLimit('chat:trade:warn', (string)$userId, 1, 300)) return;

        $msg = 'TradeBot: лимит торгового чата: 1 сообщение / 30 сек и 30 сообщений / сутки. Используй одно сообщение с полным оффером (что продаёшь/покупаешь, цена, контакт).';
        // Bot DM: kind=bot, from_user_id=NULL, to_user_id=user
        $this->repo->insertMessage('dm', 'bot', null, $userId, null, $msg, 'TradeBot');
    }
}
