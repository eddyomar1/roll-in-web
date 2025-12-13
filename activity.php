<?php
// Devuelve actividad reciente de control_activity con info del control.
// Usa config local (despliegue separado).
require_once __DIR__ . '/config-web.php';

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

$sql = "SELECT ca.id, ca.control_id, ca.door, ca.used_at,
               c.label, c.code, c.active
        FROM control_activity ca
        JOIN whitelist_controls c ON ca.control_id = c.id
        ORDER BY ca.used_at DESC
        LIMIT :lim";

try {
  $st = pdo()->prepare($sql);
  $st->bindValue(':lim', $limit, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll();
} catch (Throwable $e) {
  respond(['ok'=>false,'error'=>$e->getMessage()], 500);
}

$activities = [];
foreach ($rows as $r) {
  $activities[] = [
    'id'         => (int)$r['id'],
    'control_id' => (int)$r['control_id'],
    'door'       => $r['door'],
    'used_at'    => $r['used_at'],
    'label'      => $r['label'],
    'code'       => strtoupper(bin2hex($r['code'])),
    'active'     => (bool)$r['active'],
  ];
}

respond(['ok'=>true, 'count'=>count($activities), 'activities'=>$activities], 200, ['Cache-Control'=>'no-cache']);
