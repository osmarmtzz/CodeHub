<?php
/* ═══════════════════════════════════════════════════════════
   CodeHub v5 — Localhost Project Dashboard
   Design: Refined dark editorial with warm accents
═══════════════════════════════════════════════════════════ */

$carpetas = array_diff(scandir(__DIR__), [
  '.',
  '..',
  'index.php',
  'index.html',
  '.codehub_config.json',
  '.codehub_images',
  '.codehub_data.json',
  '.codehub_trash'
]);
$mostrarVolver = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR) !==
  rtrim(realpath($_SERVER['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);
$rutaBase = str_replace('\\', '/', realpath(__DIR__)) . '/';
$cfgFile  = __DIR__ . '/.codehub_config.json';
$datFile  = __DIR__ . '/.codehub_data.json';
$cfg  = file_exists($cfgFile) ? json_decode(file_get_contents($cfgFile), true) : [];
$dat  = file_exists($datFile)  ? json_decode(file_get_contents($datFile),  true) : [];

function detectType($dir)
{
  $map = [
    'composer.json'    => ['PHP',    'fa-php',        '#a78bfa'],
    'package.json'     => ['Node',   'fa-node-js',    '#34d399'],
    'index.html'       => ['HTML',   'fa-html5',      '#f97316'],
    'style.css'        => ['CSS',    'fa-css3-alt',   '#38bdf8'],
    'pom.xml'          => ['Java',   'fa-java',       '#fb7185'],
    'requirements.txt' => ['Python', 'fa-python',     '#facc15'],
    '.git'             => ['Git',    'fa-git-alt',    '#f87171'],
    'Dockerfile'       => ['Docker', 'fa-docker',     '#60a5fa'],
    'go.mod'           => ['Go',     'fa-code',       '#5eead4'],
    'Cargo.toml'       => ['Rust',   'fa-code',       '#fdba74'],
    'artisan'          => ['Laravel', 'fa-php',        '#ff6b6b'],
    'manage.py'        => ['Django', 'fa-python',     '#34d399'],
    'pubspec.yaml'     => ['Flutter', 'fa-mobile-alt', '#93c5fd'],
    'Gemfile'          => ['Ruby',   'fa-gem',        '#e879f9'],
    'deno.json'        => ['Deno',   'fa-code',       '#4ade80'],
  ];
  foreach ($map as $f => [$t, $i, $c]) if (file_exists("$dir/$f")) return [$t, $i, $c];
  return ['Project', 'fa-folder', '#818cf8'];
}
function fc($d)
{
  return count(glob("$d/*"));
}
function ago($t)
{
  $s = time() - $t;
  if ($s < 60)     return 'just now';
  if ($s < 3600)   return floor($s / 60) . 'm ago';
  if ($s < 86400)  return floor($s / 3600) . 'h ago';
  if ($s < 604800) return floor($s / 86400) . 'd ago';
  return date('M j', $t);
}
function e($s)
{
  return htmlspecialchars($s ?? '');
}

/* ── AJAX ─────────────────────────────────────── */
if (isset($_GET['a'])) {
  header('Content-Type: application/json');
  $a = $_GET['a'];

  if ($a === 'mk' && isset($_POST['n'])) {
    $n = preg_replace('/[^a-zA-Z0-9_\-. ]/', '', trim($_POST['n']));
    $p = $rutaBase . $n;
    if (!$n) {
      echo json_encode(['ok' => false, 'm' => 'Invalid name']);
      exit;
    }
    if (!file_exists($p)) {
      mkdir($p, 0755, true);
      if ($_POST['tpl'] ?? '' === 'html') {
        file_put_contents("$p/index.html", "<!DOCTYPE html>\n<html>\n<head>\n  <title>$n</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>$n</h1>\n  <script src=\"script.js\"></script>\n</body>\n</html>");
        file_put_contents("$p/style.css", "*{box-sizing:border-box;margin:0;padding:0}\nbody{font-family:sans-serif;padding:2rem}\n");
        file_put_contents("$p/script.js", "console.log('$n');\n");
      } elseif ($_POST['tpl'] ?? '' === 'readme') {
        file_put_contents("$p/README.md", "# $n\n\nDescription here.\n");
      }
      echo json_encode(['ok' => true, 'm' => 'Created!']);
    } else echo json_encode(['ok' => false, 'm' => 'Already exists']);
    exit;
  }

  if ($a === 'mf' && isset($_POST['f'], $_POST['d'])) {
    $d = realpath($rutaBase . $_POST['d']);
    if ($d && is_dir($d)) {
      file_put_contents($d . '/' . basename($_POST['f']), '');
      echo json_encode(['ok' => true]);
    } else echo json_encode(['ok' => false]);
    exit;
  }

  if ($a === 'ren' && isset($_POST['old'], $_POST['new'])) {
    $o = realpath($rutaBase . $_POST['old']);
    $n = $rutaBase . preg_replace('/[^a-zA-Z0-9_\-. ]/', '', trim($_POST['new']));
    if ($o && is_dir($o) && !file_exists($n)) {
      rename($o, $n);
      $c = file_exists($cfgFile) ? json_decode(file_get_contents($cfgFile), true) : [];
      if (isset($c[$_POST['old']])) {
        $c[basename($n)] = $c[$_POST['old']];
        unset($c[$_POST['old']]);
        file_put_contents($cfgFile, json_encode($c, JSON_PRETTY_PRINT));
      }
      echo json_encode(['ok' => true]);
    } else echo json_encode(['ok' => false, 'm' => 'Failed']);
    exit;
  }

  if ($a === 'del' && isset($_POST['d'])) {
    $d = realpath($rutaBase . $_POST['d']);
    if ($d && is_dir($d) && strpos($d, $rutaBase) === 0) {
      $tr = __DIR__ . '/.codehub_trash';
      if (!file_exists($tr)) mkdir($tr, 0755);
      rename($d, $tr . '/' . basename($d) . '_' . time());
      echo json_encode(['ok' => true]);
    } else echo json_encode(['ok' => false]);
    exit;
  }

  if ($a === 'cfg' && isset($_POST['d'])) {
    $k = $_POST['d'];
    $c = file_exists($cfgFile) ? json_decode(file_get_contents($cfgFile), true) : [];
    $c[$k] = array_merge($c[$k] ?? [], [
      'ico' => $_POST['ico'] ?? null,
      'col' => $_POST['col'] ?? null,
      'img' => $_POST['img'] ?? ($c[$k]['img'] ?? null),
      'desc' => $_POST['desc'] ?? null,
      'pin' => isset($_POST['pin']) ? ($_POST['pin'] === '1') : ($c[$k]['pin'] ?? false),
      'st' => $_POST['st'] ?? ($c[$k]['st'] ?? 'active'),
      'tags' => isset($_POST['tags']) ? json_decode($_POST['tags'], true) : ($c[$k]['tags'] ?? []),
      'rate' => isset($_POST['rate']) ? (int)$_POST['rate'] : ($c[$k]['rate'] ?? 0),
      'url' => $_POST['url'] ?? ($c[$k]['url'] ?? ''),
    ]);
    file_put_contents($cfgFile, json_encode($c, JSON_PRETTY_PRINT));
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($a === 'note' && isset($_POST['d'], $_POST['n'])) {
    $d = file_exists($datFile) ? json_decode(file_get_contents($datFile), true) : [];
    $d[$_POST['d']]['note'] = $_POST['n'];
    $d[$_POST['d']]['note_ts'] = time();
    file_put_contents($datFile, json_encode($d, JSON_PRETTY_PRINT));
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($a === 'todos' && isset($_POST['d'], $_POST['t'])) {
    $d = file_exists($datFile) ? json_decode(file_get_contents($datFile), true) : [];
    $d[$_POST['d']]['todos'] = json_decode($_POST['t'], true);
    file_put_contents($datFile, json_encode($d, JSON_PRETTY_PRINT));
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($a === 'track' && isset($_POST['d'])) {
    $d = file_exists($datFile) ? json_decode(file_get_contents($datFile), true) : [];
    $d[$_POST['d']]['last_open'] = time();
    $d[$_POST['d']]['opens'] = ($d[$_POST['d']]['opens'] ?? 0) + 1;
    file_put_contents($datFile, json_encode($d, JSON_PRETTY_PRINT));
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($a === 'img' && isset($_FILES['f'], $_POST['d'])) {
    $dir = __DIR__ . '/.codehub_images';
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['f']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
      echo json_encode(['ok' => false]);
      exit;
    }
    $nm = md5($_POST['d'] . time()) . '.' . $ext;
    if (move_uploaded_file($_FILES['f']['tmp_name'], "$dir/$nm"))
      echo json_encode(['ok' => true, 'img' => ".codehub_images/$nm"]);
    else echo json_encode(['ok' => false]);
    exit;
  }

  /* FIX: Separate endpoint for file explorer — 'exp' is now export-only */
  if ($a === 'fexp' && isset($_GET['dir'])) {
    $d = realpath($rutaBase . $_GET['dir']);
    if ($d && is_dir($d)) {
      $w = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
      exec($w ? 'explorer ' . escapeshellarg($d) : 'xdg-open ' . escapeshellarg($d));
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($a === 'exp') {
    $rows = [];
    foreach (array_diff(scandir(__DIR__), ['.', '..', 'index.php', 'index.html']) as $x)
      if (is_dir($x)) {
        [$t] = detectType($x);
        $c = $cfg[$x] ?? [];
        $d = $dat[$x] ?? [];
        $rows[] = compact('x') + ['type' => $t, 'files' => fc($x), 'status' => $c['st'] ?? 'active', 'rating' => $c['rate'] ?? 0, 'tags' => implode(';', $c['tags'] ?? []), 'opens' => $d['opens'] ?? 0, 'desc' => $c['desc'] ?? ''];
      }
    $fmt = $_GET['fmt'] ?? 'json';
    if ($fmt === 'csv') {
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename="codehub.csv"');
      echo implode(',', array_keys($rows[0] ?? [])) . "\n";
      foreach ($rows as $r) echo implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', array_values($r))) . "\n";
    } else {
      header('Content-Type: application/json');
      header('Content-Disposition: attachment; filename="codehub.json"');
      echo json_encode(['at' => date('c'), 'projects' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
  }

  if (isset($_GET['dir'])) {
    $d = realpath($rutaBase . $_GET['dir']);
    if ($d && is_dir($d)) {
      $w = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
      if ($a === 'vsc')  exec("code --new-window " . escapeshellarg($d));
      if ($a === 'term') exec($w ? "start cmd /K cd /d " . escapeshellarg($d) : "gnome-terminal --working-directory=" . escapeshellarg($d));
      if ($a === 'open') {
        $idx = null;
        foreach (['index.php', 'index.html', 'index.htm'] as $f) {
          if (file_exists("$d/$f")) {
            $idx = $f;
            break;
          }
        }
        echo json_encode(['ok' => true, 'has_index' => (bool)$idx, 'index' => $idx]);
        exit;
      }
    }
    echo json_encode(['ok' => true]);
    exit;
  }
}

/* ── BUILD PROJECTS ─────────────────────────────── */
$projects = [];
foreach ($carpetas as $name) {
  if (!is_dir($name)) continue;
  [$tipo, $ico, $col] = detectType($name);
  $c = $cfg[$name] ?? [];
  $d = $dat[$name] ?? [];
  $todos = $d['todos'] ?? [];
  $projects[] = [
    'name'    => $name,
    'tipo'    => $tipo,
    'ico'     => $c['ico'] ?? $ico,
    'col'     => $c['col'] ?? $col,
    'img'     => $c['img'] ?? null,
    'desc'    => $c['desc'] ?? null,
    'pin'     => $c['pin'] ?? false,
    'st'      => $c['st']  ?? 'active',
    'tags'    => $c['tags'] ?? [],
    'rate'    => $c['rate'] ?? 0,
    'url'     => $c['url']  ?? '',
    'note'    => $d['note'] ?? '',
    'note_ts' => $d['note_ts'] ?? null,
    'todos'   => $todos,
    'opens'   => $d['opens'] ?? 0,
    'lastop'  => $d['last_open'] ?? null,
    'files'   => fc($name),
    'mtime'   => filemtime($name),
    'ago'     => ago(filemtime($name)),
    'pend'    => count(array_filter($todos, fn($t) => !($t['done'] ?? false))),
  ];
}
usort($projects, fn($a, $b) => $a['pin'] && !$b['pin'] ? -1 : (!$a['pin'] && $b['pin'] ? 1 : strcmp($a['name'], $b['name'])));

$total   = count($projects);
$pinned  = count(array_filter($projects, fn($p) => $p['pin']));
$recent  = count(array_filter($projects, fn($p) => time() - $p['mtime'] < 604800));
$pending = array_sum(array_column($projects, 'pend'));
$stCounts = array_count_values(array_column($projects, 'st'));
$typeCounts = [];
foreach ($projects as $p) {
  $typeCounts[$p['tipo']] = ($typeCounts[$p['tipo']] ?? 0) + 1;
}
arsort($typeCounts);
$allTags = [];
foreach ($projects as $p) foreach ($p['tags'] as $t) $allTags[$t] = ($allTags[$t] ?? 0) + 1;
arsort($allTags);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>CodeHub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="shortcut icon" href="icono.png" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html,html *,html *::before,html *::after{cursor:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'%3E%3Ccircle cx='10' cy='10' r='2.5' fill='%236d7fff'/%3E%3Ccircle cx='10' cy='10' r='6' fill='none' stroke='%236d7fff' stroke-width='1' opacity='.4'/%3E%3C/svg%3E") 10 10,auto!important}
    a,button,label,select,[onclick],[role="button"],.card,.nav-item,.btn,.ca-btn,.qa-btn,.stag,.theme-dot,.sort-item,.ctx-item,.sp-item,.todo-item,.vbtn,.col-sw,.ico-sw,.m-close,.dp-close,.r-star,.todo-del,.sp-act,.tag-del,.up-zone,.note-save-btn,.todo-add-btn,.submit-btn,.cancel-btn,.back-link{cursor:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='12' r='4' fill='%236d7fff'/%3E%3Ccircle cx='12' cy='12' r='9' fill='none' stroke='%236d7fff' stroke-width='1.2' opacity='.45'/%3E%3C/svg%3E") 12 12,auto!important}
    input,textarea{cursor:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='22' viewBox='0 0 14 22'%3E%3Crect x='6' y='1' width='2' height='20' rx='1' fill='%236d7fff'/%3E%3Crect x='2' y='1' width='10' height='2' rx='1' fill='%236d7fff' opacity='.6'/%3E%3Crect x='2' y='19' width='10' height='2' rx='1' fill='%236d7fff' opacity='.6'/%3E%3C/svg%3E") 7 11,auto!important}
    .drag-h{cursor:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 22 22'%3E%3Ccircle cx='11' cy='11' r='3.5' fill='%23f97316'/%3E%3Ccircle cx='11' cy='11' r='8' fill='none' stroke='%23f97316' stroke-width='1.2' stroke-dasharray='3 2' opacity='.55'/%3E%3C/svg%3E") 11 11,auto!important}

    [data-theme="dark"]{--bg:#07080f;--bg2:#0d1022;--bg3:#121630;--bg4:#1a1f3d;--bg5:#20264a;--glass:rgba(109,127,255,.028);--glass2:rgba(109,127,255,.058);--bdr:rgba(109,127,255,.1);--bdr2:rgba(109,127,255,.18);--bdr3:rgba(109,127,255,.32);--txt:#eef0ff;--txt2:#7b88b8;--txt3:#363f68;--acc:#6d7fff;--acc2:#a5b0ff;--acc3:rgba(109,127,255,.14);--acc4:rgba(109,127,255,.07);--warm:#ff8c42;--warm2:#ffb07a;--grn:#34d399;--red:#f87171;--ylw:#fbbf24;--pnk:#f472b6;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.7)}
    [data-theme="midnight"]{--bg:#000000;--bg2:#030508;--bg3:#060b12;--bg4:#0a1220;--bg5:#0d192e;--glass:rgba(0,220,255,.018);--glass2:rgba(0,220,255,.04);--bdr:rgba(0,200,255,.08);--bdr2:rgba(0,200,255,.16);--bdr3:rgba(0,200,255,.28);--txt:#d0f4ff;--txt2:#2e6888;--txt3:#0e2d40;--acc:#00d4ff;--acc2:#66e8ff;--acc3:rgba(0,212,255,.1);--acc4:rgba(0,212,255,.05);--warm:#ff4f7b;--warm2:#ff80a0;--grn:#00ff9f;--red:#ff4f7b;--ylw:#ffe566;--pnk:#cc55ff;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.85)}
    [data-theme="forest"]{--bg:#040a06;--bg2:#081410;--bg3:#0e1e18;--bg4:#142820;--bg5:#1a3228;--glass:rgba(74,222,128,.022);--glass2:rgba(74,222,128,.048);--bdr:rgba(74,222,128,.09);--bdr2:rgba(74,222,128,.18);--bdr3:rgba(74,222,128,.3);--txt:#d8f5e8;--txt2:#3d8060;--txt3:#153824;--acc:#4ade80;--acc2:#86efac;--acc3:rgba(74,222,128,.12);--acc4:rgba(74,222,128,.06);--warm:#fb923c;--warm2:#fdba74;--grn:#4ade80;--red:#f87171;--ylw:#fde047;--pnk:#f472b6;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.72)}
    [data-theme="rose"]{--bg:#0c0409;--bg2:#180814;--bg3:#220d1e;--bg4:#2e1228;--bg5:#3a1832;--glass:rgba(251,113,133,.022);--glass2:rgba(251,113,133,.048);--bdr:rgba(251,113,133,.09);--bdr2:rgba(251,113,133,.18);--bdr3:rgba(251,113,133,.32);--txt:#ffe4f0;--txt2:#994466;--txt3:#521830;--acc:#fb7185;--acc2:#fda4af;--acc3:rgba(251,113,133,.12);--acc4:rgba(251,113,133,.06);--warm:#f97316;--warm2:#fb923c;--grn:#34d399;--red:#ef4444;--ylw:#fbbf24;--pnk:#e879f9;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.72)}
    [data-theme="void"]{--bg:#000000;--bg2:#0a0a0a;--bg3:#111111;--bg4:#1a1a1a;--bg5:#222222;--glass:rgba(255,255,255,.018);--glass2:rgba(255,255,255,.04);--bdr:rgba(255,255,255,.07);--bdr2:rgba(255,255,255,.14);--bdr3:rgba(255,255,255,.24);--txt:#f8f8f8;--txt2:#666666;--txt3:#333333;--acc:#ffffff;--acc2:#cccccc;--acc3:rgba(255,255,255,.08);--acc4:rgba(255,255,255,.04);--warm:#ff4400;--warm2:#ff6622;--grn:#00ff88;--red:#ff4444;--ylw:#ffee00;--pnk:#ff44aa;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.85)}
    [data-theme="dusk"]{--bg:#0c0618;--bg2:#130b26;--bg3:#1c1136;--bg4:#261845;--bg5:#301f54;--glass:rgba(167,139,250,.025);--glass2:rgba(167,139,250,.052);--bdr:rgba(167,139,250,.1);--bdr2:rgba(167,139,250,.2);--bdr3:rgba(167,139,250,.36);--txt:#ede4ff;--txt2:#7a5aaa;--txt3:#3c1e70;--acc:#a78bfa;--acc2:#c4b5fd;--acc3:rgba(167,139,250,.13);--acc4:rgba(167,139,250,.06);--warm:#fb923c;--warm2:#fdba74;--grn:#6ee7b7;--red:#f87171;--ylw:#fde047;--pnk:#f0abfc;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.75)}
    [data-theme="mocha"]{--bg:#0e0804;--bg2:#1a100a;--bg3:#241810;--bg4:#302016;--bg5:#3c281c;--glass:rgba(251,191,36,.02);--glass2:rgba(251,191,36,.042);--bdr:rgba(220,160,80,.09);--bdr2:rgba(220,160,80,.18);--bdr3:rgba(220,160,80,.32);--txt:#fff2dc;--txt2:#a07848;--txt3:#4e2e14;--acc:#f59e0b;--acc2:#fcd34d;--acc3:rgba(245,158,11,.13);--acc4:rgba(245,158,11,.065);--warm:#e05c2a;--warm2:#f07848;--grn:#86efac;--red:#fca5a5;--ylw:#fde047;--pnk:#fda4af;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.78)}
    [data-theme="ocean"]{--bg:#010d10;--bg2:#02161e;--bg3:#04202c;--bg4:#062c3c;--bg5:#08384c;--glass:rgba(20,184,166,.022);--glass2:rgba(20,184,166,.048);--bdr:rgba(20,184,166,.09);--bdr2:rgba(20,184,166,.19);--bdr3:rgba(20,184,166,.34);--txt:#ccfbf1;--txt2:#2a7a6e;--txt3:#0c3830;--acc:#2dd4bf;--acc2:#5eead4;--acc3:rgba(45,212,191,.12);--acc4:rgba(45,212,191,.06);--warm:#f97316;--warm2:#fb923c;--grn:#4ade80;--red:#f87171;--ylw:#fbbf24;--pnk:#e879f9;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.82)}
    [data-theme="dracula"]{--bg:#0f0f1a;--bg2:#181824;--bg3:#21212f;--bg4:#2a2a3c;--bg5:#333348;--glass:rgba(189,147,249,.022);--glass2:rgba(189,147,249,.048);--bdr:rgba(98,114,164,.14);--bdr2:rgba(98,114,164,.26);--bdr3:rgba(98,114,164,.44);--txt:#f8f8f2;--txt2:#6272a4;--txt3:#30355a;--acc:#bd93f9;--acc2:#d6b8fe;--acc3:rgba(189,147,249,.13);--acc4:rgba(189,147,249,.065);--warm:#ffb86c;--warm2:#ffd08a;--grn:#50fa7b;--red:#ff5555;--ylw:#f1fa8c;--pnk:#ff79c6;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.72)}
    [data-theme="nord"]{--bg:#181c28;--bg2:#1e2232;--bg3:#242a3e;--bg4:#2c324a;--bg5:#343c56;--glass:rgba(136,192,208,.022);--glass2:rgba(136,192,208,.048);--bdr:rgba(136,192,208,.1);--bdr2:rgba(136,192,208,.2);--bdr3:rgba(136,192,208,.34);--txt:#eceff4;--txt2:#7890a8;--txt3:#384462;--acc:#88c0d0;--acc2:#9ecfdf;--acc3:rgba(136,192,208,.11);--acc4:rgba(136,192,208,.055);--warm:#ebcb8b;--warm2:#f0d8a0;--grn:#a3be8c;--red:#bf616a;--ylw:#ebcb8b;--pnk:#b48ead;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.6)}
    [data-theme="solar"]{--bg:#001018;--bg2:#002028;--bg3:#002d38;--bg4:#003c48;--bg5:#004a58;--glass:rgba(38,139,210,.02);--glass2:rgba(38,139,210,.042);--bdr:rgba(101,123,131,.13);--bdr2:rgba(101,123,131,.26);--bdr3:rgba(101,123,131,.44);--txt:#fdf6e3;--txt2:#5e8090;--txt3:#1c4050;--acc:#268bd2;--acc2:#52a8ea;--acc3:rgba(38,139,210,.13);--acc4:rgba(38,139,210,.065);--warm:#cb4b16;--warm2:#e06a30;--grn:#859900;--red:#dc322f;--ylw:#b58900;--pnk:#d33682;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.78)}
    [data-theme="crimson"]{--bg:#0e0204;--bg2:#1a0408;--bg3:#26060c;--bg4:#320810;--bg5:#3e0a14;--glass:rgba(225,29,72,.022);--glass2:rgba(225,29,72,.048);--bdr:rgba(225,29,72,.1);--bdr2:rgba(225,29,72,.2);--bdr3:rgba(225,29,72,.36);--txt:#ffe4e8;--txt2:#8a2040;--txt3:#4c0820;--acc:#f43f5e;--acc2:#fb7185;--acc3:rgba(244,63,94,.13);--acc4:rgba(244,63,94,.065);--warm:#f97316;--warm2:#fb923c;--grn:#4ade80;--red:#ff2244;--ylw:#fbbf24;--pnk:#f0abfc;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.82)}
    [data-theme="slate"]{--bg:#080a10;--bg2:#0e1118;--bg3:#141822;--bg4:#1c202e;--bg5:#22283a;--glass:rgba(56,189,248,.02);--glass2:rgba(56,189,248,.042);--bdr:rgba(148,163,184,.09);--bdr2:rgba(148,163,184,.18);--bdr3:rgba(148,163,184,.3);--txt:#e2e8f4;--txt2:#5a6a88;--txt3:#28324a;--acc:#38bdf8;--acc2:#7dd3fc;--acc3:rgba(56,189,248,.11);--acc4:rgba(56,189,248,.055);--warm:#fb923c;--warm2:#fdba74;--grn:#4ade80;--red:#f87171;--ylw:#fbbf24;--pnk:#f472b6;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.68)}
    [data-theme="neon"]{--bg:#010102;--bg2:#040408;--bg3:#07070f;--bg4:#0c0c18;--bg5:#121220;--glass:rgba(57,255,20,.018);--glass2:rgba(57,255,20,.038);--bdr:rgba(57,255,20,.08);--bdr2:rgba(57,255,20,.17);--bdr3:rgba(57,255,20,.3);--txt:#e8ffe4;--txt2:#1e6818;--txt3:#082408;--acc:#39ff14;--acc2:#80ff60;--acc3:rgba(57,255,20,.1);--acc4:rgba(57,255,20,.05);--warm:#ff0080;--warm2:#ff40a0;--grn:#39ff14;--red:#ff0055;--ylw:#ffff00;--pnk:#ff00ff;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.9)}
    [data-theme="amber"]{--bg:#0a0602;--bg2:#140d04;--bg3:#1e1408;--bg4:#281c0c;--bg5:#322410;--glass:rgba(252,211,77,.018);--glass2:rgba(252,211,77,.038);--bdr:rgba(245,158,11,.1);--bdr2:rgba(245,158,11,.2);--bdr3:rgba(245,158,11,.36);--txt:#fffbeb;--txt2:#927020;--txt3:#4a3008;--acc:#fcd34d;--acc2:#fde68a;--acc3:rgba(252,211,77,.13);--acc4:rgba(252,211,77,.065);--warm:#ea580c;--warm2:#f97316;--grn:#86efac;--red:#fca5a5;--ylw:#fde047;--pnk:#fda4af;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.78)}
    [data-theme="sakura"]{--bg:#0c0610;--bg2:#160c1c;--bg3:#201228;--bg4:#2a1834;--bg5:#341e40;--glass:rgba(244,114,182,.022);--glass2:rgba(244,114,182,.048);--bdr:rgba(244,114,182,.09);--bdr2:rgba(244,114,182,.18);--bdr3:rgba(244,114,182,.32);--txt:#fce7f3;--txt2:#994477;--txt3:#521844;--acc:#ec4899;--acc2:#f9a8d4;--acc3:rgba(236,72,153,.12);--acc4:rgba(236,72,153,.06);--warm:#fb923c;--warm2:#fdba74;--grn:#86efac;--red:#fca5a5;--ylw:#fde047;--pnk:#f0abfc;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.75)}
    [data-theme="mint"]{--bg:#01080a;--bg2:#020f12;--bg3:#04181c;--bg4:#062228;--bg5:#082c34;--glass:rgba(110,231,183,.02);--glass2:rgba(110,231,183,.042);--bdr:rgba(110,231,183,.09);--bdr2:rgba(110,231,183,.19);--bdr3:rgba(110,231,183,.34);--txt:#d1fae5;--txt2:#1e7a55;--txt3:#063824;--acc:#6ee7b7;--acc2:#a7f3d0;--acc3:rgba(110,231,183,.12);--acc4:rgba(110,231,183,.06);--warm:#f97316;--warm2:#fb923c;--grn:#4ade80;--red:#f87171;--ylw:#fde047;--pnk:#f9a8d4;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.78)}
    [data-theme="lavender"]{--bg:#08060e;--bg2:#100c1e;--bg3:#18122e;--bg4:#20183e;--bg5:#281e4e;--glass:rgba(196,181,253,.022);--glass2:rgba(196,181,253,.048);--bdr:rgba(167,139,250,.1);--bdr2:rgba(167,139,250,.2);--bdr3:rgba(167,139,250,.36);--txt:#ede9ff;--txt2:#8060c0;--txt3:#401880;--acc:#c4b5fd;--acc2:#ddd6fe;--acc3:rgba(196,181,253,.13);--acc4:rgba(196,181,253,.065);--warm:#f9a8d4;--warm2:#fbcfe8;--grn:#86efac;--red:#fca5a5;--ylw:#fde047;--pnk:#e879f9;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.78)}
    [data-theme="copper"]{--bg:#0a0603;--bg2:#160e06;--bg3:#22160a;--bg4:#2e1e0e;--bg5:#3a2612;--glass:rgba(205,124,58,.02);--glass2:rgba(205,124,58,.042);--bdr:rgba(205,124,58,.1);--bdr2:rgba(205,124,58,.2);--bdr3:rgba(205,124,58,.36);--txt:#fdf0d4;--txt2:#9a6230;--txt3:#4e2a0a;--acc:#e8974a;--acc2:#f5b878;--acc3:rgba(232,151,74,.13);--acc4:rgba(232,151,74,.065);--warm:#dc2626;--warm2:#ef4444;--grn:#86efac;--red:#fca5a5;--ylw:#fde047;--pnk:#fda4af;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.78)}
    [data-theme="ice"]{--bg:#000810;--bg2:#001020;--bg3:#001830;--bg4:#002040;--bg5:#002850;--glass:rgba(186,230,253,.018);--glass2:rgba(186,230,253,.038);--bdr:rgba(147,210,240,.09);--bdr2:rgba(147,210,240,.19);--bdr3:rgba(147,210,240,.34);--txt:#f0faff;--txt2:#3a7898;--txt3:#0e3858;--acc:#7dd3fc;--acc2:#bae6fd;--acc3:rgba(125,211,252,.11);--acc4:rgba(125,211,252,.055);--warm:#f97316;--warm2:#fb923c;--grn:#4ade80;--red:#f87171;--ylw:#fbbf24;--pnk:#f9a8d4;--ui:'DM Sans',sans-serif;--mono:'DM Mono',monospace;--r:13px;--sidebar:264px;--shadow:0 24px 64px rgba(0,0,0,.82)}

    html{scroll-behavior:smooth}
    body{background:var(--bg);color:var(--txt);font-family:var(--ui);min-height:100vh;overflow-x:hidden;line-height:1.5;-webkit-font-smoothing:antialiased}
    body::after{content:'';position:fixed;inset:0;z-index:9990;pointer-events:none;opacity:.35;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23g)' opacity='.055'/%3E%3C/svg%3E")}
    ::-webkit-scrollbar{width:5px;height:5px}
    ::-webkit-scrollbar-track{background:transparent}
    ::-webkit-scrollbar-thumb{background:rgba(124,140,255,.25);border-radius:4px}
    ::-webkit-scrollbar-thumb:hover{background:var(--acc)}

    .app{display:grid;grid-template-columns:var(--sidebar) 1fr;min-height:100vh;position:relative;z-index:1}
    .sidebar{background:var(--bg2);border-right:1px solid var(--bdr);padding:20px 14px;display:flex;flex-direction:column;gap:22px;position:sticky;top:0;height:100vh;overflow-y:auto;overflow-x:hidden}
    .sidebar::-webkit-scrollbar{width:2px}
    .logo{display:flex;align-items:center;gap:11px;padding:3px 5px;user-select:none}
    .logo-mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(145deg,#6d7fff 0%,#a855f7 100%);display:flex;align-items:center;justify-content:center;font-size:.9em;color:#fff;flex-shrink:0;box-shadow:0 0 20px rgba(124,140,255,.3),0 4px 12px rgba(0,0,0,.4);animation:logoglw 5s ease-in-out infinite}
    @keyframes logoglw{0%,100%{box-shadow:0 0 18px rgba(124,140,255,.28),0 4px 10px rgba(0,0,0,.4)}50%{box-shadow:0 0 36px rgba(124,140,255,.55),0 4px 18px rgba(0,0,0,.5)}}
    .logo-name{font-size:1.18em;font-weight:800;letter-spacing:-.5px;color:var(--txt)}
    .logo-name span{color:var(--acc2)}
    .logo-env{font-family:var(--mono);font-size:.58em;color:var(--txt3);margin-top:2px;letter-spacing:.3px}
    .stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
    .stat-cell{background:var(--glass);border:1px solid var(--bdr);border-radius:10px;padding:10px 11px;transition:all .2s}
    .stat-cell:hover{border-color:var(--bdr2);background:var(--glass2)}
    .stat-cell.accent{background:var(--acc3);border-color:rgba(124,140,255,.22)}
    .stat-val{font-family:var(--mono);font-size:1.45em;font-weight:500;color:var(--txt);line-height:1}
    .stat-cell.accent .stat-val{color:var(--acc2)}
    .stat-lbl{font-size:.64em;color:var(--txt3);margin-top:3px;text-transform:uppercase;letter-spacing:.6px;font-weight:600}
    .s-label{font-size:.6em;font-weight:700;color:var(--txt3);letter-spacing:1.8px;text-transform:uppercase;display:flex;align-items:center;gap:8px;padding:0 4px}
    .s-label::after{content:'';flex:1;height:1px;background:var(--bdr)}
    .nav{list-style:none;display:flex;flex-direction:column;gap:1px}
    .nav-item{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;color:var(--txt3);font-size:.82em;font-weight:500;transition:all .17s;user-select:none;border:1px solid transparent;position:relative}
    .nav-item i{width:14px;font-size:.88em;flex-shrink:0}
    .nav-item:hover{color:var(--txt2);background:var(--glass)}
    .nav-item.on{color:var(--acc2);background:var(--acc3);border-color:rgba(124,140,255,.16)}
    .nav-item .cnt{margin-left:auto;font-family:var(--mono);font-size:.67em;background:rgba(124,140,255,.15);color:var(--acc2);padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
    .nav-item.on .cnt{background:var(--acc);color:#fff}
    .tag-cloud{display:flex;flex-wrap:wrap;gap:4px}
    .stag{padding:3px 8px;border-radius:7px;font-family:var(--mono);font-size:.63em;border:1px solid var(--bdr);background:transparent;color:var(--txt3);transition:all .16s;user-select:none}
    .stag:hover,.stag.on{color:var(--acc2);border-color:rgba(124,140,255,.3);background:var(--acc3)}
    .theme-picker{display:grid;grid-template-columns:repeat(5,1fr);gap:5px}
    .theme-dot{aspect-ratio:1;border-radius:8px;border:2px solid var(--bdr);transition:transform .18s,border-color .18s,box-shadow .18s;position:relative;overflow:visible}
    .theme-dot:hover{transform:scale(1.14);border-color:var(--bdr2)}
    .theme-dot.on{border-color:rgba(255,255,255,.45);box-shadow:0 0 0 2px rgba(255,255,255,.12);transform:scale(1.1)}
    .theme-dot::after{content:attr(title);position:absolute;bottom:calc(100% + 4px);left:50%;transform:translateX(-50%);background:rgba(0,0,0,.9);color:#fff;font-size:9px;font-family:'DM Mono',monospace;padding:2px 6px;border-radius:4px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .14s;z-index:100}
    .theme-dot:hover::after{opacity:1}
    .theme-dot[data-t="dark"]{background:linear-gradient(135deg,#07080f 52%,#6d7fff 52%)}
    .theme-dot[data-t="midnight"]{background:linear-gradient(135deg,#000000 52%,#00d4ff 52%)}
    .theme-dot[data-t="forest"]{background:linear-gradient(135deg,#040a06 52%,#4ade80 52%)}
    .theme-dot[data-t="rose"]{background:linear-gradient(135deg,#0c0409 52%,#fb7185 52%)}
    .theme-dot[data-t="void"]{background:linear-gradient(135deg,#000000 52%,#f8f8f8 52%)}
    .theme-dot[data-t="dusk"]{background:linear-gradient(135deg,#0c0618 52%,#a78bfa 52%)}
    .theme-dot[data-t="mocha"]{background:linear-gradient(135deg,#0e0804 52%,#f59e0b 52%)}
    .theme-dot[data-t="ocean"]{background:linear-gradient(135deg,#010d10 52%,#2dd4bf 52%)}
    .theme-dot[data-t="dracula"]{background:linear-gradient(135deg,#0f0f1a 52%,#bd93f9 52%)}
    .theme-dot[data-t="nord"]{background:linear-gradient(135deg,#181c28 52%,#88c0d0 52%)}
    .theme-dot[data-t="solar"]{background:linear-gradient(135deg,#001018 52%,#268bd2 52%)}
    .theme-dot[data-t="crimson"]{background:linear-gradient(135deg,#0e0204 52%,#f43f5e 52%)}
    .theme-dot[data-t="slate"]{background:linear-gradient(135deg,#080a10 52%,#38bdf8 52%)}
    .theme-dot[data-t="neon"]{background:linear-gradient(135deg,#010102 52%,#39ff14 52%)}
    .theme-dot[data-t="amber"]{background:linear-gradient(135deg,#0a0602 52%,#fcd34d 52%)}
    .theme-dot[data-t="sakura"]{background:linear-gradient(135deg,#0c0610 52%,#ec4899 52%)}
    .theme-dot[data-t="mint"]{background:linear-gradient(135deg,#01080a 52%,#6ee7b7 52%)}
    .theme-dot[data-t="lavender"]{background:linear-gradient(135deg,#08060e 52%,#c4b5fd 52%)}
    .theme-dot[data-t="copper"]{background:linear-gradient(135deg,#0a0603 52%,#e8974a 52%)}
    .theme-dot[data-t="ice"]{background:linear-gradient(135deg,#000810 52%,#7dd3fc 52%)}
    .sidebar-foot{margin-top:auto;padding-top:14px;border-top:1px solid var(--bdr)}
    .live-badge{display:flex;align-items:center;gap:7px;font-family:var(--mono);font-size:.62em;color:var(--txt3)}
    .live-dot{width:5px;height:5px;border-radius:50%;background:var(--grn);animation:ldot 2s ease-in-out infinite}
    @keyframes ldot{0%,100%{opacity:1}50%{opacity:.2}}
    .main{display:flex;flex-direction:column;min-height:100vh}
    .topbar{display:flex;align-items:center;gap:10px;padding:13px 24px;border-bottom:1px solid var(--bdr);background:rgba(6,9,16,.9);backdrop-filter:blur(24px);position:sticky;top:0;z-index:300;flex-shrink:0}
    .search-wrap{flex:1;max-width:440px;position:relative}
    .search-ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--txt3);font-size:.78em;pointer-events:none;transition:color .2s}
    .search-input{width:100%;background:var(--bg3);border:1px solid var(--bdr);border-radius:10px;padding:9px 38px;color:var(--txt);font-family:var(--mono);font-size:.8em;outline:none;transition:all .22s;letter-spacing:.1px}
    .search-input:focus{border-color:var(--acc);background:var(--acc4);box-shadow:0 0 0 3px rgba(124,140,255,.1)}
    .search-input::placeholder{color:var(--txt3)}
    .search-wrap:focus-within .search-ico{color:var(--acc)}
    .search-kbd{position:absolute;right:9px;top:50%;transform:translateY(-50%);font-family:var(--mono);font-size:.57em;color:var(--txt3);background:var(--bg5);border:1px solid var(--bdr);border-radius:4px;padding:1px 5px;pointer-events:none}
    .tb-spacer{flex:1}
    .tb-right{display:flex;align-items:center;gap:6px}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:9px;font-family:var(--ui);font-size:.8em;font-weight:600;border:1px solid transparent;transition:all .17s;user-select:none;white-space:nowrap;letter-spacing:-.1px}
    .btn-ghost{background:var(--glass);border-color:var(--bdr);color:var(--txt2)}
    .btn-ghost:hover{background:var(--glass2);color:var(--txt);border-color:var(--bdr2)}
    .btn-primary{background:var(--acc);color:#fff;font-weight:700}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(124,140,255,.38);filter:brightness(1.08)}
    .btn-warm{background:rgba(249,115,22,.12);border-color:rgba(249,115,22,.25);color:var(--warm2)}
    .btn-warm:hover{background:rgba(249,115,22,.2)}
    .btn-danger{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.2);color:var(--red)}
    .btn-danger:hover{background:rgba(248,113,113,.18)}
    .icon-btn{width:34px;height:34px;padding:0;justify-content:center;font-size:.82em;flex-shrink:0}
    .view-toggle{display:flex;background:var(--bg3);border:1px solid var(--bdr);border-radius:9px;overflow:hidden}
    .vbtn{padding:7px 10px;background:transparent;border:none;color:var(--txt3);font-size:.8em;transition:all .16s}
    .vbtn.on{background:var(--acc);color:#fff}
    .vbtn:hover:not(.on){color:var(--txt2);background:var(--glass2)}
    .sort-wrap{position:relative}
    .sort-dd{position:absolute;top:calc(100% + 5px);right:0;background:var(--bg3);border:1px solid var(--bdr2);border-radius:11px;padding:5px;min-width:175px;z-index:250;display:none;box-shadow:var(--shadow);animation:fadeup .14s ease}
    .sort-dd.open{display:block}
    @keyframes fadeup{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
    .sort-item{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:7px;font-size:.78em;font-weight:500;color:var(--txt2);transition:all .14s}
    .sort-item i{width:13px;color:var(--txt3);font-size:.88em}
    .sort-item:hover{background:var(--glass2);color:var(--txt)}
    .sort-item.on{color:var(--acc2);background:var(--acc3)}
    .sort-item.on i{color:var(--acc2)}
    .content{flex:1;padding:24px 24px 60px;overflow-y:auto}
    .page-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:14px;flex-wrap:wrap}
    .page-title{font-size:1.55em;font-weight:800;letter-spacing:-.7px;line-height:1.1}
    .page-sub{font-family:var(--mono);font-size:.7em;color:var(--txt3);margin-top:4px}
    .bulk-bar{display:none;align-items:center;gap:8px;padding:8px 13px;background:var(--acc3);border:1px solid rgba(124,140,255,.28);border-radius:10px;font-family:var(--mono);font-size:.72em;color:var(--acc2)}
    .bulk-bar.show{display:flex}
    .sec-head{font-family:var(--mono);font-size:.6em;font-weight:600;color:var(--txt3);letter-spacing:1.8px;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:8px}
    .sec-head i{font-size:1em}
    .sec-head::after{content:'';flex:1;height:1px;background:var(--bdr)}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(285px,1fr));gap:13px}
    .grid.list{grid-template-columns:1fr;gap:7px}
    .grid.compact{grid-template-columns:repeat(auto-fill,minmax(195px,1fr));gap:8px}
    [data-csize="xs"] .card{font-size:.68em}[data-csize="sm"] .card{font-size:.82em}[data-csize="md"] .card{font-size:1em}[data-csize="lg"] .card{font-size:1.18em}[data-csize="xl"] .card{font-size:1.38em}
    [data-csize="xs"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(130px,1fr))!important;gap:6px!important}[data-csize="sm"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(195px,1fr))!important;gap:9px!important}[data-csize="md"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(285px,1fr))!important;gap:13px!important}[data-csize="lg"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(320px,1fr))!important;gap:15px!important}[data-csize="xl"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(430px,1fr))!important;gap:18px!important}
    .card{min-height:unset!important}
    [data-csize="xs"] .card-body{padding:.55em .65em .5em!important}[data-csize="sm"] .card-body{padding:.78em .88em .62em!important}[data-csize="md"] .card-body{padding:1em 1em .75em!important}[data-csize="lg"] .card-body{padding:1.12em 1.12em .88em!important}[data-csize="xl"] .card-body{padding:1.28em 1.22em 1em!important}
    [data-csize="xs"] .card-foot{padding:.38em .65em!important}[data-csize="sm"] .card-foot{padding:.5em .88em!important}[data-csize="md"] .card-foot{padding:.62em 1em!important}[data-csize="lg"] .card-foot{padding:.7em 1.12em!important}[data-csize="xl"] .card-foot{padding:.8em 1.22em!important}
    [data-csize="xs"] .c-thumb{width:2.1em;height:2.1em;border-radius:.45em;font-size:.95em}[data-csize="sm"] .c-thumb{width:2.5em;height:2.5em;border-radius:.62em;font-size:1em}[data-csize="md"] .c-thumb{width:2.8em;height:2.8em;border-radius:.75em;font-size:1.1em}[data-csize="lg"] .c-thumb{width:3em;height:3em;border-radius:.82em;font-size:1.22em}[data-csize="xl"] .c-thumb{width:3.3em;height:3.3em;border-radius:.96em;font-size:1.36em}
    [data-csize="xs"] .card-top{gap:.5em}[data-csize="sm"] .card-top{gap:.72em}[data-csize="md"] .card-top{gap:.88em}[data-csize="lg"] .card-top{gap:.96em}[data-csize="xl"] .card-top{gap:1.06em}
    [data-csize="xs"] .c-name{font-size:.82em}[data-csize="sm"] .c-name{font-size:.88em}[data-csize="md"] .c-name{font-size:.95em}[data-csize="lg"] .c-name{font-size:1em}[data-csize="xl"] .c-name{font-size:1.05em}
    [data-csize="xs"] .c-meta-row{gap:.22em;margin-top:.15em}[data-csize="sm"] .c-meta-row{gap:.32em;margin-top:.2em}[data-csize="md"] .c-meta-row{gap:.4em;margin-top:.25em}[data-csize="lg"] .c-meta-row{gap:.42em;margin-top:.28em}[data-csize="xl"] .c-meta-row{gap:.48em;margin-top:.32em}
    [data-csize="xs"] .type-badge{font-size:.56em}[data-csize="sm"] .type-badge{font-size:.60em}[data-csize="md"] .type-badge{font-size:.63em}[data-csize="lg"] .type-badge{font-size:.66em}[data-csize="xl"] .type-badge{font-size:.7em}
    [data-csize="xs"] .status-pill{font-size:.52em;padding:1px 5px 1px 4px;gap:3px}[data-csize="sm"] .status-pill{font-size:.57em;padding:1px 6px 1px 5px;gap:4px}[data-csize="md"] .status-pill{font-size:.615em;padding:2px 8px 2px 6px;gap:5px}[data-csize="lg"] .status-pill{font-size:.65em;padding:2px 8px 2px 6px;gap:5px}[data-csize="xl"] .status-pill{font-size:.7em;padding:3px 9px 3px 7px;gap:5px}
    [data-csize="xs"] .sp-dot{width:3px;height:3px}[data-csize="sm"] .sp-dot{width:4px;height:4px}[data-csize="md"] .sp-dot{width:5px;height:5px}[data-csize="lg"] .sp-dot{width:5px;height:5px}[data-csize="xl"] .sp-dot{width:6px;height:6px}
    [data-csize="xs"] .new-badge{font-size:.5em}[data-csize="sm"] .new-badge{font-size:.54em}[data-csize="md"] .new-badge{font-size:.58em}[data-csize="lg"] .new-badge{font-size:.62em}[data-csize="xl"] .new-badge{font-size:.67em}
    [data-csize="md"] .c-desc{font-size:.76em;margin-top:.55em;-webkit-line-clamp:2}[data-csize="lg"] .c-desc{font-size:.78em;margin-top:.6em;-webkit-line-clamp:3}[data-csize="xl"] .c-desc{font-size:.8em;margin-top:.65em;-webkit-line-clamp:4}
    [data-csize="xs"] .ctag{font-size:.52em;padding:1px 5px;border-radius:.38em}[data-csize="sm"] .ctag{font-size:.57em;padding:2px 6px;border-radius:.42em}[data-csize="md"] .ctag{font-size:.61em;padding:2px 7px;border-radius:.48em}[data-csize="lg"] .ctag{font-size:.65em;padding:2px 8px;border-radius:.5em}[data-csize="xl"] .ctag{font-size:.7em;padding:3px 9px;border-radius:.55em}
    [data-csize="sm"] .c-tags{margin-top:.42em;padding-top:.42em}[data-csize="md"] .c-tags{margin-top:.5em;padding-top:.5em}[data-csize="lg"] .c-tags{margin-top:.55em;padding-top:.55em}[data-csize="xl"] .c-tags{margin-top:.6em;padding-top:.6em}
    [data-csize="xs"] .star{font-size:.6em}[data-csize="sm"] .star{font-size:.65em}[data-csize="md"] .star{font-size:.7em}[data-csize="lg"] .star{font-size:.75em}[data-csize="xl"] .star{font-size:.82em}
    [data-csize="md"] .c-stars{padding-top:.5em}[data-csize="lg"] .c-stars{padding-top:.55em}[data-csize="xl"] .c-stars{padding-top:.6em}
    [data-csize="xs"] .cf-m{font-size:.55em;gap:.2em}[data-csize="sm"] .cf-m{font-size:.58em;gap:.28em}[data-csize="md"] .cf-m{font-size:.62em;gap:.35em}[data-csize="lg"] .cf-m{font-size:.65em;gap:.38em}[data-csize="xl"] .cf-m{font-size:.7em;gap:.4em}
    [data-csize="xs"] .qa-btn{font-size:.54em;padding:2px 5px;border-radius:.38em}[data-csize="sm"] .qa-btn{font-size:.58em;padding:2px 6px;border-radius:.42em}[data-csize="md"] .qa-btn{font-size:.62em;padding:3px 7px;border-radius:.48em}[data-csize="lg"] .qa-btn{font-size:.66em;padding:3px 8px;border-radius:.5em}[data-csize="xl"] .qa-btn{font-size:.72em;padding:4px 9px;border-radius:.55em}
    [data-csize="xs"] .ca-btn{width:1.58em;height:1.58em;font-size:.7em;border-radius:.48em}[data-csize="sm"] .ca-btn{width:1.72em;height:1.72em;font-size:.75em;border-radius:.52em}[data-csize="md"] .ca-btn{width:1.88em;height:1.88em;font-size:.8em;border-radius:.58em}[data-csize="lg"] .ca-btn{width:2em;height:2em;font-size:.86em;border-radius:.62em}[data-csize="xl"] .ca-btn{width:2.2em;height:2.2em;font-size:.92em;border-radius:.68em}
    [data-csize="xs"] .todo-badge{font-size:.46em;min-width:13px;height:13px;border-radius:.35em}[data-csize="sm"] .todo-badge{font-size:.5em;min-width:14px;height:14px;border-radius:.38em}[data-csize="md"] .todo-badge{font-size:.54em;min-width:15px;height:15px;border-radius:.4em}[data-csize="lg"] .todo-badge{font-size:.58em;min-width:16px;height:16px;border-radius:.42em}[data-csize="xl"] .todo-badge{font-size:.63em;min-width:18px;height:18px;border-radius:.45em}
    [data-csize="xs"] .c-desc,[data-csize="xs"] .c-tags,[data-csize="xs"] .c-stars,[data-csize="xs"] .card-foot{display:none!important}
    [data-csize="sm"] .c-desc,[data-csize="sm"] .c-stars{display:none!important}

    .card{background:var(--bg2);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden;position:relative;display:flex;flex-direction:column;transition:transform .38s cubic-bezier(.34,1.56,.64,1),border-color .25s,box-shadow .3s,background .25s;will-change:transform;animation:cardIn .4s ease both}
    @keyframes cardIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    <?php for($i=0;$i<100;$i++): ?>.card:nth-child(<?=$i+1?>){animation-delay:<?=round($i*.026,3)?>s}<?php endfor; ?>
    .card::before{content:'';position:absolute;top:0;left:0;right:0;height:1.5px;background:linear-gradient(90deg,var(--c,var(--acc)),transparent 70%);transform:scaleX(0);transform-origin:left;transition:transform .4s ease}
    .card:hover::before{transform:scaleX(1)}
    .card::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at var(--mx,50%) var(--my,50%),color-mix(in srgb,var(--c,var(--acc)) 9%,transparent),transparent 65%);opacity:0;transition:opacity .35s}
    .card:hover::after{opacity:1}
    .card:hover{transform:translateY(-5px) perspective(900px) rotateY(var(--rx,0deg)) rotateX(var(--ry,0deg));border-color:color-mix(in srgb,var(--c,var(--acc)) 38%,transparent);box-shadow:0 16px 48px rgba(0,0,0,.45),0 0 28px color-mix(in srgb,var(--c,var(--acc)) 9%,transparent);background:color-mix(in srgb,var(--c,var(--acc)) 3.5%,var(--bg2))}
    .card.pinned{background:color-mix(in srgb,var(--c,var(--acc)) 5%,var(--bg2));border-color:color-mix(in srgb,var(--c,var(--acc)) 20%,var(--bdr))}
    .card.selected{border-color:var(--acc)!important;box-shadow:0 0 0 2px rgba(124,140,255,.28)!important}
    .card.dragging{opacity:.22;transform:scale(.95) rotate(1deg);z-index:999}
    .card.dragover{border-color:var(--acc);border-style:dashed}
    .card-cb{position:absolute;top:11px;left:11px;width:18px;height:18px;border-radius:5px;border:1.5px solid var(--bdr2);background:var(--bg3);display:none;align-items:center;justify-content:center;z-index:20;transition:all .17s;font-size:.58em}
    .card-cb.show{display:flex}
    .card-cb.on{background:var(--acc);border-color:var(--acc);color:#fff}
    .todo-badge{position:absolute;top:6px;left:6px;min-width:15px;height:15px;padding:0 4px;border-radius:4px;background:var(--warm);color:#fff;font-family:var(--mono);font-size:.52em;font-weight:600;display:flex;align-items:center;justify-content:center;z-index:10}
    .card-acts{position:absolute;top:9px;right:9px;display:flex;gap:4px;opacity:0;transform:translateY(-4px);transition:all .22s;z-index:15}
    .card:hover .card-acts,.card.selected .card-acts{opacity:1;transform:translateY(0)}
    .ca-btn{width:27px;height:27px;border-radius:7px;display:flex;align-items:center;justify-content:center;background:rgba(6,9,16,.85);backdrop-filter:blur(10px);border:1px solid var(--bdr2);color:var(--txt2);font-size:.7em;transition:all .16s}
    .ca-btn:hover{transform:scale(1.1)}
    .ca-btn.pin:hover,.ca-btn.pin.on{background:rgba(251,191,36,.18);border-color:var(--ylw);color:var(--ylw)}
    .ca-btn.edit:hover{background:var(--acc3);border-color:var(--acc);color:var(--acc2)}
    .ca-btn.note:hover,.ca-btn.note.has{background:rgba(52,211,153,.1);border-color:var(--grn);color:var(--grn)}
    .ca-btn.go:hover{background:rgba(52,211,153,.12);border-color:var(--grn);color:var(--grn)}
    .card-body{padding:16px 15px 11px;flex:1;display:flex;flex-direction:column}
    .card-top{display:flex;align-items:flex-start;gap:11px;flex-shrink:0}
    .c-thumb{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.18em;flex-shrink:0;background-size:cover;background-position:center;transition:transform .4s cubic-bezier(.34,1.56,.64,1);position:relative;overflow:hidden}
    .card:hover .c-thumb{transform:scale(1.14) rotate(-6deg)}
    .c-thumb::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.12) 0%,transparent 60%);border-radius:inherit}
    .c-info{flex:1;min-width:0}
    .c-name{font-size:.95em;font-weight:700;color:var(--txt);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-.25px;text-decoration:none;transition:color .2s}
    .card:hover .c-name{color:var(--c,var(--acc2))}
    .c-meta-row{display:flex;align-items:center;gap:5px;margin-top:3px;flex-wrap:wrap;flex-shrink:0}
    .type-badge{font-family:var(--mono);font-size:.62em;color:var(--c,var(--acc2));display:flex;align-items:center;gap:3px;opacity:.85}
    .type-badge i{font-size:.45em}
    .status-pill{display:inline-flex;align-items:center;gap:5px;font-family:var(--mono);font-size:.595em;font-weight:600;padding:2px 8px 2px 6px;border-radius:20px;letter-spacing:.15px;user-select:none;line-height:1.6}
    .status-pill .sp-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0;position:relative}
    .sp-active{background:rgba(52,211,153,.08);color:#2dd4a0;border:1px solid rgba(52,211,153,.2)}
    .sp-active .sp-dot{background:#34d399;box-shadow:0 0 5px rgba(52,211,153,.7);animation:breath-green 2.2s ease-in-out infinite}
    @keyframes breath-green{0%,100%{transform:scale(1);opacity:1;box-shadow:0 0 4px rgba(52,211,153,.6)}50%{transform:scale(.55);opacity:.3;box-shadow:0 0 2px rgba(52,211,153,.2)}}
    .sp-wip{background:rgba(244,114,182,.08);color:#f472b6;border:1px solid rgba(244,114,182,.2)}
    .sp-wip .sp-dot{background:#f472b6;box-shadow:0 0 5px rgba(244,114,182,.6);animation:breath-pink 1.3s ease-in-out infinite}
    @keyframes breath-pink{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.5);opacity:.25}}
    .sp-paused{background:rgba(251,191,36,.07);color:#f59e0b;border:1px solid rgba(251,191,36,.18)}
    .sp-paused .sp-dot{background:#fbbf24;animation:breath-amber 3s ease-in-out infinite}
    @keyframes breath-amber{0%,100%{opacity:1}50%{opacity:.35}}
    .sp-done{background:rgba(96,165,250,.07);color:#60a5fa;border:1px solid rgba(96,165,250,.18)}
    .sp-done .sp-dot{background:#60a5fa;opacity:.85}
    .sp-archived{background:rgba(100,116,139,.065);color:#64748b;border:1px solid rgba(100,116,139,.14)}
    .sp-archived .sp-dot{background:#64748b;opacity:.45}
    .new-badge{font-family:var(--mono);font-size:.58em;font-weight:700;color:var(--grn);letter-spacing:.5px}
    .c-desc{font-size:.76em;color:var(--txt2);margin-top:7px;line-height:1.65;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-weight:400;flex-shrink:0}
    .c-tags{display:flex;flex-wrap:wrap;gap:3px;margin-top:auto;padding-top:7px}
    .ctag{padding:2px 7px;border-radius:6px;font-family:var(--mono);font-size:.59em;background:var(--acc4);color:var(--acc2);border:1px solid rgba(124,140,255,.18);transition:all .16s}
    .ctag:hover{background:var(--acc3)}
    .c-stars{display:flex;gap:2px;margin-top:auto;padding-top:6px}
    .star{font-size:.7em;color:var(--bdr2);transition:color .12s}
    .star.on{color:var(--ylw)}
    .card-foot{display:flex;align-items:center;padding:9px 15px;border-top:1px solid var(--bdr);background:rgba(0,0,0,.18)}
    .cf-m{display:flex;align-items:center;gap:4px;font-family:var(--mono);font-size:.62em;color:var(--txt3);flex-shrink:0}
    .cf-m i{font-size:.8em}
    .cf-m+.cf-m{margin-left:10px}
    .cf-sp{flex:1}
    .qas{display:flex;gap:3px;opacity:0;transition:opacity .22s}
    .card:hover .qas{opacity:1}
    .qa-btn{padding:3px 7px;border-radius:6px;font-family:var(--mono);font-size:.62em;font-weight:500;border:1px solid var(--bdr);background:var(--glass);color:var(--txt3);display:flex;align-items:center;gap:3px;transition:all .16s}
    .qa-btn:hover{background:var(--glass2);color:var(--txt)}
    .qa-btn.vsc:hover{border-color:#0078d4;color:#0078d4;background:rgba(0,120,212,.09)}
    .qa-btn.trm:hover{border-color:var(--grn);color:var(--grn);background:rgba(52,211,153,.09)}
    .drag-h{position:absolute;left:7px;top:50%;transform:translateY(-50%);color:var(--txt3);font-size:.62em;opacity:0;transition:opacity .16s;z-index:5;padding:4px 3px}
    .card:hover .drag-h{opacity:.5}
    .drag-h:hover{opacity:1!important;color:var(--acc)}
    .compact .c-desc,.compact .c-tags,.compact .c-stars{display:none}
    .compact .card-body{padding:11px 12px}
    .compact .c-thumb{width:34px;height:34px;font-size:1em}
    .list .card{display:flex;align-items:center}
    .list .card-body{flex:1;padding:10px 13px;display:flex;align-items:center;gap:11px}
    .list .c-info{flex:1}
    .list .c-thumb{width:34px;height:34px;font-size:1em;flex-shrink:0}
    .list .c-desc,.list .c-tags,.list .c-stars{display:none}
    .list .card-foot{padding:0 13px;border-top:none;border-left:1px solid var(--bdr);min-width:220px;background:none}
    .list .card-acts{position:static;opacity:1;transform:none;margin-right:5px}
    .list .qas{opacity:1}
    .list .card:hover{transform:none;box-shadow:inset 3px 0 0 var(--c,var(--acc))}
    .empty{text-align:center;padding:70px 20px;color:var(--txt3)}
    .empty i{font-size:2.2em;display:block;margin-bottom:12px;opacity:.2}
    .empty p{font-family:var(--mono);font-size:.8em}
    #dp{position:fixed;right:-420px;top:0;width:400px;height:100vh;background:var(--bg2);border-left:1px solid var(--bdr2);z-index:450;transition:right .4s cubic-bezier(.34,1.56,.64,1);display:flex;flex-direction:column;box-shadow:-20px 0 60px rgba(0,0,0,.5)}
    #dp.open{right:0}
    .dp-head{padding:16px 18px 12px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:12px}
    .dp-title{font-weight:700;font-size:.93em;flex:1;letter-spacing:-.2px}
    .dp-close{width:26px;height:26px;background:var(--glass);border:1px solid var(--bdr);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--txt3);font-size:.78em;transition:all .16s}
    .dp-close:hover{background:var(--glass2);color:var(--txt)}
    .dp-body{flex:1;overflow-y:auto;padding:18px}
    .dp-body::-webkit-scrollbar{width:3px}
    .dp-hero{display:flex;align-items:center;gap:13px;margin-bottom:18px;padding:14px;background:var(--glass);border:1px solid var(--bdr);border-radius:12px}
    .dp-ico{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4em;flex-shrink:0}
    .dp-pname{font-size:1.05em;font-weight:800;letter-spacing:-.3px}
    .dp-ptype{font-family:var(--mono);font-size:.67em;color:var(--txt3);margin-top:2px}
    .dp-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:18px}
    .dp-stat{background:var(--glass);border:1px solid var(--bdr);border-radius:9px;padding:9px 6px;text-align:center}
    .dp-sv{font-family:var(--mono);font-size:1.05em;font-weight:500;color:var(--txt);line-height:1}
    .dp-sl{font-family:var(--mono);font-size:.56em;color:var(--txt3);margin-top:2px;text-transform:uppercase;letter-spacing:.4px}
    .dp-sec{font-family:var(--mono);font-size:.59em;font-weight:600;color:var(--txt3);letter-spacing:1.6px;text-transform:uppercase;margin-bottom:9px;display:flex;align-items:center;gap:7px}
    .dp-sec i{color:var(--acc2);font-size:.95em}
    .dp-sec::after{content:'';flex:1;height:1px;background:var(--bdr)}
    .dp-block{margin-bottom:20px}
    .note-editor-wrap{border-radius:12px;overflow:hidden;border:1px solid var(--bdr);transition:border-color .22s,box-shadow .22s;background:var(--bg3)}
    .note-editor-wrap:focus-within{border-color:rgba(109,127,255,.5);box-shadow:0 0 0 3px rgba(109,127,255,.1),0 4px 20px rgba(0,0,0,.3)}
    .note-tb{display:flex;align-items:center;justify-content:space-between;padding:10px 14px 9px;background:rgba(0,0,0,.25);border-bottom:1px solid var(--bdr)}
    .note-tb-l{display:flex;align-items:center;gap:8px;font-family:var(--mono);font-size:.62em;color:var(--txt2);font-weight:500;letter-spacing:.2px}
    .note-tb-l i{color:var(--acc2);font-size:1em;width:13px}
    .note-tb-r{display:flex;align-items:center;gap:7px}
    .note-ts{font-family:var(--mono);font-size:.57em;color:var(--txt3);background:var(--bg4);padding:2px 7px;border-radius:5px;border:1px solid var(--bdr)}
    .note-ta{display:block;width:100%;background:transparent;border:none;outline:none;padding:14px 16px;color:var(--txt);font-family:var(--mono);font-size:.78em;line-height:1.95;min-height:130px;resize:none;letter-spacing:.05px;background-image:repeating-linear-gradient(transparent,transparent calc(1.95em - 1px),rgba(109,127,255,.045) calc(1.95em - 1px),rgba(109,127,255,.045) 1.95em);background-size:100% 1.95em;background-attachment:local}
    .note-ta::placeholder{color:var(--txt3);font-style:italic}
    .note-foot{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;border-top:1px solid var(--bdr);background:rgba(0,0,0,.2)}
    .note-chars{font-family:var(--mono);font-size:.59em;color:var(--txt3);display:flex;align-items:center;gap:5px}
    .note-chars i{font-size:.85em}
    .note-actions{display:flex;align-items:center;gap:9px}
    .note-saved{font-family:var(--mono);font-size:.6em;color:var(--grn);display:flex;align-items:center;gap:4px;opacity:0;transition:opacity .3s;pointer-events:none}
    .note-saved.show{opacity:1}
    .note-save-btn{padding:5px 12px;background:var(--acc);color:#fff;border:none;border-radius:7px;font-family:var(--ui);font-size:.7em;font-weight:700;letter-spacing:.1px;transition:all .16s}
    .note-save-btn:hover{filter:brightness(1.12);transform:translateY(-1px);box-shadow:0 4px 12px rgba(109,127,255,.35)}
    .todo-list{display:flex;flex-direction:column;gap:5px;margin-bottom:9px}
    .todo-item{display:flex;align-items:center;gap:8px;padding:8px 11px;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;font-size:.79em;font-weight:500;transition:all .16s}
    .todo-item:hover{border-color:var(--bdr2)}
    .todo-cb{width:14px;height:14px;border-radius:3px;border:1.5px solid var(--bdr2);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .16s;font-size:.6em}
    .todo-item.done .todo-cb{background:var(--grn);border-color:var(--grn);color:#fff}
    .todo-item.done .todo-t{text-decoration:line-through;color:var(--txt3)}
    .todo-t{flex:1}
    .todo-del{color:var(--txt3);font-size:.8em;transition:color .14s}
    .todo-del:hover{color:var(--red)}
    .todo-add{display:flex;gap:6px;margin-top:5px}
    .todo-inp{flex:1;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;padding:7px 10px;color:var(--txt);font-family:var(--mono);font-size:.74em;outline:none;transition:all .2s}
    .todo-inp:focus{border-color:var(--acc)}
    .todo-inp::placeholder{color:var(--txt3)}
    .todo-add-btn{padding:7px 11px;background:var(--acc);color:#fff;border:none;border-radius:8px;font-family:var(--mono);font-size:.74em;font-weight:600;transition:all .16s}
    .todo-add-btn:hover{filter:brightness(1.1)}
    .dp-foot{padding:13px 18px;border-top:1px solid var(--bdr);display:flex;gap:6px;flex-wrap:wrap}
    .dp-foot .btn{font-size:.72em;padding:7px 11px}
    #spotlight{position:fixed;inset:0;background:rgba(0,0,0,.82);backdrop-filter:blur(18px);z-index:700;display:none;align-items:flex-start;justify-content:center;padding-top:13vh}
    #spotlight.open{display:flex}
    .sp-box{width:600px;max-width:93vw;background:var(--bg3);border:1px solid var(--bdr2);border-radius:16px;overflow:hidden;box-shadow:var(--shadow);animation:spIn .2s cubic-bezier(.34,1.56,.64,1)}
    @keyframes spIn{from{opacity:0;transform:scale(.9) translateY(-14px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .sp-top{display:flex;align-items:center;gap:11px;padding:13px 16px;border-bottom:1px solid var(--bdr)}
    .sp-top i{color:var(--acc);font-size:.95em;flex-shrink:0}
    #sp-inp{flex:1;background:transparent;border:none;outline:none;color:var(--txt);font-family:var(--mono);font-size:.88em}
    #sp-inp::placeholder{color:var(--txt3)}
    .sp-list{max-height:320px;overflow-y:auto;padding:5px}
    .sp-item{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:8px;transition:all .12s}
    .sp-item:hover,.sp-item.hi{background:var(--acc3)}
    .sp-thumb{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.88em;flex-shrink:0}
    .sp-info{flex:1;min-width:0}
    .sp-name{font-weight:600;font-size:.84em;color:var(--txt);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sp-meta{font-family:var(--mono);font-size:.63em;color:var(--txt3);margin-top:1px}
    .sp-acts{display:flex;gap:3px;flex-shrink:0}
    .sp-act{padding:2px 6px;border-radius:5px;font-family:var(--mono);font-size:.59em;background:var(--glass2);border:1px solid var(--bdr);color:var(--txt3);transition:all .12s}
    .sp-act:hover{color:var(--txt)}
    .sp-divider{padding:5px 11px 3px;font-family:var(--mono);font-size:.57em;color:var(--txt3);letter-spacing:1.5px;text-transform:uppercase}
    .sp-foot{padding:8px 14px;border-top:1px solid var(--bdr);display:flex;gap:12px;font-family:var(--mono);font-size:.6em;color:var(--txt3)}
    .sp-hint{display:flex;align-items:center;gap:4px}
    .sp-hint kbd{background:var(--bg5);border:1px solid var(--bdr);border-radius:3px;padding:1px 4px;font-size:.9em}
    #rnd-overlay{position:fixed;inset:0;z-index:800;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.88);backdrop-filter:blur(20px)}
    #rnd-overlay.open{display:flex}
    .rnd-card{background:var(--bg2);border:1px solid var(--bdr2);border-radius:20px;padding:36px;text-align:center;max-width:360px;width:90%;animation:rndIn .5s cubic-bezier(.34,1.56,.64,1)}
    @keyframes rndIn{from{opacity:0;transform:scale(.62) rotate(-8deg)}to{opacity:1;transform:scale(1) rotate(0)}}
    .rnd-ico{width:68px;height:68px;border-radius:17px;display:flex;align-items:center;justify-content:center;font-size:2em;margin:0 auto 16px}
    .rnd-name{font-size:1.38em;font-weight:800;letter-spacing:-.4px;margin-bottom:5px}
    .rnd-type{font-family:var(--mono);font-size:.72em;color:var(--txt3);margin-bottom:20px}
    .rnd-acts{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(12px);z-index:600;align-items:center;justify-content:center}
    .overlay.open{display:flex}
    .modal{background:var(--bg2);border:1px solid var(--bdr2);border-radius:16px;padding:26px;max-width:490px;width:93%;animation:mIn .26s cubic-bezier(.34,1.56,.64,1);box-shadow:var(--shadow);max-height:92vh;overflow-y:auto}
    @keyframes mIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
    .modal::-webkit-scrollbar{width:3px}
    .m-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .m-title{font-size:1.02em;font-weight:800;display:flex;align-items:center;gap:8px;letter-spacing:-.2px}
    .m-title i{color:var(--acc2);font-size:.9em}
    .m-close{width:26px;height:26px;background:var(--glass);border:1px solid var(--bdr);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--txt3);font-size:.78em;transition:all .16s}
    .m-close:hover{background:var(--glass2);color:var(--txt)}
    .field{margin-bottom:15px}
    .field label{display:block;font-family:var(--mono);font-size:.6em;color:var(--txt3);margin-bottom:6px;letter-spacing:.5px;text-transform:uppercase;font-weight:600}
    .field input,.field textarea,.field select{width:100%;background:var(--bg3);border:1px solid var(--bdr);border-radius:9px;padding:9px 12px;color:var(--txt);font-family:var(--mono);font-size:.8em;outline:none;transition:all .2s}
    .field input:focus,.field textarea:focus,.field select:focus{border-color:var(--acc);background:var(--acc4)}
    .field input::placeholder{color:var(--txt3)}
    .field textarea{resize:vertical;min-height:68px;line-height:1.7}
    .field select{appearance:none}
    .field select option{background:var(--bg3)}
    .col-grid,.ico-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
    .col-sw{aspect-ratio:1;border-radius:7px;border:2px solid transparent;transition:all .16s}
    .col-sw:hover{transform:scale(1.12)}
    .col-sw.sel{border-color:#fff;box-shadow:0 0 0 2px rgba(255,255,255,.12)}
    .ico-sw{aspect-ratio:1;border-radius:7px;border:2px solid var(--bdr);background:var(--glass);display:flex;align-items:center;justify-content:center;font-size:.95em;color:var(--txt3);transition:all .16s}
    .ico-sw:hover{border-color:var(--acc);color:var(--txt)}
    .ico-sw.sel{border-color:var(--acc);background:var(--acc3);color:var(--txt)}
    .tag-wrap{display:flex;flex-wrap:wrap;gap:4px;background:var(--bg3);border:1px solid var(--bdr);border-radius:9px;padding:7px;transition:border-color .2s;min-height:38px}
    .tag-wrap:focus-within{border-color:var(--acc)}
    .tw-tag{display:inline-flex;align-items:center;gap:5px;padding:2px 7px;border-radius:6px;background:var(--acc3);border:1px solid rgba(124,140,255,.22);color:var(--acc2);font-family:var(--mono);font-size:.66em}
    .tag-del{color:var(--txt3);font-size:.82em;transition:color .14s}
    .tag-del:hover{color:var(--red)}
    .tag-field{background:transparent;border:none;outline:none;color:var(--txt);font-family:var(--mono);font-size:.78em;min-width:80px;flex:1}
    .tag-field::placeholder{color:var(--txt3)}
    .up-zone{border:2px dashed var(--bdr);border-radius:10px;padding:20px;text-align:center;color:var(--txt3);font-family:var(--mono);font-size:.73em;transition:all .18s}
    .up-zone:hover{border-color:var(--acc);background:var(--acc4);color:var(--acc2)}
    .up-zone i{font-size:1.5em;display:block;margin-bottom:6px}
    .up-zone.has{padding:0;border:none;overflow:hidden}
    .up-zone img{width:100%;height:100px;object-fit:cover;border-radius:9px}
    .r-row{display:flex;gap:4px}
    .r-star{font-size:1.25em;color:var(--bdr2);transition:color .12s}
    .r-star.on{color:var(--ylw)}
    .form-btns{display:flex;gap:7px;margin-top:5px}
    .submit-btn{flex:1;padding:10px;border-radius:9px;background:var(--acc);color:#fff;font-family:var(--ui);font-weight:700;font-size:.8em;border:none;transition:all .16s}
    .submit-btn:hover{filter:brightness(1.08);transform:translateY(-1px)}
    .cancel-btn{padding:10px 14px;border-radius:9px;background:var(--glass);color:var(--txt2);font-family:var(--ui);font-size:.8em;border:1px solid var(--bdr);transition:all .16s}
    .cancel-btn:hover{background:var(--glass2);color:var(--txt)}
    .modal-wide{max-width:620px!important}
    .stabs{display:flex;gap:2px;background:var(--bg3);border:1px solid var(--bdr);border-radius:11px;padding:3px;margin-bottom:20px}
    .stab{flex:1;padding:8px 4px;border-radius:8px;font-size:.72em;font-weight:600;text-align:center;color:var(--txt3);transition:all .17s;display:flex;align-items:center;justify-content:center;gap:5px;letter-spacing:-.1px}
    .stab i{font-size:.82em}
    .stab:hover{color:var(--txt2);background:var(--glass2)}
    .stab.on{background:var(--acc);color:#fff;box-shadow:0 2px 8px rgba(0,0,0,.3)}
    .spanel{display:none;animation:fadeup .16s ease}
    .spanel.on{display:block}
    .srow{display:flex;align-items:center;gap:14px;padding:11px 0;border-bottom:1px solid var(--bdr)}
    .srow:last-child{border-bottom:none}
    .srow-l{flex:1;min-width:0}
    .srow-title{font-size:.83em;font-weight:600;color:var(--txt);letter-spacing:-.15px;display:flex;align-items:center;gap:7px}
    .srow-title i{color:var(--acc2);font-size:.82em}
    .srow-desc{font-size:.68em;color:var(--txt3);margin-top:2px;line-height:1.5}
    .toggle-wrap{position:relative;width:38px;height:21px;flex-shrink:0}
    .toggle-inp{opacity:0;width:0;height:0;position:absolute}
    .toggle-slider{position:absolute;inset:0;background:var(--bg5);border:1.5px solid var(--bdr2);border-radius:21px;transition:all .2s}
    .toggle-slider::after{content:'';position:absolute;width:15px;height:15px;left:2px;top:2px;background:var(--txt3);border-radius:50%;transition:all .2s;box-shadow:0 1px 4px rgba(0,0,0,.4)}
    .toggle-inp:checked~.toggle-slider{background:var(--acc);border-color:var(--acc)}
    .toggle-inp:checked~.toggle-slider::after{transform:translateX(17px);background:#fff}
    .seg-ctrl{display:flex;background:var(--bg3);border:1.5px solid var(--bdr);border-radius:9px;padding:2px;gap:1px;flex-shrink:0}
    .seg-btn{flex:1;padding:6px 9px;border-radius:7px;font-family:var(--mono);font-size:.68em;font-weight:600;color:var(--txt3);text-align:center;transition:all .15s;white-space:nowrap}
    .seg-btn:hover{color:var(--txt2);background:var(--glass)}
    .seg-btn.on{background:var(--acc);color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.3)}
    .s-select{background:var(--bg3);border:1.5px solid var(--bdr);border-radius:9px;padding:7px 11px;color:var(--txt);font-family:var(--mono);font-size:.75em;outline:none;transition:border-color .18s;min-width:130px}
    .s-select:focus{border-color:var(--acc);background:var(--acc4)}
    .s-select option{background:var(--bg3)}
    input[type="range"]{-webkit-appearance:none;flex:1;height:4px;border-radius:4px;background:var(--bg5);border:none;outline:none;padding:0;accent-color:var(--acc)}
    input[type="range"]::-webkit-slider-thumb{-webkit-appearance:none;width:15px;height:15px;border-radius:50%;background:var(--acc);box-shadow:0 2px 8px rgba(0,0,0,.4);transition:transform .15s}
    input[type="range"]::-webkit-slider-thumb:hover{transform:scale(1.25)}
    .accent-palette{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
    .acc-sw{width:22px;height:22px;border-radius:6px;border:2.5px solid transparent;transition:all .16s}
    .acc-sw:hover{transform:scale(1.2)}
    .acc-sw.sel{border-color:#fff;box-shadow:0 0 0 2px rgba(255,255,255,.18);transform:scale(1.15)}
    .tpv-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-top:6px;width:100%}
    .tpv{border-radius:10px;overflow:hidden;border:2px solid var(--bdr);transition:all .18s;position:relative;aspect-ratio:16/10}
    .tpv:hover{transform:scale(1.04);border-color:var(--bdr2)}
    .tpv.sel{border-color:var(--acc);box-shadow:0 0 0 2px rgba(109,127,255,.3),0 6px 18px rgba(0,0,0,.5)}
    .tpv-inner{width:100%;height:100%;padding:5px;display:flex;flex-direction:column;gap:2px}
    .tpv-bar{height:2.5px;border-radius:2px}
    .tpv-lines{display:flex;flex-direction:column;gap:2px;flex:1;padding-top:2px}
    .tpv-line{height:2.5px;border-radius:2px;opacity:.28}
    .tpv-name{position:absolute;bottom:3px;left:0;right:0;text-align:center;font-size:7.5px;font-family:'DM Mono',monospace;color:rgba(255,255,255,.6);text-transform:capitalize;letter-spacing:.2px}
    .tpv.sel .tpv-name{color:rgba(255,255,255,.95);font-weight:700}
    .font-card{background:var(--bg3);border:1px solid var(--bdr);border-radius:9px;padding:10px 12px;margin-top:8px}
    .font-card .fc-big{font-size:.95em;font-weight:700;color:var(--txt)}
    .font-card .fc-sm{font-size:.67em;color:var(--txt3);margin-top:2px;font-family:var(--mono)}
    .dash-save-bar{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--acc3);border:1px solid rgba(109,127,255,.2);border-radius:11px;margin-top:18px}
    .dash-save-bar i{color:var(--acc2);font-size:1.05em;flex-shrink:0}
    .dash-save-bar span{font-size:.74em;color:var(--txt2);flex:1}
    [data-compact="1"] .card-body{padding:9px 11px!important}
    [data-compact="1"] .c-desc,[data-compact="1"] .c-stars{display:none!important}
    [data-compact="1"] .card-foot{padding:6px 11px!important}
    [data-density="comfortable"] .grid{gap:18px!important}[data-density="cozy"] .grid{gap:12px!important}[data-density="tight"] .grid{gap:7px!important}
    [data-rounded="sm"]{--r:7px}[data-rounded="md"]{--r:13px}[data-rounded="lg"]{--r:20px}[data-rounded="xl"]{--r:28px}
    [data-sidebar="narrow"]{--sidebar:216px}[data-sidebar="normal"]{--sidebar:264px}[data-sidebar="wide"]{--sidebar:308px}
    [data-animations="off"] .card{animation:none!important}[data-animations="off"] .card::before{display:none}
    [data-sidebar-glass="on"] .sidebar{background:rgba(13,17,33,.72)!important;backdrop-filter:blur(28px)}
    #ctx-menu{position:fixed;display:none;background:var(--bg3);border:1px solid var(--bdr2);border-radius:12px;padding:5px;z-index:1000;min-width:200px;box-shadow:var(--shadow);animation:ctxIn .13s ease}
    @keyframes ctxIn{from{opacity:0;transform:scale(.93)}to{opacity:1;transform:scale(1)}}
    .ctx-lbl{padding:5px 10px 3px;font-family:var(--mono);font-size:.57em;color:var(--txt3);letter-spacing:1.5px;text-transform:uppercase}
    .ctx-item{display:flex;align-items:center;gap:9px;padding:7px 10px;border-radius:7px;color:var(--txt2);font-size:.79em;font-weight:500;transition:all .12s}
    .ctx-item i{width:13px;font-size:.86em;color:var(--txt3);flex-shrink:0}
    .ctx-item:hover{background:var(--acc3);color:var(--txt)}
    .ctx-item:hover i{color:var(--acc2)}
    .ctx-div{height:1px;background:var(--bdr);margin:3px 0}
    .ctx-danger:hover{background:rgba(248,113,113,.1);color:var(--red)}
    .ctx-danger:hover i{color:var(--red)}
    #toast{position:fixed;bottom:20px;right:20px;background:var(--bg3);border:1px solid rgba(124,140,255,.28);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:8px;z-index:2000;font-family:var(--mono);font-size:.74em;box-shadow:var(--shadow);transform:translateY(55px);opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1);pointer-events:none;max-width:280px}
    #toast.show{transform:translateY(0);opacity:1}
    #toast i{font-size:.95em;flex-shrink:0}
    #toast.ok i{color:var(--grn)}#toast.err i{color:var(--red)}#toast.err{border-color:rgba(248,113,113,.28)}#toast.info i{color:var(--acc2)}
    .back-link{display:flex;align-items:center;gap:7px;color:var(--txt3);text-decoration:none;font-family:var(--mono);font-size:.73em;font-weight:500;transition:color .16s;padding:7px 10px;border-radius:8px;background:var(--glass);border:1px solid var(--bdr)}
    .back-link:hover{color:var(--txt)}

    /* ── FIX: Custom Delete Confirmation Modal ── */
    .del-icon-wrap{width:56px;height:56px;border-radius:16px;background:rgba(248,113,113,.12);border:1.5px solid rgba(248,113,113,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;animation:del-pulse 2s ease-in-out infinite}
    .del-icon-wrap i{color:var(--red);font-size:1.5em}
    @keyframes del-pulse{0%,100%{box-shadow:0 0 0 0 rgba(248,113,113,.3)}50%{box-shadow:0 0 0 8px rgba(248,113,113,0)}}
    .del-title{text-align:center;font-size:1.05em;font-weight:800;letter-spacing:-.3px;margin-bottom:8px}
    .del-desc{text-align:center;font-size:.76em;color:var(--txt2);line-height:1.7;margin-bottom:20px}
    .del-desc code{font-family:var(--mono);font-size:.9em;background:var(--bg3);padding:1px 5px;border-radius:4px;border:1px solid var(--bdr)}
    .del-btns{display:flex;gap:8px}
    .del-btn-confirm{flex:1;padding:10px;border-radius:9px;background:var(--red);color:#fff;font-family:var(--ui);font-weight:700;font-size:.82em;border:none;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .16s}
    .del-btn-confirm:hover{filter:brightness(1.12);transform:translateY(-1px);box-shadow:0 6px 18px rgba(248,113,113,.35)}
    .del-btn-cancel{flex:1;padding:10px;border-radius:9px;background:var(--glass);color:var(--txt2);font-family:var(--ui);font-size:.82em;border:1px solid var(--bdr);display:flex;align-items:center;justify-content:center;gap:6px;transition:all .16s}
    .del-btn-cancel:hover{background:var(--glass2);color:var(--txt)}

    @media(max-width:820px){.app{grid-template-columns:1fr}.sidebar{display:none}.content{padding:14px}.topbar{padding:10px 14px}.grid{grid-template-columns:1fr}#dp{width:100%;right:-100%}}
  </style>
</head>

<body>
  <canvas id="cv" style="position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.18;"></canvas>
  <div class="app">
    <aside class="sidebar">
      <div class="logo">
        <div class="logo-mark"><i class="fas fa-code"></i></div>
        <div>
          <div class="logo-name">Code<span>Hub</span></div>
          <div class="logo-env">localhost</div>
        </div>
      </div>
      <div>
        <div class="stats-grid">
          <div class="stat-cell"><div class="stat-val"><?=$total?></div><div class="stat-lbl">Projects</div></div>
          <div class="stat-cell accent"><div class="stat-val"><?=$pinned?></div><div class="stat-lbl">Pinned</div></div>
          <div class="stat-cell"><div class="stat-val"><?=$recent?></div><div class="stat-lbl">New</div></div>
          <div class="stat-cell <?=$pending>0?'accent':''?>"><div class="stat-val" <?=$pending>0?'style="color:var(--warm2)"':''?>><?=$pending?></div><div class="stat-lbl">Todos</div></div>
        </div>
      </div>
      <div>
        <div class="s-label">Browse</div>
        <ul class="nav">
          <li class="nav-item on" data-f="all"><i class="fas fa-th-large"></i>All Projects<span class="cnt"><?=$total?></span></li>
          <li class="nav-item" data-f="pin"><i class="fas fa-thumbtack"></i>Pinned<span class="cnt"><?=$pinned?></span></li>
          <li class="nav-item" data-f="recent"><i class="fas fa-clock"></i>Recent<span class="cnt"><?=$recent?></span></li>
          <li class="nav-item" data-f="notes"><i class="fas fa-sticky-note"></i>Has Notes<span class="cnt"><?=count(array_filter($projects,fn($p)=>!empty($p['note'])))?></span></li>
        </ul>
      </div>
      <div>
        <div class="s-label">Status</div>
        <ul class="nav">
          <?php $sts=['active'=>['grn','circle','Active'],'wip'=>['pnk','circle-notch','In Progress'],'paused'=>['ylw','pause','Paused'],'done'=>['acc2','check','Done'],'archived'=>['txt3','archive','Archived']];foreach($sts as $k=>[$col,$icon,$lbl]):$cnt=$stCounts[$k]??0;if(!$cnt)continue; ?>
          <li class="nav-item" data-f="st-<?=$k?>"><i class="fas fa-<?=$icon?>" style="color:var(--<?=$col?>)!important"></i><?=$lbl?><span class="cnt"><?=$cnt?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php if(!empty($typeCounts)): ?>
      <div><div class="s-label">Stack</div><div class="tag-cloud"><?php foreach($typeCounts as $t=>$c): ?><span class="stag" data-type="<?=strtolower($t)?>"><?=$t?> <span style="opacity:.45"><?=$c?></span></span><?php endforeach; ?></div></div>
      <?php endif; ?>
      <?php if(!empty($allTags)): ?>
      <div><div class="s-label">Tags</div><div class="tag-cloud"><?php foreach(array_slice($allTags,0,16,true) as $tag=>$c): ?><span class="stag" data-tag="<?=e($tag)?>">#<?=e($tag)?></span><?php endforeach; ?></div></div>
      <?php endif; ?>
      <div>
        <div class="s-label">Actions</div>
        <ul class="nav">
          <li class="nav-item" onclick="openModal('m-create')"><i class="fas fa-folder-plus"></i>New Project</li>
          <li class="nav-item" onclick="openSpotlight()"><i class="fas fa-search"></i>Spotlight<span class="cnt">⌘K</span></li>
          <li class="nav-item" onclick="showRandom()"><i class="fas fa-random"></i>Random Pick<span class="cnt">R</span></li>
          <li class="nav-item" onclick="toggleBulk()"><i class="fas fa-check-square"></i>Bulk Select<span class="cnt">B</span></li>
          <li class="nav-item" onclick="doExport('json')"><i class="fas fa-download"></i>Export JSON</li>
          <li class="nav-item" onclick="doExport('csv')"><i class="fas fa-file-csv"></i>Export CSV</li>
        </ul>
      </div>
      <div>
        <div class="s-label" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <span>Theme</span>
          <span style="font-size:.75em;opacity:.6;font-family:var(--mono);font-weight:400;letter-spacing:0;text-transform:none" id="theme-name-lbl">dark</span>
        </div>
        <div class="theme-picker" id="theme-picker">
          <?php foreach(['dark','midnight','forest','rose','void','dusk','mocha','ocean','dracula','nord','solar','crimson','slate','neon','amber','sakura','mint','lavender','copper','ice'] as $t): ?>
          <div class="theme-dot" data-t="<?=$t?>" title="<?=ucfirst($t)?>"></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <div class="s-label">Personalize</div>
        <ul class="nav">
          <li class="nav-item" onclick="openModal('m-dash')"><i class="fas fa-sliders-h"></i>Dashboard Settings</li>
        </ul>
      </div>
      <div class="sidebar-foot">
        <div class="live-badge"><div class="live-dot"></div>localhost · <?=date('H:i')?></div>
        <div style="font-family:var(--mono);font-size:.58em;color:var(--txt3);margin-top:4px"><?=date('D, M j Y')?></div>
      </div>
    </aside>
    <div class="main">
      <header class="topbar">
        <?php if($mostrarVolver): ?><a href=".." class="back-link"><i class="fas fa-arrow-left"></i>Back</a><?php endif; ?>
        <div class="search-wrap">
          <i class="fas fa-search search-ico"></i>
          <input type="text" id="srch" class="search-input" placeholder="Search projects, tags, descriptions…" autocomplete="off">
          <span class="search-kbd">/</span>
        </div>
        <div class="tb-spacer"></div>
        <div class="tb-right">
          <div class="sort-wrap">
            <button class="btn btn-ghost icon-btn" id="sort-btn" title="Sort"><i class="fas fa-sort-amount-down"></i></button>
            <div class="sort-dd" id="sort-dd">
              <div class="sort-item on" data-s="az"><i class="fas fa-sort-alpha-down"></i>Name A–Z</div>
              <div class="sort-item" data-s="za"><i class="fas fa-sort-alpha-up"></i>Name Z–A</div>
              <div class="sort-item" data-s="new"><i class="fas fa-clock"></i>Most Recent</div>
              <div class="sort-item" data-s="old"><i class="fas fa-history"></i>Oldest First</div>
              <div class="sort-item" data-s="files"><i class="fas fa-file"></i>Most Files</div>
              <div class="sort-item" data-s="opens"><i class="fas fa-eye"></i>Most Opened</div>
              <div class="sort-item" data-s="rating"><i class="fas fa-star"></i>Top Rated</div>
            </div>
          </div>
          <div class="view-toggle">
            <button class="vbtn on" id="v-grid" title="Grid"><i class="fas fa-th"></i></button>
            <button class="vbtn" id="v-list" title="List"><i class="fas fa-list"></i></button>
            <button class="vbtn" id="v-compact" title="Compact"><i class="fas fa-border-all"></i></button>
          </div>
          <button class="btn btn-ghost icon-btn" onclick="location.reload()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
          <button class="btn btn-primary" onclick="openModal('m-create')"><i class="fas fa-plus"></i>New</button>
        </div>
      </header>
      <main class="content">
        <div class="page-head">
          <div>
            <div class="page-title">Projects</div>
            <div class="page-sub" id="page-sub"><?=$total?> projects · localhost</div>
          </div>
          <div class="bulk-bar" id="bulk-bar">
            <i class="fas fa-check-square"></i>
            <span id="bulk-cnt">0</span>&nbsp;selected
            <button class="btn btn-ghost" style="font-size:.7em;padding:5px 9px" onclick="doBulk('pin')"><i class="fas fa-thumbtack"></i>Pin</button>
            <button class="btn btn-ghost" style="font-size:.7em;padding:5px 9px" onclick="doBulk('done')"><i class="fas fa-check"></i>Done</button>
            <button class="btn btn-ghost" style="font-size:.7em;padding:5px 9px" onclick="doBulk('archive')"><i class="fas fa-archive"></i>Archive</button>
            <button class="btn btn-danger" style="font-size:.7em;padding:5px 9px" onclick="doBulk('delete')"><i class="fas fa-trash"></i>Delete</button>
            <button class="btn btn-ghost" style="font-size:.7em;padding:5px 9px;width:30px" onclick="clearBulk()"><i class="fas fa-times"></i></button>
          </div>
        </div>
        <?php $pinnedProjects=array_filter($projects,fn($p)=>$p['pin']); ?>
        <?php if(!empty($pinnedProjects)): ?>
        <div id="sec-pinned">
          <div class="sec-head"><i class="fas fa-thumbtack" style="color:var(--ylw)"></i>Pinned</div>
          <div class="grid" id="grid-pinned">
            <?php foreach($pinnedProjects as $p):echo renderCard($p);endforeach; ?>
          </div>
          <br>
        </div>
        <?php endif; ?>
        <div class="sec-head"><i class="fas fa-folder-open"></i>All Projects</div>
        <div class="grid" id="main-grid">
          <?php foreach($projects as $p):echo renderCard($p);endforeach; ?>
        </div>
        <?php if(!$total): ?>
        <div class="empty"><i class="fas fa-folder-open"></i><p>No projects yet — create one!</p></div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <!-- DETAIL PANEL -->
  <div id="dp">
    <div class="dp-head"><span class="dp-title" id="dp-title">Details</span><div class="dp-close" onclick="closeDp()"><i class="fas fa-times"></i></div></div>
    <div class="dp-body" id="dp-body"></div>
    <div class="dp-foot" id="dp-foot"></div>
  </div>

  <!-- SPOTLIGHT -->
  <div id="spotlight">
    <div class="sp-box">
      <div class="sp-top"><i class="fas fa-terminal"></i><input id="sp-inp" placeholder="Search projects or run a command…"></div>
      <div class="sp-list" id="sp-list"></div>
      <div class="sp-foot">
        <div class="sp-hint"><kbd>↑↓</kbd>navigate</div>
        <div class="sp-hint"><kbd>↵</kbd>open</div>
        <div class="sp-hint"><kbd>Esc</kbd>close</div>
      </div>
    </div>
  </div>

  <!-- RANDOM -->
  <div id="rnd-overlay">
    <div class="rnd-card">
      <div class="rnd-ico" id="rnd-ico"></div>
      <div class="rnd-name" id="rnd-name"></div>
      <div class="rnd-type" id="rnd-type"></div>
      <div class="rnd-acts">
        <button class="btn btn-primary" id="rnd-open"><i class="fas fa-arrow-right"></i>Open</button>
        <button class="btn btn-ghost" onclick="showRandom()"><i class="fas fa-random"></i>Again</button>
        <button class="btn btn-ghost" onclick="closeRnd()"><i class="fas fa-times"></i></button>
      </div>
    </div>
  </div>

  <!-- FIX: Custom Delete Confirmation Modal -->
  <div class="overlay" id="m-confirm-del">
    <div class="modal" style="max-width:420px;text-align:center">
      <div class="del-icon-wrap"><i class="fas fa-trash"></i></div>
      <div class="del-title">¿Deseas eliminar este proyecto?</div>
      <div class="del-desc">El proyecto <strong id="del-proj-name"></strong> se moverá a la papelera.<br>Puedes recuperarlo desde <code>.codehub_trash</code>.</div>
      <div class="del-btns">
        <button class="del-btn-confirm" id="del-confirm-btn"><i class="fas fa-trash"></i> Eliminar</button>
        <button class="del-btn-cancel" onclick="closeModal('m-confirm-del')"><i class="fas fa-times"></i> Cancelar</button>
      </div>
    </div>
  </div>

  <!-- MODAL: CREATE -->
  <div class="overlay" id="m-create">
    <div class="modal">
      <div class="m-head">
        <div class="m-title"><i class="fas fa-folder-plus"></i>New Project</div>
        <div class="m-close" onclick="closeModal('m-create')"><i class="fas fa-times"></i></div>
      </div>
      <form id="f-create">
        <div class="field"><label>Project Name</label><input id="f-name" placeholder="my-project" required autofocus></div>
        <div class="field"><label>Starter</label><select id="f-tpl"><option value="">Empty folder</option><option value="html">HTML + CSS + JS</option><option value="readme">README.md</option></select></div>
        <div class="form-btns"><button type="submit" class="submit-btn"><i class="fas fa-check"></i>Create</button><button type="button" class="cancel-btn" onclick="closeModal('m-create')">Cancel</button></div>
      </form>
    </div>
  </div>

  <!-- MODAL: RENAME -->
  <div class="overlay" id="m-rename">
    <div class="modal">
      <div class="m-head">
        <div class="m-title"><i class="fas fa-pencil-alt"></i>Rename</div>
        <div class="m-close" onclick="closeModal('m-rename')"><i class="fas fa-times"></i></div>
      </div>
      <form id="f-rename">
        <input type="hidden" id="ren-old">
        <div class="field"><label>New Name</label><input id="ren-new" required autofocus></div>
        <div class="form-btns"><button type="submit" class="submit-btn"><i class="fas fa-check"></i>Rename</button><button type="button" class="cancel-btn" onclick="closeModal('m-rename')">Cancel</button></div>
      </form>
    </div>
  </div>

  <!-- MODAL: NEW FILE -->
  <div class="overlay" id="m-file">
    <div class="modal">
      <div class="m-head">
        <div class="m-title"><i class="fas fa-file-code"></i>New File</div>
        <div class="m-close" onclick="closeModal('m-file')"><i class="fas fa-times"></i></div>
      </div>
      <form id="f-file">
        <input type="hidden" id="ff-dir">
        <div class="field"><label>File Name</label><input id="ff-name" placeholder="index.html" required autofocus></div>
        <div class="form-btns"><button type="submit" class="submit-btn"><i class="fas fa-check"></i>Create</button><button type="button" class="cancel-btn" onclick="closeModal('m-file')">Cancel</button></div>
      </form>
    </div>
  </div>

  <!-- MODAL: CUSTOMIZE -->
  <div class="overlay" id="m-cust">
    <div class="modal" style="max-width:510px">
      <div class="m-head">
        <div class="m-title"><i class="fas fa-sliders-h"></i>Customize</div>
        <div class="m-close" onclick="closeModal('m-cust')"><i class="fas fa-times"></i></div>
      </div>
      <form id="f-cust">
        <input type="hidden" id="c-dir">
        <div class="field"><label>Description</label><textarea id="c-desc" placeholder="What does this project do?"></textarea></div>
        <div class="field"><label>Status</label><select id="c-st">
          <option value="active">🟢 Active</option><option value="wip">🔴 In Progress</option><option value="paused">🟡 Paused</option><option value="done">🔵 Done</option><option value="archived">⚫ Archived</option>
        </select></div>
        <div class="field"><label>Custom URL / Port</label><input id="c-url" placeholder="http://localhost:3000"></div>
        <div class="field"><label>Rating</label><div class="r-row" id="r-row"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star r-star" data-v="<?=$i?>"></i><?php endfor; ?></div></div>
        <div class="field"><label>Tags</label><div class="tag-wrap" id="tag-wrap"><input class="tag-field" id="tag-fld" placeholder="add tag + Enter"></div></div>
        <div class="field"><label>Accent Color</label><div class="col-grid" id="col-grid"></div></div>
        <div class="field"><label>Icon</label><div class="ico-grid" id="ico-grid"></div></div>
        <div class="field"><label>Thumbnail</label><div class="up-zone" id="up-zone"><i class="fas fa-image"></i>Click to upload<input type="file" id="img-fld" accept="image/*" style="display:none"></div></div>
        <div class="form-btns"><button type="submit" class="submit-btn"><i class="fas fa-save"></i>Save Changes</button><button type="button" class="cancel-btn" onclick="closeModal('m-cust')">Cancel</button></div>
      </form>
    </div>
  </div>

  <!-- MODAL: DASHBOARD -->
  <div class="overlay" id="m-dash">
    <div class="modal modal-wide" style="max-height:88vh;overflow-y:auto;padding-bottom:20px">
      <div class="m-head" style="margin-bottom:16px">
        <div class="m-title"><i class="fas fa-sliders-h"></i>Dashboard Settings</div>
        <div class="m-close" onclick="closeModal('m-dash')"><i class="fas fa-times"></i></div>
      </div>
      <div class="stabs">
        <div class="stab on" data-tab="themes" onclick="switchTab(this,'themes')"><i class="fas fa-swatchbook"></i>Themes</div>
        <div class="stab" data-tab="appearance" onclick="switchTab(this,'appearance')"><i class="fas fa-palette"></i>Style</div>
        <div class="stab" data-tab="layout" onclick="switchTab(this,'layout')"><i class="fas fa-th-large"></i>Layout</div>
        <div class="stab" data-tab="effects" onclick="switchTab(this,'effects')"><i class="fas fa-magic"></i>Effects</div>
      </div>
      <div class="spanel on" id="tab-themes">
        <div class="srow" style="flex-direction:column;align-items:flex-start">
          <div class="srow-title" style="margin-bottom:4px"><i class="fas fa-swatchbook"></i>Choose Theme <span style="font-size:.8em;font-weight:400;color:var(--txt3);margin-left:4px">20 themes</span></div>
          <div class="srow-desc">Click any theme to apply instantly.</div>
          <div class="tpv-grid">
            <?php $themeData=['dark'=>['#07080f','#6d7fff'],'midnight'=>['#000000','#00d4ff'],'forest'=>['#040a06','#4ade80'],'rose'=>['#0c0409','#fb7185'],'void'=>['#000000','#f8f8f8'],'dusk'=>['#0c0618','#a78bfa'],'mocha'=>['#0e0804','#f59e0b'],'ocean'=>['#010d10','#2dd4bf'],'dracula'=>['#0f0f1a','#bd93f9'],'nord'=>['#181c28','#88c0d0'],'solar'=>['#001018','#268bd2'],'crimson'=>['#0e0204','#f43f5e'],'slate'=>['#080a10','#38bdf8'],'neon'=>['#010102','#39ff14'],'amber'=>['#0a0602','#fcd34d'],'sakura'=>['#0c0610','#ec4899'],'mint'=>['#01080a','#6ee7b7'],'lavender'=>['#08060e','#c4b5fd'],'copper'=>['#0a0603','#e8974a'],'ice'=>['#000810','#7dd3fc']];
            foreach($themeData as $tn=>[$bg,$ac]): ?>
            <div class="tpv" data-t="<?=$tn?>" onclick="applyTheme('<?=$tn?>')">
              <div class="tpv-inner" style="background:<?=$bg?>"><div class="tpv-bar" style="background:<?=$ac?>"></div><div class="tpv-lines"><div class="tpv-line" style="background:<?=$ac?>;width:80%"></div><div class="tpv-line" style="background:<?=$ac?>;width:55%"></div><div class="tpv-line" style="background:<?=$ac?>;width:65%"></div></div></div>
              <div class="tpv-name"><?=$tn?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="srow">
          <div class="srow-l">
            <div class="srow-title"><i class="fas fa-tint"></i>Accent Color Override</div>
            <div class="srow-desc">Override the theme's accent with any color</div>
            <div class="accent-palette">
              <?php foreach(['#6d7fff','#818cf8','#a78bfa','#c084fc','#e879f9','#f472b6','#fb7185','#f97316','#f59e0b','#fbbf24','#84cc16','#34d399','#14b8a6','#38bdf8','#60a5fa','#e11d48','#fff','#94a3b8'] as $ac): ?>
              <div class="acc-sw" data-col="<?=$ac?>" style="background:<?=$ac?>" onclick="pickAccent('<?=$ac?>')"></div>
              <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:9px">
              <input type="color" id="accent-custom" value="#6d7fff" style="width:30px;height:30px;padding:1px 2px;border:1.5px solid var(--bdr);border-radius:7px;background:var(--bg3)">
              <span style="font-family:var(--mono);font-size:.64em;color:var(--txt3)">Pick any hex color</span>
              <button class="btn btn-ghost" style="font-size:.65em;padding:4px 9px;margin-left:auto" onclick="resetAccent()"><i class="fas fa-undo"></i> Reset</button>
            </div>
          </div>
        </div>
      </div>
      <div class="spanel" id="tab-appearance">
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-font"></i>UI Font</div><div class="srow-desc">Typeface used throughout the interface</div><div class="font-card" id="font-card"><div class="fc-big">The quick brown fox</div><div class="fc-sm">abcdefghijklmnopqrstuvwxyz 0123456789</div></div></div><select class="s-select" id="font-pick" onchange="applyFont(this.value)" style="align-self:flex-start"><option value="DM Sans">DM Sans</option><option value="Inter">Inter</option><option value="Outfit">Outfit</option><option value="Nunito">Nunito</option><option value="Syne">Syne</option><option value="Lexend">Lexend</option><option value="Plus Jakarta Sans">Plus Jakarta</option><option value="Space Grotesk">Space Grotesk</option></select></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-text-height"></i>Font Size</div><div class="srow-desc">Base size for all text</div></div><div class="seg-ctrl"><div class="seg-btn" id="fs-sm" onclick="applyFsize('sm',this)">Small</div><div class="seg-btn on" id="fs-md" onclick="applyFsize('md',this)">Medium</div><div class="seg-btn" id="fs-lg" onclick="applyFsize('lg',this)">Large</div></div></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-vector-square"></i>Corner Radius</div><div class="srow-desc">Roundness of cards and panels</div></div><div class="seg-ctrl"><div class="seg-btn" id="r-sm" onclick="applyRound('sm',this)">■</div><div class="seg-btn on" id="r-md" onclick="applyRound('md',this)">▢</div><div class="seg-btn" id="r-lg" onclick="applyRound('lg',this)">◯</div><div class="seg-btn" id="r-xl" onclick="applyRound('xl',this)">⬭</div></div></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-arrows-alt-h"></i>Sidebar Width</div><div class="srow-desc">Controls the left sidebar size</div></div><div class="seg-ctrl"><div class="seg-btn" id="sw-narrow" onclick="applySidebarW('narrow',this)">Narrow</div><div class="seg-btn on" id="sw-normal" onclick="applySidebarW('normal',this)">Normal</div><div class="seg-btn" id="sw-wide" onclick="applySidebarW('wide',this)">Wide</div></div></div>
      </div>
      <div class="spanel" id="tab-layout">
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-th"></i>Default View</div><div class="srow-desc">How project cards are displayed by default</div></div><div class="seg-ctrl"><div class="seg-btn on" id="dv-grid" onclick="applyDefaultView('grid',this)"><i class="fas fa-th"></i></div><div class="seg-btn" id="dv-list" onclick="applyDefaultView('list',this)"><i class="fas fa-list"></i></div><div class="seg-btn" id="dv-compact" onclick="applyDefaultView('compact',this)"><i class="fas fa-border-all"></i></div></div></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-expand-alt"></i>Card Size</div><div class="srow-desc">Controls width and height together</div></div><div class="seg-ctrl"><div class="seg-btn" id="cs-xs" onclick="applyCardSize('xs',this)">XS</div><div class="seg-btn" id="cs-sm" onclick="applyCardSize('sm',this)">S</div><div class="seg-btn on" id="cs-md" onclick="applyCardSize('md',this)">M</div><div class="seg-btn" id="cs-lg" onclick="applyCardSize('lg',this)">L</div><div class="seg-btn" id="cs-xl" onclick="applyCardSize('xl',this)">XL</div></div></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-compress-alt"></i>Card Spacing</div><div class="srow-desc">Gap between cards in the grid</div></div><div class="seg-ctrl"><div class="seg-btn" id="den-comfortable" onclick="applyDensity('comfortable',this)">Airy</div><div class="seg-btn on" id="den-cozy" onclick="applyDensity('cozy',this)">Cozy</div><div class="seg-btn" id="den-tight" onclick="applyDensity('tight',this)">Tight</div></div></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-compress"></i>Compact Cards</div><div class="srow-desc">Smaller cards, less padding, hidden descriptions</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="compact-tog" onchange="applyCompact(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-sort-amount-down"></i>Default Sort</div><div class="srow-desc">How projects are ordered on load</div></div><select class="s-select" id="sort-pick-dash" onchange="applyDefaultSort(this.value)"><option value="az">Name A–Z</option><option value="za">Name Z–A</option><option value="new">Most Recent</option><option value="opens">Most Opened</option><option value="rating">Top Rated</option></select></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-hashtag"></i>Count Badges</div><div class="srow-desc">Numbers next to nav items in sidebar</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="cnt-badges" checked onchange="applyCntBadges(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-chart-bar"></i>Stats Panel</div><div class="srow-desc">4-cell summary at top of sidebar</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="stats-show" checked onchange="applyStatsShow(this.checked)"><span class="toggle-slider"></span></label></div>
      </div>
      <div class="spanel" id="tab-effects">
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-film"></i>Card Animations</div><div class="srow-desc">Fade-in slide when cards appear</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="anim-tog" checked onchange="applyAnim(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-cube"></i>3D Card Tilt</div><div class="srow-desc">Perspective tilt on card hover</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="tilt-tog" checked onchange="applyTilt(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-star"></i>Card Hover Glow</div><div class="srow-desc">Radial color glow on hover</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="glow-tog" checked onchange="applyGlow(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-network-wired"></i>Particle Background</div><div class="srow-desc">Animated dots in the background</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="particles-tog" checked onchange="applyParticles(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-adjust"></i>Grain Texture</div><div class="srow-desc">Subtle film grain overlay</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="grain-tog" checked onchange="applyGrain(this.checked)"><span class="toggle-slider"></span></label></div>
        <div class="srow"><div class="srow-l"><div class="srow-title"><i class="fas fa-glass-martini-alt"></i>Glass Sidebar</div><div class="srow-desc">Blur/frosted effect on sidebar</div></div><label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="sidebar-glass" onchange="applySidebarGlass(this.checked)"><span class="toggle-slider"></span></label></div>
      </div>
      <div class="dash-save-bar"><i class="fas fa-bolt"></i><span>All changes apply <strong>instantly</strong> and are saved in your browser.</span><button class="btn btn-ghost" style="font-size:.7em;padding:5px 10px;flex-shrink:0" onclick="resetDashSettings()"><i class="fas fa-undo"></i> Reset All</button></div>
    </div>
  </div>

  <!-- CONTEXT MENU -->
  <div id="ctx-menu">
    <div class="ctx-lbl" id="ctx-lbl">Project</div>
    <div class="ctx-item" id="ctx-open"><i class="fas fa-external-link-alt"></i>Open in Browser</div>
    <div class="ctx-item" id="ctx-exp-top"><i class="fas fa-folder-open"></i>Open in Explorer</div>
    <div class="ctx-item" id="ctx-detail"><i class="fas fa-info-circle"></i>View Details</div>
    <div class="ctx-div"></div>
    <div class="ctx-item" id="ctx-cust"><i class="fas fa-sliders-h"></i>Customize</div>
    <div class="ctx-item" id="ctx-ren"><i class="fas fa-pencil-alt"></i>Rename</div>
    <div class="ctx-item" id="ctx-nf"><i class="fas fa-file-code"></i>New File</div>
    <div class="ctx-div"></div>
    <div class="ctx-item" id="ctx-vsc"><i class="fas fa-code"></i>Open in VS Code</div>
    <div class="ctx-item" id="ctx-term"><i class="fas fa-terminal"></i>Open Terminal</div>
    <div class="ctx-item" id="ctx-exp"><i class="fas fa-folder-open"></i>File Manager</div>
    <div class="ctx-div"></div>
    <div class="ctx-item" id="ctx-cpath"><i class="fas fa-copy"></i>Copy Path</div>
    <div class="ctx-item" id="ctx-curl"><i class="fas fa-link"></i>Copy URL</div>
    <div class="ctx-div"></div>
    <div class="ctx-item ctx-danger" id="ctx-del"><i class="fas fa-trash"></i>Move to Trash</div>
  </div>

  <!-- TOAST -->
  <div id="toast"><i></i><span id="toast-msg"></span></div>
  <script>
    const PROJECTS = <?=json_encode(array_values($projects),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
    const BASE = <?=json_encode(rtrim(str_replace('\\','/',$rutaBase),'/'))?>;

    /* CANVAS */
    (()=>{const cv=document.getElementById('cv'),cx=cv.getContext('2d');let W,H,pts=[];const init=()=>{W=cv.width=innerWidth;H=cv.height=innerHeight;pts=[];for(let i=0;i<45;i++)pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.2,r:Math.random()+.3});};const frame=()=>{cx.clearRect(0,0,W,H);pts.forEach((p,i)=>{pts.slice(i+1).forEach(q=>{const d=Math.hypot(p.x-q.x,p.y-q.y);if(d<150){cx.strokeStyle=`rgba(124,140,255,${.1*(1-d/150)})`;cx.lineWidth=.5;cx.beginPath();cx.moveTo(p.x,p.y);cx.lineTo(q.x,q.y);cx.stroke();}});p.x+=p.vx;p.y+=p.vy;if(p.x<0)p.x=W;if(p.x>W)p.x=0;if(p.y<0)p.y=H;if(p.y>H)p.y=0;cx.fillStyle='rgba(124,140,255,.4)';cx.beginPath();cx.arc(p.x,p.y,p.r,0,Math.PI*2);cx.fill();});requestAnimationFrame(frame);};window.addEventListener('resize',init);init();frame();})();

    /* TOAST */
    let _tt;
    function toast(msg,type='ok'){const el=document.getElementById('toast'),ic=el.querySelector('i');document.getElementById('toast-msg').textContent=msg;el.className=type;ic.className=type==='ok'?'fas fa-check-circle':type==='err'?'fas fa-times-circle':'fas fa-info-circle';el.classList.add('show');clearTimeout(_tt);_tt=setTimeout(()=>el.classList.remove('show'),2800);}

    /* THEME */
    const _initT=localStorage.getItem('ch_theme')||'dark';
    document.documentElement.setAttribute('data-theme',_initT);
    document.querySelectorAll('.theme-dot').forEach(d=>{d.classList.toggle('on',d.getAttribute('data-t')===_initT);d.addEventListener('click',()=>applyTheme(d.getAttribute('data-t')));});
    const _lbl=document.getElementById('theme-name-lbl');if(_lbl)_lbl.textContent=_initT;

    /* MODALS */
    function openModal(id){document.getElementById(id).classList.add('open');}
    function closeModal(id){document.getElementById(id).classList.remove('open');}
    document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open');}));

    /* FORMS */
    document.getElementById('f-create').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData();fd.append('n',document.getElementById('f-name').value);fd.append('tpl',document.getElementById('f-tpl').value);const r=await fetch('?a=mk',{method:'POST',body:fd});const d=await r.json();toast(d.m,d.ok?'ok':'err');if(d.ok){closeModal('m-create');setTimeout(()=>location.reload(),700);}});
    document.getElementById('f-rename').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData();fd.append('old',document.getElementById('ren-old').value);fd.append('new',document.getElementById('ren-new').value);const r=await fetch('?a=ren',{method:'POST',body:fd});const d=await r.json();toast(d.ok?'Renamed!':d.m||'Error',d.ok?'ok':'err');if(d.ok)setTimeout(()=>location.reload(),700);});
    document.getElementById('f-file').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData();fd.append('f',document.getElementById('ff-name').value);fd.append('d',document.getElementById('ff-dir').value);const r=await fetch('?a=mf',{method:'POST',body:fd});const d=await r.json();toast(d.ok?'File created!':'Error',d.ok?'ok':'err');if(d.ok)closeModal('m-file');});

    /* CUSTOMIZE */
    const COLORS=['#7c8cff','#c084fc','#f472b6','#f97316','#fbbf24','#34d399','#38bdf8','#f87171','#5eead4','#4ade80','#e879f9','#ff6b6b','#fde047','#06d6a0','#0284c7','#ef233c','#3a0ca3','#ff9a3c','#93c5fd','#fdba74','#8338ec','#00d2ff'];
    const ICONS=['fa-code','fa-laptop-code','fa-terminal','fa-database','fa-server','fa-mobile-alt','fa-globe','fa-rocket','fa-cog','fa-palette','fa-chart-line','fa-layer-group','fa-puzzle-piece','fa-bolt','fa-bug','fa-star','fa-leaf','fa-fire','fa-gem','fa-flask','fa-gamepad','fa-music'];
    let sCol='#7c8cff',sIco='fa-code',sRate=0,sTags=[],sImg=null;
    function buildCustGrids(){const cg=document.getElementById('col-grid');cg.innerHTML='';COLORS.forEach(c=>{const d=document.createElement('div');d.className='col-sw'+(c===sCol?' sel':'');d.style.background=c;d.addEventListener('click',()=>{cg.querySelectorAll('.col-sw').forEach(x=>x.classList.remove('sel'));d.classList.add('sel');sCol=c;});cg.appendChild(d);});const ig=document.getElementById('ico-grid');ig.innerHTML='';ICONS.forEach(ic=>{const d=document.createElement('div');d.className='ico-sw'+(ic===sIco?' sel':'');d.innerHTML=`<i class="fas ${ic}"></i>`;d.addEventListener('click',()=>{ig.querySelectorAll('.ico-sw').forEach(x=>x.classList.remove('sel'));d.classList.add('sel');sIco=ic;});ig.appendChild(d);});}
    document.querySelectorAll('.r-star').forEach(s=>{s.addEventListener('click',()=>{sRate=+s.dataset.v;updStars();});s.addEventListener('mouseover',()=>{const v=+s.dataset.v;document.querySelectorAll('.r-star').forEach((x,i)=>x.classList.toggle('on',i<v));});s.addEventListener('mouseout',updStars);});
    function updStars(){document.querySelectorAll('.r-star').forEach((s,i)=>s.classList.toggle('on',i<sRate));}
    function renderTagInputTags(){document.querySelectorAll('.tw-tag').forEach(t=>t.remove());const w=document.getElementById('tag-wrap'),f=document.getElementById('tag-fld');sTags.forEach(t=>{const d=document.createElement('div');d.className='tw-tag';d.innerHTML=`#${t}<span class="tag-del" onclick="remTag('${t}')"><i class="fas fa-times"></i></span>`;w.insertBefore(d,f);});}
    function remTag(t){sTags=sTags.filter(x=>x!==t);renderTagInputTags();}
    document.getElementById('tag-fld').addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===','){e.preventDefault();const v=e.target.value.trim().toLowerCase().replace(/[^a-z0-9\-_]/g,'');if(v&&!sTags.includes(v)){sTags.push(v);renderTagInputTags();}e.target.value='';}if(e.key==='Backspace'&&!e.target.value&&sTags.length){sTags.pop();renderTagInputTags();}});
    function rebindUpload(){const uz=document.getElementById('up-zone');uz.addEventListener('click',()=>uz.querySelector('#img-fld')?.click());uz.querySelector('#img-fld')?.addEventListener('change',function(){const f=this.files[0];if(!f)return;sImg=f;const r=new FileReader();r.onload=e=>{uz.innerHTML=`<img src="${e.target.result}">`;uz.classList.add('has');};r.readAsDataURL(f);});}
    function openCust(dir,ev){if(ev)ev.stopPropagation();const p=PROJECTS.find(x=>x.name===dir)||{};document.getElementById('c-dir').value=dir;document.getElementById('c-desc').value=p.desc||'';document.getElementById('c-url').value=p.url||'';document.getElementById('c-st').value=p.st||'active';sCol=p.col||'#7c8cff';sIco=p.ico||'fa-folder';sRate=p.rate||0;sTags=[...(p.tags||[])];sImg=null;renderTagInputTags();updStars();const uz=document.getElementById('up-zone');if(p.img){uz.innerHTML=`<img src="${p.img}"><input type="file" id="img-fld" accept="image/*" style="display:none">`;uz.className='up-zone has';}else{uz.innerHTML='<i class="fas fa-image"></i>Click to upload<input type="file" id="img-fld" accept="image/*" style="display:none">';uz.className='up-zone';}rebindUpload();buildCustGrids();openModal('m-cust');}
    document.getElementById('f-cust').addEventListener('submit',async e=>{e.preventDefault();const dir=document.getElementById('c-dir').value;let imgUrl=null;if(sImg){const fd=new FormData();fd.append('f',sImg);fd.append('d',dir);const r=await fetch('?a=img',{method:'POST',body:fd});const d=await r.json();if(d.ok)imgUrl=d.img;}const fd=new FormData();fd.append('d',dir);fd.append('ico',sIco);fd.append('col',sCol);fd.append('desc',document.getElementById('c-desc').value);fd.append('st',document.getElementById('c-st').value);fd.append('url',document.getElementById('c-url').value);fd.append('rate',sRate);fd.append('tags',JSON.stringify(sTags));if(imgUrl)fd.append('img',imgUrl);await fetch('?a=cfg',{method:'POST',body:fd});toast('Saved!','ok');closeModal('m-cust');setTimeout(()=>location.reload(),700);});

    /* PIN */
    async function togglePin(dir,ev){if(ev)ev.stopPropagation();const p=PROJECTS.find(x=>x.name===dir)||{};const np=!p.pin;const fd=new FormData();fd.append('d',dir);fd.append('ico',p.ico||'fa-folder');fd.append('col',p.col||'#7c8cff');fd.append('st',p.st||'active');fd.append('pin',np?'1':'0');fd.append('tags',JSON.stringify(p.tags||[]));fd.append('rate',p.rate||0);await fetch('?a=cfg',{method:'POST',body:fd});toast(np?'Pinned!':'Unpinned','info');setTimeout(()=>location.reload(),500);}

    /* EXEC */
    async function run(action,dir){await fetch(`?a=${action}&dir=${encodeURIComponent(dir)}`);toast(`${action} launched…`,'info');}

    /* FIX: Separate file explorer function — uses 'fexp' endpoint, not 'exp' */
    async function runFexp(dir){await fetch('?a=fexp&dir='+encodeURIComponent(dir));toast('Abriendo explorador de archivos…','info');}

    async function openProj(dir){const p=PROJECTS.find(x=>x.name===dir);const fd=new FormData();fd.append('d',dir);fetch('?a=track',{method:'POST',body:fd});if(p?.url){window.open(p.url,'_blank');return;}const r=await fetch('?a=open&dir='+encodeURIComponent(dir));const d=await r.json().catch(()=>null);if(d?.has_index){window.location.href=dir+'/';}else{await runFexp(dir);}}

    /* FIX: delProj — uses custom confirmation modal instead of native confirm() */
    let _pendingDelDir='';
    function delProj(dir){_pendingDelDir=dir;document.getElementById('del-proj-name').textContent=dir;openModal('m-confirm-del');}
    document.getElementById('del-confirm-btn').addEventListener('click',async()=>{const dir=_pendingDelDir;if(!dir)return;closeModal('m-confirm-del');const fd=new FormData();fd.append('d',dir);const r=await fetch('?a=del',{method:'POST',body:fd});const d=await r.json();toast(d.ok?'Proyecto movido a la papelera':'Error al eliminar',d.ok?'ok':'err');if(d.ok){_pendingDelDir='';setTimeout(()=>location.reload(),700);}});

    /* DETAIL PANEL */
    let dpCur='';
    function openDp(dir,tab='info'){dpCur=dir;const p=PROJECTS.find(x=>x.name===dir)||{};const rgb=hexRGB(p.col||'#7c8cff');document.getElementById('dp-title').textContent=dir;const noteTs=p.note_ts?new Date(p.note_ts*1000).toLocaleDateString('en',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}):null;const pend=(p.todos||[]).filter(t=>!t.done).length;document.getElementById('dp-body').innerHTML=`<div class="dp-hero"><div class="dp-ico" style="background:rgba(${rgb},.14)"><i class="fas ${p.ico||'fa-folder'}" style="color:${p.col||'#7c8cff'}"></i></div><div><div class="dp-pname">${p.name}</div><div class="dp-ptype">${p.tipo||'Project'}</div>${(p.tags||[]).length?`<div style="display:flex;gap:3px;flex-wrap:wrap;margin-top:6px">${p.tags.map(t=>`<span class="ctag">#${t}</span>`).join('')}</div>`:''}</div></div><div class="dp-stats"><div class="dp-stat"><div class="dp-sv">${p.files}</div><div class="dp-sl">Files</div></div><div class="dp-stat"><div class="dp-sv">${p.opens||0}</div><div class="dp-sl">Opens</div></div><div class="dp-stat"><div class="dp-sv">${p.ago}</div><div class="dp-sl">Changed</div></div><div class="dp-stat"><div class="dp-sv" style="${pend>0?'color:var(--warm2)':''}">${pend}</div><div class="dp-sl">Todos</div></div></div>${p.desc?`<div class="dp-block"><div class="dp-sec"><i class="fas fa-align-left"></i>About</div><p style="font-size:.79em;color:var(--txt2);line-height:1.7">${p.desc}</p></div>`:''}<div class="dp-block"><div class="dp-sec"><i class="fas fa-pen-nib"></i>Notes</div><div class="note-editor-wrap"><div class="note-tb"><div class="note-tb-l"><i class="fas fa-scroll"></i><span>${p.name}</span></div><div class="note-tb-r"><span class="note-ts" id="note-ts">${noteTs?'🕐 '+noteTs:'Unsaved'}</span></div></div><textarea class="note-ta" id="note-ta" placeholder="Write anything…" rows="7">${(p.note||'').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</textarea><div class="note-foot"><span class="note-chars" id="note-chars"><i class="fas fa-font"></i> ${(p.note||'').length}</span><div class="note-actions"><span class="note-saved" id="note-saved"><i class="fas fa-check-circle"></i> Saved</span><button class="note-save-btn" onclick="saveNote('${dir}')"><i class="fas fa-save" style="font-size:.85em;margin-right:3px"></i>Save</button></div></div></div></div><div class="dp-block"><div class="dp-sec"><i class="fas fa-tasks"></i>To-Do List <span style="font-size:.8em;opacity:.5">${pend} pending</span></div><div class="todo-list" id="todo-list"></div><div class="todo-add"><input class="todo-inp" id="todo-inp" placeholder="Add a task…" onkeydown="if(event.key==='Enter')addTodo('${dir}')"><button class="todo-add-btn" onclick="addTodo('${dir}')">Add</button></div></div>${p.url?`<div class="dp-block"><div class="dp-sec"><i class="fas fa-link"></i>URL</div><a href="${p.url}" target="_blank" style="font-family:var(--mono);font-size:.74em;color:var(--acc2);word-break:break-all;display:block;padding:8px 12px;background:var(--glass);border:1px solid var(--bdr);border-radius:9px">${p.url}</a></div>`:''}`;renderTodos(p.todos||[],dir);const ta=document.getElementById('note-ta'),cc=document.getElementById('note-chars'),si=document.getElementById('note-saved');if(ta){ta.addEventListener('input',()=>{cc.innerHTML='<i class="fas fa-font"></i> '+ta.value.length;if(si)si.classList.remove('show');ta.style.height='auto';ta.style.height=Math.min(ta.scrollHeight,320)+'px';});setTimeout(()=>{ta.style.height='auto';ta.style.height=Math.min(ta.scrollHeight,320)+'px';},50);}document.getElementById('dp-foot').innerHTML=`<button class="btn btn-primary" onclick="openProj('${dir}')"><i class="fas fa-arrow-right"></i>Open</button><button class="btn btn-ghost" onclick="run('vsc','${dir}')"><i class="fas fa-code"></i>Code</button><button class="btn btn-ghost" onclick="run('term','${dir}')"><i class="fas fa-terminal"></i>Term</button><button class="btn btn-ghost" onclick="openCust('${dir}')"><i class="fas fa-sliders-h"></i>Edit</button>`;document.getElementById('dp').classList.add('open');if(tab==='notes')setTimeout(()=>ta?.focus(),420);}
    function closeDp(){document.getElementById('dp').classList.remove('open');}
    async function saveNote(dir){const ta=document.getElementById('note-ta');if(!ta)return;const fd=new FormData();fd.append('d',dir);fd.append('n',ta.value);const btn=document.querySelector('.note-save-btn');if(btn){btn.textContent='Saving…';btn.style.opacity='.7';}await fetch('?a=note',{method:'POST',body:fd});const p=PROJECTS.find(x=>x.name===dir);if(p)p.note=ta.value;const si=document.getElementById('note-saved'),ts=document.getElementById('note-ts'),now=new Date().toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit'});if(si){si.classList.add('show');setTimeout(()=>si.classList.remove('show'),2500);}if(ts)ts.innerHTML='🕐 '+now;if(btn){btn.innerHTML='<i class="fas fa-save" style="font-size:.85em;margin-right:3px"></i>Save';btn.style.opacity='';}toast('Note saved!','ok');}
    document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='s'&&dpCur){const ta=document.getElementById('note-ta');if(ta&&document.activeElement===ta){e.preventDefault();saveNote(dpCur);}}});
    function renderTodos(todos,dir){const el=document.getElementById('todo-list');if(!el)return;el.innerHTML=todos.length?todos.map((t,i)=>`<div class="todo-item${t.done?' done':''}" onclick="toggleTodo('${dir}',${i})"><div class="todo-cb">${t.done?'<i class="fas fa-check" style="font-size:.55em"></i>':''}</div><div class="todo-t">${t.text}</div><div class="todo-del" onclick="event.stopPropagation();delTodo('${dir}',${i})"><i class="fas fa-times"></i></div></div>`).join(''):'<p style="font-family:var(--mono);font-size:.7em;color:var(--txt3);padding:8px 0">No tasks yet</p>';}
    async function saveTodos(dir){const p=PROJECTS.find(x=>x.name===dir);if(!p)return;const fd=new FormData();fd.append('d',dir);fd.append('t',JSON.stringify(p.todos));await fetch('?a=todos',{method:'POST',body:fd});}
    function addTodo(dir){const inp=document.getElementById('todo-inp'),txt=inp.value.trim();if(!txt)return;const p=PROJECTS.find(x=>x.name===dir);if(!p)return;if(!p.todos)p.todos=[];p.todos.push({text:txt,done:false});renderTodos(p.todos,dir);inp.value='';saveTodos(dir);}
    function toggleTodo(dir,i){const p=PROJECTS.find(x=>x.name===dir);if(!p)return;p.todos[i].done=!p.todos[i].done;renderTodos(p.todos,dir);saveTodos(dir);}
    function delTodo(dir,i){const p=PROJECTS.find(x=>x.name===dir);if(!p)return;p.todos.splice(i,1);renderTodos(p.todos,dir);saveTodos(dir);}

    /* SEARCH & FILTER */
    let filt='all',fType=null,fTag=null;
    const srch=document.getElementById('srch');
    srch.addEventListener('input',filterCards);
    document.querySelectorAll('.nav-item[data-f]').forEach(el=>{el.addEventListener('click',()=>{document.querySelectorAll('.nav-item[data-f]').forEach(x=>x.classList.remove('on'));el.classList.add('on');filt=el.getAttribute('data-f');filterCards();});});
    document.querySelectorAll('.stag[data-type]').forEach(el=>{el.addEventListener('click',()=>{const t=el.getAttribute('data-type');if(fType===t){fType=null;el.classList.remove('on');}else{document.querySelectorAll('.stag[data-type]').forEach(x=>x.classList.remove('on'));fType=t;el.classList.add('on');}filterCards();});});
    document.querySelectorAll('.stag[data-tag]').forEach(el=>{el.addEventListener('click',()=>{const t=el.getAttribute('data-tag');if(fTag===t){fTag=null;el.classList.remove('on');}else{document.querySelectorAll('.stag[data-tag]').forEach(x=>x.classList.remove('on'));fTag=t;el.classList.add('on');}filterCards();});});
    function filterCards(){const q=srch.value.toLowerCase();const cards=document.querySelectorAll('#main-grid .card');let n=0;cards.forEach(c=>{const name=c.dataset.name.toLowerCase(),type=c.dataset.type.toLowerCase(),tags=JSON.parse(c.dataset.tags||'[]'),desc=(c.dataset.desc||'').toLowerCase(),st=c.dataset.st,isRecent=c.dataset.recent==='1',hasNote=c.dataset.note==='1',isPinned=c.classList.contains('pinned');let show=!q||name.includes(q)||desc.includes(q)||tags.some(t=>t.includes(q));if(fType&&type!==fType)show=false;if(fTag&&!tags.includes(fTag))show=false;if(filt==='pin'&&!isPinned)show=false;if(filt==='recent'&&!isRecent)show=false;if(filt==='notes'&&!hasNote)show=false;if(filt.startsWith('st-')&&st!==filt.slice(3))show=false;c.style.display=show?'':'none';if(show)n++;});document.getElementById('page-sub').textContent=`${n} of <?=$total?> projects`;}

    /* SORT */
    document.getElementById('sort-btn').addEventListener('click',e=>{e.stopPropagation();document.getElementById('sort-dd').classList.toggle('open');});
    document.addEventListener('click',()=>document.getElementById('sort-dd').classList.remove('open'));
    document.querySelectorAll('.sort-item').forEach(el=>{el.addEventListener('click',e=>{e.stopPropagation();document.querySelectorAll('.sort-item').forEach(x=>x.classList.remove('on'));el.classList.add('on');sortCards(el.getAttribute('data-s'));document.getElementById('sort-dd').classList.remove('open');});});
    function sortCards(by){const g=document.getElementById('main-grid'),cs=[...g.querySelectorAll('.card')];cs.sort((a,b)=>{if(by==='az')return a.dataset.name.localeCompare(b.dataset.name);if(by==='za')return b.dataset.name.localeCompare(a.dataset.name);if(by==='new')return(+b.dataset.mt)-(+a.dataset.mt);if(by==='old')return(+a.dataset.mt)-(+b.dataset.mt);if(by==='files')return(+b.dataset.files)-(+a.dataset.files);if(by==='opens')return(+b.dataset.opens||0)-(+a.dataset.opens||0);if(by==='rating')return(+b.dataset.rate||0)-(+a.dataset.rate||0);return 0;});cs.forEach(c=>g.appendChild(c));localStorage.setItem('ch_order',JSON.stringify(cs.map(c=>c.dataset.name)));}
    (()=>{const saved=JSON.parse(localStorage.getItem('ch_order')||'[]');if(!saved.length)return;const g=document.getElementById('main-grid'),cs=[...g.querySelectorAll('.card')];saved.forEach(n=>{const c=cs.find(x=>x.dataset.name===n);if(c)g.appendChild(c);});})();

    /* VIEW TOGGLE */
    const MG=document.getElementById('main-grid');
    document.getElementById('v-grid').addEventListener('click',()=>{MG.className='grid';document.getElementById('v-grid').classList.add('on');document.getElementById('v-list').classList.remove('on');document.getElementById('v-compact').classList.remove('on');localStorage.setItem('ch_view','g');});
    document.getElementById('v-list').addEventListener('click',()=>{MG.className='grid list';document.getElementById('v-list').classList.add('on');document.getElementById('v-grid').classList.remove('on');document.getElementById('v-compact').classList.remove('on');localStorage.setItem('ch_view','l');});
    document.getElementById('v-compact').addEventListener('click',()=>{MG.className='grid compact';document.getElementById('v-compact').classList.add('on');document.getElementById('v-grid').classList.remove('on');document.getElementById('v-list').classList.remove('on');localStorage.setItem('ch_view','c');});
    const sv=localStorage.getItem('ch_view')||'g';if(sv==='l')document.getElementById('v-list').click();else if(sv==='c')document.getElementById('v-compact').click();

    /* DRAG & DROP */
    let dragSrc=null;
    document.querySelectorAll('#main-grid .card').forEach(c=>{c.setAttribute('draggable','true');c.addEventListener('dragstart',e=>{dragSrc=c;c.classList.add('dragging');e.dataTransfer.effectAllowed='move';});c.addEventListener('dragend',()=>{c.classList.remove('dragging');document.querySelectorAll('.card').forEach(x=>x.classList.remove('dragover'));});c.addEventListener('dragover',e=>{e.preventDefault();if(c!==dragSrc)c.classList.add('dragover');});c.addEventListener('dragleave',()=>c.classList.remove('dragover'));c.addEventListener('drop',e=>{e.stopPropagation();c.classList.remove('dragover');if(dragSrc&&dragSrc!==c){const g=document.getElementById('main-grid'),all=[...g.querySelectorAll('.card')];if(all.indexOf(dragSrc)<all.indexOf(c))g.insertBefore(dragSrc,c.nextSibling);else g.insertBefore(dragSrc,c);localStorage.setItem('ch_order',JSON.stringify([...g.querySelectorAll('.card')].map(x=>x.dataset.name)));toast('Order saved','info');}});});

    /* BULK */
    let bulkMode=false,sel=new Set();
    function toggleBulk(){bulkMode=!bulkMode;document.querySelectorAll('.card-cb').forEach(c=>c.classList.toggle('show',bulkMode));document.getElementById('bulk-bar').classList.toggle('show',bulkMode);if(!bulkMode)clearBulk();}
    function clearBulk(){sel.clear();document.querySelectorAll('.card-cb').forEach(c=>c.classList.remove('on'));document.getElementById('bulk-cnt').textContent='0';}
    document.querySelectorAll('.card').forEach(c=>{const cb=c.querySelector('.card-cb');if(cb)cb.addEventListener('click',e=>{e.stopPropagation();const n=c.dataset.name;if(sel.has(n)){sel.delete(n);cb.classList.remove('on');}else{sel.add(n);cb.classList.add('on');}document.getElementById('bulk-cnt').textContent=sel.size;});});
    async function doBulk(action){if(!sel.size){toast('Nothing selected','info');return;}if(action==='delete'&&!confirm(`¿Eliminar ${sel.size} proyecto(s)?`))return;for(const dir of sel){const p=PROJECTS.find(x=>x.name===dir)||{};const fd=new FormData();if(action==='delete'){fd.append('d',dir);await fetch('?a=del',{method:'POST',body:fd});}else if(action==='pin'){fd.append('d',dir);fd.append('ico',p.ico||'fa-folder');fd.append('col',p.col||'#7c8cff');fd.append('st',p.st||'active');fd.append('pin','1');fd.append('tags',JSON.stringify(p.tags||[]));fd.append('rate',p.rate||0);await fetch('?a=cfg',{method:'POST',body:fd});}else{fd.append('d',dir);fd.append('ico',p.ico||'fa-folder');fd.append('col',p.col||'#7c8cff');fd.append('st',action==='archive'?'archived':'done');fd.append('pin',p.pin?'1':'0');fd.append('tags',JSON.stringify(p.tags||[]));fd.append('rate',p.rate||0);await fetch('?a=cfg',{method:'POST',body:fd});}}toast(`Done (${sel.size})!`,'ok');setTimeout(()=>location.reload(),700);}

    /* CONTEXT MENU */
    let ctxDir='';
    const ctxEl=document.getElementById('ctx-menu');
    document.querySelectorAll('.card').forEach(c=>{c.addEventListener('contextmenu',e=>{e.preventDefault();ctxDir=c.dataset.name;document.getElementById('ctx-lbl').textContent=ctxDir;ctxEl.style.top=Math.min(e.clientY,innerHeight-280)+'px';ctxEl.style.left=Math.min(e.clientX,innerWidth-210)+'px';ctxEl.style.display='block';});});
    document.addEventListener('click',()=>ctxEl.style.display='none');
    document.getElementById('ctx-open').onclick=()=>openProj(ctxDir);
    document.getElementById('ctx-detail').onclick=()=>openDp(ctxDir);
    document.getElementById('ctx-cust').onclick=()=>openCust(ctxDir);
    document.getElementById('ctx-ren').onclick=()=>{document.getElementById('ren-old').value=ctxDir;document.getElementById('ren-new').value=ctxDir;openModal('m-rename');};
    document.getElementById('ctx-nf').onclick=()=>{document.getElementById('ff-dir').value=ctxDir;openModal('m-file');};
    document.getElementById('ctx-vsc').onclick=()=>run('vsc',ctxDir);
    document.getElementById('ctx-term').onclick=()=>run('term',ctxDir);
    /* FIX: ctx-exp and ctx-exp-top now use runFexp() — separated from CSV/JSON export */
    document.getElementById('ctx-exp').onclick=()=>runFexp(ctxDir);
    document.getElementById('ctx-exp-top').onclick=()=>runFexp(ctxDir);
    document.getElementById('ctx-cpath').onclick=()=>navigator.clipboard.writeText(BASE+'/'+ctxDir).then(()=>toast('Path copied!'));
    document.getElementById('ctx-curl').onclick=()=>{const u=location.origin+location.pathname.replace('index.php','')+ctxDir+'/';navigator.clipboard.writeText(u).then(()=>toast('URL copied!'));};
    document.getElementById('ctx-del').onclick=()=>delProj(ctxDir);

    /* SPOTLIGHT */
    let spIdx=-1;
    function openSpotlight(){document.getElementById('spotlight').classList.add('open');document.getElementById('sp-inp').value='';document.getElementById('sp-inp').focus();renderSp('');}
    function closeSpotlight(){document.getElementById('spotlight').classList.remove('open');}
    document.getElementById('spotlight').addEventListener('click',e=>{if(e.target===document.getElementById('spotlight'))closeSpotlight();});
    document.getElementById('sp-inp').addEventListener('input',e=>renderSp(e.target.value.toLowerCase()));
    document.getElementById('sp-inp').addEventListener('keydown',e=>{const items=document.querySelectorAll('.sp-item');if(e.key==='ArrowDown'){spIdx=Math.min(spIdx+1,items.length-1);hlSp(items);}else if(e.key==='ArrowUp'){spIdx=Math.max(spIdx-1,0);hlSp(items);}else if(e.key==='Enter'&&spIdx>=0){const it=items[spIdx];if(it){openProj(it.dataset.dir);closeSpotlight();}}else if(e.key==='Escape')closeSpotlight();});
    function hlSp(items){items.forEach((el,i)=>el.classList.toggle('hi',i===spIdx));items[spIdx]?.scrollIntoView({block:'nearest'});}
    function renderSp(q){spIdx=-1;const cmds=[{l:'New Project',i:'fa-folder-plus',fn:"openModal('m-create')"},{l:'Random Project',i:'fa-random',fn:"showRandom()"},{l:'Bulk Select',i:'fa-check-square',fn:"toggleBulk()"},{l:'Export JSON',i:'fa-download',fn:"doExport('json')"}];const prs=PROJECTS.filter(p=>p.name.toLowerCase().includes(q)||(p.desc||'').toLowerCase().includes(q)||(p.tags||[]).some(t=>t.includes(q))).slice(0,7);const fc=q?cmds.filter(c=>c.l.toLowerCase().includes(q)):[];let html='';if(fc.length){html+='<div class="sp-divider">Commands</div>';html+=fc.map(c=>`<div class="sp-item" onclick="${c.fn};closeSpotlight()"><div class="sp-thumb" style="background:var(--acc3)"><i class="fas ${c.i}" style="color:var(--acc2)"></i></div><div><div class="sp-name">${c.l}</div><div class="sp-meta">Command</div></div></div>`).join('');}if(prs.length){html+='<div class="sp-divider">Projects</div>';html+=prs.map(p=>`<div class="sp-item" data-dir="${p.name}" onclick="openProj('${p.name}');closeSpotlight()"><div class="sp-thumb" style="background:rgba(${hexRGB(p.col||'#7c8cff')},.14)"><i class="fas ${p.ico||'fa-folder'}" style="color:${p.col||'#7c8cff'}"></i></div><div class="sp-info"><div class="sp-name">${p.name}</div><div class="sp-meta">${p.tipo||'Project'} · ${p.files} files · ${p.ago}</div></div><div class="sp-acts"><div class="sp-act" onclick="event.stopPropagation();run('vsc','${p.name}')">Code</div><div class="sp-act" onclick="event.stopPropagation();run('term','${p.name}')">Term</div><div class="sp-act" onclick="event.stopPropagation();openDp('${p.name}');closeSpotlight()">Info</div></div></div>`).join('');}if(!html)html='<div style="padding:24px;text-align:center;color:var(--txt3);font-family:var(--mono);font-size:.76em">No results</div>';document.getElementById('sp-list').innerHTML=html;}

    /* RANDOM */
    function showRandom(){const active=PROJECTS.filter(p=>p.st!=='archived');const p=active[Math.floor(Math.random()*active.length)];if(!p)return;const rgb=hexRGB(p.col||'#7c8cff');document.getElementById('rnd-ico').style.background=`rgba(${rgb},.14)`;document.getElementById('rnd-ico').innerHTML=`<i class="fas ${p.ico||'fa-folder'}" style="color:${p.col||'#7c8cff'}"></i>`;document.getElementById('rnd-name').textContent=p.name;document.getElementById('rnd-type').textContent=`${p.tipo||'Project'} · ${p.files} files · ${p.ago}`;document.getElementById('rnd-open').onclick=()=>{openProj(p.name);closeRnd();};document.getElementById('rnd-overlay').classList.add('open');}
    function closeRnd(){document.getElementById('rnd-overlay').classList.remove('open');}
    document.getElementById('rnd-overlay').addEventListener('click',e=>{if(e.target===document.getElementById('rnd-overlay'))closeRnd();});

    /* EXPORT */
    function doExport(fmt){const a=document.createElement('a');a.href=`?a=exp&fmt=${fmt}`;a.download=`codehub.${fmt}`;a.click();toast(`Exporting…`,'info');}

    /* KEYBOARD */
    document.addEventListener('keydown',e=>{const tag=document.activeElement.tagName;if(tag==='INPUT'||tag==='TEXTAREA')return;if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();openSpotlight();return;}if(e.key==='/'){e.preventDefault();srch.focus();return;}if(e.key==='Escape'){closeSpotlight();closeDp();closeRnd();document.querySelectorAll('.overlay').forEach(o=>o.classList.remove('open'));return;}if((e.ctrlKey||e.metaKey)&&e.key==='n'){e.preventDefault();openModal('m-create');return;}if(e.key==='r')showRandom();if(e.key==='b')toggleBulk();});

    /* UTILS */
    function hexRGB(hex){try{const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);return `${r},${g},${b}`;}catch{return '124,140,255';}}

    /* DASHBOARD SETTINGS */
    const DS={get:(k,d)=>{try{const v=localStorage.getItem('ch_ds_'+k);return v===null?d:JSON.parse(v);}catch{return d;}},set:(k,v)=>localStorage.setItem('ch_ds_'+k,JSON.stringify(v))};
    function switchTab(el,tab){document.querySelectorAll('.stab').forEach(t=>t.classList.remove('on'));document.querySelectorAll('.spanel').forEach(p=>p.classList.remove('on'));el.classList.add('on');document.getElementById('tab-'+tab)?.classList.add('on');}
    function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('ch_theme',t);document.querySelectorAll('.theme-dot').forEach(d=>d.classList.toggle('on',d.dataset.t===t));document.querySelectorAll('.tpv').forEach(d=>d.classList.toggle('sel',d.dataset.t===t));const lbl=document.getElementById('theme-name-lbl');if(lbl)lbl.textContent=t;toast('Theme: '+t,'info');}
    function pickAccent(col){document.documentElement.style.setProperty('--acc',col);document.documentElement.style.setProperty('--acc2',col+'cc');document.documentElement.style.setProperty('--acc3',col+'22');document.documentElement.style.setProperty('--acc4',col+'0f');document.querySelectorAll('.acc-sw').forEach(s=>s.classList.toggle('sel',s.dataset.col===col));const ci=document.getElementById('accent-custom');if(ci&&col.length===7)ci.value=col;DS.set('accent',col);}
    function resetAccent(){['--acc','--acc2','--acc3','--acc4'].forEach(v=>document.documentElement.style.removeProperty(v));document.querySelectorAll('.acc-sw').forEach(s=>s.classList.remove('sel'));DS.set('accent',null);toast('Accent reset','info');}
    document.getElementById('accent-custom')?.addEventListener('input',e=>pickAccent(e.target.value));
    function applyFont(name){const URLS={'Inter':'Inter:wght@300;400;500;600;700;800','Outfit':'Outfit:wght@300;400;500;600;700;800','Nunito':'Nunito:wght@400;500;600;700;800','Syne':'Syne:wght@400;500;600;700;800','Lexend':'Lexend:wght@300;400;500;600;700','Plus Jakarta Sans':'Plus+Jakarta+Sans:wght@300;400;500;600;700;800','Space Grotesk':'Space+Grotesk:wght@300;400;500;600;700'};if(URLS[name]){const id='gf-'+name.replace(/\s/g,'-');if(!document.getElementById(id)){const l=document.createElement('link');l.id=id;l.rel='stylesheet';l.href='https://fonts.googleapis.com/css2?family='+URLS[name]+'&display=swap';document.head.appendChild(l);}}document.documentElement.style.setProperty('--ui',"'"+name+"', sans-serif");const fc=document.getElementById('font-card');if(fc)fc.style.fontFamily="'"+name+"',sans-serif";DS.set('font',name);toast('Font: '+name,'info');}
    function applyFsize(val,el){const sizes={sm:'13px',md:'15px',lg:'17px'};document.body.style.fontSize=sizes[val]||'15px';if(el){document.querySelectorAll('#fs-sm,#fs-md,#fs-lg').forEach(b=>b.classList.remove('on'));el.classList.add('on');}DS.set('fsize',val);}
    function applyRound(val,el){const vals={sm:'7px',md:'13px',lg:'20px',xl:'28px'};document.documentElement.style.setProperty('--r',vals[val]||'13px');if(el){document.querySelectorAll('#r-sm,#r-md,#r-lg,#r-xl').forEach(b=>b.classList.remove('on'));el.classList.add('on');}DS.set('rounded',val);}
    function applyDensity(val,el){document.documentElement.setAttribute('data-density',val);if(el){document.querySelectorAll('#den-comfortable,#den-cozy,#den-tight').forEach(b=>b.classList.remove('on'));el.classList.add('on');}DS.set('density',val);}
    function applyCardSize(val,el){document.documentElement.setAttribute('data-csize',val);if(el){document.querySelectorAll('#cs-xs,#cs-sm,#cs-md,#cs-lg,#cs-xl').forEach(b=>b.classList.remove('on'));el.classList.add('on');}DS.set('csize',val);}
    function applyCompact(on){document.documentElement.setAttribute('data-compact',on?'1':'0');DS.set('compact',on);}
    function applyDefaultView(val,el){DS.set('defview',val);if(el){document.querySelectorAll('#dv-grid,#dv-list,#dv-compact').forEach(b=>b.classList.remove('on'));el.classList.add('on');}if(val==='list')document.getElementById('v-list')?.click();else if(val==='compact')document.getElementById('v-compact')?.click();else document.getElementById('v-grid')?.click();}
    function applyDefaultSort(val){DS.set('defsort',val);sortCards(val);}
    function applySidebarW(val,el){document.documentElement.setAttribute('data-sidebar',val);if(el){document.querySelectorAll('#sw-narrow,#sw-normal,#sw-wide').forEach(b=>b.classList.remove('on'));el.classList.add('on');}DS.set('sidebarW',val);}
    function applyCntBadges(on){document.querySelectorAll('.cnt').forEach(c=>c.style.display=on?'':'none');DS.set('cntBadges',on);}
    function applyStatsShow(on){const sg=document.querySelector('.stats-grid');if(sg)sg.parentElement.style.display=on?'':'none';DS.set('statsShow',on);}
    function applyAnim(on){document.documentElement.setAttribute('data-animations',on?'on':'off');DS.set('anim',on);}
    let tiltEnabled=DS.get('tilt',true);
    function applyTilt(on){tiltEnabled=on;if(!on)document.querySelectorAll('.card').forEach(c=>{c.style.setProperty('--rx','0deg');c.style.setProperty('--ry','0deg');});DS.set('tilt',on);}
    function applyParticles(on){const cv=document.getElementById('cv');if(cv)cv.style.display=on?'':'none';DS.set('particles',on);}
    function applyGrain(on){let s=document.getElementById('grain-override');if(!s){s=document.createElement('style');s.id='grain-override';document.head.appendChild(s);}s.textContent=on?'':'body::after{opacity:0!important}';DS.set('grain',on);}
    function applyGlow(on){let s=document.getElementById('glow-override');if(!s){s=document.createElement('style');s.id='glow-override';document.head.appendChild(s);}s.textContent=on?'':'.card::after{display:none!important}';DS.set('glow',on);}
    function applySidebarGlass(on){document.documentElement.setAttribute('data-sidebar-glass',on?'on':'off');DS.set('sidebarGlass',on);}
    function resetDashSettings(){if(!confirm('Reset all settings to defaults?'))return;['accent','font','fsize','rounded','density','csize','compact','defview','defsort','sidebarW','sidebarGlass','cntBadges','statsShow','anim','tilt','particles','grain','glow'].forEach(k=>localStorage.removeItem('ch_ds_'+k));toast('Reset!','info');setTimeout(()=>location.reload(),700);}

    (()=>{const acc=DS.get('accent',null);if(acc){document.documentElement.style.setProperty('--acc',acc);document.documentElement.style.setProperty('--acc2',acc+'cc');document.documentElement.style.setProperty('--acc3',acc+'22');document.documentElement.style.setProperty('--acc4',acc+'0f');}const font=DS.get('font','DM Sans');if(font!=='DM Sans')applyFont(font);const sizes={sm:'13px',md:'15px',lg:'17px'};document.body.style.fontSize=sizes[DS.get('fsize','md')]||'15px';const rvals={sm:'7px',md:'13px',lg:'20px',xl:'28px'};document.documentElement.style.setProperty('--r',rvals[DS.get('rounded','md')]||'13px');document.documentElement.setAttribute('data-density',DS.get('density','cozy'));document.documentElement.setAttribute('data-csize',DS.get('csize','md'));document.documentElement.setAttribute('data-compact',DS.get('compact',false)?'1':'0');document.documentElement.setAttribute('data-sidebar',DS.get('sidebarW','normal'));if(DS.get('sidebarGlass',false))document.documentElement.setAttribute('data-sidebar-glass','on');if(!DS.get('anim',true))document.documentElement.setAttribute('data-animations','off');if(!DS.get('particles',true)){const cv=document.getElementById('cv');if(cv)cv.style.display='none';}if(!DS.get('grain',true)){const s=document.createElement('style');s.id='grain-override';s.textContent='body::after{opacity:0!important}';document.head.appendChild(s);}if(!DS.get('glow',true)){const s=document.createElement('style');s.id='glow-override';s.textContent='.card::after{display:none!important}';document.head.appendChild(s);}if(!DS.get('cntBadges',true))document.querySelectorAll('.cnt').forEach(c=>c.style.display='none');if(!DS.get('statsShow',true)){const sg=document.querySelector('.stats-grid');if(sg)sg.parentElement.style.display='none';}const dv=DS.get('defview',null);if(dv==='list')setTimeout(()=>document.getElementById('v-list')?.click(),60);else if(dv==='compact')setTimeout(()=>document.getElementById('v-compact')?.click(),60);const ds=DS.get('defsort',null);if(ds)setTimeout(()=>sortCards(ds),80);})();

    const _origOpenModal=window.openModal;
    window.openModal=function(id){_origOpenModal(id);if(id!=='m-dash')return;const ct=localStorage.getItem('ch_theme')||'dark';document.querySelectorAll('.tpv').forEach(d=>d.classList.toggle('sel',d.dataset.t===ct));const acc=DS.get('accent',null);if(acc)document.querySelectorAll('.acc-sw').forEach(s=>s.classList.toggle('sel',s.dataset.col===acc));const fp=document.getElementById('font-pick');if(fp)fp.value=DS.get('font','DM Sans');const fc=document.getElementById('font-card');if(fc)fc.style.fontFamily="'"+DS.get('font','DM Sans')+"',sans-serif";const fsz=DS.get('fsize','md');document.querySelectorAll('#fs-sm,#fs-md,#fs-lg').forEach(b=>b.classList.remove('on'));document.getElementById('fs-'+fsz)?.classList.add('on');const rnd=DS.get('rounded','md');document.querySelectorAll('#r-sm,#r-md,#r-lg,#r-xl').forEach(b=>b.classList.remove('on'));document.getElementById('r-'+rnd)?.classList.add('on');const sw=DS.get('sidebarW','normal');document.querySelectorAll('#sw-narrow,#sw-normal,#sw-wide').forEach(b=>b.classList.remove('on'));document.getElementById('sw-'+sw)?.classList.add('on');const dv=DS.get('defview','grid');document.querySelectorAll('#dv-grid,#dv-list,#dv-compact').forEach(b=>b.classList.remove('on'));document.getElementById('dv-'+dv)?.classList.add('on');const den=DS.get('density','cozy');document.querySelectorAll('#den-comfortable,#den-cozy,#den-tight').forEach(b=>b.classList.remove('on'));document.getElementById('den-'+den)?.classList.add('on');const csize=DS.get('csize','md');document.querySelectorAll('#cs-xs,#cs-sm,#cs-md,#cs-lg,#cs-xl').forEach(b=>b.classList.remove('on'));document.getElementById('cs-'+csize)?.classList.add('on');const tog=(id,k,d)=>{const el=document.getElementById(id);if(el)el.checked=DS.get(k,d);};tog('compact-tog','compact',false);tog('cnt-badges','cntBadges',true);tog('stats-show','statsShow',true);tog('anim-tog','anim',true);tog('tilt-tog','tilt',true);tog('glow-tog','glow',true);tog('particles-tog','particles',true);tog('grain-tog','grain',true);tog('sidebar-glass','sidebarGlass',false);const sd=document.getElementById('sort-pick-dash');if(sd)sd.value=DS.get('defsort','az');};

    document.querySelectorAll('.card').forEach(c=>{c.addEventListener('mousemove',e=>{if(!tiltEnabled)return;const r=c.getBoundingClientRect(),x=(e.clientX-r.left)/r.width,y=(e.clientY-r.top)/r.height;c.style.setProperty('--rx',`${(x-.5)*5}deg`);c.style.setProperty('--ry',`${-(y-.5)*5}deg`);c.style.setProperty('--mx',`${x*100}%`);c.style.setProperty('--my',`${y*100}%`);});c.addEventListener('mouseleave',()=>{c.style.setProperty('--rx','0deg');c.style.setProperty('--ry','0deg');});});
  </script>
</body>
</html>

<?php
function renderCard($p){
  $name=$p['name'];$col=$p['col'];$ico=$p['ico'];$img=$p['img'];$desc=$p['desc'];$tipo=$p['tipo'];$tags=$p['tags'];$rate=$p['rate'];$st=$p['st'];$pin=$p['pin'];$pend=$p['pend'];$opens=$p['opens'];$note=$p['note'];
  $isRecent=(time()-$p['mtime'])<604800;
  $rgb=sscanf($col,"#%02x%02x%02x");$rgb=$rgb?implode(',',$rgb):'124,140,255';
  $stClass=['active'=>'sp-active','wip'=>'sp-wip','paused'=>'sp-paused','done'=>'sp-done','archived'=>'sp-archived'][$st]??'sp-active';
  $stLabel=['active'=>'Active','wip'=>'In Progress','paused'=>'Paused','done'=>'Done','archived'=>'Archived'][$st]??'Active';
  $stars='';for($i=1;$i<=5;$i++){$on=$i<=$rate?' on':'';$stars.="<i class=\"fas fa-star star$on\"></i>";}
  ob_start();?>
  <div class="card <?=$pin?'pinned':''?>" draggable="true"
    data-name="<?=e($name)?>" data-type="<?=strtolower($tipo)?>" data-mt="<?=$p['mtime']?>"
    data-files="<?=$p['files']?>" data-opens="<?=$opens?>" data-rate="<?=$rate?>"
    data-st="<?=$st?>" data-recent="<?=$isRecent?'1':'0'?>" data-todos="<?=count($p['todos'])>0?'1':'0'?>"
    data-note="<?=!empty($note)?'1':'0'?>" data-desc="<?=e($desc??'')?>" data-tags="<?=e(json_encode($tags))?>"
    style="--c:<?=$col?>">
    <div class="drag-h"><i class="fas fa-grip-vertical"></i></div>
    <div class="card-cb"><i class="fas fa-check" style="font-size:.6em"></i></div>
    <?php if($pend>0):?><div class="todo-badge"><?=$pend?></div><?php endif;?>
    <div class="card-acts">
      <div class="ca-btn pin <?=$pin?'on':''?>" onclick="togglePin('<?=e($name)?>', event)" title="<?=$pin?'Unpin':'Pin'?>"><i class="fas fa-thumbtack"></i></div>
      <div class="ca-btn note <?=!empty($note)?'has':''?>" onclick="openDp('<?=e($name)?>','notes');event.stopPropagation()" title="Notes"><i class="fas fa-sticky-note"></i></div>
      <div class="ca-btn edit" onclick="openCust('<?=e($name)?>', event)" title="Customize"><i class="fas fa-sliders-h"></i></div>
      <div class="ca-btn go" onclick="openProj('<?=e($name)?>');event.stopPropagation()" title="Open"><i class="fas fa-arrow-right"></i></div>
    </div>
    <div class="card-body" onclick="openProj('<?=e($name)?>')">
      <div class="card-top">
        <div class="c-thumb" style="<?=$img?"background-image:url(".e($img).");background-size:cover;background-position:center":"background:rgba($rgb,.14)"?>">
          <?php if(!$img):?><i class="fas <?=$ico?>" style="color:<?=$col?>"></i><?php endif;?>
        </div>
        <div class="c-info">
          <a href="<?=e($name)?>" class="c-name"><?=e($name)?></a>
          <div class="c-meta-row">
            <span class="type-badge"><i class="fas fa-circle"></i><?=$tipo?></span>
            <span class="status-pill <?=$stClass?>"><span class="sp-dot"></span><?=$stLabel?></span>
            <?php if($isRecent):?><span class="new-badge">NEW</span><?php endif;?>
          </div>
          <?php if($desc):?><div class="c-desc"><?=e($desc)?></div><?php endif;?>
          <?php if(!empty($tags)):?>
            <div class="c-tags"><?php foreach(array_slice($tags,0,4) as $t):?><span class="ctag">#<?=e($t)?></span><?php endforeach;?></div>
          <?php endif;?>
          <?php if($rate>0):?><div class="c-stars"><?=$stars?></div><?php endif;?>
        </div>
      </div>
    </div>
    <div class="card-foot">
      <span class="cf-m"><i class="fas fa-file"></i><?=$p['files']?></span>
      <span class="cf-m"><i class="fas fa-clock"></i><?=$p['ago']?></span>
      <?php if($opens>0):?><span class="cf-m"><i class="fas fa-eye"></i><?=$opens?></span><?php endif;?>
      <div class="cf-sp"></div>
      <div class="qas">
        <?php if($p['url']):?><button class="qa-btn" onclick="event.stopPropagation();window.open('<?=e($p['url'])?>','_blank')" title="Open URL"><i class="fas fa-external-link-alt"></i></button><?php endif;?>
        <button class="qa-btn vsc" onclick="event.stopPropagation();run('vsc','<?=e($name)?>')" title="VS Code"><i class="fas fa-code"></i></button>
        <button class="qa-btn trm" onclick="event.stopPropagation();run('term','<?=e($name)?>')" title="Terminal"><i class="fas fa-terminal"></i></button>
        <button class="qa-btn" onclick="event.stopPropagation();openDp('<?=e($name)?>')" title="Details" style="color:<?=$col?>;border-color:rgba(<?=$rgb?>,.3)"><i class="fas fa-info"></i></button>
      </div>
    </div>
  </div>
<?php return ob_get_clean();}