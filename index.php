<?php
/**
 * CodeHub — Project Dashboard
 * Fixed & Enhanced Version
 */

/* ── HELPERS ─────────────────────────────────────────────── */
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function ago($t) {
    $s = time() - $t;
    if ($s < 60)     return 'just now';
    if ($s < 3600)   return floor($s/60).'m ago';
    if ($s < 86400)  return floor($s/3600).'h ago';
    if ($s < 604800) return floor($s/86400).'d ago';
    return date('M j', $t);
}

function fc($d) {
    $items = glob("$d/*");
    return $items ? count($items) : 0;
}

function detectType($dir) {
    $map = [
        'composer.json'    => ['PHP',     'fa-php',        '#a78bfa'],
        'package.json'     => ['Node',    'fa-node-js',    '#34d399'],
        'index.html'       => ['HTML',    'fa-html5',      '#f97316'],
        'style.css'        => ['CSS',     'fa-css3-alt',   '#38bdf8'],
        'pom.xml'          => ['Java',    'fa-java',       '#fb7185'],
        'requirements.txt' => ['Python',  'fa-python',     '#facc15'],
        '.git'             => ['Git',     'fa-git-alt',    '#f87171'],
        'Dockerfile'       => ['Docker',  'fa-docker',     '#60a5fa'],
        'go.mod'           => ['Go',      'fa-code',       '#5eead4'],
        'Cargo.toml'       => ['Rust',    'fa-code',       '#fdba74'],
        'artisan'          => ['Laravel', 'fa-php',        '#ff6b6b'],
        'manage.py'        => ['Django',  'fa-python',     '#34d399'],
        'pubspec.yaml'     => ['Flutter', 'fa-mobile-alt', '#93c5fd'],
        'Gemfile'          => ['Ruby',    'fa-gem',        '#e879f9'],
        'deno.json'        => ['Deno',    'fa-code',       '#4ade80'],
    ];
    foreach ($map as $f => [$t,$i,$c]) {
        if (file_exists("$dir/$f")) return [$t,$i,$c];
    }
    return ['Project','fa-folder','#818cf8'];
}

/* ── PATHS ───────────────────────────────────────────────── */
$baseDir  = __DIR__;
$cfgFile  = $baseDir . DIRECTORY_SEPARATOR . '.codehub_config.json';
$datFile  = $baseDir . DIRECTORY_SEPARATOR . '.codehub_data.json';
$trashDir = $baseDir . DIRECTORY_SEPARATOR . '.codehub_trash';
$imgDir   = $baseDir . DIRECTORY_SEPARATOR . '.codehub_images';

// Normalized base path for safe comparisons
$baseDirNorm = rtrim(str_replace('\\','/',$baseDir),'/');

$ignore = ['.','..','index.php','index.html','.codehub_config.json',
           '.codehub_images','.codehub_data.json','.codehub_trash'];

$cfg = file_exists($cfgFile) ? (json_decode(file_get_contents($cfgFile),true) ?: []) : [];
$dat = file_exists($datFile)  ? (json_decode(file_get_contents($datFile), true) ?: []) : [];

$mostrarVolver = rtrim(str_replace('\\','/',realpath($baseDir)),'/') !==
                 rtrim(str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'])),'/');

/* ── SAFE PATH CHECK ─────────────────────────────────────── */
function safePath(string $base, string $rel): string|false {
    // Sanitize: strip traversal
    $rel  = str_replace(['..','\\'], '', $rel);
    $rel  = trim($rel, '/');
    if ($rel === '') return false;
    $full = $base . DIRECTORY_SEPARATOR . $rel;
    $real = realpath($full);
    if ($real === false) {
        // Path may not exist yet (new dir) — check parent
        $parent = realpath(dirname($full));
        if ($parent === false) return false;
        $normParent = rtrim(str_replace('\\','/',$parent),'/');
        $normBase   = rtrim(str_replace('\\','/',realpath($base)),'/');
        if (strpos($normParent.'/', $normBase.'/') !== 0) return false;
        return $full;
    }
    $normReal = rtrim(str_replace('\\','/',$real),'/');
    $normBase = rtrim(str_replace('\\','/',realpath($base)),'/');
    if (strpos($normReal.'/', $normBase.'/') !== 0) return false;
    return $real;
}

/* ── AJAX ────────────────────────────────────────────────── */
if (isset($_GET['a'])) {
    header('Content-Type: application/json; charset=utf-8');
    $a = trim($_GET['a']);

    /* Create folder */
    if ($a === 'mk' && isset($_POST['n'])) {
        $n = preg_replace('/[^a-zA-Z0-9_\-. ]/', '', trim($_POST['n']));
        if (!$n) { echo json_encode(['ok'=>false,'m'=>'Invalid name']); exit; }
        $p = $baseDir . DIRECTORY_SEPARATOR . $n;
        if (file_exists($p)) { echo json_encode(['ok'=>false,'m'=>'Already exists']); exit; }
        if (!mkdir($p, 0755, true)) { echo json_encode(['ok'=>false,'m'=>'Cannot create folder']); exit; }
        $tpl = $_POST['tpl'] ?? '';
        if ($tpl === 'html') {
            file_put_contents("$p/index.html", "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1.0\">\n  <title>$n</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>$n</h1>\n  <script src=\"script.js\"></script>\n</body>\n</html>");
            file_put_contents("$p/style.css", "*{box-sizing:border-box;margin:0;padding:0}\nbody{font-family:sans-serif;padding:2rem;background:#0f172a;color:#e2e8f0}\n");
            file_put_contents("$p/script.js", "// $n\nconsole.log('$n loaded');\n");
        } elseif ($tpl === 'readme') {
            file_put_contents("$p/README.md", "# $n\n\nProject description here.\n\n## Getting Started\n\n```bash\n# install & run\n```\n");
        } elseif ($tpl === 'node') {
            file_put_contents("$p/package.json", "{\n  \"name\": \"".strtolower(str_replace(' ','-',$n))."\",\n  \"version\": \"1.0.0\",\n  \"description\": \"\",\n  \"main\": \"index.js\",\n  \"scripts\": {\"start\": \"node index.js\"},\n  \"license\": \"MIT\"\n}\n");
            file_put_contents("$p/index.js", "// $n\nconsole.log('Hello from $n');\n");
            file_put_contents("$p/README.md", "# $n\n\n```bash\nnpm start\n```\n");
        }
        echo json_encode(['ok'=>true,'m'=>'Created!']);
        exit;
    }

    /* New file */
    if ($a === 'mf' && isset($_POST['f'], $_POST['d'])) {
        $target = safePath($baseDir, $_POST['d']);
        if ($target && is_dir($target)) {
            $fname = basename($_POST['f']);
            if ($fname && file_put_contents($target.DIRECTORY_SEPARATOR.$fname, '') !== false)
                echo json_encode(['ok'=>true]);
            else echo json_encode(['ok'=>false,'m'=>'Cannot create file']);
        } else echo json_encode(['ok'=>false,'m'=>'Invalid directory']);
        exit;
    }

    /* Rename */
    if ($a === 'ren' && isset($_POST['old'], $_POST['new'])) {
        $oldName = basename($_POST['old']);
        $newName = preg_replace('/[^a-zA-Z0-9_\-. ]/', '', trim($_POST['new']));
        $oldPath = safePath($baseDir, $oldName);
        if (!$oldPath || !is_dir($oldPath)) { echo json_encode(['ok'=>false,'m'=>'Source not found']); exit; }
        $newPath = $baseDir . DIRECTORY_SEPARATOR . $newName;
        if (file_exists($newPath))   { echo json_encode(['ok'=>false,'m'=>'Name already taken']); exit; }
        if (!rename($oldPath, $newPath)) { echo json_encode(['ok'=>false,'m'=>'Rename failed']); exit; }
        // Update config key
        if (isset($cfg[$oldName])) {
            $cfg[$newName] = $cfg[$oldName];
            unset($cfg[$oldName]);
            file_put_contents($cfgFile, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* Delete (move to trash) */
    if ($a === 'del' && isset($_POST['d'])) {
        $dirName = basename(trim($_POST['d']));
        $target  = safePath($baseDir, $dirName);
        if (!$target || !is_dir($target)) {
            echo json_encode(['ok'=>false,'m'=>"Directory not found: '$dirName'"]);
            exit;
        }
        if (!file_exists($trashDir)) mkdir($trashDir, 0755, true);
        $dest = $trashDir . DIRECTORY_SEPARATOR . $dirName . '_' . time();
        if (rename($target, $dest)) {
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false,'m'=>'Could not move to trash (permission error?)']);
        }
        exit;
    }

    /* Save config */
    if ($a === 'cfg' && isset($_POST['d'])) {
        $k = basename(trim($_POST['d']));
        $existing = $cfg[$k] ?? [];
        $cfg[$k] = array_merge($existing, [
            'ico'  => $_POST['ico']  ?? ($existing['ico']  ?? null),
            'col'  => $_POST['col']  ?? ($existing['col']  ?? null),
            'img'  => $_POST['img']  ?? ($existing['img']  ?? null),
            'desc' => $_POST['desc'] ?? ($existing['desc'] ?? null),
            'pin'  => isset($_POST['pin'])  ? ($_POST['pin']  === '1') : ($existing['pin']  ?? false),
            'st'   => $_POST['st']   ?? ($existing['st']   ?? 'active'),
            'tags' => isset($_POST['tags']) ? (json_decode($_POST['tags'],true) ?: []) : ($existing['tags'] ?? []),
            'rate' => isset($_POST['rate']) ? (int)$_POST['rate'] : ($existing['rate'] ?? 0),
            'url'  => $_POST['url']  ?? ($existing['url']  ?? ''),
        ]);
        file_put_contents($cfgFile, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* Save note */
    if ($a === 'note' && isset($_POST['d'], $_POST['n'])) {
        $k = basename(trim($_POST['d']));
        if (!isset($dat[$k])) $dat[$k] = [];
        $dat[$k]['note']    = $_POST['n'];
        $dat[$k]['note_ts'] = time();
        file_put_contents($datFile, json_encode($dat, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* Save todos */
    if ($a === 'todos' && isset($_POST['d'], $_POST['t'])) {
        $k = basename(trim($_POST['d']));
        if (!isset($dat[$k])) $dat[$k] = [];
        $dat[$k]['todos'] = json_decode($_POST['t'], true) ?: [];
        file_put_contents($datFile, json_encode($dat, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* Track open */
    if ($a === 'track' && isset($_POST['d'])) {
        $k = basename(trim($_POST['d']));
        if (!isset($dat[$k])) $dat[$k] = [];
        $dat[$k]['last_open'] = time();
        $dat[$k]['opens']     = ($dat[$k]['opens'] ?? 0) + 1;
        file_put_contents($datFile, json_encode($dat, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* Upload image */
    if ($a === 'img' && isset($_FILES['f'], $_POST['d'])) {
        if (!file_exists($imgDir)) mkdir($imgDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['f']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
            echo json_encode(['ok'=>false,'m'=>'Invalid image type']); exit;
        }
        $nm = md5($_POST['d'].time()).'.'.$ext;
        if (move_uploaded_file($_FILES['f']['tmp_name'], $imgDir.DIRECTORY_SEPARATOR.$nm)) {
            echo json_encode(['ok'=>true,'img'=>'.codehub_images/'.$nm]);
        } else {
            echo json_encode(['ok'=>false,'m'=>'Upload failed']);
        }
        exit;
    }

    /* Check if project has index */
    if ($a === 'open' && isset($_GET['dir'])) {
        $target = safePath($baseDir, basename(trim($_GET['dir'])));
        if ($target && is_dir($target)) {
            $idx = null;
            foreach (['index.php','index.html','index.htm'] as $f) {
                if (file_exists($target.DIRECTORY_SEPARATOR.$f)) { $idx = $f; break; }
            }
            echo json_encode(['ok'=>true,'has_index'=>(bool)$idx,'index'=>$idx]);
        } else {
            echo json_encode(['ok'=>false,'has_index'=>false,'index'=>null]);
        }
        exit;
    }

    /* System commands (vsc / term / exp) */
    if (in_array($a,['vsc','term','exp']) && isset($_GET['dir'])) {
        $target = safePath($baseDir, basename(trim($_GET['dir'])));
        if ($target && is_dir($target)) {
            $isWin = strtoupper(substr(PHP_OS,0,3)) === 'WIN';
            if ($a === 'vsc')  @exec('code --new-window '.escapeshellarg($target));
            if ($a === 'term') @exec($isWin ? 'start cmd /K cd /d '.escapeshellarg($target) : 'gnome-terminal --working-directory='.escapeshellarg($target));
            if ($a === 'exp')  @exec($isWin ? 'explorer '.escapeshellarg($target) : 'xdg-open '.escapeshellarg($target));
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* Export */
    if ($a === 'exp_data') {
        $rows = [];
        foreach (array_diff(scandir($baseDir), $ignore) as $x) {
            if (!is_dir($baseDir.DIRECTORY_SEPARATOR.$x)) continue;
            [$t] = detectType($baseDir.DIRECTORY_SEPARATOR.$x);
            $c = $cfg[$x] ?? [];
            $d = $dat[$x] ?? [];
            $rows[] = ['name'=>$x,'type'=>$t,'files'=>fc($baseDir.DIRECTORY_SEPARATOR.$x),
                       'status'=>$c['st']??'active','rating'=>$c['rate']??0,
                       'tags'=>implode(';',$c['tags']??[]),'opens'=>$d['opens']??0,
                       'desc'=>$c['desc']??''];
        }
        $fmt = $_GET['fmt'] ?? 'json';
        if ($fmt === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="codehub.csv"');
            if (!empty($rows)) {
                echo implode(',', array_keys($rows[0]))."\n";
                foreach ($rows as $r)
                    echo implode(',', array_map(fn($v)=>'"'.str_replace('"','""',$v).'"', array_values($r)))."\n";
            }
        } else {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="codehub.json"');
            echo json_encode(['exported_at'=>date('c'),'projects'=>$rows], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    echo json_encode(['ok'=>false,'m'=>'Unknown action']);
    exit;
}

/* ── BUILD PROJECTS ──────────────────────────────────────── */
$projects = [];
$scanned  = array_diff(scandir($baseDir) ?: [], $ignore);
foreach ($scanned as $name) {
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . $name;
    if (!is_dir($fullPath)) continue;
    [$tipo,$ico,$col] = detectType($fullPath);
    $c    = $cfg[$name] ?? [];
    $d    = $dat[$name] ?? [];
    $todos = $d['todos'] ?? [];
    $mtime = filemtime($fullPath);
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
        'files'   => fc($fullPath),
        'mtime'   => $mtime,
        'ago'     => ago($mtime),
        'pend'    => count(array_filter($todos, fn($t)=>!($t['done']??false))),
    ];
}
usort($projects, fn($a,$b) => $a['pin']&&!$b['pin'] ? -1 : (!$a['pin']&&$b['pin'] ? 1 : strcmp($a['name'],$b['name'])));

$total    = count($projects);
$pinned   = count(array_filter($projects, fn($p)=>$p['pin']));
$recent   = count(array_filter($projects, fn($p)=>time()-$p['mtime']<604800));
$pending  = array_sum(array_column($projects,'pend'));
$hasNotes = count(array_filter($projects, fn($p)=>!empty($p['note'])));
$stCounts = array_count_values(array_column($projects,'st'));
$typeCounts = [];
foreach ($projects as $p) $typeCounts[$p['tipo']] = ($typeCounts[$p['tipo']]??0)+1;
arsort($typeCounts);
$allTags = [];
foreach ($projects as $p) foreach ($p['tags'] as $t) $allTags[$t] = ($allTags[$t]??0)+1;
arsort($allTags);

/* ── CARD RENDERER ───────────────────────────────────────── */
function renderCard(array $p): string {
    $name    = $p['name'];
    $col     = $p['col']   ?: '#818cf8';
    $ico     = $p['ico']   ?: 'fa-folder';
    $img     = $p['img'];
    $desc    = $p['desc'];
    $tipo    = $p['tipo'];
    $tags    = $p['tags'];
    $rate    = $p['rate'];
    $st      = $p['st'];
    $pin     = $p['pin'];
    $pend    = $p['pend'];
    $opens   = $p['opens'];
    $note    = $p['note'];
    $isRecent = (time()-$p['mtime']) < 604800;

    // Safe hex-to-rgb
    $rgb = '130,140,255';
    if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i',$col,$m))
        $rgb = hexdec($m[1]).','.hexdec($m[2]).','.hexdec($m[3]);

    $stMap = [
        'active'   => ['sp-active',  'Active'],
        'wip'      => ['sp-wip',     'In Progress'],
        'paused'   => ['sp-paused',  'Paused'],
        'done'     => ['sp-done',    'Done'],
        'archived' => ['sp-archived','Archived'],
    ];
    [$stClass,$stLabel] = $stMap[$st] ?? ['sp-active','Active'];

    $stars = implode('', array_map(fn($i)=>'<i class="fas fa-star star'.($i<=$rate?' on':'').'"></i>', range(1,5)));

    ob_start(); ?>
<div class="card <?= $pin?'pinned':'' ?>"
     data-name="<?=e($name)?>" data-type="<?=strtolower($tipo)?>" data-mt="<?=$p['mtime']?>"
     data-files="<?=$p['files']?>" data-opens="<?=$opens?>" data-rate="<?=$rate?>"
     data-st="<?=$st?>" data-recent="<?=$isRecent?'1':'0'?>" data-note="<?=!empty($note)?'1':'0'?>"
     data-desc="<?=e($desc??'')?>" data-tags="<?=e(json_encode($tags))?>"
     style="--c:<?=$col?>">
  <div class="drag-h"><i class="fas fa-grip-vertical"></i></div>
  <div class="card-cb"><i class="fas fa-check"></i></div>
  <?php if($pend>0):?><div class="todo-badge"></div><?php endif;?>
  <div class="card-acts">
    <div class="ca-btn pin <?=$pin?'on':''?>" onclick="togglePin('<?=e($name)?>', event)" title="<?=$pin?'Unpin':'Pin'?>"><i class="fas fa-thumbtack"></i></div>
    <div class="ca-btn note <?=!empty($note)?'has':''?>" onclick="openDp('<?=e($name)?>','notes');event.stopPropagation()" title="Notes"><i class="fas fa-sticky-note"></i></div>
    <div class="ca-btn edit" onclick="openCust('<?=e($name)?>', event)" title="Customize"><i class="fas fa-sliders-h"></i></div>
    <div class="ca-btn go" onclick="openProj('<?=e($name)?>');event.stopPropagation()" title="Open"><i class="fas fa-arrow-right"></i></div>
  </div>
  <div class="card-body" onclick="openProj('<?=e($name)?>')">
    <div class="card-top">
      <div class="c-thumb" style="<?=$img?"background-image:url(".e($img).");background-size:cover;background-position:center":"background:rgba($rgb,.15)"?>">
        <?php if(!$img):?><i class="fas <?=$ico?>" style="color:<?=$col?>"></i><?php endif;?>
      </div>
      <div class="c-info">
        <span class="c-name"><?=e($name)?></span>
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
    <span class="cf-m"><i class="fas fa-file-alt"></i><?=$p['files']?></span>
    <span class="cf-m"><i class="fas fa-clock"></i><?=$p['ago']?></span>
    <?php if($opens>0):?><span class="cf-m"><i class="fas fa-eye"></i><?=$opens?></span><?php endif;?>
    <div class="cf-sp"></div>
    <div class="qas">
      <?php if($p['url']):?><button class="qa-btn" onclick="event.stopPropagation();window.open('<?=e($p['url'])?>','_blank')" title="Open URL"><i class="fas fa-external-link-alt"></i></button><?php endif;?>
      <button class="qa-btn vsc" onclick="event.stopPropagation();sysRun('vsc','<?=e($name)?>')" title="VS Code"><i class="fas fa-code"></i></button>
      <button class="qa-btn trm" onclick="event.stopPropagation();sysRun('term','<?=e($name)?>')" title="Terminal"><i class="fas fa-terminal"></i></button>
      <button class="qa-btn info-btn" onclick="event.stopPropagation();openDp('<?=e($name)?>')" title="Details" style="color:<?=$col?>;border-color:rgba(<?=$rgb?>,.3)"><i class="fas fa-info-circle"></i></button>
    </div>
  </div>
</div>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>CodeHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="icono.png" type="image/x-icon">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}

/* ═══════════════════════════════════════════
   THEMES
═══════════════════════════════════════════ */
[data-theme="dark"]{
  --bg:#010d0e;--bg2:#031618;--bg3:#061f23;--bg4:#0a2b30;--bg5:#0e3840;
  --glass:rgba(11,135,145,.03);--glass2:rgba(11,135,145,.065);
  --bdr:rgba(11,135,145,.15);--bdr2:rgba(11,135,145,.28);--bdr3:rgba(11,135,145,.46);
  --txt:#c8f0f4;--txt2:#2e8a92;--txt3:#0f4a50;
  --acc:#0b8791;--acc2:#00d4e0;--acc3:rgba(11,135,145,.16);--acc4:rgba(11,135,145,.07);
  --warm:#ff6040;--warm2:#ff9070;--grn:#00e5aa;--red:#ff3d5a;--ylw:#ffe040;--pnk:#ff40c8;
  --shadow:0 24px 64px rgba(0,0,0,.88)
}
[data-theme="midnight"]{
  --bg:#000;--bg2:#030508;--bg3:#060b12;--bg4:#0a1220;--bg5:#0d192e;
  --glass:rgba(0,220,255,.018);--glass2:rgba(0,220,255,.04);
  --bdr:rgba(0,200,255,.08);--bdr2:rgba(0,200,255,.16);--bdr3:rgba(0,200,255,.28);
  --txt:#d0f4ff;--txt2:#2e6888;--txt3:#0e2d40;
  --acc:#00d4ff;--acc2:#66e8ff;--acc3:rgba(0,212,255,.1);--acc4:rgba(0,212,255,.05);
  --warm:#ff4f7b;--warm2:#ff80a0;--grn:#00ff9f;--red:#ff4f7b;--ylw:#ffe566;--pnk:#cc55ff;
  --shadow:0 24px 64px rgba(0,0,0,.85)
}
[data-theme="forest"]{
  --bg:#040a06;--bg2:#081410;--bg3:#0e1e18;--bg4:#142820;--bg5:#1a3228;
  --glass:rgba(74,222,128,.022);--glass2:rgba(74,222,128,.048);
  --bdr:rgba(74,222,128,.09);--bdr2:rgba(74,222,128,.18);--bdr3:rgba(74,222,128,.3);
  --txt:#d8f5e8;--txt2:#3d8060;--txt3:#153824;
  --acc:#4ade80;--acc2:#86efac;--acc3:rgba(74,222,128,.12);--acc4:rgba(74,222,128,.06);
  --warm:#fb923c;--warm2:#fdba74;--grn:#4ade80;--red:#f87171;--ylw:#fde047;--pnk:#f472b6;
  --shadow:0 24px 64px rgba(0,0,0,.72)
}
[data-theme="rose"]{
  --bg:#0c0409;--bg2:#180814;--bg3:#220d1e;--bg4:#2e1228;--bg5:#3a1832;
  --glass:rgba(251,113,133,.022);--glass2:rgba(251,113,133,.048);
  --bdr:rgba(251,113,133,.09);--bdr2:rgba(251,113,133,.18);--bdr3:rgba(251,113,133,.32);
  --txt:#ffe4f0;--txt2:#994466;--txt3:#521830;
  --acc:#fb7185;--acc2:#fda4af;--acc3:rgba(251,113,133,.12);--acc4:rgba(251,113,133,.06);
  --warm:#f97316;--warm2:#fb923c;--grn:#34d399;--red:#ef4444;--ylw:#fbbf24;--pnk:#e879f9;
  --shadow:0 24px 64px rgba(0,0,0,.72)
}
[data-theme="dusk"]{
  --bg:#0c0618;--bg2:#130b26;--bg3:#1c1136;--bg4:#261845;--bg5:#301f54;
  --glass:rgba(167,139,250,.025);--glass2:rgba(167,139,250,.052);
  --bdr:rgba(167,139,250,.1);--bdr2:rgba(167,139,250,.2);--bdr3:rgba(167,139,250,.36);
  --txt:#ede4ff;--txt2:#7a5aaa;--txt3:#3c1e70;
  --acc:#a78bfa;--acc2:#c4b5fd;--acc3:rgba(167,139,250,.13);--acc4:rgba(167,139,250,.06);
  --warm:#fb923c;--warm2:#fdba74;--grn:#6ee7b7;--red:#f87171;--ylw:#fde047;--pnk:#f0abfc;
  --shadow:0 24px 64px rgba(0,0,0,.75)
}
[data-theme="ocean"]{
  --bg:#010d10;--bg2:#02161e;--bg3:#04202c;--bg4:#062c3c;--bg5:#08384c;
  --glass:rgba(20,184,166,.022);--glass2:rgba(20,184,166,.048);
  --bdr:rgba(20,184,166,.09);--bdr2:rgba(20,184,166,.19);--bdr3:rgba(20,184,166,.34);
  --txt:#ccfbf1;--txt2:#2a7a6e;--txt3:#0c3830;
  --acc:#2dd4bf;--acc2:#5eead4;--acc3:rgba(45,212,191,.12);--acc4:rgba(45,212,191,.06);
  --warm:#f97316;--warm2:#fb923c;--grn:#4ade80;--red:#f87171;--ylw:#fbbf24;--pnk:#e879f9;
  --shadow:0 24px 64px rgba(0,0,0,.82)
}
[data-theme="dracula"]{
  --bg:#0f0f1a;--bg2:#181824;--bg3:#21212f;--bg4:#2a2a3c;--bg5:#333348;
  --glass:rgba(189,147,249,.022);--glass2:rgba(189,147,249,.048);
  --bdr:rgba(98,114,164,.14);--bdr2:rgba(98,114,164,.26);--bdr3:rgba(98,114,164,.44);
  --txt:#f8f8f2;--txt2:#6272a4;--txt3:#30355a;
  --acc:#bd93f9;--acc2:#d6b8fe;--acc3:rgba(189,147,249,.13);--acc4:rgba(189,147,249,.065);
  --warm:#ffb86c;--warm2:#ffd08a;--grn:#50fa7b;--red:#ff5555;--ylw:#f1fa8c;--pnk:#ff79c6;
  --shadow:0 24px 64px rgba(0,0,0,.72)
}
[data-theme="amber"]{
  --bg:#0a0602;--bg2:#140d04;--bg3:#1e1408;--bg4:#281c0c;--bg5:#322410;
  --glass:rgba(252,211,77,.018);--glass2:rgba(252,211,77,.038);
  --bdr:rgba(245,158,11,.1);--bdr2:rgba(245,158,11,.2);--bdr3:rgba(245,158,11,.36);
  --txt:#fffbeb;--txt2:#927020;--txt3:#4a3008;
  --acc:#fcd34d;--acc2:#fde68a;--acc3:rgba(252,211,77,.13);--acc4:rgba(252,211,77,.065);
  --warm:#ea580c;--warm2:#f97316;--grn:#86efac;--red:#fca5a5;--ylw:#fde047;--pnk:#fda4af;
  --shadow:0 24px 64px rgba(0,0,0,.78)
}
[data-theme="neon"]{
  --bg:#010102;--bg2:#040408;--bg3:#07070f;--bg4:#0c0c18;--bg5:#121220;
  --glass:rgba(57,255,20,.018);--glass2:rgba(57,255,20,.038);
  --bdr:rgba(57,255,20,.08);--bdr2:rgba(57,255,20,.17);--bdr3:rgba(57,255,20,.3);
  --txt:#e8ffe4;--txt2:#1e6818;--txt3:#082408;
  --acc:#39ff14;--acc2:#80ff60;--acc3:rgba(57,255,20,.1);--acc4:rgba(57,255,20,.05);
  --warm:#ff0080;--warm2:#ff40a0;--grn:#39ff14;--red:#ff0055;--ylw:#ffff00;--pnk:#ff00ff;
  --shadow:0 24px 64px rgba(0,0,0,.9)
}
[data-theme="nord"]{
  --bg:#181c28;--bg2:#1e2232;--bg3:#242a3e;--bg4:#2c324a;--bg5:#343c56;
  --glass:rgba(136,192,208,.022);--glass2:rgba(136,192,208,.048);
  --bdr:rgba(136,192,208,.1);--bdr2:rgba(136,192,208,.2);--bdr3:rgba(136,192,208,.34);
  --txt:#eceff4;--txt2:#7890a8;--txt3:#384462;
  --acc:#88c0d0;--acc2:#9ecfdf;--acc3:rgba(136,192,208,.11);--acc4:rgba(136,192,208,.055);
  --warm:#ebcb8b;--warm2:#f0d8a0;--grn:#a3be8c;--red:#bf616a;--ylw:#ebcb8b;--pnk:#b48ead;
  --shadow:0 24px 64px rgba(0,0,0,.6)
}

:root{
  --ui:'DM Sans',sans-serif;
  --mono:'DM Mono',monospace;
  --r:13px;
  --sidebar:264px
}

/* ═══════════════════════════════════════════
   SCROLLBAR
═══════════════════════════════════════════ */
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:rgba(124,140,255,.2);border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:var(--acc)}

/* ═══════════════════════════════════════════
   BODY & LAYOUT
═══════════════════════════════════════════ */
body{
  background:var(--bg);
  background-image:
    radial-gradient(ellipse 75% 50% at 8% -8%,rgba(11,135,145,.22) 0%,transparent 100%),
    radial-gradient(ellipse 55% 45% at 92% 105%,rgba(11,135,145,.12) 0%,transparent 100%),
    radial-gradient(circle,rgba(11,135,145,.1) 1px,transparent 1px);
  background-size:100% 100%,100% 100%,28px 28px;
  background-attachment:fixed;
  color:var(--txt);font-family:var(--ui);
  min-height:100vh;overflow-x:hidden;line-height:1.5;
  -webkit-font-smoothing:antialiased
}
body::after{
  content:'';position:fixed;inset:0;z-index:9990;pointer-events:none;
  opacity:.3;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23g)' opacity='.055'/%3E%3C/svg%3E")
}
canvas#cv{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.15}

.app{display:grid;grid-template-columns:var(--sidebar) 1fr;min-height:100vh;position:relative;z-index:1}

/* ═══════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════ */
.sidebar{
  background:var(--bg2);border-right:1px solid var(--bdr);
  padding:18px 12px;display:flex;flex-direction:column;gap:20px;
  position:sticky;top:0;height:100vh;overflow-y:auto;overflow-x:hidden
}
.sidebar::-webkit-scrollbar{width:2px}

.logo{display:flex;align-items:center;gap:10px;padding:2px 4px;user-select:none}
.logo-mark{
  width:32px;height:32px;border-radius:9px;flex-shrink:0;
  background:linear-gradient(145deg,var(--acc) 0%,var(--acc2) 100%);
  display:flex;align-items:center;justify-content:center;font-size:.88em;color:#fff;
  box-shadow:0 0 22px rgba(11,135,145,.45),0 4px 12px rgba(0,0,0,.5);
  animation:logoglw 4s ease-in-out infinite
}
@keyframes logoglw{
  0%,100%{box-shadow:0 0 18px rgba(11,135,145,.35),0 4px 12px rgba(0,0,0,.5)}
  50%{box-shadow:0 0 42px rgba(11,135,145,.75),0 0 70px rgba(0,212,224,.25),0 4px 12px rgba(0,0,0,.5)}
}
.logo-name{font-size:1.12em;font-weight:800;letter-spacing:-.5px;color:var(--txt)}
.logo-name span{color:var(--acc2)}
.logo-env{font-family:var(--mono);font-size:.56em;color:var(--txt3);margin-top:1px}

.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.stat-cell{background:var(--glass);border:1px solid var(--bdr);border-radius:10px;padding:9px 10px;transition:all .2s}
.stat-cell:hover{border-color:var(--bdr2);background:var(--glass2)}
.stat-cell.accent{background:var(--acc3);border-color:rgba(124,140,255,.22)}
.stat-val{font-family:var(--mono);font-size:1.4em;font-weight:500;color:var(--txt);line-height:1}
.stat-cell.accent .stat-val{color:var(--acc2)}
.stat-lbl{font-size:.62em;color:var(--txt3);margin-top:3px;text-transform:uppercase;letter-spacing:.6px;font-weight:600}

.s-label{font-size:.58em;font-weight:700;color:var(--txt3);letter-spacing:1.8px;text-transform:uppercase;display:flex;align-items:center;gap:8px;padding:0 3px}
.s-label::after{content:'';flex:1;height:1px;background:var(--bdr)}

.nav{list-style:none;display:flex;flex-direction:column;gap:1px}
.nav-item{
  display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:8px;
  color:var(--txt3);font-size:.8em;font-weight:500;transition:all .15s;user-select:none;
  border:1px solid transparent;cursor:pointer
}
.nav-item i{width:13px;font-size:.85em;flex-shrink:0}
.nav-item:hover{color:var(--txt2);background:var(--glass)}
.nav-item.on{color:var(--acc2);background:var(--acc3);border-color:rgba(124,140,255,.16)}
.nav-item .cnt{margin-left:auto;font-family:var(--mono);font-size:.64em;background:rgba(124,140,255,.12);color:var(--acc2);padding:1px 6px;border-radius:9px;min-width:16px;text-align:center}
.nav-item.on .cnt{background:var(--acc);color:#fff}

.tag-cloud{display:flex;flex-wrap:wrap;gap:4px}
.stag{
  padding:3px 7px;border-radius:6px;font-family:var(--mono);font-size:.61em;
  border:1px solid var(--bdr);background:transparent;color:var(--txt3);transition:all .15s;
  user-select:none;cursor:pointer
}
.stag:hover,.stag.on{color:var(--acc2);border-color:rgba(124,140,255,.3);background:var(--acc3)}

.theme-picker{display:grid;grid-template-columns:repeat(5,1fr);gap:4px}
.theme-dot{
  aspect-ratio:1;border-radius:7px;border:2px solid var(--bdr);
  transition:transform .16s,border-color .16s,box-shadow .16s;position:relative;cursor:pointer
}
.theme-dot:hover{transform:scale(1.14);border-color:var(--bdr2)}
.theme-dot.on{border-color:rgba(255,255,255,.45);box-shadow:0 0 0 2px rgba(255,255,255,.12);transform:scale(1.1)}
.theme-dot[data-t="dark"]{background:linear-gradient(135deg,#07080f 52%,#0e3840 52%)}
.theme-dot[data-t="midnight"]{background:linear-gradient(135deg,#000 52%,#00d4ff 52%)}
.theme-dot[data-t="forest"]{background:linear-gradient(135deg,#040a06 52%,#4ade80 52%)}
.theme-dot[data-t="rose"]{background:linear-gradient(135deg,#0c0409 52%,#fb7185 52%)}
.theme-dot[data-t="dusk"]{background:linear-gradient(135deg,#0c0618 52%,#a78bfa 52%)}
.theme-dot[data-t="ocean"]{background:linear-gradient(135deg,#010d10 52%,#2dd4bf 52%)}
.theme-dot[data-t="dracula"]{background:linear-gradient(135deg,#0f0f1a 52%,#bd93f9 52%)}
.theme-dot[data-t="amber"]{background:linear-gradient(135deg,#0a0602 52%,#fcd34d 52%)}
.theme-dot[data-t="neon"]{background:linear-gradient(135deg,#010102 52%,#39ff14 52%)}
.theme-dot[data-t="nord"]{background:linear-gradient(135deg,#181c28 52%,#88c0d0 52%)}

.sidebar-foot{margin-top:auto;padding-top:12px;border-top:1px solid var(--bdr)}
.live-badge{display:flex;align-items:center;gap:7px;font-family:var(--mono);font-size:.6em;color:var(--txt3)}
.live-dot{width:5px;height:5px;border-radius:50%;background:var(--grn);animation:ldot 2s ease-in-out infinite}
@keyframes ldot{0%,100%{opacity:1}50%{opacity:.2}}

/* ═══════════════════════════════════════════
   TOPBAR
═══════════════════════════════════════════ */
.main{display:flex;flex-direction:column;min-height:100vh}
.topbar{
  display:flex;align-items:center;gap:9px;padding:11px 22px;
  border-bottom:1px solid var(--bdr);
  background:rgba(6,9,16,.92);backdrop-filter:blur(24px);
  position:sticky;top:0;z-index:300;flex-shrink:0
}
.search-wrap{flex:1;max-width:420px;position:relative}
.search-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--txt3);font-size:.75em;pointer-events:none;transition:color .2s}
.search-input{
  width:100%;background:var(--bg3);border:1px solid var(--bdr);border-radius:9px;
  padding:8px 36px;color:var(--txt);font-family:var(--mono);font-size:.78em;outline:none;transition:all .2s
}
.search-input:focus{border-color:var(--acc);background:var(--acc4);box-shadow:0 0 0 3px rgba(124,140,255,.1)}
.search-input::placeholder{color:var(--txt3)}
.search-wrap:focus-within .search-ico{color:var(--acc)}
.search-kbd{position:absolute;right:8px;top:50%;transform:translateY(-50%);font-family:var(--mono);font-size:.55em;color:var(--txt3);background:var(--bg5);border:1px solid var(--bdr);border-radius:4px;padding:1px 5px;pointer-events:none}

.tb-spacer{flex:1}
.tb-right{display:flex;align-items:center;gap:6px}
.btn{
  display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;
  font-family:var(--ui);font-size:.78em;font-weight:600;border:1px solid transparent;
  transition:all .16s;user-select:none;white-space:nowrap;cursor:pointer
}
.btn-ghost{background:var(--glass);border-color:var(--bdr);color:var(--txt2)}
.btn-ghost:hover{background:var(--glass2);color:var(--txt);border-color:var(--bdr2)}
.btn-primary{background:var(--acc);color:#fff;font-weight:700}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(124,140,255,.38);filter:brightness(1.08)}
.btn-danger{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.2);color:var(--red)}
.btn-danger:hover{background:rgba(248,113,113,.18)}
.icon-btn{width:32px;height:32px;padding:0;justify-content:center;font-size:.8em;flex-shrink:0}

.view-toggle{display:flex;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;overflow:hidden}
.vbtn{padding:6px 9px;background:transparent;border:none;color:var(--txt3);font-size:.78em;transition:all .15s;cursor:pointer}
.vbtn.on{background:var(--acc);color:#fff}
.vbtn:hover:not(.on){color:var(--txt2);background:var(--glass2)}

.sort-wrap{position:relative}
.sort-dd{
  position:absolute;top:calc(100% + 5px);right:0;background:var(--bg3);
  border:1px solid var(--bdr2);border-radius:11px;padding:4px;min-width:170px;
  z-index:250;display:none;box-shadow:var(--shadow);animation:fadeup .13s ease
}
.sort-dd.open{display:block}
@keyframes fadeup{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
.sort-item{display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:7px;font-size:.76em;font-weight:500;color:var(--txt2);transition:all .13s;cursor:pointer}
.sort-item i{width:12px;color:var(--txt3);font-size:.85em}
.sort-item:hover{background:var(--glass2);color:var(--txt)}
.sort-item.on{color:var(--acc2);background:var(--acc3)}
.sort-item.on i{color:var(--acc2)}

/* ═══════════════════════════════════════════
   CONTENT
═══════════════════════════════════════════ */
.content{flex:1;padding:22px 22px 60px;overflow-y:auto}
.page-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap}
.page-title{font-size:1.5em;font-weight:800;letter-spacing:-.7px;line-height:1.1}
.page-sub{font-family:var(--mono);font-size:.68em;color:var(--txt3);margin-top:4px}

.bulk-bar{display:none;align-items:center;gap:7px;padding:7px 12px;background:var(--acc3);border:1px solid rgba(124,140,255,.28);border-radius:9px;font-family:var(--mono);font-size:.7em;color:var(--acc2)}
.bulk-bar.show{display:flex}

.sec-head{font-family:var(--mono);font-size:.58em;font-weight:600;color:var(--txt3);letter-spacing:1.8px;text-transform:uppercase;margin-bottom:11px;display:flex;align-items:center;gap:7px}
.sec-head i{font-size:1em}
.sec-head::after{content:'';flex:1;height:1px;background:var(--bdr)}

/* ═══════════════════════════════════════════
   GRID
═══════════════════════════════════════════ */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.grid.list{grid-template-columns:1fr;gap:6px}
.grid.compact{grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px}

/* Card size system */
[data-csize="xs"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(160px,1fr))!important;gap:6px!important}
[data-csize="sm"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(230px,1fr))!important;gap:10px!important}
[data-csize="md"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(300px,1fr))!important;gap:14px!important}
[data-csize="lg"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(380px,1fr))!important;gap:16px!important}
[data-csize="xl"] .grid:not(.list){grid-template-columns:repeat(auto-fill,minmax(500px,1fr))!important;gap:20px!important}
[data-csize="xs"] .card{font-size:.68em}
[data-csize="sm"] .card{font-size:.82em}
[data-csize="md"] .card{font-size:1em}
[data-csize="lg"] .card{font-size:1.18em}
[data-csize="xl"] .card{font-size:1.36em}
[data-csize="xs"] .c-desc,[data-csize="xs"] .c-tags,[data-csize="xs"] .c-stars,[data-csize="xs"] .card-foot{display:none!important}
[data-csize="sm"] .c-desc,[data-csize="sm"] .c-stars{display:none!important}
[data-density="comfortable"] .grid{gap:18px!important}
[data-density="tight"] .grid{gap:6px!important}
[data-compact="1"] .c-desc,[data-compact="1"] .c-stars{display:none!important}
[data-compact="1"] .card-top{padding:.68em .85em!important}
[data-compact="1"] .card-foot{padding:.4em .85em!important}
[data-rounded="sm"]{--r:7px}
[data-rounded="lg"]{--r:20px}
[data-rounded="xl"]{--r:28px}
[data-sidebar="narrow"]{--sidebar:210px}
[data-sidebar="wide"]{--sidebar:300px}
[data-animations="off"] .card{animation:none!important}
[data-animations="off"] .card::before{display:none}
[data-sidebar-glass="on"] .sidebar{background:rgba(13,17,33,.72)!important;backdrop-filter:blur(28px)}

/* ═══════════════════════════════════════════
   CARD
═══════════════════════════════════════════ */
.card{
  background:linear-gradient(155deg,#081519 0%,#040c10 100%);
  border:1px solid rgba(11,135,145,.11);
  border-left:2px solid color-mix(in srgb,var(--c,var(--acc)) 42%,transparent);
  border-radius:var(--r);
  overflow:hidden;position:relative;display:flex;flex-direction:column;
  transition:transform .4s cubic-bezier(.34,1.56,.64,1),border-color .28s,border-left-color .28s,box-shadow .36s,background .28s;
  will-change:transform;animation:cardIn .46s cubic-bezier(.34,1.56,.64,1) both;cursor:pointer
}
@keyframes cardIn{
  0%  {opacity:0;transform:translateY(24px) scale(.92);filter:blur(5px)}
  50% {opacity:1;filter:blur(0)}
  68% {transform:translateY(-6px) scale(1.025)}
  83% {transform:translateY(2px) scale(.99)}
  100%{opacity:1;transform:translateY(0) scale(1)}
}
<?php for($i=0;$i<80;$i++): ?>
.card:nth-child(<?=$i+1?>){animation-delay:<?=round($i*.03,3)?>s}
<?php endfor; ?>
.card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent 0%,color-mix(in srgb,var(--c,var(--acc)) 90%,white) 25%,color-mix(in srgb,var(--c,var(--acc)) 90%,white) 75%,transparent 100%);
  transform:scaleX(0);opacity:0;transform-origin:center;
  transition:transform .5s cubic-bezier(.34,1.56,.64,1),opacity .42s
}
.card:hover::before{transform:scaleX(1);opacity:1}
.card::after{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:radial-gradient(ellipse 85% 80% at var(--mx,50%) var(--my,50%),color-mix(in srgb,var(--c,var(--acc)) 14%,transparent),transparent 68%);
  opacity:0;transition:opacity .38s
}
.card:hover::after{opacity:1}
.card:hover{
  transform:translateY(-5px) perspective(800px) rotateY(var(--rx,0deg)) rotateX(var(--ry,0deg));
  border-color:color-mix(in srgb,var(--c,var(--acc)) 28%,transparent);
  border-left-color:color-mix(in srgb,var(--c,var(--acc)) 75%,transparent);
  box-shadow:
    0 22px 56px rgba(0,0,0,.72),
    0 8px 22px rgba(0,0,0,.42),
    0 0 0 1px color-mix(in srgb,var(--c,var(--acc)) 18%,transparent),
    0 0 50px color-mix(in srgb,var(--c,var(--acc)) 8%,transparent);
  background:linear-gradient(155deg,
    color-mix(in srgb,var(--c,var(--acc)) 6%,#081519),
    color-mix(in srgb,var(--c,var(--acc)) 3%,#040c10))
}
.card.pinned{
  background:linear-gradient(155deg,
    color-mix(in srgb,var(--c,var(--acc)) 8%,#081519),
    color-mix(in srgb,var(--c,var(--acc)) 4%,#040c10));
  border-color:color-mix(in srgb,var(--c,var(--acc)) 22%,rgba(11,135,145,.11));
  border-left-color:color-mix(in srgb,var(--c,var(--acc)) 62%,transparent)
}
.card.selected{border-color:var(--acc)!important;box-shadow:0 0 0 2px rgba(11,135,145,.35)!important}
.card.dragging{opacity:.18;transform:scale(.93) rotate(1.5deg);z-index:999}
.card.dragover{border-color:var(--acc);border-style:dashed}

.card-cb{position:absolute;top:.65em;left:.65em;width:1.1em;height:1.1em;border-radius:.32em;border:1.5px solid var(--bdr2);background:var(--bg3);display:none;align-items:center;justify-content:center;z-index:20;transition:all .18s cubic-bezier(.34,1.56,.64,1);font-size:.55em;cursor:pointer}
.card-cb.show{display:flex}
.card-cb.on{background:var(--acc);border-color:var(--acc);color:#fff;box-shadow:0 0 8px color-mix(in srgb,var(--acc) 50%,transparent)}
.todo-badge{position:absolute;top:.55em;right:.55em;width:.52em;height:.52em;border-radius:50%;background:var(--red);z-index:10;box-shadow:0 0 8px rgba(255,61,90,.7),0 0 0 2px rgba(255,61,90,.2);animation:todoPulse 2.4s ease-in-out infinite}
@keyframes todoPulse{0%,100%{box-shadow:0 0 8px rgba(255,61,90,.7),0 0 0 2px rgba(255,61,90,.2)}50%{box-shadow:0 0 16px rgba(255,61,90,.9),0 0 0 4px rgba(255,61,90,.15)}}

.card-acts{position:absolute;top:.52em;right:.52em;display:flex;gap:.28em;opacity:0;transform:translateY(-7px) scale(.88);transition:opacity .26s,transform .34s cubic-bezier(.34,1.56,.64,1);z-index:15}
.card:hover .card-acts,.card.selected .card-acts{opacity:1;transform:translateY(0) scale(1)}
.ca-btn{width:1.72em;height:1.72em;border-radius:.46em;display:flex;align-items:center;justify-content:center;background:rgba(6,9,16,.9);backdrop-filter:blur(16px);border:1px solid var(--bdr2);color:var(--txt2);font-size:.68em;transition:all .22s cubic-bezier(.34,1.56,.64,1);cursor:pointer}
.ca-btn:hover{transform:scale(1.22) translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.5)}
.ca-btn.pin:hover,.ca-btn.pin.on{background:rgba(251,191,36,.2);border-color:var(--ylw);color:var(--ylw);box-shadow:0 0 12px rgba(251,191,36,.28)}
.ca-btn.edit:hover{background:var(--acc3);border-color:var(--acc);color:var(--acc2);box-shadow:0 0 12px rgba(124,140,255,.24)}
.ca-btn.note:hover,.ca-btn.note.has{background:rgba(52,211,153,.13);border-color:var(--grn);color:var(--grn);box-shadow:0 0 12px rgba(52,211,153,.24)}
.ca-btn.go:hover{background:rgba(52,211,153,.15);border-color:var(--grn);color:var(--grn);box-shadow:0 0 12px rgba(52,211,153,.24)}

.drag-h{position:absolute;left:.38em;top:50%;transform:translateY(-50%);color:var(--txt3);font-size:.6em;opacity:0;transition:opacity .18s;z-index:25;padding:.28em .2em;cursor:grab}
.card:hover .drag-h{opacity:.38}
.drag-h:hover{opacity:1!important;color:var(--acc2)}

/* ── Card body layout ──────────────────────────────────── */
.card-body{
  padding:0;flex:1;display:flex;flex-direction:column;
  position:relative;overflow:hidden
}
.card-body::before{display:none}
.card-top{
  display:flex;align-items:flex-start;gap:.75em;
  padding:1em 1em .7em;
  flex-shrink:0
}
.c-thumb{
  position:relative;
  width:2.75em;height:2.75em;border-radius:.65em;
  flex-shrink:0;display:flex;align-items:center;justify-content:center;
  font-size:1.1em;background-size:cover;background-position:center;
  box-shadow:
    0 0 0 1px color-mix(in srgb,var(--c,var(--acc)) 32%,transparent),
    0 4px 14px color-mix(in srgb,var(--c,var(--acc)) 22%,transparent),
    0 2px 6px rgba(0,0,0,.4);
  transition:transform .4s cubic-bezier(.34,1.56,.64,1),box-shadow .3s;
  overflow:hidden;margin-top:.05em
}
.c-thumb::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.18) 0%,transparent 52%)}
.card:hover .c-thumb{
  transform:scale(1.1) rotate(-6deg);
  box-shadow:
    0 0 0 2px color-mix(in srgb,var(--c,var(--acc)) 68%,transparent),
    0 6px 22px color-mix(in srgb,var(--c,var(--acc)) 48%,transparent),
    0 0 18px color-mix(in srgb,var(--c,var(--acc)) 36%,transparent)
}
.c-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:.22em;position:relative;z-index:1}
.c-name{font-size:1em;font-weight:800;color:var(--txt);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-.3px;transition:color .3s,text-shadow .3s}
.card:hover .c-name{color:var(--c,var(--acc2));text-shadow:0 0 24px color-mix(in srgb,var(--c,var(--acc)) 42%,transparent)}
.c-meta-row{display:flex;align-items:center;gap:.35em;flex-wrap:wrap}
.type-badge{font-family:var(--mono);font-size:.6em;color:var(--c,var(--acc2));display:flex;align-items:center;gap:.25em;opacity:.88}
.type-badge i{font-size:.45em}
.status-pill{display:inline-flex;align-items:center;gap:.28em;font-family:var(--mono);font-size:.58em;font-weight:600;padding:.15em .52em .15em .36em;border-radius:20px;letter-spacing:.1px;user-select:none;transition:filter .2s}
.card:hover .status-pill{filter:brightness(1.15)}
.sp-dot{width:.46em;height:.46em;border-radius:50%;flex-shrink:0;position:relative}
.sp-active{background:rgba(0,229,170,.09);color:#00e5aa;border:1px solid rgba(0,229,170,.26)}
.sp-active .sp-dot{background:#00e5aa;box-shadow:0 0 7px rgba(0,229,170,.95);animation:breathG 2.2s ease-in-out infinite}
@keyframes breathG{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.45);opacity:.18}}
.sp-wip{background:rgba(255,64,200,.09);color:#ff40c8;border:1px solid rgba(255,64,200,.26)}
.sp-wip .sp-dot{background:#ff40c8;box-shadow:0 0 7px rgba(255,64,200,.9);animation:breathP 1.3s ease-in-out infinite}
@keyframes breathP{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.45);opacity:.16}}
.sp-paused{background:rgba(255,224,64,.08);color:#ffe040;border:1px solid rgba(255,224,64,.22)}
.sp-paused .sp-dot{background:#ffe040;box-shadow:0 0 6px rgba(255,224,64,.7);animation:breathA 3s ease-in-out infinite}
@keyframes breathA{0%,100%{opacity:1}50%{opacity:.22}}
.sp-done{background:rgba(11,135,145,.1);color:var(--acc2);border:1px solid rgba(11,135,145,.28)}
.sp-done .sp-dot{background:var(--acc2);box-shadow:0 0 5px rgba(0,212,224,.6);opacity:.9}
.sp-archived{background:rgba(100,116,139,.07);color:#64748b;border:1px solid rgba(100,116,139,.15)}
.sp-archived .sp-dot{background:#64748b;opacity:.45}
.new-badge{font-family:var(--mono);font-size:.56em;font-weight:700;color:var(--acc2);letter-spacing:.6px;animation:newPulse 2.4s ease-in-out infinite}
@keyframes newPulse{0%,100%{opacity:1;text-shadow:none}50%{opacity:.5;text-shadow:0 0 12px var(--acc2)}}

.c-desc{
  font-size:.78em;color:var(--txt2);line-height:1.68;
  display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;
  overflow:hidden;font-weight:400;
  padding-left:.58em;
  border-left:2px solid color-mix(in srgb,var(--c,var(--acc)) 32%,transparent);
  margin-top:.1em;
  transition:border-color .3s,color .3s
}
.card:hover .c-desc{color:var(--txt);border-left-color:color-mix(in srgb,var(--c,var(--acc)) 55%,transparent)}

.c-tags{display:flex;flex-wrap:wrap;gap:.26em;margin-top:auto;padding-top:.35em}
.ctag{
  padding:.18em .48em;border-radius:.38em;
  font-family:var(--mono);font-size:.58em;
  background:color-mix(in srgb,var(--c,var(--acc)) 10%,var(--acc4));
  color:color-mix(in srgb,var(--c,var(--acc)) 60%,var(--acc2));
  border:1px solid color-mix(in srgb,var(--c,var(--acc)) 24%,rgba(124,140,255,.15));
  transition:all .24s cubic-bezier(.34,1.56,.64,1)
}
.ctag:hover{
  background:color-mix(in srgb,var(--c,var(--acc)) 22%,var(--acc3));
  transform:translateY(-2px) scale(1.1);
  box-shadow:0 4px 12px color-mix(in srgb,var(--c,var(--acc)) 24%,transparent)
}
.c-stars{display:flex;gap:.18em;margin-top:auto;padding-top:.3em}
.star{font-size:.72em;color:var(--bdr2);transition:color .16s,transform .22s cubic-bezier(.34,1.56,.64,1),filter .16s}
.star.on{color:var(--ylw);filter:drop-shadow(0 0 4px rgba(251,191,36,.55))}
.star:hover{transform:scale(1.35) rotate(-10deg)}

.card-foot{
  display:flex;align-items:center;padding:.5em 1em;
  border-top:1px solid rgba(11,135,145,.07);
  background:rgba(0,0,0,.2);
  transition:background .28s;position:relative;z-index:1
}
.card:hover .card-foot{background:rgba(0,0,0,.28)}
.cf-m{display:flex;align-items:center;gap:.3em;font-family:var(--mono);font-size:.6em;color:var(--txt3);transition:color .22s}
.cf-m i{font-size:.8em}
.cf-m+.cf-m{margin-left:.65em}
.card:hover .cf-m{color:var(--txt2)}
.cf-sp{flex:1}
.qas{display:flex;gap:.24em;opacity:0;transform:translateY(4px) scale(.96);transition:opacity .26s,transform .32s cubic-bezier(.34,1.56,.64,1)}
.card:hover .qas{opacity:1;transform:translateY(0) scale(1)}
.qa-btn{padding:.25em .48em;border-radius:.38em;font-family:var(--mono);font-size:.6em;font-weight:500;border:1px solid var(--bdr);background:var(--glass);color:var(--txt3);display:flex;align-items:center;gap:.22em;transition:all .22s cubic-bezier(.34,1.56,.64,1);cursor:pointer}
.qa-btn:hover{background:var(--glass2);color:var(--txt);transform:translateY(-2px) scale(1.08);box-shadow:0 5px 12px rgba(0,0,0,.3)}
.qa-btn.vsc:hover{border-color:#0078d4;color:#0078d4;background:rgba(0,120,212,.11);box-shadow:0 4px 12px rgba(0,120,212,.18)}
.qa-btn.trm:hover{border-color:var(--grn);color:var(--grn);background:rgba(52,211,153,.11);box-shadow:0 4px 12px rgba(52,211,153,.18)}
.info-btn{color:var(--acc2)!important}

/* Compact view */
.compact .c-desc,.compact .c-tags,.compact .c-stars{display:none}
.compact .card-body{padding:0}
.compact .card-top{padding:.72em .85em;min-height:unset}
.compact .c-thumb{width:2.4em;height:2.4em;font-size:1em;margin-top:0}
.compact .card-foot{padding:.42em .85em}

/* List view */
.list .card{flex-direction:row;align-items:center;min-height:unset;border-left-width:2px}
.list .card-body{flex:1;padding:.62em .9em;display:flex;align-items:center;gap:.7em;position:relative}
.list .card-top{position:static;display:flex;flex-direction:row;align-items:center;gap:.6em;min-height:unset;padding:0;flex:1}
.list .c-thumb{width:2.15em;height:2.15em;font-size:.95em;flex-shrink:0;margin-top:0}
.list .card:hover .c-thumb{transform:scale(1.1) rotate(-6deg)}
.list .c-info{flex:1;gap:.1em}
.list .c-desc,.list .c-tags,.list .c-stars{display:none}
.list .card-foot{padding:0 .8em;border-top:none;border-left:1px solid rgba(11,135,145,.1);min-width:12em;background:none;position:static}
.list .card-acts{position:static;opacity:1;transform:none;margin-right:4px}
.list .qas{opacity:1;transform:none}
.list .card:hover{transform:none;border-left-color:color-mix(in srgb,var(--c,var(--acc)) 80%,transparent);box-shadow:inset 3px 0 0 color-mix(in srgb,var(--c,var(--acc)) 75%,transparent),0 4px 16px rgba(0,0,0,.22)}

.empty{text-align:center;padding:70px 20px;color:var(--txt3)}
.empty i{font-size:2.2em;display:block;margin-bottom:12px;opacity:.2}
.empty p{font-family:var(--mono);font-size:.78em}

/* ── Grid section container ────────────────────────────── */
.grid-section{
  background:rgba(3,9,12,.62);
  border:1px solid rgba(11,135,145,.07);
  border-radius:20px;
  padding:18px;
  margin-bottom:22px;
  box-shadow:0 4px 32px rgba(0,0,0,.28),inset 0 1px 0 rgba(11,135,145,.06)
}

/* ═══════════════════════════════════════════
   DETAIL PANEL
═══════════════════════════════════════════ */
#dp{position:fixed;right:-420px;top:0;width:400px;height:100vh;background:var(--bg2);border-left:1px solid var(--bdr2);z-index:450;transition:right .38s cubic-bezier(.34,1.56,.64,1);display:flex;flex-direction:column;box-shadow:-18px 0 56px rgba(0,0,0,.5)}
#dp.open{right:0}
.dp-head{padding:14px 17px 11px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:11px}
.dp-title{font-weight:700;font-size:.9em;flex:1;letter-spacing:-.2px}
.dp-close{width:25px;height:25px;background:var(--glass);border:1px solid var(--bdr);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--txt3);font-size:.76em;transition:all .15s;cursor:pointer}
.dp-close:hover{background:var(--glass2);color:var(--txt)}
.dp-body{flex:1;overflow-y:auto;padding:16px}
.dp-body::-webkit-scrollbar{width:2px}
.dp-hero{display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:13px;background:var(--glass);border:1px solid var(--bdr);border-radius:11px}
.dp-ico{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3em;flex-shrink:0}
.dp-pname{font-size:1em;font-weight:800;letter-spacing:-.3px}
.dp-ptype{font-family:var(--mono);font-size:.65em;color:var(--txt3);margin-top:2px}
.dp-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-bottom:16px}
.dp-stat{background:var(--glass);border:1px solid var(--bdr);border-radius:9px;padding:8px 5px;text-align:center}
.dp-sv{font-family:var(--mono);font-size:1em;font-weight:500;color:var(--txt);line-height:1}
.dp-sl{font-family:var(--mono);font-size:.54em;color:var(--txt3);margin-top:2px;text-transform:uppercase;letter-spacing:.4px}
.dp-sec{font-family:var(--mono);font-size:.57em;font-weight:600;color:var(--txt3);letter-spacing:1.6px;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.dp-sec i{color:var(--acc2);font-size:.95em}
.dp-sec::after{content:'';flex:1;height:1px;background:var(--bdr)}
.dp-block{margin-bottom:18px}

/* Note editor */
.note-editor-wrap{border-radius:11px;overflow:hidden;border:1px solid var(--bdr);transition:border-color .2s,box-shadow .2s;background:var(--bg3)}
.note-editor-wrap:focus-within{border-color:rgba(109,127,255,.5);box-shadow:0 0 0 3px rgba(109,127,255,.1)}
.note-tb{display:flex;align-items:center;justify-content:space-between;padding:9px 13px;background:rgba(0,0,0,.22);border-bottom:1px solid var(--bdr)}
.note-tb-l{display:flex;align-items:center;gap:7px;font-family:var(--mono);font-size:.6em;color:var(--txt2);font-weight:500}
.note-tb-l i{color:var(--acc2);font-size:1em;width:12px}
.note-ts{font-family:var(--mono);font-size:.56em;color:var(--txt3);background:var(--bg4);padding:2px 6px;border-radius:5px;border:1px solid var(--bdr)}
.note-ta{display:block;width:100%;background:transparent;border:none;outline:none;padding:13px 15px;color:var(--txt);font-family:var(--mono);font-size:.76em;line-height:1.95;min-height:120px;resize:none;letter-spacing:.05px}
.note-ta::placeholder{color:var(--txt3);font-style:italic}
.note-foot{display:flex;align-items:center;justify-content:space-between;padding:7px 13px;border-top:1px solid var(--bdr);background:rgba(0,0,0,.18)}
.note-chars{font-family:var(--mono);font-size:.57em;color:var(--txt3);display:flex;align-items:center;gap:5px}
.note-saved{font-family:var(--mono);font-size:.58em;color:var(--grn);display:flex;align-items:center;gap:4px;opacity:0;transition:opacity .3s;pointer-events:none}
.note-saved.show{opacity:1}
.note-save-btn{padding:5px 11px;background:var(--acc);color:#fff;border:none;border-radius:6px;font-family:var(--ui);font-size:.68em;font-weight:700;transition:all .15s;cursor:pointer}
.note-save-btn:hover{filter:brightness(1.12);transform:translateY(-1px)}

/* Todos */
.todo-list{display:flex;flex-direction:column;gap:4px;margin-bottom:8px}
.todo-item{display:flex;align-items:center;gap:7px;padding:7px 10px;background:var(--bg3);border:1px solid var(--bdr);border-radius:7px;font-size:.77em;font-weight:500;transition:all .15s;cursor:pointer}
.todo-item:hover{border-color:var(--bdr2)}
.todo-cb{width:13px;height:13px;border-radius:3px;border:1.5px solid var(--bdr2);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;font-size:.58em}
.todo-item.done .todo-cb{background:var(--grn);border-color:var(--grn);color:#fff}
.todo-item.done .todo-t{text-decoration:line-through;color:var(--txt3)}
.todo-t{flex:1}
.todo-del{color:var(--txt3);font-size:.78em;transition:color .13s;cursor:pointer}
.todo-del:hover{color:var(--red)}
.todo-add{display:flex;gap:5px;margin-top:4px}
.todo-inp{flex:1;background:var(--bg3);border:1px solid var(--bdr);border-radius:7px;padding:6px 9px;color:var(--txt);font-family:var(--mono);font-size:.72em;outline:none;transition:all .2s}
.todo-inp:focus{border-color:var(--acc)}
.todo-inp::placeholder{color:var(--txt3)}
.todo-add-btn{padding:6px 10px;background:var(--acc);color:#fff;border:none;border-radius:7px;font-family:var(--mono);font-size:.72em;font-weight:600;transition:all .15s;cursor:pointer}
.todo-add-btn:hover{filter:brightness(1.1)}

.dp-foot{padding:12px 17px;border-top:1px solid var(--bdr);display:flex;gap:5px;flex-wrap:wrap}
.dp-foot .btn{font-size:.7em;padding:6px 10px}

/* ═══════════════════════════════════════════
   SPOTLIGHT
═══════════════════════════════════════════ */
#spotlight{position:fixed;inset:0;background:rgba(0,0,0,.82);backdrop-filter:blur(18px);z-index:700;display:none;align-items:flex-start;justify-content:center;padding-top:13vh}
#spotlight.open{display:flex}
.sp-box{width:580px;max-width:93vw;background:var(--bg3);border:1px solid var(--bdr2);border-radius:16px;overflow:hidden;box-shadow:var(--shadow);animation:spIn .18s cubic-bezier(.34,1.56,.64,1)}
@keyframes spIn{from{opacity:0;transform:scale(.9) translateY(-14px)}to{opacity:1;transform:scale(1) translateY(0)}}
.sp-top{display:flex;align-items:center;gap:10px;padding:12px 15px;border-bottom:1px solid var(--bdr)}
.sp-top i{color:var(--acc);font-size:.92em;flex-shrink:0}
#sp-inp{flex:1;background:transparent;border:none;outline:none;color:var(--txt);font-family:var(--mono);font-size:.86em}
#sp-inp::placeholder{color:var(--txt3)}
.sp-list{max-height:310px;overflow-y:auto;padding:4px}
.sp-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:7px;transition:all .11s;cursor:pointer}
.sp-item:hover,.sp-item.hi{background:var(--acc3)}
.sp-thumb{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.84em;flex-shrink:0}
.sp-info{flex:1;min-width:0}
.sp-name{font-weight:600;font-size:.82em;color:var(--txt);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sp-meta{font-family:var(--mono);font-size:.6em;color:var(--txt3);margin-top:1px}
.sp-acts{display:flex;gap:3px;flex-shrink:0}
.sp-act{padding:2px 5px;border-radius:4px;font-family:var(--mono);font-size:.57em;background:var(--glass2);border:1px solid var(--bdr);color:var(--txt3);transition:all .11s;cursor:pointer}
.sp-act:hover{color:var(--txt)}
.sp-divider{padding:5px 10px 2px;font-family:var(--mono);font-size:.55em;color:var(--txt3);letter-spacing:1.5px;text-transform:uppercase}
.sp-foot{padding:7px 13px;border-top:1px solid var(--bdr);display:flex;gap:11px;font-family:var(--mono);font-size:.58em;color:var(--txt3)}
.sp-hint{display:flex;align-items:center;gap:4px}
.sp-hint kbd{background:var(--bg5);border:1px solid var(--bdr);border-radius:3px;padding:1px 4px;font-size:.9em}

/* ═══════════════════════════════════════════
   RANDOM OVERLAY
═══════════════════════════════════════════ */
#rnd-overlay{position:fixed;inset:0;z-index:800;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.88);backdrop-filter:blur(20px)}
#rnd-overlay.open{display:flex}
.rnd-card{background:var(--bg2);border:1px solid var(--bdr2);border-radius:20px;padding:34px;text-align:center;max-width:350px;width:90%;animation:rndIn .46s cubic-bezier(.34,1.56,.64,1)}
@keyframes rndIn{from{opacity:0;transform:scale(.62) rotate(-8deg)}to{opacity:1;transform:scale(1) rotate(0)}}
.rnd-ico{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.9em;margin:0 auto 14px}
.rnd-name{font-size:1.32em;font-weight:800;letter-spacing:-.4px;margin-bottom:4px}
.rnd-type{font-family:var(--mono);font-size:.7em;color:var(--txt3);margin-bottom:18px}
.rnd-acts{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}

/* ═══════════════════════════════════════════
   MODALS
═══════════════════════════════════════════ */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(12px);z-index:600;align-items:center;justify-content:center}
.overlay.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--bdr2);border-radius:16px;padding:24px;max-width:480px;width:93%;animation:mIn .24s cubic-bezier(.34,1.56,.64,1);box-shadow:var(--shadow);max-height:92vh;overflow-y:auto}
@keyframes mIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.modal::-webkit-scrollbar{width:3px}
.m-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.m-title{font-size:1em;font-weight:800;display:flex;align-items:center;gap:8px;letter-spacing:-.2px}
.m-title i{color:var(--acc2);font-size:.88em}
.m-close{width:25px;height:25px;background:var(--glass);border:1px solid var(--bdr);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--txt3);font-size:.76em;transition:all .15s;cursor:pointer}
.m-close:hover{background:var(--glass2);color:var(--txt)}
.field{margin-bottom:13px}
.field label{display:block;font-family:var(--mono);font-size:.58em;color:var(--txt3);margin-bottom:5px;letter-spacing:.5px;text-transform:uppercase;font-weight:600}
.field input,.field textarea,.field select{width:100%;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;padding:8px 11px;color:var(--txt);font-family:var(--mono);font-size:.78em;outline:none;transition:all .2s}
.field input:focus,.field textarea:focus,.field select:focus{border-color:var(--acc);background:var(--acc4)}
.field input::placeholder{color:var(--txt3)}
.field textarea{resize:vertical;min-height:65px;line-height:1.7}
.field select{appearance:none}
.field select option{background:var(--bg3)}
.col-grid,.ico-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:5px}
.col-sw{aspect-ratio:1;border-radius:7px;border:2px solid transparent;transition:all .15s;cursor:pointer}
.col-sw:hover{transform:scale(1.12)}
.col-sw.sel{border-color:#fff;box-shadow:0 0 0 2px rgba(255,255,255,.12)}
.ico-sw{aspect-ratio:1;border-radius:7px;border:2px solid var(--bdr);background:var(--glass);display:flex;align-items:center;justify-content:center;font-size:.92em;color:var(--txt3);transition:all .15s;cursor:pointer}
.ico-sw:hover{border-color:var(--acc);color:var(--txt)}
.ico-sw.sel{border-color:var(--acc);background:var(--acc3);color:var(--txt)}
.tag-wrap{display:flex;flex-wrap:wrap;gap:4px;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;padding:6px;transition:border-color .2s;min-height:36px}
.tag-wrap:focus-within{border-color:var(--acc)}
.tw-tag{display:inline-flex;align-items:center;gap:5px;padding:2px 6px;border-radius:5px;background:var(--acc3);border:1px solid rgba(124,140,255,.22);color:var(--acc2);font-family:var(--mono);font-size:.64em}
.tag-del{color:var(--txt3);font-size:.8em;transition:color .13s;cursor:pointer}
.tag-del:hover{color:var(--red)}
.tag-field{background:transparent;border:none;outline:none;color:var(--txt);font-family:var(--mono);font-size:.76em;min-width:80px;flex:1}
.tag-field::placeholder{color:var(--txt3)}
.up-zone{border:2px dashed var(--bdr);border-radius:9px;padding:18px;text-align:center;color:var(--txt3);font-family:var(--mono);font-size:.71em;transition:all .17s;cursor:pointer}
.up-zone:hover{border-color:var(--acc);background:var(--acc4);color:var(--acc2)}
.up-zone i{font-size:1.5em;display:block;margin-bottom:6px}
.up-zone.has{padding:0;border:none;overflow:hidden}
.up-zone img{width:100%;height:95px;object-fit:cover;border-radius:8px}
.r-row{display:flex;gap:4px}
.r-star{font-size:1.22em;color:var(--bdr2);transition:color .11s;cursor:pointer}
.r-star.on{color:var(--ylw)}
.form-btns{display:flex;gap:6px;margin-top:4px}
.submit-btn{flex:1;padding:9px;border-radius:8px;background:var(--acc);color:#fff;font-family:var(--ui);font-weight:700;font-size:.78em;border:none;transition:all .15s;cursor:pointer}
.submit-btn:hover{filter:brightness(1.08);transform:translateY(-1px)}
.cancel-btn{padding:9px 13px;border-radius:8px;background:var(--glass);color:var(--txt2);font-family:var(--ui);font-size:.78em;border:1px solid var(--bdr);transition:all .15s;cursor:pointer}
.cancel-btn:hover{background:var(--glass2);color:var(--txt)}

/* ═══════════════════════════════════════════
   DASHBOARD SETTINGS MODAL
═══════════════════════════════════════════ */
.modal-wide{max-width:600px!important}
.stabs{display:flex;gap:2px;background:var(--bg3);border:1px solid var(--bdr);border-radius:10px;padding:3px;margin-bottom:18px}
.stab{flex:1;padding:7px 4px;border-radius:7px;font-size:.7em;font-weight:600;text-align:center;color:var(--txt3);transition:all .15s;display:flex;align-items:center;justify-content:center;gap:5px;cursor:pointer}
.stab i{font-size:.8em}
.stab:hover{color:var(--txt2);background:var(--glass2)}
.stab.on{background:var(--acc);color:#fff}
.spanel{display:none;animation:fadeup .14s ease}
.spanel.on{display:block}
.srow{display:flex;align-items:center;gap:13px;padding:10px 0;border-bottom:1px solid var(--bdr)}
.srow:last-child{border-bottom:none}
.srow-l{flex:1;min-width:0}
.srow-title{font-size:.8em;font-weight:600;color:var(--txt);letter-spacing:-.15px;display:flex;align-items:center;gap:7px}
.srow-title i{color:var(--acc2);font-size:.8em}
.srow-desc{font-size:.66em;color:var(--txt3);margin-top:2px;line-height:1.5}
.toggle-wrap{position:relative;width:36px;height:20px;flex-shrink:0}
.toggle-inp{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{position:absolute;inset:0;background:var(--bg5);border:1.5px solid var(--bdr2);border-radius:20px;transition:all .2s;cursor:pointer}
.toggle-slider::after{content:'';position:absolute;width:14px;height:14px;left:2px;top:1px;background:var(--txt3);border-radius:50%;transition:all .2s;box-shadow:0 1px 4px rgba(0,0,0,.4)}
.toggle-inp:checked~.toggle-slider{background:var(--acc);border-color:var(--acc)}
.toggle-inp:checked~.toggle-slider::after{transform:translateX(16px);background:#fff}
.seg-ctrl{display:flex;background:var(--bg3);border:1.5px solid var(--bdr);border-radius:8px;padding:2px;gap:1px;flex-shrink:0}
.seg-btn{flex:1;padding:5px 8px;border-radius:6px;font-family:var(--mono);font-size:.66em;font-weight:600;color:var(--txt3);text-align:center;transition:all .13s;white-space:nowrap;cursor:pointer}
.seg-btn:hover{color:var(--txt2);background:var(--glass)}
.seg-btn.on{background:var(--acc);color:#fff}
.s-select{background:var(--bg3);border:1.5px solid var(--bdr);border-radius:8px;padding:6px 10px;color:var(--txt);font-family:var(--mono);font-size:.73em;outline:none;transition:border-color .17s;min-width:125px;cursor:pointer}
.s-select:focus{border-color:var(--acc);background:var(--acc4)}
.s-select option{background:var(--bg3)}
.tpv-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:5px;width:100%}
.tpv{border-radius:9px;overflow:hidden;border:2px solid var(--bdr);transition:all .17s;position:relative;aspect-ratio:16/10;cursor:pointer}
.tpv:hover{transform:scale(1.04);border-color:var(--bdr2)}
.tpv.sel{border-color:var(--acc);box-shadow:0 0 0 2px rgba(109,127,255,.3)}
.tpv-inner{width:100%;height:100%;padding:4px;display:flex;flex-direction:column;gap:2px}
.tpv-bar{height:2px;border-radius:2px}
.tpv-lines{display:flex;flex-direction:column;gap:2px;flex:1;padding-top:2px}
.tpv-line{height:2px;border-radius:2px;opacity:.28}
.tpv-name{position:absolute;bottom:2px;left:0;right:0;text-align:center;font-size:7px;font-family:var(--mono);color:rgba(255,255,255,.55);text-transform:capitalize}
.tpv.sel .tpv-name{color:rgba(255,255,255,.9);font-weight:700}
.accent-palette{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px}
.acc-sw{width:20px;height:20px;border-radius:6px;border:2.5px solid transparent;transition:all .15s;cursor:pointer}
.acc-sw:hover{transform:scale(1.2)}
.acc-sw.sel{border-color:#fff;box-shadow:0 0 0 2px rgba(255,255,255,.18);transform:scale(1.15)}
.font-card{background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;padding:9px 11px;margin-top:7px}
.font-card .fc-big{font-size:.92em;font-weight:700;color:var(--txt)}
.font-card .fc-sm{font-size:.65em;color:var(--txt3);margin-top:2px;font-family:var(--mono)}
.dash-save-bar{display:flex;align-items:center;gap:9px;padding:11px 13px;background:var(--acc3);border:1px solid rgba(109,127,255,.2);border-radius:10px;margin-top:16px}
.dash-save-bar i{color:var(--acc2);font-size:1em;flex-shrink:0}
.dash-save-bar span{font-size:.72em;color:var(--txt2);flex:1}

/* ═══════════════════════════════════════════
   CONTEXT MENU
═══════════════════════════════════════════ */
#ctx-menu{position:fixed;display:none;background:var(--bg3);border:1px solid var(--bdr2);border-radius:11px;padding:4px;z-index:1000;min-width:195px;box-shadow:var(--shadow);animation:ctxIn .12s ease}
@keyframes ctxIn{from{opacity:0;transform:scale(.93)}to{opacity:1;transform:scale(1)}}
.ctx-lbl{padding:5px 9px 3px;font-family:var(--mono);font-size:.55em;color:var(--txt3);letter-spacing:1.5px;text-transform:uppercase}
.ctx-item{display:flex;align-items:center;gap:8px;padding:6px 9px;border-radius:6px;color:var(--txt2);font-size:.77em;font-weight:500;transition:all .11s;cursor:pointer}
.ctx-item i{width:12px;font-size:.84em;color:var(--txt3);flex-shrink:0}
.ctx-item:hover{background:var(--acc3);color:var(--txt)}
.ctx-item:hover i{color:var(--acc2)}
.ctx-div{height:1px;background:var(--bdr);margin:3px 0}
.ctx-danger:hover{background:rgba(248,113,113,.1);color:var(--red)}
.ctx-danger:hover i{color:var(--red)}

/* ═══════════════════════════════════════════
   TOAST
═══════════════════════════════════════════ */
#toast{
  position:fixed;bottom:18px;right:18px;background:var(--bg3);border:1px solid rgba(124,140,255,.26);
  border-radius:9px;padding:9px 13px;display:flex;align-items:center;gap:8px;z-index:2000;
  font-family:var(--mono);font-size:.72em;box-shadow:var(--shadow);
  transform:translateY(52px);opacity:0;transition:all .28s cubic-bezier(.34,1.56,.64,1);
  pointer-events:none;max-width:270px
}
#toast.show{transform:translateY(0);opacity:1}
#toast i{font-size:.92em;flex-shrink:0}
#toast.ok i{color:var(--grn)}
#toast.err i{color:var(--red)}
#toast.err{border-color:rgba(248,113,113,.28)}
#toast.info i{color:var(--acc2)}

/* Back link */
.back-link{display:flex;align-items:center;gap:6px;color:var(--txt3);text-decoration:none;font-family:var(--mono);font-size:.7em;font-weight:500;transition:color .15s;padding:6px 9px;border-radius:7px;background:var(--glass);border:1px solid var(--bdr)}
.back-link:hover{color:var(--txt)}

/* ═══════════════════════════════════════════
   MOBILE COMPONENTS
═══════════════════════════════════════════ */
.mob-menu-btn{
  display:none;width:34px;height:34px;flex-shrink:0;
  background:var(--glass);border:1px solid var(--bdr);border-radius:8px;
  align-items:center;justify-content:center;
  color:var(--txt2);font-size:.85em;cursor:pointer;transition:all .16s
}
.mob-menu-btn:hover{background:var(--glass2);color:var(--txt)}

.sidebar-backdrop{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.65);backdrop-filter:blur(4px);z-index:499
}
.sidebar-backdrop.show{display:block}

.mob-nav{
  display:none;position:fixed;bottom:0;left:0;right:0;
  height:60px;background:var(--bg2);border-top:1px solid var(--bdr);
  z-index:400;align-items:center;justify-content:space-around;
  padding:0 4px;padding-bottom:env(safe-area-inset-bottom,0px)
}
.mob-nb{
  display:flex;flex-direction:column;align-items:center;gap:2px;
  color:var(--txt3);font-size:.5em;padding:5px 12px;border-radius:10px;
  transition:all .16s;cursor:pointer;user-select:none;flex:1;text-align:center
}
.mob-nb i{font-size:1.55em;display:block}
.mob-nb.on{color:var(--acc2)}
.mob-nb.new-nb{
  background:var(--acc);color:#fff;border-radius:14px;
  padding:8px 16px;flex:none;font-size:.56em
}
.mob-nb.new-nb i{font-size:1.4em}

/* ═══════════════════════════════════════════
   RESPONSIVE — TABLET
═══════════════════════════════════════════ */
@media(min-width:821px) and (max-width:1100px){
  :root{--sidebar:220px}
  .grid{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
}

/* ═══════════════════════════════════════════
   RESPONSIVE — MOBILE
═══════════════════════════════════════════ */
@media(max-width:820px){
  /* Layout */
  .app{grid-template-columns:1fr}

  /* Sidebar → slide-in drawer */
  .sidebar{
    position:fixed;left:-290px;top:0;bottom:0;width:280px;height:100vh;
    z-index:500;display:flex;
    transition:left .32s cubic-bezier(.34,1.56,.64,1);
    box-shadow:8px 0 48px rgba(0,0,0,.8)
  }
  .sidebar.mob-open{left:0}

  /* Show mobile-only elements */
  .mob-menu-btn{display:flex}
  .mob-nav{display:flex}

  /* Topbar */
  .topbar{padding:8px 10px;gap:6px}
  .search-wrap{max-width:none}
  .view-toggle,.sort-wrap{display:none}

  /* Content — bottom padding for mobile nav */
  .content{padding:12px 12px 72px}

  /* Grid */
  .grid{grid-template-columns:1fr;gap:10px}
  [data-csize="xs"] .grid:not(.list),
  [data-csize="sm"] .grid:not(.list),
  [data-csize="md"] .grid:not(.list),
  [data-csize="lg"] .grid:not(.list),
  [data-csize="xl"] .grid:not(.list){grid-template-columns:1fr!important;gap:10px!important}
  .grid-section{padding:12px;border-radius:14px;margin-bottom:14px}

  /* Cards — always show actions for touch */
  .card-acts{opacity:1!important;transform:none!important}
  .qas{opacity:1!important;transform:none!important}
  .ca-btn{width:2em;height:2em}
  .card:hover{transform:none}

  /* Detail panel → bottom sheet */
  #dp{
    width:100%!important;right:0!important;left:0;
    top:auto!important;bottom:-100vh;height:88vh;
    border-left:none;border-top:1px solid var(--bdr2);
    border-radius:20px 20px 0 0;
    transition:bottom .38s cubic-bezier(.34,1.56,.64,1)!important
  }
  #dp.open{bottom:0!important;right:0!important}

  /* Modals */
  .modal{width:95vw;max-width:none;margin:0}
  .sp-box{width:96vw}

  /* Typography */
  .page-title{font-size:1.2em}
  .page-sub{font-size:.62em}
  .logo-name{font-size:1em}
}
</style>
</head>
<body>
<canvas id="cv"></canvas>

<div class="app">
<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark"><i class="fas fa-code"></i></div>
    <div>
      <div class="logo-name">Code<span>Hub</span></div>
      <div class="logo-env">localhost · PHP <?=PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION?></div>
    </div>
  </div>

  <div>
    <div class="stats-grid">
      <div class="stat-cell"><div class="stat-val"><?=$total?></div><div class="stat-lbl">Projects</div></div>
      <div class="stat-cell accent"><div class="stat-val"><?=$pinned?></div><div class="stat-lbl">Pinned</div></div>
      <div class="stat-cell"><div class="stat-val"><?=$recent?></div><div class="stat-lbl">New</div></div>
      <div class="stat-cell <?=$pending>0?'accent':''?>">
        <div class="stat-val" <?=$pending>0?'style="color:var(--warm2)"':''?>><?=$pending?></div>
        <div class="stat-lbl">Todos</div>
      </div>
    </div>
  </div>

  <div>
    <div class="s-label">Browse</div>
    <ul class="nav">
      <li class="nav-item on" data-f="all"><i class="fas fa-th-large"></i>All Projects<span class="cnt"><?=$total?></span></li>
      <li class="nav-item" data-f="pin"><i class="fas fa-thumbtack"></i>Pinned<span class="cnt"><?=$pinned?></span></li>
      <li class="nav-item" data-f="recent"><i class="fas fa-clock"></i>Recent<span class="cnt"><?=$recent?></span></li>
      <li class="nav-item" data-f="notes"><i class="fas fa-sticky-note"></i>Has Notes<span class="cnt"><?=$hasNotes?></span></li>
    </ul>
  </div>

  <?php
  $sts=['active'=>['grn','circle','Active'],'wip'=>['pnk','circle-notch','In Progress'],'paused'=>['ylw','pause','Paused'],'done'=>['acc2','check','Done'],'archived'=>['txt3','archive','Archived']];
  $visibleSt=array_filter($sts, fn($k)=>($stCounts[$k]??0)>0, ARRAY_FILTER_USE_KEY);
  if(!empty($visibleSt)):?>
  <div>
    <div class="s-label">Status</div>
    <ul class="nav">
      <?php foreach($visibleSt as $k=>[$col,$icon,$lbl]):$cnt=$stCounts[$k]??0;?>
      <li class="nav-item" data-f="st-<?=$k?>"><i class="fas fa-<?=$icon?>" style="color:var(--<?=$col?>)!important"></i><?=$lbl?><span class="cnt"><?=$cnt?></span></li>
      <?php endforeach;?>
    </ul>
  </div>
  <?php endif;?>

  <?php if(!empty($typeCounts)):?>
  <div>
    <div class="s-label">Stack</div>
    <div class="tag-cloud">
      <?php foreach($typeCounts as $t=>$c):?><span class="stag" data-type="<?=strtolower($t)?>"><?=e($t)?> <span style="opacity:.45"><?=$c?></span></span><?php endforeach;?>
    </div>
  </div>
  <?php endif;?>

  <?php if(!empty($allTags)):?>
  <div>
    <div class="s-label">Tags</div>
    <div class="tag-cloud">
      <?php foreach(array_slice($allTags,0,14,true) as $tag=>$c):?><span class="stag" data-tag="<?=e($tag)?>">#<?=e($tag)?></span><?php endforeach;?>
    </div>
  </div>
  <?php endif;?>

  <div>
    <div class="s-label">Actions</div>
    <ul class="nav">
      <li class="nav-item" onclick="openModal('m-create')"><i class="fas fa-folder-plus"></i>New Project</li>
      <li class="nav-item" onclick="openSpotlight()"><i class="fas fa-search"></i>Spotlight<span class="cnt">⌘K</span></li>
      <li class="nav-item" onclick="toggleBulk()"><i class="fas fa-check-square"></i>Bulk Select<span class="cnt">B</span></li>
      <li class="nav-item" onclick="doExport('json')"><i class="fas fa-download"></i>Export JSON</li>
      <li class="nav-item" onclick="doExport('csv')"><i class="fas fa-file-csv"></i>Export CSV</li>
    </ul>
  </div>

  <div>
    <div class="s-label" style="justify-content:space-between;margin-bottom:7px">
      <span>Theme</span>
      <span style="font-size:.75em;opacity:.55;font-family:var(--mono);font-weight:400;letter-spacing:0;text-transform:none" id="theme-name-lbl">dark</span>
    </div>
    <div class="theme-picker" id="theme-picker">
      <?php foreach(['dark','midnight','forest','rose','dusk','ocean','dracula','amber','neon','nord'] as $t):?>
        <div class="theme-dot" data-t="<?=$t?>" title="<?=ucfirst($t)?>"></div>
      <?php endforeach;?>
    </div>
  </div>

  <div>
    <div class="s-label">Settings</div>
    <ul class="nav">
      <li class="nav-item" onclick="openModal('m-dash')"><i class="fas fa-sliders-h"></i>Dashboard Settings</li>
    </ul>
  </div>

  <div class="sidebar-foot">
    <div class="live-badge"><div class="live-dot"></div>localhost · <?=date('H:i')?></div>
    <div style="font-family:var(--mono);font-size:.56em;color:var(--txt3);margin-top:3px"><?=date('D, M j Y')?></div>
  </div>
</aside>

<!-- ═══ MAIN ═══ -->
<div class="main">
  <header class="topbar">
    <button class="mob-menu-btn" onclick="toggleSidebar()" title="Menu"><i class="fas fa-bars"></i></button>
    <?php if($mostrarVolver):?><a href=".." class="back-link"><i class="fas fa-arrow-left"></i>Back</a><?php endif;?>
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
        <button class="btn btn-ghost" style="font-size:.68em;padding:4px 8px" onclick="doBulk('pin')"><i class="fas fa-thumbtack"></i>Pin</button>
        <button class="btn btn-ghost" style="font-size:.68em;padding:4px 8px" onclick="doBulk('done')"><i class="fas fa-check"></i>Done</button>
        <button class="btn btn-ghost" style="font-size:.68em;padding:4px 8px" onclick="doBulk('archive')"><i class="fas fa-archive"></i>Archive</button>
        <button class="btn btn-danger" style="font-size:.68em;padding:4px 8px" onclick="doBulk('delete')"><i class="fas fa-trash"></i>Delete</button>
        <button class="btn btn-ghost" style="font-size:.68em;padding:4px 8px;width:28px" onclick="clearBulk()"><i class="fas fa-times"></i></button>
      </div>
    </div>

    <?php $pinnedProjs=array_filter($projects,fn($p)=>$p['pin']);?>
    <?php if(!empty($pinnedProjs)):?>
    <div class="grid-section" id="sec-pinned">
      <div class="sec-head"><i class="fas fa-thumbtack" style="color:var(--ylw)"></i>Pinned</div>
      <div class="grid" id="grid-pinned">
        <?php foreach($pinnedProjs as $p) echo renderCard($p);?>
      </div>
    </div>
    <?php endif;?>

    <div class="grid-section">
      <div class="sec-head"><i class="fas fa-folder-open"></i>All Projects</div>
      <div class="grid" id="main-grid">
        <?php foreach($projects as $p) echo renderCard($p);?>
      </div>
      <?php if(!$total):?>
      <div class="empty">
        <i class="fas fa-folder-open"></i>
        <p>No projects yet — click <strong>New</strong> to create one!</p>
      </div>
      <?php endif;?>
    </div>
  </main>
</div>
</div><!-- /.app -->

<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebar()"></div>

<!-- ═══ MOBILE BOTTOM NAV ═══ -->
<nav class="mob-nav">
  <div class="mob-nb on" id="mob-all" onclick="document.querySelector('[data-f=all]').click();mobFilter(this)"><i class="fas fa-th-large"></i>All</div>
  <div class="mob-nb" id="mob-pin" onclick="document.querySelector('[data-f=pin]').click();mobFilter(this)"><i class="fas fa-thumbtack"></i>Pinned</div>
  <div class="mob-nb new-nb" onclick="openModal('m-create')"><i class="fas fa-plus"></i></div>
  <div class="mob-nb" id="mob-recent" onclick="document.querySelector('[data-f=recent]').click();mobFilter(this)"><i class="fas fa-clock"></i>Recent</div>
  <div class="mob-nb" onclick="toggleSidebar()"><i class="fas fa-bars"></i>Menu</div>
</nav>

<!-- ═══ DETAIL PANEL ═══ -->
<div id="dp">
  <div class="dp-head">
    <span class="dp-title" id="dp-title">Details</span>
    <div class="dp-close" onclick="closeDp()"><i class="fas fa-times"></i></div>
  </div>
  <div class="dp-body" id="dp-body"></div>
  <div class="dp-foot" id="dp-foot"></div>
</div>

<!-- ═══ SPOTLIGHT ═══ -->
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

<!-- ═══ RANDOM ═══ -->
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

<!-- ═══ MODAL: CREATE ═══ -->
<div class="overlay" id="m-create">
  <div class="modal">
    <div class="m-head">
      <div class="m-title"><i class="fas fa-folder-plus"></i>New Project</div>
      <div class="m-close" onclick="closeModal('m-create')"><i class="fas fa-times"></i></div>
    </div>
    <form id="f-create">
      <div class="field"><label>Project Name</label><input id="f-name" placeholder="my-project" required autofocus></div>
      <div class="field"><label>Starter Template</label>
        <select id="f-tpl">
          <option value="">Empty folder</option>
          <option value="html">HTML + CSS + JS</option>
          <option value="node">Node.js (package.json)</option>
          <option value="readme">README.md only</option>
        </select>
      </div>
      <div class="form-btns">
        <button type="submit" class="submit-btn"><i class="fas fa-check"></i>Create</button>
        <button type="button" class="cancel-btn" onclick="closeModal('m-create')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: RENAME ═══ -->
<div class="overlay" id="m-rename">
  <div class="modal">
    <div class="m-head">
      <div class="m-title"><i class="fas fa-pencil-alt"></i>Rename Project</div>
      <div class="m-close" onclick="closeModal('m-rename')"><i class="fas fa-times"></i></div>
    </div>
    <form id="f-rename">
      <input type="hidden" id="ren-old">
      <div class="field"><label>New Name</label><input id="ren-new" required autofocus></div>
      <div class="form-btns">
        <button type="submit" class="submit-btn"><i class="fas fa-check"></i>Rename</button>
        <button type="button" class="cancel-btn" onclick="closeModal('m-rename')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: NEW FILE ═══ -->
<div class="overlay" id="m-file">
  <div class="modal">
    <div class="m-head">
      <div class="m-title"><i class="fas fa-file-code"></i>New File</div>
      <div class="m-close" onclick="closeModal('m-file')"><i class="fas fa-times"></i></div>
    </div>
    <form id="f-file">
      <input type="hidden" id="ff-dir">
      <div class="field"><label>File Name</label><input id="ff-name" placeholder="index.html" required autofocus></div>
      <div class="form-btns">
        <button type="submit" class="submit-btn"><i class="fas fa-check"></i>Create</button>
        <button type="button" class="cancel-btn" onclick="closeModal('m-file')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: CUSTOMIZE ═══ -->
<div class="overlay" id="m-cust">
  <div class="modal" style="max-width:500px">
    <div class="m-head">
      <div class="m-title"><i class="fas fa-sliders-h"></i>Customize Project</div>
      <div class="m-close" onclick="closeModal('m-cust')"><i class="fas fa-times"></i></div>
    </div>
    <form id="f-cust">
      <input type="hidden" id="c-dir">
      <div class="field"><label>Description</label><textarea id="c-desc" placeholder="What does this project do?"></textarea></div>
      <div class="field"><label>Status</label>
        <select id="c-st">
          <option value="active">🟢 Active</option>
          <option value="wip">🔴 In Progress</option>
          <option value="paused">🟡 Paused</option>
          <option value="done">🔵 Done</option>
          <option value="archived">⚫ Archived</option>
        </select>
      </div>
      <div class="field"><label>Custom URL / Port</label><input id="c-url" placeholder="http://localhost:3000"></div>
      <div class="field"><label>Rating</label>
        <div class="r-row" id="r-row">
          <?php for($i=1;$i<=5;$i++):?><i class="fas fa-star r-star" data-v="<?=$i?>"></i><?php endfor;?>
        </div>
      </div>
      <div class="field"><label>Tags</label>
        <div class="tag-wrap" id="tag-wrap"><input class="tag-field" id="tag-fld" placeholder="add tag + Enter"></div>
      </div>
      <div class="field"><label>Accent Color</label><div class="col-grid" id="col-grid"></div></div>
      <div class="field"><label>Icon</label><div class="ico-grid" id="ico-grid"></div></div>
      <div class="field"><label>Thumbnail Image</label>
        <div class="up-zone" id="up-zone"><i class="fas fa-image"></i>Click to upload image<input type="file" id="img-fld" accept="image/*" style="display:none"></div>
      </div>
      <div class="form-btns">
        <button type="submit" class="submit-btn"><i class="fas fa-save"></i>Save Changes</button>
        <button type="button" class="cancel-btn" onclick="closeModal('m-cust')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: DASHBOARD SETTINGS ═══ -->
<div class="overlay" id="m-dash">
  <div class="modal modal-wide" style="max-height:88vh;overflow-y:auto;padding-bottom:18px">
    <div class="m-head" style="margin-bottom:14px">
      <div class="m-title"><i class="fas fa-sliders-h"></i>Dashboard Settings</div>
      <div class="m-close" onclick="closeModal('m-dash')"><i class="fas fa-times"></i></div>
    </div>
    <div class="stabs">
      <div class="stab on" data-tab="themes" onclick="switchTab(this,'themes')"><i class="fas fa-swatchbook"></i>Themes</div>
      <div class="stab" data-tab="appearance" onclick="switchTab(this,'appearance')"><i class="fas fa-palette"></i>Style</div>
      <div class="stab" data-tab="layout" onclick="switchTab(this,'layout')"><i class="fas fa-th-large"></i>Layout</div>
      <div class="stab" data-tab="effects" onclick="switchTab(this,'effects')"><i class="fas fa-magic"></i>Effects</div>
    </div>

    <!-- Themes tab -->
    <div class="spanel on" id="tab-themes">
      <div class="srow" style="flex-direction:column;align-items:flex-start">
        <div class="srow-title" style="margin-bottom:3px"><i class="fas fa-swatchbook"></i>Theme</div>
        <div class="srow-desc">Click any theme to apply instantly.</div>
        <div class="tpv-grid">
          <?php $themeData=['dark'=>['#07080f','#6d7fff'],'midnight'=>['#000','#00d4ff'],'forest'=>['#040a06','#4ade80'],'rose'=>['#0c0409','#fb7185'],'dusk'=>['#0c0618','#a78bfa'],'ocean'=>['#010d10','#2dd4bf'],'dracula'=>['#0f0f1a','#bd93f9'],'amber'=>['#0a0602','#fcd34d'],'neon'=>['#010102','#39ff14'],'nord'=>['#181c28','#88c0d0']];
          foreach($themeData as $tn=>[$bg,$ac]):?>
          <div class="tpv" data-t="<?=$tn?>" onclick="applyTheme('<?=$tn?>')">
            <div class="tpv-inner" style="background:<?=$bg?>">
              <div class="tpv-bar" style="background:<?=$ac?>"></div>
              <div class="tpv-lines">
                <div class="tpv-line" style="background:<?=$ac?>;width:80%"></div>
                <div class="tpv-line" style="background:<?=$ac?>;width:55%"></div>
              </div>
            </div>
            <div class="tpv-name"><?=$tn?></div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
      <div class="srow">
        <div class="srow-l">
          <div class="srow-title"><i class="fas fa-tint"></i>Accent Override</div>
          <div class="srow-desc">Override the accent color</div>
          <div class="accent-palette">
            <?php foreach(['#6d7fff','#818cf8','#a78bfa','#c084fc','#e879f9','#f472b6','#fb7185','#f97316','#f59e0b','#fbbf24','#84cc16','#34d399','#14b8a6','#38bdf8','#60a5fa','#e11d48'] as $ac):?>
            <div class="acc-sw" data-col="<?=$ac?>" style="background:<?=$ac?>" onclick="pickAccent('<?=$ac?>')"></div>
            <?php endforeach;?>
          </div>
          <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
            <input type="color" id="accent-custom" value="#6d7fff" style="width:28px;height:28px;padding:1px;border:1.5px solid var(--bdr);border-radius:6px;background:var(--bg3);cursor:pointer">
            <span style="font-family:var(--mono);font-size:.62em;color:var(--txt3)">Pick custom color</span>
            <button class="btn btn-ghost" style="font-size:.63em;padding:3px 8px;margin-left:auto" onclick="resetAccent()"><i class="fas fa-undo"></i> Reset</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Style tab -->
    <div class="spanel" id="tab-appearance">
      <div class="srow">
        <div class="srow-l">
          <div class="srow-title"><i class="fas fa-font"></i>UI Font</div>
          <div class="srow-desc">Typeface for the interface</div>
          <div class="font-card" id="font-card"><div class="fc-big">The quick brown fox jumps</div><div class="fc-sm">abcdefghijklmnopqrstuvwxyz</div></div>
        </div>
        <select class="s-select" id="font-pick" onchange="applyFont(this.value)" style="align-self:flex-start">
          <option value="DM Sans">DM Sans</option>
          <option value="Inter">Inter</option>
          <option value="Outfit">Outfit</option>
          <option value="Nunito">Nunito</option>
          <option value="Syne">Syne</option>
          <option value="Lexend">Lexend</option>
          <option value="Space Grotesk">Space Grotesk</option>
        </select>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-text-height"></i>Font Size</div></div>
        <div class="seg-ctrl">
          <div class="seg-btn" id="fs-sm" onclick="applyFsize('sm',this)">Small</div>
          <div class="seg-btn on" id="fs-md" onclick="applyFsize('md',this)">Medium</div>
          <div class="seg-btn" id="fs-lg" onclick="applyFsize('lg',this)">Large</div>
        </div>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-vector-square"></i>Corner Radius</div></div>
        <div class="seg-ctrl">
          <div class="seg-btn" id="r-sm" onclick="applyRound('sm',this)">■</div>
          <div class="seg-btn on" id="r-md" onclick="applyRound('md',this)">▢</div>
          <div class="seg-btn" id="r-lg" onclick="applyRound('lg',this)">◯</div>
          <div class="seg-btn" id="r-xl" onclick="applyRound('xl',this)">⬭</div>
        </div>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-arrows-alt-h"></i>Sidebar Width</div></div>
        <div class="seg-ctrl">
          <div class="seg-btn" id="sw-narrow" onclick="applySidebarW('narrow',this)">Narrow</div>
          <div class="seg-btn on" id="sw-normal" onclick="applySidebarW('normal',this)">Normal</div>
          <div class="seg-btn" id="sw-wide" onclick="applySidebarW('wide',this)">Wide</div>
        </div>
      </div>
    </div>

    <!-- Layout tab -->
    <div class="spanel" id="tab-layout">
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-th"></i>Default View</div></div>
        <div class="seg-ctrl">
          <div class="seg-btn on" id="dv-grid" onclick="applyDefaultView('grid',this)"><i class="fas fa-th"></i></div>
          <div class="seg-btn" id="dv-list" onclick="applyDefaultView('list',this)"><i class="fas fa-list"></i></div>
          <div class="seg-btn" id="dv-compact" onclick="applyDefaultView('compact',this)"><i class="fas fa-border-all"></i></div>
        </div>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-expand-alt"></i>Card Size</div></div>
        <div class="seg-ctrl">
          <div class="seg-btn" id="cs-xs" onclick="applyCardSize('xs',this)">XS</div>
          <div class="seg-btn" id="cs-sm" onclick="applyCardSize('sm',this)">S</div>
          <div class="seg-btn on" id="cs-md" onclick="applyCardSize('md',this)">M</div>
          <div class="seg-btn" id="cs-lg" onclick="applyCardSize('lg',this)">L</div>
          <div class="seg-btn" id="cs-xl" onclick="applyCardSize('xl',this)">XL</div>
        </div>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-compress-alt"></i>Card Spacing</div></div>
        <div class="seg-ctrl">
          <div class="seg-btn" id="den-comfortable" onclick="applyDensity('comfortable',this)">Airy</div>
          <div class="seg-btn on" id="den-cozy" onclick="applyDensity('cozy',this)">Cozy</div>
          <div class="seg-btn" id="den-tight" onclick="applyDensity('tight',this)">Tight</div>
        </div>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-compress"></i>Compact Cards</div><div class="srow-desc">Less padding, hidden descriptions</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="compact-tog" onchange="applyCompact(this.checked)"><span class="toggle-slider"></span></label>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-sort-amount-down"></i>Default Sort</div></div>
        <select class="s-select" id="sort-pick-dash" onchange="applyDefaultSort(this.value)">
          <option value="az">Name A–Z</option>
          <option value="za">Name Z–A</option>
          <option value="new">Most Recent</option>
          <option value="opens">Most Opened</option>
          <option value="rating">Top Rated</option>
        </select>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-chart-bar"></i>Stats Panel</div><div class="srow-desc">4-cell summary at top of sidebar</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="stats-show" checked onchange="applyStatsShow(this.checked)"><span class="toggle-slider"></span></label>
      </div>
    </div>

    <!-- Effects tab -->
    <div class="spanel" id="tab-effects">
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-film"></i>Card Animations</div><div class="srow-desc">Fade-in slide when cards appear</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="anim-tog" checked onchange="applyAnim(this.checked)"><span class="toggle-slider"></span></label>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-cube"></i>3D Card Tilt</div><div class="srow-desc">Perspective tilt on hover</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="tilt-tog" checked onchange="applyTilt(this.checked)"><span class="toggle-slider"></span></label>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-star"></i>Hover Glow</div><div class="srow-desc">Radial color glow on hover</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="glow-tog" checked onchange="applyGlow(this.checked)"><span class="toggle-slider"></span></label>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-network-wired"></i>Particle Background</div><div class="srow-desc">Animated dots in background</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="particles-tog" checked onchange="applyParticles(this.checked)"><span class="toggle-slider"></span></label>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-adjust"></i>Grain Texture</div><div class="srow-desc">Subtle film grain overlay</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="grain-tog" checked onchange="applyGrain(this.checked)"><span class="toggle-slider"></span></label>
      </div>
      <div class="srow">
        <div class="srow-l"><div class="srow-title"><i class="fas fa-glass-martini-alt"></i>Glass Sidebar</div><div class="srow-desc">Frosted glass effect on sidebar</div></div>
        <label class="toggle-wrap"><input class="toggle-inp" type="checkbox" id="sidebar-glass" onchange="applySidebarGlass(this.checked)"><span class="toggle-slider"></span></label>
      </div>
    </div>

    <div class="dash-save-bar">
      <i class="fas fa-bolt"></i>
      <span>All changes apply <strong>instantly</strong> and are saved in your browser.</span>
      <button class="btn btn-ghost" style="font-size:.68em;padding:4px 9px;flex-shrink:0" onclick="resetDashSettings()"><i class="fas fa-undo"></i> Reset</button>
    </div>
  </div>
</div>

<!-- ═══ CONTEXT MENU ═══ -->
<div id="ctx-menu">
  <div class="ctx-lbl" id="ctx-lbl">Project</div>
  <div class="ctx-item" id="ctx-open"><i class="fas fa-external-link-alt"></i>Open in Browser</div>
  <div class="ctx-item" id="ctx-exp-top"><i class="fas fa-folder-open"></i>Open in Explorer</div>
  <div class="ctx-item" id="ctx-detail"><i class="fas fa-info-circle"></i>View Details</div>
  <div class="ctx-div"></div>
  <div class="ctx-item" id="ctx-cust"><i class="fas fa-sliders-h"></i>Customize</div>
  <div class="ctx-item" id="ctx-ren"><i class="fas fa-pencil-alt"></i>Rename</div>
  <div class="ctx-item" id="ctx-nf"><i class="fas fa-file-code"></i>New File Inside</div>
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

<!-- ═══ TOAST ═══ -->
<div id="toast"><i></i><span id="toast-msg"></span></div>

<script>
/* ═══════════════════════════════════════════════════════
   DATA & CONFIG
═══════════════════════════════════════════════════════ */
const PROJECTS = <?=json_encode(array_values($projects),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const BASE_PATH = <?=json_encode($baseDirNorm)?>;

/* ═══════════════════════════════════════════════════════
   PARTICLE CANVAS
═══════════════════════════════════════════════════════ */
(()=>{
  const cv=document.getElementById('cv'),cx=cv.getContext('2d');
  let W,H,pts=[];
  const init=()=>{
    W=cv.width=innerWidth;H=cv.height=innerHeight;pts=[];
    for(let i=0;i<40;i++) pts.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.18,vy:(Math.random()-.5)*.18,r:Math.random()+.3});
  };
  const frame=()=>{
    cx.clearRect(0,0,W,H);
    pts.forEach((p,i)=>{
      pts.slice(i+1).forEach(q=>{
        const d=Math.hypot(p.x-q.x,p.y-q.y);
        if(d<140){cx.strokeStyle=`rgba(124,140,255,${.09*(1-d/140)})`;cx.lineWidth=.5;cx.beginPath();cx.moveTo(p.x,p.y);cx.lineTo(q.x,q.y);cx.stroke();}
      });
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0)p.x=W;if(p.x>W)p.x=0;if(p.y<0)p.y=H;if(p.y>H)p.y=0;
      cx.fillStyle='rgba(124,140,255,.38)';cx.beginPath();cx.arc(p.x,p.y,p.r,0,Math.PI*2);cx.fill();
    });
    requestAnimationFrame(frame);
  };
  window.addEventListener('resize',init);
  init();frame();
})();

/* ═══════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════ */
let _tt;
function toast(msg,type='ok'){
  const el=document.getElementById('toast');
  const ic=el.querySelector('i');
  document.getElementById('toast-msg').textContent=msg;
  el.className=type;
  ic.className=type==='ok'?'fas fa-check-circle':type==='err'?'fas fa-times-circle':'fas fa-info-circle';
  el.classList.add('show');
  clearTimeout(_tt);
  _tt=setTimeout(()=>el.classList.remove('show'),3000);
}

/* ═══════════════════════════════════════════════════════
   THEME
═══════════════════════════════════════════════════════ */
const _initT=localStorage.getItem('ch_theme')||'dark';
document.documentElement.setAttribute('data-theme',_initT);
document.querySelectorAll('.theme-dot').forEach(d=>{
  d.classList.toggle('on',d.getAttribute('data-t')===_initT);
  d.addEventListener('click',()=>applyTheme(d.getAttribute('data-t')));
});
const _tlbl=document.getElementById('theme-name-lbl');
if(_tlbl) _tlbl.textContent=_initT;

/* ═══════════════════════════════════════════════════════
   MODAL HELPERS
═══════════════════════════════════════════════════════ */
function openModal(id){document.getElementById(id)?.classList.add('open')}
function closeModal(id){document.getElementById(id)?.classList.remove('open')}
document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open')}));

/* ═══════════════════════════════════════════════════════
   API HELPER — single function for all AJAX
═══════════════════════════════════════════════════════ */
async function api(action,data={},isFile=false){
  const fd=new FormData();
  Object.entries(data).forEach(([k,v])=>{
    if(v!==undefined&&v!==null) fd.append(k,v);
  });
  try{
    const r=await fetch(`?a=${action}`,{method:'POST',body:fd});
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    return await r.json();
  }catch(e){
    console.error('API error:',action,e);
    return {ok:false,m:e.message};
  }
}

async function apiGet(action,params={}){
  const qs=new URLSearchParams({a:action,...params});
  try{
    const r=await fetch('?'+qs);
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    return await r.json();
  }catch(e){
    console.error('API GET error:',action,e);
    return {ok:false,m:e.message};
  }
}

/* ═══════════════════════════════════════════════════════
   CREATE PROJECT
═══════════════════════════════════════════════════════ */
document.getElementById('f-create').addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=e.target.querySelector('.submit-btn');
  btn.textContent='Creating…';btn.disabled=true;
  const d=await api('mk',{n:document.getElementById('f-name').value,tpl:document.getElementById('f-tpl').value});
  btn.innerHTML='<i class="fas fa-check"></i>Create';btn.disabled=false;
  toast(d.m,d.ok?'ok':'err');
  if(d.ok){closeModal('m-create');setTimeout(()=>location.reload(),700);}
});

/* ═══════════════════════════════════════════════════════
   RENAME
═══════════════════════════════════════════════════════ */
document.getElementById('f-rename').addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await api('ren',{old:document.getElementById('ren-old').value,new:document.getElementById('ren-new').value});
  toast(d.ok?'Renamed!':(d.m||'Error'),d.ok?'ok':'err');
  if(d.ok) setTimeout(()=>location.reload(),700);
});

/* ═══════════════════════════════════════════════════════
   NEW FILE
═══════════════════════════════════════════════════════ */
document.getElementById('f-file').addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await api('mf',{f:document.getElementById('ff-name').value,d:document.getElementById('ff-dir').value});
  toast(d.ok?'File created!':(d.m||'Error'),d.ok?'ok':'err');
  if(d.ok) closeModal('m-file');
});

/* ═══════════════════════════════════════════════════════
   DELETE PROJECT
═══════════════════════════════════════════════════════ */
async function delProj(dir){
  if(!confirm(`Move "${dir}" to trash?`)) return;
  toast('Moving to trash…','info');
  const d=await api('del',{d:dir});
  if(d.ok){
    toast('Moved to trash','ok');
    // Remove card from DOM immediately
    document.querySelectorAll(`.card[data-name="${CSS.escape(dir)}"]`).forEach(c=>c.remove());
    // Reload after short delay
    setTimeout(()=>location.reload(),800);
  }else{
    toast('Error: '+(d.m||'Could not delete'),'err');
    console.error('Delete failed:',d);
  }
}

/* ═══════════════════════════════════════════════════════
   OPEN PROJECT
═══════════════════════════════════════════════════════ */
async function openProj(dir){
  const p=PROJECTS.find(x=>x.name===dir);
  // Track open
  api('track',{d:dir});
  // If custom URL, open directly
  if(p?.url){window.open(p.url,'_blank');return;}
  // Check for index file
  const d=await apiGet('open',{dir});
  if(d.has_index){
    window.location.href=dir+'/';
  }else{
    // Open in file explorer as fallback
    await apiGet('exp',{dir});
    toast('Opened in file explorer','info');
  }
}

/* ═══════════════════════════════════════════════════════
   SYSTEM COMMANDS
═══════════════════════════════════════════════════════ */
async function sysRun(action,dir){
  await apiGet(action,{dir});
  const labels={vsc:'VS Code',term:'Terminal',exp:'Explorer'};
  toast(`Opening ${labels[action]||action}…`,'info');
}

/* ═══════════════════════════════════════════════════════
   PIN / UNPIN
═══════════════════════════════════════════════════════ */
async function togglePin(dir,ev){
  if(ev) ev.stopPropagation();
  const p=PROJECTS.find(x=>x.name===dir)||{};
  const np=!p.pin;
  const d=await api('cfg',{d:dir,ico:p.ico||'fa-folder',col:p.col||'#7c8cff',st:p.st||'active',pin:np?'1':'0',tags:JSON.stringify(p.tags||[]),rate:p.rate||0,url:p.url||''});
  if(d.ok){toast(np?'Pinned! 📌':'Unpinned','info');setTimeout(()=>location.reload(),500);}
  else toast('Error: '+(d.m||'Failed'),'err');
}

/* ═══════════════════════════════════════════════════════
   CUSTOMIZE
═══════════════════════════════════════════════════════ */
const COLORS=['#7c8cff','#c084fc','#f472b6','#f97316','#fbbf24','#34d399','#38bdf8','#f87171','#5eead4','#4ade80','#e879f9','#ff6b6b','#fde047','#06d6a0','#0284c7','#ef233c','#3a0ca3','#ff9a3c','#93c5fd','#fdba74','#8338ec','#00d2ff'];
const ICONS=['fa-code','fa-laptop-code','fa-terminal','fa-database','fa-server','fa-mobile-alt','fa-globe','fa-rocket','fa-cog','fa-palette','fa-chart-line','fa-layer-group','fa-puzzle-piece','fa-bolt','fa-bug','fa-star','fa-leaf','fa-fire','fa-gem','fa-flask','fa-gamepad','fa-music'];
let sCol='#7c8cff',sIco='fa-code',sRate=0,sTags=[],sImg=null;

function buildCustGrids(){
  const cg=document.getElementById('col-grid');
  cg.innerHTML='';
  COLORS.forEach(c=>{
    const d=document.createElement('div');
    d.className='col-sw'+(c===sCol?' sel':'');
    d.style.background=c;
    d.addEventListener('click',()=>{cg.querySelectorAll('.col-sw').forEach(x=>x.classList.remove('sel'));d.classList.add('sel');sCol=c;});
    cg.appendChild(d);
  });
  const ig=document.getElementById('ico-grid');
  ig.innerHTML='';
  ICONS.forEach(ic=>{
    const d=document.createElement('div');
    d.className='ico-sw'+(ic===sIco?' sel':'');
    d.innerHTML=`<i class="fas ${ic}"></i>`;
    d.addEventListener('click',()=>{ig.querySelectorAll('.ico-sw').forEach(x=>x.classList.remove('sel'));d.classList.add('sel');sIco=ic;});
    ig.appendChild(d);
  });
}

document.querySelectorAll('.r-star').forEach(s=>{
  s.addEventListener('click',()=>{sRate=+s.dataset.v;updStars();});
  s.addEventListener('mouseover',()=>{const v=+s.dataset.v;document.querySelectorAll('.r-star').forEach((x,i)=>x.classList.toggle('on',i<v));});
  s.addEventListener('mouseout',updStars);
});
function updStars(){document.querySelectorAll('.r-star').forEach((s,i)=>s.classList.toggle('on',i<sRate));}

function renderTagInput(){
  document.querySelectorAll('.tw-tag').forEach(t=>t.remove());
  const w=document.getElementById('tag-wrap'),f=document.getElementById('tag-fld');
  sTags.forEach(t=>{
    const d=document.createElement('div');
    d.className='tw-tag';
    d.innerHTML=`#${t}<span class="tag-del" onclick="remTag('${t}')"><i class="fas fa-times"></i></span>`;
    w.insertBefore(d,f);
  });
}
function remTag(t){sTags=sTags.filter(x=>x!==t);renderTagInput();}
document.getElementById('tag-fld').addEventListener('keydown',e=>{
  if(e.key==='Enter'||e.key===','){
    e.preventDefault();
    const v=e.target.value.trim().toLowerCase().replace(/[^a-z0-9\-_]/g,'');
    if(v&&!sTags.includes(v)){sTags.push(v);renderTagInput();}
    e.target.value='';
  }
  if(e.key==='Backspace'&&!e.target.value&&sTags.length){sTags.pop();renderTagInput();}
});

function rebindUpload(){
  const uz=document.getElementById('up-zone');
  uz.addEventListener('click',()=>uz.querySelector('#img-fld')?.click());
  uz.querySelector('#img-fld')?.addEventListener('change',function(){
    const f=this.files[0];if(!f)return;
    sImg=f;
    const r=new FileReader();
    r.onload=e=>{uz.innerHTML=`<img src="${e.target.result}">`;uz.classList.add('has');};
    r.readAsDataURL(f);
  });
}

function openCust(dir,ev){
  if(ev) ev.stopPropagation();
  const p=PROJECTS.find(x=>x.name===dir)||{};
  document.getElementById('c-dir').value=dir;
  document.getElementById('c-desc').value=p.desc||'';
  document.getElementById('c-url').value=p.url||'';
  document.getElementById('c-st').value=p.st||'active';
  sCol=p.col||'#7c8cff';sIco=p.ico||'fa-folder';sRate=p.rate||0;
  sTags=[...(p.tags||[])];sImg=null;
  renderTagInput();updStars();
  const uz=document.getElementById('up-zone');
  if(p.img){
    uz.innerHTML=`<img src="${p.img}"><input type="file" id="img-fld" accept="image/*" style="display:none">`;
    uz.className='up-zone has';
  }else{
    uz.innerHTML='<i class="fas fa-image"></i>Click to upload image<input type="file" id="img-fld" accept="image/*" style="display:none">';
    uz.className='up-zone';
  }
  rebindUpload();buildCustGrids();openModal('m-cust');
}

document.getElementById('f-cust').addEventListener('submit',async e=>{
  e.preventDefault();
  const dir=document.getElementById('c-dir').value;
  let imgUrl=null;
  if(sImg){
    const fd2=new FormData();fd2.append('f',sImg);fd2.append('d',dir);
    try{
      const r=await fetch('?a=img',{method:'POST',body:fd2});
      const d=await r.json();
      if(d.ok) imgUrl=d.img;
    }catch(e){console.error('Image upload failed',e);}
  }
  const payload={d:dir,ico:sIco,col:sCol,desc:document.getElementById('c-desc').value,st:document.getElementById('c-st').value,url:document.getElementById('c-url').value,rate:sRate,tags:JSON.stringify(sTags)};
  if(imgUrl) payload.img=imgUrl;
  const d=await api('cfg',payload);
  toast(d.ok?'Saved!':'Error','ok');
  if(d.ok){closeModal('m-cust');setTimeout(()=>location.reload(),700);}
});

/* ═══════════════════════════════════════════════════════
   DETAIL PANEL
═══════════════════════════════════════════════════════ */
let dpCur='';
function openDp(dir,tab='info'){
  dpCur=dir;
  const p=PROJECTS.find(x=>x.name===dir)||{};
  const rgb=hexRGB(p.col||'#7c8cff');
  const pend=(p.todos||[]).filter(t=>!t.done).length;
  const noteTs=p.note_ts?new Date(p.note_ts*1000).toLocaleDateString('en',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}):null;
  document.getElementById('dp-title').textContent=dir;
  document.getElementById('dp-body').innerHTML=`
    <div class="dp-hero">
      <div class="dp-ico" style="background:rgba(${rgb},.14)"><i class="fas ${p.ico||'fa-folder'}" style="color:${p.col||'#7c8cff'}"></i></div>
      <div>
        <div class="dp-pname">${p.name}</div>
        <div class="dp-ptype">${p.tipo||'Project'}</div>
        ${(p.tags||[]).length?`<div style="display:flex;gap:3px;flex-wrap:wrap;margin-top:5px">${p.tags.map(t=>`<span class="ctag">#${t}</span>`).join('')}</div>`:''}
      </div>
    </div>
    <div class="dp-stats">
      <div class="dp-stat"><div class="dp-sv">${p.files}</div><div class="dp-sl">Files</div></div>
      <div class="dp-stat"><div class="dp-sv">${p.opens||0}</div><div class="dp-sl">Opens</div></div>
      <div class="dp-stat"><div class="dp-sv">${p.ago}</div><div class="dp-sl">Changed</div></div>
      <div class="dp-stat"><div class="dp-sv" style="${pend>0?'color:var(--warm2)':''}">${pend}</div><div class="dp-sl">Todos</div></div>
    </div>
    ${p.desc?`<div class="dp-block"><div class="dp-sec"><i class="fas fa-align-left"></i>About</div><p style="font-size:.77em;color:var(--txt2);line-height:1.7">${p.desc}</p></div>`:''}
    <div class="dp-block">
      <div class="dp-sec"><i class="fas fa-pen-nib"></i>Notes</div>
      <div class="note-editor-wrap">
        <div class="note-tb">
          <div class="note-tb-l"><i class="fas fa-scroll"></i><span>${p.name}</span></div>
          <span class="note-ts" id="note-ts">${noteTs?'🕐 '+noteTs:'Unsaved'}</span>
        </div>
        <textarea class="note-ta" id="note-ta" placeholder="Write anything — ideas, links, progress…" rows="6">${(p.note||'').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</textarea>
        <div class="note-foot">
          <span class="note-chars" id="note-chars"><i class="fas fa-font"></i> ${(p.note||'').length}</span>
          <div style="display:flex;align-items:center;gap:8px">
            <span class="note-saved" id="note-saved"><i class="fas fa-check-circle"></i> Saved</span>
            <button class="note-save-btn" onclick="saveNote('${dir}')"><i class="fas fa-save" style="font-size:.85em;margin-right:3px"></i>Save</button>
          </div>
        </div>
      </div>
    </div>
    <div class="dp-block">
      <div class="dp-sec"><i class="fas fa-tasks"></i>To-Do List <span style="font-size:.8em;opacity:.5">${pend} pending</span></div>
      <div class="todo-list" id="todo-list"></div>
      <div class="todo-add">
        <input class="todo-inp" id="todo-inp" placeholder="Add a task…" onkeydown="if(event.key==='Enter')addTodo('${dir}')">
        <button class="todo-add-btn" onclick="addTodo('${dir}')">Add</button>
      </div>
    </div>
    ${p.url?`<div class="dp-block"><div class="dp-sec"><i class="fas fa-link"></i>URL</div><a href="${p.url}" target="_blank" style="font-family:var(--mono);font-size:.72em;color:var(--acc2);word-break:break-all;display:block;padding:7px 11px;background:var(--glass);border:1px solid var(--bdr);border-radius:8px">${p.url}</a></div>`:''}
  `;
  renderTodos(p.todos||[],dir);
  const ta=document.getElementById('note-ta');
  const cc=document.getElementById('note-chars');
  if(ta){
    ta.addEventListener('input',()=>{
      cc.innerHTML='<i class="fas fa-font"></i> '+ta.value.length;
      document.getElementById('note-saved')?.classList.remove('show');
    });
    setTimeout(()=>{ta.style.height='auto';ta.style.height=Math.min(ta.scrollHeight,300)+'px';},60);
    ta.addEventListener('input',()=>{ta.style.height='auto';ta.style.height=Math.min(ta.scrollHeight,300)+'px';});
  }
  document.getElementById('dp-foot').innerHTML=`
    <button class="btn btn-primary" onclick="openProj('${dir}')"><i class="fas fa-arrow-right"></i>Open</button>
    <button class="btn btn-ghost" onclick="sysRun('vsc','${dir}')"><i class="fas fa-code"></i>Code</button>
    <button class="btn btn-ghost" onclick="sysRun('term','${dir}')"><i class="fas fa-terminal"></i>Term</button>
    <button class="btn btn-ghost" onclick="openCust('${dir}')"><i class="fas fa-sliders-h"></i>Edit</button>
    <button class="btn btn-danger" onclick="delProj('${dir}')"><i class="fas fa-trash"></i></button>
  `;
  document.getElementById('dp').classList.add('open');
  if(tab==='notes') setTimeout(()=>ta?.focus(),420);
}
function closeDp(){document.getElementById('dp').classList.remove('open');}

async function saveNote(dir){
  const ta=document.getElementById('note-ta');
  if(!ta)return;
  const btn=document.querySelector('.note-save-btn');
  if(btn){btn.innerHTML='<i class="fas fa-spinner fa-spin" style="font-size:.8em;margin-right:3px"></i>Saving…';btn.disabled=true;}
  const d=await api('note',{d:dir,n:ta.value});
  const p=PROJECTS.find(x=>x.name===dir);
  if(p) p.note=ta.value;
  const si=document.getElementById('note-saved');
  const ts=document.getElementById('note-ts');
  const now=new Date().toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit'});
  if(si){si.classList.add('show');setTimeout(()=>si.classList.remove('show'),2500);}
  if(ts) ts.innerHTML='🕐 '+now;
  if(btn){btn.innerHTML='<i class="fas fa-save" style="font-size:.85em;margin-right:3px"></i>Save';btn.disabled=false;}
  if(d.ok) toast('Note saved!','ok');
}

document.addEventListener('keydown',e=>{
  if((e.ctrlKey||e.metaKey)&&e.key==='s'&&dpCur){
    const ta=document.getElementById('note-ta');
    if(ta&&document.activeElement===ta){e.preventDefault();saveNote(dpCur);}
  }
});

function renderTodos(todos,dir){
  const el=document.getElementById('todo-list');
  if(!el)return;
  el.innerHTML=todos.length
    ?todos.map((t,i)=>`<div class="todo-item${t.done?' done':''}" onclick="toggleTodo('${dir}',${i})"><div class="todo-cb">${t.done?'<i class="fas fa-check" style="font-size:.55em"></i>':''}</div><div class="todo-t">${t.text}</div><div class="todo-del" onclick="event.stopPropagation();delTodo('${dir}',${i})"><i class="fas fa-times"></i></div></div>`).join('')
    :'<p style="font-family:var(--mono);font-size:.68em;color:var(--txt3);padding:7px 0">No tasks yet</p>';
}
async function saveTodos(dir){
  const p=PROJECTS.find(x=>x.name===dir);
  if(!p)return;
  await api('todos',{d:dir,t:JSON.stringify(p.todos)});
}
function addTodo(dir){
  const inp=document.getElementById('todo-inp');
  const txt=inp.value.trim();if(!txt)return;
  const p=PROJECTS.find(x=>x.name===dir);if(!p)return;
  if(!p.todos)p.todos=[];
  p.todos.push({text:txt,done:false});
  renderTodos(p.todos,dir);inp.value='';saveTodos(dir);
}
function toggleTodo(dir,i){
  const p=PROJECTS.find(x=>x.name===dir);if(!p)return;
  p.todos[i].done=!p.todos[i].done;renderTodos(p.todos,dir);saveTodos(dir);
}
function delTodo(dir,i){
  const p=PROJECTS.find(x=>x.name===dir);if(!p)return;
  p.todos.splice(i,1);renderTodos(p.todos,dir);saveTodos(dir);
}

/* ═══════════════════════════════════════════════════════
   SEARCH & FILTER
═══════════════════════════════════════════════════════ */
let filt='all',fType=null,fTag=null;
const srch=document.getElementById('srch');
srch.addEventListener('input',filterCards);
document.querySelectorAll('.nav-item[data-f]').forEach(el=>{
  el.addEventListener('click',()=>{
    document.querySelectorAll('.nav-item[data-f]').forEach(x=>x.classList.remove('on'));
    el.classList.add('on');filt=el.getAttribute('data-f');filterCards();
  });
});
document.querySelectorAll('.stag[data-type]').forEach(el=>{
  el.addEventListener('click',()=>{
    const t=el.getAttribute('data-type');
    if(fType===t){fType=null;el.classList.remove('on');}
    else{document.querySelectorAll('.stag[data-type]').forEach(x=>x.classList.remove('on'));fType=t;el.classList.add('on');}
    filterCards();
  });
});
document.querySelectorAll('.stag[data-tag]').forEach(el=>{
  el.addEventListener('click',()=>{
    const t=el.getAttribute('data-tag');
    if(fTag===t){fTag=null;el.classList.remove('on');}
    else{document.querySelectorAll('.stag[data-tag]').forEach(x=>x.classList.remove('on'));fTag=t;el.classList.add('on');}
    filterCards();
  });
});
function filterCards(){
  const q=srch.value.toLowerCase();
  let n=0;
  document.querySelectorAll('#main-grid .card, #grid-pinned .card').forEach(c=>{
    const name=c.dataset.name?.toLowerCase()||'';
    const type=c.dataset.type?.toLowerCase()||'';
    const tags=JSON.parse(c.dataset.tags||'[]');
    const desc=(c.dataset.desc||'').toLowerCase();
    const st=c.dataset.st;
    const isRecent=c.dataset.recent==='1';
    const hasNote=c.dataset.note==='1';
    const isPinned=c.classList.contains('pinned');
    let show=!q||(name.includes(q)||desc.includes(q)||tags.some(t=>t.includes(q)));
    if(fType&&type!==fType)show=false;
    if(fTag&&!tags.includes(fTag))show=false;
    if(filt==='pin'&&!isPinned)show=false;
    if(filt==='recent'&&!isRecent)show=false;
    if(filt==='notes'&&!hasNote)show=false;
    if(filt.startsWith('st-')&&st!==filt.slice(3))show=false;
    c.style.display=show?'':'none';
    if(show)n++;
  });
  document.getElementById('page-sub').textContent=`${n} of <?=$total?> projects`;
}

/* ═══════════════════════════════════════════════════════
   SORT
═══════════════════════════════════════════════════════ */
document.getElementById('sort-btn').addEventListener('click',e=>{e.stopPropagation();document.getElementById('sort-dd').classList.toggle('open');});
document.addEventListener('click',()=>document.getElementById('sort-dd').classList.remove('open'));
document.querySelectorAll('.sort-item').forEach(el=>{
  el.addEventListener('click',e=>{
    e.stopPropagation();
    document.querySelectorAll('.sort-item').forEach(x=>x.classList.remove('on'));
    el.classList.add('on');sortCards(el.getAttribute('data-s'));
    document.getElementById('sort-dd').classList.remove('open');
  });
});
function sortCards(by){
  const g=document.getElementById('main-grid');
  const cs=[...g.querySelectorAll('.card')];
  cs.sort((a,b)=>{
    if(by==='az')return a.dataset.name.localeCompare(b.dataset.name);
    if(by==='za')return b.dataset.name.localeCompare(a.dataset.name);
    if(by==='new')return(+b.dataset.mt)-(+a.dataset.mt);
    if(by==='old')return(+a.dataset.mt)-(+b.dataset.mt);
    if(by==='files')return(+b.dataset.files)-(+a.dataset.files);
    if(by==='opens')return(+b.dataset.opens||0)-(+a.dataset.opens||0);
    if(by==='rating')return(+b.dataset.rate||0)-(+a.dataset.rate||0);
    return 0;
  });
  cs.forEach(c=>g.appendChild(c));
  localStorage.setItem('ch_order',JSON.stringify(cs.map(c=>c.dataset.name)));
}
// Restore saved order
(()=>{
  const saved=JSON.parse(localStorage.getItem('ch_order')||'[]');
  if(!saved.length)return;
  const g=document.getElementById('main-grid'),cs=[...g.querySelectorAll('.card')];
  saved.forEach(n=>{const c=cs.find(x=>x.dataset.name===n);if(c)g.appendChild(c);});
})();

/* ═══════════════════════════════════════════════════════
   VIEW TOGGLE
═══════════════════════════════════════════════════════ */
const MG=document.getElementById('main-grid');
function setView(v){
  MG.className='grid'+(v==='list'?' list':v==='compact'?' compact':'');
  document.getElementById('v-grid').classList.toggle('on',v==='grid'||!v);
  document.getElementById('v-list').classList.toggle('on',v==='list');
  document.getElementById('v-compact').classList.toggle('on',v==='compact');
  localStorage.setItem('ch_view',v);
}
document.getElementById('v-grid').addEventListener('click',()=>setView('grid'));
document.getElementById('v-list').addEventListener('click',()=>setView('list'));
document.getElementById('v-compact').addEventListener('click',()=>setView('compact'));
const sv=localStorage.getItem('ch_view')||'grid';
setView(sv);

/* ═══════════════════════════════════════════════════════
   DRAG & DROP
═══════════════════════════════════════════════════════ */
let dragSrc=null;
document.querySelectorAll('#main-grid .card').forEach(c=>{
  c.setAttribute('draggable','true');
  c.addEventListener('dragstart',e=>{dragSrc=c;c.classList.add('dragging');e.dataTransfer.effectAllowed='move';});
  c.addEventListener('dragend',()=>{c.classList.remove('dragging');document.querySelectorAll('.card').forEach(x=>x.classList.remove('dragover'));});
  c.addEventListener('dragover',e=>{e.preventDefault();if(c!==dragSrc)c.classList.add('dragover');});
  c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
  c.addEventListener('drop',e=>{
    e.stopPropagation();c.classList.remove('dragover');
    if(dragSrc&&dragSrc!==c){
      const g=document.getElementById('main-grid'),all=[...g.querySelectorAll('.card')];
      if(all.indexOf(dragSrc)<all.indexOf(c))g.insertBefore(dragSrc,c.nextSibling);
      else g.insertBefore(dragSrc,c);
      localStorage.setItem('ch_order',JSON.stringify([...g.querySelectorAll('.card')].map(x=>x.dataset.name)));
      toast('Order saved','info');
    }
  });
});

/* ═══════════════════════════════════════════════════════
   BULK SELECT
═══════════════════════════════════════════════════════ */
let bulkMode=false,sel=new Set();
function toggleBulk(){
  bulkMode=!bulkMode;
  document.querySelectorAll('.card-cb').forEach(c=>c.classList.toggle('show',bulkMode));
  document.getElementById('bulk-bar').classList.toggle('show',bulkMode);
  if(!bulkMode)clearBulk();
}
function clearBulk(){
  sel.clear();
  document.querySelectorAll('.card-cb').forEach(c=>c.classList.remove('on'));
  document.getElementById('bulk-cnt').textContent='0';
}
document.querySelectorAll('.card').forEach(c=>{
  const cb=c.querySelector('.card-cb');
  if(cb)cb.addEventListener('click',e=>{
    e.stopPropagation();
    const n=c.dataset.name;
    if(sel.has(n)){sel.delete(n);cb.classList.remove('on');}
    else{sel.add(n);cb.classList.add('on');}
    document.getElementById('bulk-cnt').textContent=sel.size;
  });
});
async function doBulk(action){
  if(!sel.size){toast('Nothing selected','info');return;}
  if(action==='delete'&&!confirm(`Move ${sel.size} project(s) to trash?`))return;
  for(const dir of sel){
    const p=PROJECTS.find(x=>x.name===dir)||{};
    if(action==='delete'){
      await api('del',{d:dir});
    }else{
      const st=action==='archive'?'archived':action==='done'?'done':(p.st||'active');
      const pin=action==='pin'?'1':(p.pin?'1':'0');
      await api('cfg',{d:dir,ico:p.ico||'fa-folder',col:p.col||'#7c8cff',st,pin,tags:JSON.stringify(p.tags||[]),rate:p.rate||0});
    }
  }
  toast(`Done (${sel.size})!`,'ok');
  setTimeout(()=>location.reload(),700);
}

/* ═══════════════════════════════════════════════════════
   CONTEXT MENU
═══════════════════════════════════════════════════════ */
let ctxDir='';
const ctxEl=document.getElementById('ctx-menu');
document.querySelectorAll('.card').forEach(c=>{
  c.addEventListener('contextmenu',e=>{
    e.preventDefault();
    ctxDir=c.dataset.name;
    document.getElementById('ctx-lbl').textContent=ctxDir;
    ctxEl.style.top=Math.min(e.clientY,innerHeight-300)+'px';
    ctxEl.style.left=Math.min(e.clientX,innerWidth-215)+'px';
    ctxEl.style.display='block';
  });
});
document.addEventListener('click',()=>ctxEl.style.display='none');
document.getElementById('ctx-open').onclick=()=>openProj(ctxDir);
document.getElementById('ctx-detail').onclick=()=>openDp(ctxDir);
document.getElementById('ctx-cust').onclick=()=>openCust(ctxDir);
document.getElementById('ctx-ren').onclick=()=>{
  document.getElementById('ren-old').value=ctxDir;
  document.getElementById('ren-new').value=ctxDir;
  openModal('m-rename');
};
document.getElementById('ctx-nf').onclick=()=>{
  document.getElementById('ff-dir').value=ctxDir;
  openModal('m-file');
};
document.getElementById('ctx-vsc').onclick=()=>sysRun('vsc',ctxDir);
document.getElementById('ctx-term').onclick=()=>sysRun('term',ctxDir);
document.getElementById('ctx-exp').onclick=()=>sysRun('exp',ctxDir);
document.getElementById('ctx-exp-top').onclick=()=>sysRun('exp',ctxDir);
document.getElementById('ctx-cpath').onclick=()=>navigator.clipboard.writeText(BASE_PATH+'/'+ctxDir).then(()=>toast('Path copied!'));
document.getElementById('ctx-curl').onclick=()=>{
  const u=location.origin+location.pathname.replace(/index\.php$/,'')+ctxDir+'/';
  navigator.clipboard.writeText(u).then(()=>toast('URL copied!'));
};
document.getElementById('ctx-del').onclick=()=>delProj(ctxDir);

/* ═══════════════════════════════════════════════════════
   SPOTLIGHT
═══════════════════════════════════════════════════════ */
let spIdx=-1;
function openSpotlight(){
  document.getElementById('spotlight').classList.add('open');
  document.getElementById('sp-inp').value='';
  document.getElementById('sp-inp').focus();
  renderSp('');
}
function closeSpotlight(){document.getElementById('spotlight').classList.remove('open');}
document.getElementById('spotlight').addEventListener('click',e=>{if(e.target===document.getElementById('spotlight'))closeSpotlight();});
document.getElementById('sp-inp').addEventListener('input',e=>renderSp(e.target.value.toLowerCase()));
document.getElementById('sp-inp').addEventListener('keydown',e=>{
  const items=document.querySelectorAll('.sp-item');
  if(e.key==='ArrowDown'){spIdx=Math.min(spIdx+1,items.length-1);hlSp(items);}
  else if(e.key==='ArrowUp'){spIdx=Math.max(spIdx-1,0);hlSp(items);}
  else if(e.key==='Enter'&&spIdx>=0){const it=items[spIdx];if(it&&it.dataset.dir){openProj(it.dataset.dir);closeSpotlight();}}
  else if(e.key==='Escape')closeSpotlight();
});
function hlSp(items){items.forEach((el,i)=>el.classList.toggle('hi',i===spIdx));items[spIdx]?.scrollIntoView({block:'nearest'});}
function renderSp(q){
  spIdx=-1;
  const cmds=[
    {l:'New Project',i:'fa-folder-plus',fn:"openModal('m-create')"},
    {l:'Random Project',i:'fa-random',fn:"showRandom()"},
    {l:'Bulk Select',i:'fa-check-square',fn:"toggleBulk()"},
    {l:'Export JSON',i:'fa-download',fn:"doExport('json')"},
  ];
  const prs=PROJECTS.filter(p=>p.name.toLowerCase().includes(q)||(p.desc||'').toLowerCase().includes(q)||(p.tags||[]).some(t=>t.includes(q))).slice(0,7);
  const fc=q?cmds.filter(c=>c.l.toLowerCase().includes(q)):[];
  let html='';
  if(fc.length){
    html+='<div class="sp-divider">Commands</div>';
    html+=fc.map(c=>`<div class="sp-item" onclick="${c.fn};closeSpotlight()"><div class="sp-thumb" style="background:var(--acc3)"><i class="fas ${c.i}" style="color:var(--acc2)"></i></div><div><div class="sp-name">${c.l}</div><div class="sp-meta">Command</div></div></div>`).join('');
  }
  if(prs.length){
    html+='<div class="sp-divider">Projects</div>';
    html+=prs.map(p=>`<div class="sp-item" data-dir="${p.name}" onclick="openProj('${p.name}');closeSpotlight()"><div class="sp-thumb" style="background:rgba(${hexRGB(p.col||'#7c8cff')},.14)"><i class="fas ${p.ico||'fa-folder'}" style="color:${p.col||'#7c8cff'}"></i></div><div class="sp-info"><div class="sp-name">${p.name}</div><div class="sp-meta">${p.tipo||'Project'} · ${p.files} files · ${p.ago}</div></div><div class="sp-acts"><div class="sp-act" onclick="event.stopPropagation();sysRun('vsc','${p.name}')">Code</div><div class="sp-act" onclick="event.stopPropagation();openDp('${p.name}');closeSpotlight()">Info</div></div></div>`).join('');
  }
  if(!html)html='<div style="padding:22px;text-align:center;color:var(--txt3);font-family:var(--mono);font-size:.74em">No results</div>';
  document.getElementById('sp-list').innerHTML=html;
}

/* ═══════════════════════════════════════════════════════
   RANDOM PICK
═══════════════════════════════════════════════════════ */
function showRandom(){
  const active=PROJECTS.filter(p=>p.st!=='archived');
  if(!active.length){toast('No active projects','info');return;}
  const p=active[Math.floor(Math.random()*active.length)];
  const rgb=hexRGB(p.col||'#7c8cff');
  document.getElementById('rnd-ico').style.background=`rgba(${rgb},.14)`;
  document.getElementById('rnd-ico').innerHTML=`<i class="fas ${p.ico||'fa-folder'}" style="color:${p.col||'#7c8cff'}"></i>`;
  document.getElementById('rnd-name').textContent=p.name;
  document.getElementById('rnd-type').textContent=`${p.tipo||'Project'} · ${p.files} files · ${p.ago}`;
  document.getElementById('rnd-open').onclick=()=>{openProj(p.name);closeRnd();};
  document.getElementById('rnd-overlay').classList.add('open');
}
function closeRnd(){document.getElementById('rnd-overlay').classList.remove('open');}
document.getElementById('rnd-overlay').addEventListener('click',e=>{if(e.target===document.getElementById('rnd-overlay'))closeRnd();});

/* ═══════════════════════════════════════════════════════
   MOBILE SIDEBAR
═══════════════════════════════════════════════════════ */
function toggleSidebar(){
  const sb=document.querySelector('.sidebar');
  const bd=document.getElementById('sidebar-backdrop');
  const open=sb.classList.toggle('mob-open');
  bd.classList.toggle('show',open);
  document.body.style.overflow=open?'hidden':'';
}
function closeSidebar(){
  document.querySelector('.sidebar').classList.remove('mob-open');
  document.getElementById('sidebar-backdrop').classList.remove('show');
  document.body.style.overflow='';
}
// Close sidebar when a nav filter item is clicked on mobile
document.querySelectorAll('.sidebar .nav-item[data-f]').forEach(el=>{
  el.addEventListener('click',()=>{if(window.innerWidth<=820)closeSidebar();});
});
function mobFilter(el){
  document.querySelectorAll('.mob-nb').forEach(x=>x.classList.remove('on'));
  el.classList.add('on');
}

/* ═══════════════════════════════════════════════════════
   EXPORT
═══════════════════════════════════════════════════════ */
function doExport(fmt){
  const a=document.createElement('a');
  a.href=`?a=exp_data&fmt=${fmt}`;
  a.download=`codehub.${fmt}`;
  a.click();
  toast('Exporting…','info');
}

/* ═══════════════════════════════════════════════════════
   KEYBOARD SHORTCUTS
═══════════════════════════════════════════════════════ */
document.addEventListener('keydown',e=>{
  const tag=document.activeElement.tagName;
  if(tag==='INPUT'||tag==='TEXTAREA')return;
  if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();openSpotlight();return;}
  if(e.key==='/'){e.preventDefault();srch.focus();return;}
  if(e.key==='Escape'){closeSpotlight();closeDp();closeRnd();document.querySelectorAll('.overlay').forEach(o=>o.classList.remove('open'));return;}
  if((e.ctrlKey||e.metaKey)&&e.key==='n'){e.preventDefault();openModal('m-create');return;}
  if(e.key==='r')showRandom();
  if(e.key==='b')toggleBulk();
});

/* ═══════════════════════════════════════════════════════
   UTILS
═══════════════════════════════════════════════════════ */
function hexRGB(hex){
  try{const m=hex.match(/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i);if(m)return parseInt(m[1],16)+','+parseInt(m[2],16)+','+parseInt(m[3],16);}catch(e){}
  return '124,140,255';
}

/* ═══════════════════════════════════════════════════════
   CARD TILT (3D effect)
═══════════════════════════════════════════════════════ */
let tiltEnabled=true;
document.querySelectorAll('.card').forEach(c=>{
  c.addEventListener('mousemove',e=>{
    if(!tiltEnabled)return;
    const r=c.getBoundingClientRect();
    const x=(e.clientX-r.left)/r.width,y=(e.clientY-r.top)/r.height;
    c.style.setProperty('--rx',`${(x-.5)*8}deg`);
    c.style.setProperty('--ry',`${-(y-.5)*8}deg`);
    c.style.setProperty('--mx',`${x*100}%`);
    c.style.setProperty('--my',`${y*100}%`);
  });
  c.addEventListener('mouseleave',()=>{
    c.style.setProperty('--rx','0deg');
    c.style.setProperty('--ry','0deg');
  });
});

/* ═══════════════════════════════════════════════════════
   DASHBOARD SETTINGS
═══════════════════════════════════════════════════════ */
const DS={
  get:(k,d)=>{try{const v=localStorage.getItem('ch_ds_'+k);return v===null?d:JSON.parse(v);}catch{return d;}},
  set:(k,v)=>localStorage.setItem('ch_ds_'+k,JSON.stringify(v))
};

function switchTab(el,tab){
  document.querySelectorAll('.stab').forEach(t=>t.classList.remove('on'));
  document.querySelectorAll('.spanel').forEach(p=>p.classList.remove('on'));
  el.classList.add('on');
  document.getElementById('tab-'+tab)?.classList.add('on');
}

function applyTheme(t){
  document.documentElement.setAttribute('data-theme',t);
  localStorage.setItem('ch_theme',t);
  document.querySelectorAll('.theme-dot').forEach(d=>d.classList.toggle('on',d.dataset.t===t));
  document.querySelectorAll('.tpv').forEach(d=>d.classList.toggle('sel',d.dataset.t===t));
  const lbl=document.getElementById('theme-name-lbl');if(lbl)lbl.textContent=t;
  toast('Theme: '+t,'info');
}
function pickAccent(col){
  document.documentElement.style.setProperty('--acc',col);
  document.documentElement.style.setProperty('--acc2',col+'cc');
  document.documentElement.style.setProperty('--acc3',col+'22');
  document.documentElement.style.setProperty('--acc4',col+'0f');
  document.querySelectorAll('.acc-sw').forEach(s=>s.classList.toggle('sel',s.dataset.col===col));
  const ci=document.getElementById('accent-custom');if(ci&&col.length===7)ci.value=col;
  DS.set('accent',col);
}
function resetAccent(){
  ['--acc','--acc2','--acc3','--acc4'].forEach(v=>document.documentElement.style.removeProperty(v));
  document.querySelectorAll('.acc-sw').forEach(s=>s.classList.remove('sel'));
  DS.set('accent',null);toast('Accent reset','info');
}
document.getElementById('accent-custom')?.addEventListener('input',e=>pickAccent(e.target.value));

function applyFont(name){
  const URLS={'Inter':'Inter:wght@300;400;500;600;700;800','Outfit':'Outfit:wght@300;400;500;600;700;800','Nunito':'Nunito:wght@400;500;600;700;800','Syne':'Syne:wght@400;500;600;700;800','Lexend':'Lexend:wght@300;400;500;600;700','Space Grotesk':'Space+Grotesk:wght@300;400;500;600;700'};
  if(URLS[name]){
    const id='gf-'+name.replace(/\s/g,'-');
    if(!document.getElementById(id)){const l=document.createElement('link');l.id=id;l.rel='stylesheet';l.href='https://fonts.googleapis.com/css2?family='+URLS[name]+'&display=swap';document.head.appendChild(l);}
  }
  document.documentElement.style.setProperty('--ui',"'"+name+"', sans-serif");
  const fc=document.getElementById('font-card');if(fc)fc.style.fontFamily="'"+name+"',sans-serif";
  DS.set('font',name);toast('Font: '+name,'info');
}
function applyFsize(val,el){
  const s={sm:'13px',md:'15px',lg:'17px'};
  document.body.style.fontSize=s[val]||'15px';
  if(el){document.querySelectorAll('#fs-sm,#fs-md,#fs-lg').forEach(b=>b.classList.remove('on'));el.classList.add('on');}
  DS.set('fsize',val);
}
function applyRound(val,el){
  const v={sm:'7px',md:'13px',lg:'20px',xl:'28px'};
  document.documentElement.style.setProperty('--r',v[val]||'13px');
  if(el){document.querySelectorAll('#r-sm,#r-md,#r-lg,#r-xl').forEach(b=>b.classList.remove('on'));el.classList.add('on');}
  DS.set('rounded',val);
}
function applyDensity(val,el){
  document.documentElement.setAttribute('data-density',val);
  if(el){document.querySelectorAll('#den-comfortable,#den-cozy,#den-tight').forEach(b=>b.classList.remove('on'));el.classList.add('on');}
  DS.set('density',val);
}
function applyCardSize(val,el){
  document.documentElement.setAttribute('data-csize',val);
  if(el){document.querySelectorAll('#cs-xs,#cs-sm,#cs-md,#cs-lg,#cs-xl').forEach(b=>b.classList.remove('on'));el.classList.add('on');}
  DS.set('csize',val);
}
function applyCompact(on){document.documentElement.setAttribute('data-compact',on?'1':'0');DS.set('compact',on);}
function applyDefaultView(val,el){
  DS.set('defview',val);
  if(el){document.querySelectorAll('#dv-grid,#dv-list,#dv-compact').forEach(b=>b.classList.remove('on'));el.classList.add('on');}
  setView(val);
}
function applyDefaultSort(val){DS.set('defsort',val);sortCards(val);}
function applySidebarW(val,el){
  document.documentElement.setAttribute('data-sidebar',val);
  if(el){document.querySelectorAll('#sw-narrow,#sw-normal,#sw-wide').forEach(b=>b.classList.remove('on'));el.classList.add('on');}
  DS.set('sidebarW',val);
}
function applyStatsShow(on){
  const sg=document.querySelector('.stats-grid');if(sg)sg.parentElement.style.display=on?'':'none';DS.set('statsShow',on);
}
function applyAnim(on){document.documentElement.setAttribute('data-animations',on?'on':'off');DS.set('anim',on);}
function applyTilt(on){tiltEnabled=on;if(!on)document.querySelectorAll('.card').forEach(c=>{c.style.setProperty('--rx','0deg');c.style.setProperty('--ry','0deg');});DS.set('tilt',on);}
function applyParticles(on){const cv=document.getElementById('cv');if(cv)cv.style.display=on?'':'none';DS.set('particles',on);}
function applyGrain(on){
  let s=document.getElementById('grain-override');
  if(!s){s=document.createElement('style');s.id='grain-override';document.head.appendChild(s);}
  s.textContent=on?'':'body::after{opacity:0!important}';DS.set('grain',on);
}
function applyGlow(on){
  let s=document.getElementById('glow-override');
  if(!s){s=document.createElement('style');s.id='glow-override';document.head.appendChild(s);}
  s.textContent=on?'':'.card::after{display:none!important}';DS.set('glow',on);
}
function applySidebarGlass(on){document.documentElement.setAttribute('data-sidebar-glass',on?'on':'off');DS.set('sidebarGlass',on);}
function resetDashSettings(){
  if(!confirm('Reset all settings?'))return;
  ['accent','font','fsize','rounded','density','csize','compact','defview','defsort','sidebarW','sidebarGlass','statsShow','anim','tilt','particles','grain','glow'].forEach(k=>localStorage.removeItem('ch_ds_'+k));
  toast('Reset to defaults','info');
  setTimeout(()=>location.reload(),700);
}

/* ═══════════════════════════════════════════════════════
   RESTORE SAVED SETTINGS
═══════════════════════════════════════════════════════ */
(()=>{
  const acc=DS.get('accent',null);
  if(acc){document.documentElement.style.setProperty('--acc',acc);document.documentElement.style.setProperty('--acc2',acc+'cc');document.documentElement.style.setProperty('--acc3',acc+'22');document.documentElement.style.setProperty('--acc4',acc+'0f');}
  const font=DS.get('font','DM Sans');if(font!=='DM Sans')applyFont(font);
  document.body.style.fontSize={sm:'13px',md:'15px',lg:'17px'}[DS.get('fsize','md')]||'15px';
  document.documentElement.style.setProperty('--r',{sm:'7px',md:'13px',lg:'20px',xl:'28px'}[DS.get('rounded','md')]||'13px');
  document.documentElement.setAttribute('data-density',DS.get('density','cozy'));
  document.documentElement.setAttribute('data-csize',DS.get('csize','md'));
  document.documentElement.setAttribute('data-compact',DS.get('compact',false)?'1':'0');
  document.documentElement.setAttribute('data-sidebar',DS.get('sidebarW','normal'));
  if(DS.get('sidebarGlass',false))document.documentElement.setAttribute('data-sidebar-glass','on');
  if(!DS.get('anim',true))document.documentElement.setAttribute('data-animations','off');
  if(!DS.get('particles',true)){const cv=document.getElementById('cv');if(cv)cv.style.display='none';}
  if(!DS.get('grain',true)){const s=document.createElement('style');s.id='grain-override';s.textContent='body::after{opacity:0!important}';document.head.appendChild(s);}
  if(!DS.get('glow',true)){const s=document.createElement('style');s.id='glow-override';s.textContent='.card::after{display:none!important}';document.head.appendChild(s);}
  if(!DS.get('statsShow',true)){const sg=document.querySelector('.stats-grid');if(sg)sg.parentElement.style.display='none';}
  const ds=DS.get('defsort',null);if(ds)setTimeout(()=>sortCards(ds),80);
})();

/* Sync dashboard modal state on open */
const _origOM=window.openModal;
window.openModal=function(id){
  _origOM(id);if(id!=='m-dash')return;
  const ct=localStorage.getItem('ch_theme')||'dark';
  document.querySelectorAll('.tpv').forEach(d=>d.classList.toggle('sel',d.dataset.t===ct));
  const acc=DS.get('accent',null);
  if(acc)document.querySelectorAll('.acc-sw').forEach(s=>s.classList.toggle('sel',s.dataset.col===acc));
  const fp=document.getElementById('font-pick');if(fp)fp.value=DS.get('font','DM Sans');
  const fc=document.getElementById('font-card');if(fc)fc.style.fontFamily="'"+DS.get('font','DM Sans')+"',sans-serif";
  const fsz=DS.get('fsize','md');
  document.querySelectorAll('#fs-sm,#fs-md,#fs-lg').forEach(b=>b.classList.remove('on'));
  document.getElementById('fs-'+fsz)?.classList.add('on');
  const rnd=DS.get('rounded','md');
  document.querySelectorAll('#r-sm,#r-md,#r-lg,#r-xl').forEach(b=>b.classList.remove('on'));
  document.getElementById('r-'+rnd)?.classList.add('on');
  const sw=DS.get('sidebarW','normal');
  document.querySelectorAll('#sw-narrow,#sw-normal,#sw-wide').forEach(b=>b.classList.remove('on'));
  document.getElementById('sw-'+sw)?.classList.add('on');
  const dv=DS.get('defview','grid');
  document.querySelectorAll('#dv-grid,#dv-list,#dv-compact').forEach(b=>b.classList.remove('on'));
  document.getElementById('dv-'+dv)?.classList.add('on');
  const den=DS.get('density','cozy');
  document.querySelectorAll('#den-comfortable,#den-cozy,#den-tight').forEach(b=>b.classList.remove('on'));
  document.getElementById('den-'+den)?.classList.add('on');
  const csize=DS.get('csize','md');
  document.querySelectorAll('#cs-xs,#cs-sm,#cs-md,#cs-lg,#cs-xl').forEach(b=>b.classList.remove('on'));
  document.getElementById('cs-'+csize)?.classList.add('on');
  const tog=(id,k,d)=>{const el=document.getElementById(id);if(el)el.checked=DS.get(k,d);};
  tog('compact-tog','compact',false);
  tog('stats-show','statsShow',true);
  tog('anim-tog','anim',true);
  tog('tilt-tog','tilt',true);
  tog('glow-tog','glow',true);
  tog('particles-tog','particles',true);
  tog('grain-tog','grain',true);
  tog('sidebar-glass','sidebarGlass',false);
  const sd=document.getElementById('sort-pick-dash');if(sd)sd.value=DS.get('defsort','az');
};
</script>
</body>
</html>
