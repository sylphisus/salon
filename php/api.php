<?php
// ── Salon Scheduler API ──────────────────────────────────────────
// Replaces the browser→Supabase calls with browser→PHP→MySQL.
// Actions: load, upsertAppts, deleteAppt, saveAvail.
require __DIR__ . '/config.php';
header('Content-Type: application/json');

function fail($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['error' => $msg]);
  exit;
}

function ensureSchema($pdo) {
  $pdo->exec(
    'CREATE TABLE IF NOT EXISTS appointments (
       id          VARCHAR(64)  PRIMARY KEY,
       client_name VARCHAR(255) NOT NULL,
       service     VARCHAR(64)  NOT NULL,
       stylist     VARCHAR(64)  NOT NULL,
       `date`      VARCHAR(10)  NOT NULL,
       `time`      VARCHAR(5)   NOT NULL,
       duration    INT          NOT NULL,
       phone       VARCHAR(64)  NULL,
       notes       TEXT         NULL,
       created_at  VARCHAR(32)  NULL,
       updated_at  VARCHAR(32)  NULL,
       deleted_at  VARCHAR(32)  NULL
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
  );
  $pdo->exec(
    'CREATE TABLE IF NOT EXISTS availability (
       stylist VARCHAR(64) PRIMARY KEY,
       hours   JSON         NOT NULL
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
  );
}

try {
  $pdo = db();
} catch (Throwable $e) {
  fail('Database connection failed: ' . $e->getMessage(), 500);
}

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

try {
  switch ($action) {

    case 'load': {
      ensureSchema($pdo);
      // Purge tombstones older than 30 days so soft-deleted rows don't pile up.
      $cutoff = gmdate('c', time() - 30 * 86400);
      $pdo->prepare('DELETE FROM appointments WHERE deleted_at IS NOT NULL AND deleted_at < ?')
          ->execute([$cutoff]);

      $appointments = $pdo->query('SELECT * FROM appointments')->fetchAll();
      $availability = $pdo->query('SELECT stylist, hours FROM availability')->fetchAll();
      foreach ($availability as &$row) {
        $row['hours'] = json_decode($row['hours']); // send hours as an object, like Supabase jsonb
      }
      unset($row);

      echo json_encode(['appointments' => $appointments, 'availability' => $availability]);
      break;
    }

    case 'upsertAppts': {
      $rows = $input['rows'] ?? [];
      if (!$rows) fail('No rows provided.');
      $now  = gmdate('c');
      $stmt = $pdo->prepare(
        'INSERT INTO appointments
           (id, client_name, service, stylist, `date`, `time`, duration, phone, notes, created_at, updated_at, deleted_at)
         VALUES
           (:id, :client_name, :service, :stylist, :date, :time, :duration, :phone, :notes, :created_at, :updated_at, NULL)
         ON DUPLICATE KEY UPDATE
           client_name = VALUES(client_name), service = VALUES(service), stylist = VALUES(stylist),
           `date` = VALUES(`date`), `time` = VALUES(`time`), duration = VALUES(duration),
           phone = VALUES(phone), notes = VALUES(notes), updated_at = VALUES(updated_at)'
      );
      foreach ($rows as $r) {
        $stmt->execute([
          ':id'          => $r['id'],
          ':client_name' => $r['client_name'],
          ':service'     => $r['service'],
          ':stylist'     => $r['stylist'],
          ':date'        => $r['date'],
          ':time'        => $r['time'],
          ':duration'    => $r['duration'],
          ':phone'       => $r['phone'] ?? null,
          ':notes'       => $r['notes'] ?? null,
          ':created_at'  => $now,
          ':updated_at'  => $r['updated_at'] ?? $now,
        ]);
      }
      echo json_encode(['ok' => true]);
      break;
    }

    case 'deleteAppt': {
      $id = $input['id'] ?? '';
      $ts = $input['deleted_at'] ?? gmdate('c');
      if (!$id) fail('No id provided.');
      $pdo->prepare('UPDATE appointments SET deleted_at = ? WHERE id = ?')->execute([$ts, $id]);
      echo json_encode(['ok' => true]);
      break;
    }

    case 'saveAvail': {
      $stylist = $input['stylist'] ?? '';
      if ($stylist === '') fail('No stylist provided.');
      $hours = json_encode($input['hours']);
      $pdo->prepare(
        'INSERT INTO availability (stylist, hours) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE hours = VALUES(hours)'
      )->execute([$stylist, $hours]);
      echo json_encode(['ok' => true]);
      break;
    }

    default:
      fail('Unknown action.');
  }
} catch (Throwable $e) {
  fail($e->getMessage(), 500);
}
