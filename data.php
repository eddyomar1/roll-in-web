<?php
// Proxy sencillo hacia la base de datos para el dashboard web.
// Usa las mismas credenciales que la API y expone el mismo JSON que codes.php,
// pero sin exigir X-API-Key (pensado para uso interno).

require_once __DIR__ . '/../config-web.php';

$onlyActive = !isset($_GET['only_active']) || $_GET['only_active'] !== '0';

$sql = 'SELECT id,label,code,active,last_used_at,updated_at
        FROM whitelist_controls';
if ($onlyActive) $sql .= ' WHERE active = 1';
$sql .= ' ORDER BY id';

try {
  $rows = pdo()->query($sql)->fetchAll();
} catch (Throwable $e) {
  respond(['ok'=>false,'error'=>$e->getMessage()], 500);
}

$codes = [];
$ctx = hash_init('sha256');
foreach ($rows as $r) {
  $hex = strtoupper(bin2hex($r['code'])); // 10 hex chars
  $codes[] = [
    'id'           => (int)$r['id'],
    'label'        => $r['label'],
    'code'         => $hex,
    'active'       => (bool)$r['active'],
    'last_used_at' => $r['last_used_at'],
  ];
  hash_update($ctx, $r['code']);
}
$hash = hash_final($ctx);
$etag = '"'.substr($hash,0,32).'"';

respond([
  'ok'    => true,
  'count' => count($codes),
  'hash'  => $hash,
  'codes' => $codes,
], 200, ['ETag'=>$etag, 'Cache-Control'=>'no-cache']);
