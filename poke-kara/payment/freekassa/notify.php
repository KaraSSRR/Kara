\
    <?php
    /**
     * FreeKassa notification (Result URL / URL оповещения)
     *
     * Critical notes:
     * - FreeKassa will POST (form-data) payment info to this endpoint after a successful payment.
     * - Signature for NOTIFICATION is:
     *     md5(MERCHANT_ID:AMOUNT:SECRET_WORD_2:MERCHANT_ORDER_ID)
     *   (This differs from the payment form signature, which includes currency and SECRET_WORD_1.)
     *
     * Recommended:
     * - Validate SIGN and (optionally) sender IP (taking proxies/Cloudflare into account).
     * - Make processing idempotent (use intid, or a file marker) to avoid double credits on retries.
     */

    require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/conf/global.php';

    // ---- Config (prefer ENV, keep safe fallbacks) ----
    $EXPECTED_MERCHANT_ID = getenv('FREEKASSA_MERCHANT_ID') ?: '';
    $SECRET_WORD_2        = getenv('FREEKASSA_SECRET2') ?: 'RNW$ZG)zD-*ZcG/';

    $ITEM_ID         = (int)(getenv('FREEKASSA_ITEM_ID') ?: 25);
    $PRICE_PER_ITEM  = (float)(getenv('FREEKASSA_PRICE_PER_ITEM') ?: 10);

    // Logging (optional)
    $DEBUG_LOG = getenv('FREEKASSA_DEBUG_LOG') ?: ''; // e.g. /var/log/freekassa_notify.log

    // IP check (optional, recommended by FK docs)
    $SKIP_IP_CHECK = (getenv('FREEKASSA_SKIP_IP_CHECK') === '1');
    $DEFAULT_ALLOWED_IPS = ['168.119.157.136', '168.119.60.227', '178.154.197.79', '51.250.54.238']; // FK docs
    $EXTRA_ALLOWED_IPS = array_filter(array_map('trim', explode(',', (string)getenv('FREEKASSA_ALLOWED_IPS'))));
    $ALLOWED_IPS = array_values(array_unique(array_merge($DEFAULT_ALLOWED_IPS, $EXTRA_ALLOWED_IPS)));

    // Idempotency markers (filesystem, no DB schema dependency)
    $MARKER_DIR = getenv('FREEKASSA_MARKER_DIR') ?: (sys_get_temp_dir() . '/freekassa_markers');

    // ---- Helpers ----
    function fk_get_request_ip(): array {
        // Return: [real_ip, remote_addr]
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        // Cloudflare (best-effort)
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return [trim($_SERVER['HTTP_CF_CONNECTING_IP']), $remote];
        }
        // Some reverse proxies
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return [trim($_SERVER['HTTP_X_REAL_IP']), $remote];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return [trim($parts[0]), $remote];
        }
        return [$remote, $remote];
    }

    function fk_log(string $path, array $fields): void {
        if ($path === '') return;
        $ts = date('Y-m-d H:i:s');
        $pairs = [];
        foreach ($fields as $k => $v) {
            if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $pairs[] = $k . ':' . $v;
        }
        @file_put_contents($path, $ts . '|' . implode('|', $pairs) . PHP_EOL, FILE_APPEND);
    }

    function fk_amount_to_float(string $amount_raw): float {
        // Keep signature verification on raw string, but for math use normalized float.
        $norm = str_replace(',', '.', trim($amount_raw));
        return (float)$norm;
    }

    function fk_extract_user_id(string $order_id, array $req): int {
        // 1) Prefer us_id (FreeKassa returns all us_* fields back to notification URL)
        $candidates = [
            $req['us_id'] ?? null,
            $req['us_user_id'] ?? null,
            $req['us_uid'] ?? null,
            $req['us_user'] ?? null,
        ];
        foreach ($candidates as $c) {
            if ($c !== null && $c !== '' && ctype_digit((string)$c)) return (int)$c;
        }

        $oid = trim($order_id);

        // 2) Pattern: "<uid>-<timestamp>" or "<uid>_<timestamp>"
        if (preg_match('/^(\d+)[-_](\d{9,12})$/', $oid, $m)) {
            return (int)$m[1];
        }

        // 3) Pattern: "<uid><timestamp>" (e.g. 4 + 1751880187 => 41751880187)
        if (ctype_digit($oid) && strlen($oid) >= 11) {
            $ts = (int)substr($oid, -10);
            // plausible unix ts range (2000-2038)
            if ($ts >= 946684800 && $ts <= 2147483647) {
                $prefix = substr($oid, 0, -10);
                if ($prefix !== '' && ctype_digit($prefix)) return (int)$prefix;
            }
        }

        // 4) If order_id itself is a small integer, accept it
        if (ctype_digit($oid) && strlen($oid) <= 9) return (int)$oid;

        return 0;
    }

    function fk_marker_open(string $dir, string $key) {
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = rtrim($dir, '/\\') . '/fk_' . $key . '.json';
        $fh = @fopen($file, 'c+');
        if (!$fh) return [null, $file, null];
        if (!flock($fh, LOCK_EX)) return [$fh, $file, null];
        // read existing
        $contents = stream_get_contents($fh);
        $data = null;
        if ($contents !== false && trim($contents) !== '') {
            $decoded = json_decode($contents, true);
            if (is_array($decoded)) $data = $decoded;
        }
        return [$fh, $file, $data];
    }

    function fk_marker_write($fh, array $data): void {
        if (!$fh) return;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fh);
    }

    // ---- Read request ----
    $req = $_REQUEST;

    $merchant_id = isset($req['MERCHANT_ID']) ? trim((string)$req['MERCHANT_ID']) : '';
    $amount_raw  = isset($req['AMOUNT']) ? trim((string)$req['AMOUNT']) : '';
    $order_id    = isset($req['MERCHANT_ORDER_ID']) ? trim((string)$req['MERCHANT_ORDER_ID']) : '';
    $sign        = isset($req['SIGN']) ? trim((string)$req['SIGN']) : '';
    $intid       = isset($req['intid']) ? trim((string)$req['intid']) : '';
    $count_param = isset($req['count']) ? (int)$req['count'] : 0;

    [$real_ip, $remote_addr] = fk_get_request_ip();

    // Minimal log of incoming request (safe fields only)
    fk_log($DEBUG_LOG, [
        'stage' => 'in',
        'merchant' => $merchant_id,
        'order' => $order_id,
        'amount' => $amount_raw,
        'intid' => $intid,
        'ip' => $real_ip,
        'ra' => $remote_addr,
        'sign' => ($sign !== '' ? substr($sign, 0, 32) : ''),
    ]);

    // ---- Validate required params ----
    if ($merchant_id === '' || $amount_raw === '' || $order_id === '' || $sign === '') {
        fk_log($DEBUG_LOG, ['stage'=>'fail','reason'=>'missing_param']);
        http_response_code(400);
        exit('missing param');
    }

    // ---- Optional merchant id check ----
    if ($EXPECTED_MERCHANT_ID !== '' && $merchant_id !== $EXPECTED_MERCHANT_ID) {
        fk_log($DEBUG_LOG, ['stage'=>'fail','reason'=>'merchant_mismatch','expected'=>$EXPECTED_MERCHANT_ID]);
        http_response_code(403);
        exit('merchant mismatch');
    }

    // ---- Optional IP check ----
    if (!$SKIP_IP_CHECK) {
        if (!in_array($real_ip, $ALLOWED_IPS, true)) {
            fk_log($DEBUG_LOG, [
                'stage' => 'fail',
                'reason' => 'ip_not_allowed',
                'ip' => $real_ip,
                'allowed' => $ALLOWED_IPS
            ]);
            http_response_code(403);
            exit('hacking attempt');
        }
    }

    // ---- Signature check (NOTIFICATION) ----
    $expected_sign = md5($merchant_id . ':' . $amount_raw . ':' . $SECRET_WORD_2 . ':' . $order_id);
    if (strtolower($sign) !== strtolower($expected_sign)) {
        fk_log($DEBUG_LOG, [
            'stage' => 'fail',
            'reason' => 'bad_sign',
            'expected' => $expected_sign,
        ]);
        http_response_code(403);
        exit('bad sign');
    }

    // ---- Determine user & item count ----
    $user_id = fk_extract_user_id($order_id, $req);
    $amount_val = fk_amount_to_float($amount_raw);

    // Default: 1 item per PRICE_PER_ITEM currency units (floor)
    $items_to_add = (int)floor($amount_val / max(0.000001, $PRICE_PER_ITEM));
    // If your form sends "count", you can force exact item count
    if ($count_param > 0) $items_to_add = $count_param;

    if ($user_id <= 0) {
        fk_log($DEBUG_LOG, [
            'stage'=>'fail',
            'reason'=>'no_user_id',
            'order'=>$order_id
        ]);
        // Do NOT confirm (so FK retries) — otherwise payment will be lost.
        http_response_code(422);
        exit('no user');
    }

    if ($items_to_add <= 0) {
        // Payment is valid, but amount is below threshold.
        // Confirm to FK to stop retries, but log for review.
        fk_log($DEBUG_LOG, [
            'stage'=>'ok',
            'reason'=>'amount_too_small',
            'user'=>$user_id,
            'items'=>0,
            'amount'=>$amount_val
        ]);
        exit('YES');
    }

    // ---- Idempotency key ----
    $uniq = ($intid !== '') ? ('intid:' . $intid) : ('fallback:' . $order_id . '|' . $amount_raw . '|' . $sign);
    $key = sha1($uniq);

    [$fh, $marker_file, $marker_data] = fk_marker_open($MARKER_DIR, $key);
    if ($marker_data && isset($marker_data['status']) && $marker_data['status'] === 'done') {
        fk_log($DEBUG_LOG, [
            'stage'=>'ok',
            'reason'=>'duplicate',
            'user'=>$user_id,
            'order'=>$order_id,
            'intid'=>$intid
        ]);
        if ($fh) { flock($fh, LOCK_UN); fclose($fh); }
        exit('YES');
    }

    // ---- Apply credit (transaction best-effort) ----
    $ok = false;
    $err = '';

    // $mysqli is expected from global.php
    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $err = 'no mysqli';
    } else {
        try {
            // Some projects use MyISAM; begin_transaction will silently fail in that case.
            if (method_exists($mysqli, 'begin_transaction')) {
                @$mysqli->begin_transaction();
            }

            // Check existing record
            $stmt = $mysqli->prepare("SELECT id, count FROM items_users WHERE user = ? AND item_id = ? LIMIT 1");
            if (!$stmt) throw new Exception('prepare select items_users failed: ' . $mysqli->error);
            $stmt->bind_param("ii", $user_id, $ITEM_ID);
            if (!$stmt->execute()) throw new Exception('execute select failed: ' . $stmt->error);
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($row_id, $cur_count);
                $stmt->fetch();
                $stmt->close();

                $new_count = (int)$cur_count + (int)$items_to_add;

                $stmt2 = $mysqli->prepare("UPDATE items_users SET count = ? WHERE id = ? LIMIT 1");
                if (!$stmt2) throw new Exception('prepare update failed: ' . $mysqli->error);
                $stmt2->bind_param("ii", $new_count, $row_id);
                if (!$stmt2->execute()) throw new Exception('execute update failed: ' . $stmt2->error);
                $stmt2->close();
            } else {
                $stmt->close();

                $stmt2 = $mysqli->prepare("INSERT INTO items_users (item_id, count, user) VALUES (?, ?, ?)");
                if (!$stmt2) throw new Exception('prepare insert items_users failed: ' . $mysqli->error);
                $stmt2->bind_param("iii", $ITEM_ID, $items_to_add, $user_id);
                if (!$stmt2->execute()) throw new Exception('execute insert failed: ' . $stmt2->error);
                $stmt2->close();
            }

            // Optional: try to record the payment (non-fatal if schema differs)
            // Note: do NOT throw on failure here, because marker already provides idempotency.
            $payment_logged = false;
            $sql_candidates = [
                // common schema variants
                ["INSERT INTO payments (order_id, user_id, amount, item_id, items_added, fk_intid, date) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                 "sidiii"],
                ["INSERT INTO payments (order_id, user_id, amount, item_id, items_added, date) VALUES (?, ?, ?, ?, ?, NOW())",
                 "sidii"],
                ["INSERT INTO payments (order_id, user, amount, item_id, items_added, date) VALUES (?, ?, ?, ?, ?, NOW())",
                 "sidii"],
            ];

            foreach ($sql_candidates as $cand) {
                [$sql, $types] = $cand;
                $st = @$mysqli->prepare($sql);
                if (!$st) continue;

                // Bind based on pattern
                if ($types === "sidiii") {
                    // order_id (s), user_id (i), amount (d), item_id (i), items_added (i), fk_intid (i)
                    $intid_int = ($intid !== '' && ctype_digit($intid)) ? (int)$intid : 0;
                    $st->bind_param("sidiii", $order_id, $user_id, $amount_val, $ITEM_ID, $items_to_add, $intid_int);
                } elseif ($types === "sidii") {
                    $st->bind_param("sidii", $order_id, $user_id, $amount_val, $ITEM_ID, $items_to_add);
                } else {
                    $st->close();
                    continue;
                }

                if (@$st->execute()) {
                    $payment_logged = true;
                    $st->close();
                    break;
                }
                $st->close();
            }

            if (method_exists($mysqli, 'commit')) {
                @$mysqli->commit();
            }

            $ok = true;

            fk_log($DEBUG_LOG, [
                'stage'=>'ok',
                'reason'=>'credited',
                'user'=>$user_id,
                'order'=>$order_id,
                'amount'=>$amount_val,
                'items'=>$items_to_add,
                'item_id'=>$ITEM_ID,
                'intid'=>$intid,
                'paylog'=>($payment_logged ? 1 : 0),
            ]);

        } catch (Throwable $e) {
            $err = $e->getMessage();
            if (isset($mysqli) && method_exists($mysqli, 'rollback')) {
                @$mysqli->rollback();
            }
        }
    }

    if (!$ok) {
        fk_log($DEBUG_LOG, [
            'stage'=>'fail',
            'reason'=>'credit_failed',
            'user'=>$user_id,
            'order'=>$order_id,
            'err'=>$err
        ]);
        http_response_code(500);
        exit('error');
    }

    // Mark done (idempotency)
    if ($fh) {
        fk_marker_write($fh, [
            'status' => 'done',
            'ts' => time(),
            'user_id' => $user_id,
            'order_id' => $order_id,
            'amount' => $amount_raw,
            'items' => $items_to_add,
            'item_id' => $ITEM_ID,
            'intid' => $intid,
        ]);
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    echo 'YES';
    ?>
