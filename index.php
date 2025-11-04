<?php
$carpetas = array_diff(scandir(__DIR__), ['.', '..', 'index.php', 'index.html']);
$mostrarVolver = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR) !== rtrim(realpath($_SERVER['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);

// Ruta base real
$rutaBase = str_replace('\\', '/', realpath(__DIR__)) . '/';

// Función para detectar tipo de proyecto
function detectarTipoProyecto($carpeta) {
    $tipos = [
        'composer.json' => ['tipo' => 'PHP', 'icono' => 'fa-php', 'color' => '#777bb3'],
        'package.json' => ['tipo' => 'Node.js', 'icono' => 'fa-node-js', 'color' => '#68a063'],
        'index.html' => ['tipo' => 'HTML', 'icono' => 'fa-html5', 'color' => '#e34c26'],
        'style.css' => ['tipo' => 'CSS', 'icono' => 'fa-css3-alt', 'color' => '#264de4'],
        'pom.xml' => ['tipo' => 'Java', 'icono' => 'fa-java', 'color' => '#007396'],
        'requirements.txt' => ['tipo' => 'Python', 'icono' => 'fa-python', 'color' => '#3776ab'],
        '.git' => ['tipo' => 'Git', 'icono' => 'fa-git-alt', 'color' => '#f05032'],
    ];
    
    foreach ($tipos as $archivo => $info) {
        if (file_exists($carpeta . '/' . $archivo)) {
            return $info;
        }
    }
    
    return ['tipo' => 'Proyecto', 'icono' => 'fa-folder', 'color' => '#3b82f6'];
}

// Contar archivos en carpeta
function contarArchivos($carpeta) {
    $archivos = glob($carpeta . '/*');
    return count($archivos);
}

// Obtener última modificación
function ultimaModificacion($carpeta) {
    $tiempo = filemtime($carpeta);
    $ahora = time();
    $diff = $ahora - $tiempo;
    
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs';
    if ($diff < 604800) return floor($diff / 86400) . ' días';
    return date('d/m/Y', $tiempo);
}

// Acciones AJAX
if (isset($_GET['accion'])) {
    header('Content-Type: application/json');
    
    if ($_GET['accion'] === 'info' && isset($_GET['carpeta'])) {
        $carpeta = realpath($rutaBase . $_GET['carpeta']);
        if ($carpeta && is_dir($carpeta)) {
            $info = detectarTipoProyecto($carpeta);
            echo json_encode([
                'carpeta' => basename($carpeta),
                'tipo' => $info['tipo'],
                'archivos' => contarArchivos($carpeta),
                'modificado' => ultimaModificacion($carpeta),
                'ruta' => $carpeta
            ]);
        }
        exit;
    }
    
    if (isset($_GET['carpeta'])) {
        $carpetaAbrir = realpath($rutaBase . $_GET['carpeta']);
        if ($carpetaAbrir && is_dir($carpetaAbrir)) {
            if ($_GET['accion'] === 'explorer') {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec("explorer " . escapeshellarg($carpetaAbrir));
                } else {
                    exec("xdg-open " . escapeshellarg($carpetaAbrir));
                }
            }
            if ($_GET['accion'] === 'vscode') {
                exec("code --new-window " . escapeshellarg($carpetaAbrir));
            }
            if ($_GET['accion'] === 'terminal') {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec("start cmd /K cd /d " . escapeshellarg($carpetaAbrir));
                } else {
                    exec("gnome-terminal --working-directory=" . escapeshellarg($carpetaAbrir));
                }
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Búsqueda
$busqueda = isset($_GET['buscar']) ? strtolower($_GET['buscar']) : '';
if ($busqueda) {
    $carpetas = array_filter($carpetas, function($carpeta) use ($busqueda) {
        return strpos(strtolower($carpeta), $busqueda) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeHub - Panel de Proyectos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="icono.png" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: white;
            overflow-x: hidden;
        }

        body {
            background: #0d1117;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.03) 0%, transparent 70%);
            z-index: 0;
        }

        .light-accent {
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }

        .light-1 {
            top: -200px;
            left: -200px;
            background: #3b82f6;
        }

        .light-2 {
            bottom: -200px;
            right: -200px;
            background: #8b5cf6;
        }

        .light-3 {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #06b6d4;
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.1; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.2; transform: translate(-50%, -50%) scale(1.1); }
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            padding: 60px 20px 40px;
            animation: fadeInDown 0.8s ease;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .logo-icon {
            font-size: 4em;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 30px rgba(59, 130, 246, 0.6));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 3.5em;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
        }

        .subtitle {
            color: #8b949e;
            font-size: 1.2em;
            margin-top: 10px;
            font-weight: 300;
        }

        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .controls-left {
            display: flex;
            gap: 15px;
            flex: 1;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 280px;
        }

        .search-box input {
            width: 100%;
            padding: 16px 50px 16px 22px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            color: white;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box input::placeholder {
            color: #6b7280;
        }

        .search-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #3b82f6;
            font-size: 1.1em;
        }

        .view-controls {
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.03);
            padding: 6px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .btn-view {
            padding: 10px 16px;
            border: none;
            background: transparent;
            border-radius: 8px;
            color: #8b949e;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.1em;
        }

        .btn-view:hover {
            color: white;
            background: rgba(59, 130, 246, 0.1);
        }

        .btn-view.active {
            background: #3b82f6;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 10px 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            color: #c9d1d9;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
            color: white;
        }

        .btn-action i {
            font-size: 1em;
        }

        .filter-dropdown {
            position: relative;
        }

        .dropdown-toggle {
            padding: 20px 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            color: #c9d1d9;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-menu {
            position: absolute;
            top: 110%;
            right: 0;
            background: #161b22;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 8px;
            min-width: 180px;
            display: none;
            z-index: 100;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px 14px;
            cursor: pointer;
            color: #c9d1d9;
            font-size: 0.9em;
            border-radius: 6px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.15);
            color: white;
        }

        .dropdown-item.active {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .favorite-star {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 1.2em;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .favorite-star:hover {
            color: #fbbf24;
            transform: scale(1.2);
        }

        .favorite-star.active {
            color: #fbbf24;
        }

        .stats {
            display: flex;
            gap: 20px;
            color: #8b949e;
            font-size: 0.95em;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .stats i {
            color: #3b82f6;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            animation: fadeInUp 0.8s ease;
        }

        .grid.list-view {
            grid-template-columns: 1fr;
        }

        .card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: grab;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(59, 130, 246, 0.4);
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 20px 60px rgba(59, 130, 246, 0.2);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        /* DRAG & DROP STYLES */
        .card.dragging {
            opacity: 0.5;
            cursor: grabbing;
            transform: scale(1.05) rotate(5deg);
            z-index: 1000;
            box-shadow: 0 30px 80px rgba(59, 130, 246, 0.4);
        }

        .card.drag-over {
            transform: scale(0.95);
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .card.dropping {
            animation: dropBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes dropBounce {
            0% { transform: scale(1.1); }
            50% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }

        .drag-handle {
            position: absolute;
            top: 12px;
            right: 48px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 1.1em;
            opacity: 0;
            transition: all 0.3s ease;
            cursor: grab;
            z-index: 10;
            pointer-events: all;
        }

        .card:hover .drag-handle {
            opacity: 1;
        }

        .drag-handle:hover {
            color: #3b82f6;
            transform: scale(1.2);
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .card-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            flex-shrink: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .card:hover .card-icon-wrapper {
            transform: scale(1.1) rotate(-5deg);
        }

        .card.dragging .card-icon-wrapper {
            transform: scale(1.2) rotate(15deg);
        }

        .card-content {
            flex: 1;
            min-width: 0;
        }

        .card-title {
            font-size: 1.25em;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: block;
            margin-bottom: 6px;
            word-break: break-word;
            transition: color 0.2s ease;
        }

        .card:hover .card-title {
            color: #3b82f6;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.75em;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .card-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #8b949e;
            font-size: 0.9em;
        }

        .info-item i {
            width: 18px;
            color: #6b7280;
            font-size: 0.95em;
        }

        .list-view .card {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .list-view .card-header {
            margin-bottom: 0;
            flex: 1;
        }

        .list-view .card-info {
            flex-direction: row;
            margin-top: 0;
            padding-top: 0;
            border-top: none;
            gap: 30px;
        }

        #contextMenu {
            position: absolute;
            display: none;
            background: #161b22;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 6px;
            z-index: 1000;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.6);
            min-width: 220px;
        }

        .menu-item {
            padding: 10px 14px;
            cursor: pointer;
            color: #c9d1d9;
            font-size: 0.9em;
            border-radius: 6px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-item i {
            width: 18px;
            color: #8b949e;
            font-size: 1em;
        }

        .menu-item:hover {
            background: rgba(59, 130, 246, 0.15);
            color: white;
        }

        .menu-item:hover i {
            color: #3b82f6;
        }

        .back-arrow {
            position: fixed;
            top: 24px;
            left: 24px;
            color: #c9d1d9;
            font-size: 1.1em;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.2s ease;
            z-index: 100;
        }

        .back-arrow:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: #3b82f6;
            color: white;
            transform: translateX(-4px);
        }

        .notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #161b22;
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease;
            z-index: 1002;
        }

        .notification i {
            color: #10b981;
            font-size: 1.2em;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2.5em;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .search-box {
                width: 100%;
            }

            .stats {
                width: 100%;
                justify-content: center;
            }
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state p {
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="light-accent light-1"></div>
    <div class="light-accent light-2"></div>
    <div class="light-accent light-3"></div>

    <div class="container">
        <?php if ($mostrarVolver): ?>
            <a href=".." class="back-arrow">
                <i class="fas fa-arrow-left"></i>
                <span>Volver</span>
            </a>
        <?php endif; ?>

        <header>
            <div class="logo-container">
                <i class="fas fa-code logo-icon"></i>
                <h1>CodeHub</h1>
            </div>
            <p class="subtitle">Centro de control de proyectos - Arrastra para reorganizar</p>
        </header>

        <div class="controls">
            <div class="controls-left">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Buscar proyectos..." autocomplete="off">
                    <i class="fas fa-search"></i>
                </div>
           
                <div class="action-buttons">
                     
                <div class="filter-dropdown" >
                    <button class="dropdown-toggle" id="sortToggle">
                        <i class="fas fa-sort"></i>
                        <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-left: 4px;"></i>
                    </button>
                    <div class="dropdown-menu" id="sortMenu">
                        <div class="dropdown-item active" data-sort="name-asc">
                            <i class="fas fa-sort-alpha-down"></i>
                            Nombre (A-Z)
                        </div>
                        <div class="dropdown-item" data-sort="name-desc">
                            <i class="fas fa-sort-alpha-up"></i>
                            Nombre (Z-A)
                        </div>
                        <div class="dropdown-item" data-sort="date-new">
                            <i class="fas fa-clock"></i>
                            Más recientes
                        </div>
                        <div class="dropdown-item" data-sort="date-old">
                            <i class="fas fa-history"></i>
                            Más antiguos
                        </div>
                    </div>
                </div>
                <button class="btn-action" id="refreshBtn" title="Recargar proyectos">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <div class="view-controls">
                    <button class="btn-view active" id="gridView" title="Vista de cuadrícula">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="btn-view" id="listView" title="Vista de lista">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            </div>
            
     
            <div class="stats">
                <span><i class="fas fa-folder"></i> <span id="projectCount"><?= count($carpetas) ?></span> proyectos</span>
            </div>
        </div>

        <div class="grid" id="projectGrid">
            <?php foreach ($carpetas as $carpeta): ?>
                <?php if (is_dir($carpeta)): ?>
                    <?php 
                        $info = detectarTipoProyecto($carpeta);
                        $numArchivos = contarArchivos($carpeta);
                        $ultimaMod = ultimaModificacion($carpeta);
                    ?>
                    <div class="card" draggable="true" data-carpeta="<?= htmlspecialchars($carpeta) ?>" data-tipo="<?= strtolower($info['tipo']) ?>" data-fecha="<?= filemtime($carpeta) ?>">
                        <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
                        <i class="far fa-star favorite-star" data-carpeta="<?= htmlspecialchars($carpeta) ?>"></i>
                        <div class="card-header" onclick="window.location.href='<?= htmlspecialchars($carpeta) ?>'">
                            <div class="card-icon-wrapper" style="background: linear-gradient(135deg, <?= $info['color'] ?>22, <?= $info['color'] ?>44);">
                                <i class="fas <?= $info['icono'] ?>" style="color: <?= $info['color'] ?>"></i>
                            </div>
                            <div class="card-content">
                                <a href="<?= htmlspecialchars($carpeta) ?>" class="card-title"><?= htmlspecialchars($carpeta) ?></a>
                                <span class="badge"><?= $info['tipo'] ?></span>
                            </div>
                        </div>
                        <div class="card-info">
                            <div class="info-item">
                                <i class="fas fa-file-code"></i>
                                <span><?= $numArchivos ?> archivos</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>Hace <?= $ultimaMod ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if (count($carpetas) == 0): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No hay proyectos para mostrar</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="contextMenu">
        <div class="menu-item" id="abrirVscode">
            <i class="fas fa-code"></i>
            <span>Abrir con VS Code</span>
        </div>
        <div class="menu-item" id="abrirExplorer">
            <i class="fas fa-folder-open"></i>
            <span>Abrir en Explorador</span>
        </div>
        <div class="menu-item" id="abrirTerminal">
            <i class="fas fa-terminal"></i>
            <span>Abrir Terminal</span>
        </div>
        <div class="menu-item" id="copiarRuta">
            <i class="fas fa-copy"></i>
            <span>Copiar ruta</span>
        </div>
    </div>

    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span id="notificationText"></span>
    </div>

    <script>
        let contextMenu = document.getElementById('contextMenu');
        let selectedFolder = '';
        let selectedCard = null;
        let favorites = JSON.parse(localStorage.getItem('codehub_favorites') || '[]');
        let currentSort = 'name-asc';
        let draggedCard = null;
        let cardOrder = JSON.parse(localStorage.getItem('codehub_card_order') || '[]');

        // Inicializar orden de cards
        function initCardOrder() {
            const cards = Array.from(document.querySelectorAll('.card'));
            
            if (cardOrder.length === 0) {
                cardOrder = cards.map(card => card.getAttribute('data-carpeta'));
                localStorage.setItem('codehub_card_order', JSON.stringify(cardOrder));
            } else {
                // Reorganizar cards según el orden guardado
                const grid = document.getElementById('projectGrid');
                const orderedCards = [];
                
                cardOrder.forEach(carpeta => {
                    const card = cards.find(c => c.getAttribute('data-carpeta') === carpeta);
                    if (card) orderedCards.push(card);
                });
                
                // Agregar cards que no están en el orden guardado
                cards.forEach(card => {
                    if (!orderedCards.includes(card)) {
                        orderedCards.push(card);
                        cardOrder.push(card.getAttribute('data-carpeta'));
                    }
                });
                
                orderedCards.forEach(card => grid.appendChild(card));
                localStorage.setItem('codehub_card_order', JSON.stringify(cardOrder));
            }
        }

        // Sistema de Drag & Drop
        function initDragAndDrop() {
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                card.addEventListener('dragstart', handleDragStart);
                card.addEventListener('dragend', handleDragEnd);
                card.addEventListener('dragover', handleDragOver);
                card.addEventListener('drop', handleDrop);
                card.addEventListener('dragenter', handleDragEnter);
                card.addEventListener('dragleave', handleDragLeave);
            });
        }

        function handleDragStart(e) {
            draggedCard = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        }

        function handleDragEnd(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('.card').forEach(card => {
                card.classList.remove('drag-over');
            });
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        }

        function handleDragEnter(e) {
            if (this !== draggedCard) {
                this.classList.add('drag-over');
            }
        }

        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            if (draggedCard !== this) {
                const draggedCarpeta = draggedCard.getAttribute('data-carpeta');
                const targetCarpeta = this.getAttribute('data-carpeta');
                
                const draggedIndex = cardOrder.indexOf(draggedCarpeta);
                const targetIndex = cardOrder.indexOf(targetCarpeta);
                
                cardOrder.splice(draggedIndex, 1);
                cardOrder.splice(targetIndex, 0, draggedCarpeta);
                
                localStorage.setItem('codehub_card_order', JSON.stringify(cardOrder));
                
                const grid = document.getElementById('projectGrid');
                const allCards = Array.from(grid.querySelectorAll('.card'));
                
                allCards.sort((a, b) => {
                    const aIndex = cardOrder.indexOf(a.getAttribute('data-carpeta'));
                    const bIndex = cardOrder.indexOf(b.getAttribute('data-carpeta'));
                    return aIndex - bIndex;
                });
                
                this.classList.add('dropping');
                setTimeout(() => {
                    this.classList.remove('dropping');
                    allCards.forEach(card => grid.appendChild(card));
                    mostrarNotificacion('Orden guardado correctamente');
                }, 100);
            }
            
            return false;
        }

        // Inicializar favoritos
        function initFavorites() {
            favorites.forEach(fav => {
                const star = document.querySelector(`.favorite-star[data-carpeta="${fav}"]`);
                if (star) {
                    star.classList.remove('far');
                    star.classList.add('fas', 'active');
                }
            });
        }

        // Toggle favorito
        document.querySelectorAll('.favorite-star').forEach(star => {
            star.addEventListener('click', (e) => {
                e.stopPropagation();
                const carpeta = star.getAttribute('data-carpeta');
                
                if (favorites.includes(carpeta)) {
                    favorites = favorites.filter(f => f !== carpeta);
                    star.classList.remove('fas', 'active');
                    star.classList.add('far');
                    mostrarNotificacion('Eliminado de favoritos');
                } else {
                    favorites.push(carpeta);
                    star.classList.remove('far');
                    star.classList.add('fas', 'active');
                    mostrarNotificacion('Agregado a favoritos');
                }
                
                localStorage.setItem('codehub_favorites', JSON.stringify(favorites));
            });
        });

        // Menú contextual
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('contextmenu', e => {
                e.preventDefault();
                e.stopPropagation();
                selectedFolder = card.getAttribute('data-carpeta');
                selectedCard = card;
                contextMenu.style.top = e.pageY + 'px';
                contextMenu.style.left = e.pageX + 'px';
                contextMenu.style.display = 'block';
            });
        });

        document.addEventListener('click', () => {
            contextMenu.style.display = 'none';
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        });

        // Dropdowns
        document.getElementById('sortToggle').addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = document.getElementById('sortMenu');
            menu.classList.toggle('show');
        });

        // Ordenamiento
        document.querySelectorAll('#sortMenu .dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('#sortMenu .dropdown-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                currentSort = item.getAttribute('data-sort');
                sortCards();
                document.getElementById('sortMenu').classList.remove('show');
            });
        });

        function sortCards() {
            const grid = document.getElementById('projectGrid');
            const cards = Array.from(grid.querySelectorAll('.card'));
            
            cards.sort((a, b) => {
                const nameA = a.getAttribute('data-carpeta').toLowerCase();
                const nameB = b.getAttribute('data-carpeta').toLowerCase();
                const dateA = parseInt(a.getAttribute('data-fecha'));
                const dateB = parseInt(b.getAttribute('data-fecha'));
                
                switch(currentSort) {
                    case 'name-asc':
                        return nameA.localeCompare(nameB);
                    case 'name-desc':
                        return nameB.localeCompare(nameA);
                    case 'date-new':
                        return dateB - dateA;
                    case 'date-old':
                        return dateA - dateB;
                    default:
                        return 0;
                }
            });
            
            cards.forEach(card => grid.appendChild(card));
            
            // Actualizar orden guardado
            cardOrder = cards.map(card => card.getAttribute('data-carpeta'));
            localStorage.setItem('codehub_card_order', JSON.stringify(cardOrder));
        }

        // Funciones del menú contextual
        function ejecutarAccion(accion) {
            fetch(`?accion=${accion}&carpeta=${encodeURIComponent(selectedFolder)}`)
                .then(response => response.json())
                .then(() => {
                    mostrarNotificacion(`Abriendo ${accion}...`);
                })
                .catch(() => {
                    mostrarNotificacion('Error al ejecutar la acción');
                });
        }

        document.getElementById('abrirVscode').addEventListener('click', () => ejecutarAccion('vscode'));
        document.getElementById('abrirExplorer').addEventListener('click', () => ejecutarAccion('explorer'));
        document.getElementById('abrirTerminal').addEventListener('click', () => ejecutarAccion('terminal'));

        document.getElementById('copiarRuta').addEventListener('click', () => {
            if (selectedFolder) {
                const rutaCompleta = window.location.href.replace(/\/$/, '') + '/' + selectedFolder;
                navigator.clipboard.writeText(rutaCompleta).then(() => {
                    mostrarNotificacion('Ruta copiada al portapapeles');
                });
            }
        });

        // Búsqueda
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.card');
            let visibleCount = 0;

            cards.forEach(card => {
                const carpeta = card.getAttribute('data-carpeta').toLowerCase();
                if (carpeta.includes(searchTerm)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('projectCount').textContent = visibleCount;
        });

        // Cambio de vista
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        const projectGrid = document.getElementById('projectGrid');

        gridView.addEventListener('click', () => {
            projectGrid.classList.remove('list-view');
            gridView.classList.add('active');
            listView.classList.remove('active');
            localStorage.setItem('codehub_view', 'grid');
        });

        listView.addEventListener('click', () => {
            projectGrid.classList.add('list-view');
            listView.classList.add('active');
            gridView.classList.remove('active');
            localStorage.setItem('codehub_view', 'list');
        });

        // Restaurar vista guardada
        const savedView = localStorage.getItem('codehub_view');
        if (savedView === 'list') {
            listView.click();
        }

        // Botón refresh
        document.getElementById('refreshBtn').addEventListener('click', () => {
            const btn = document.getElementById('refreshBtn');
            const icon = btn.querySelector('i');
            icon.style.animation = 'spin 0.5s linear';
            
            setTimeout(() => {
                icon.style.animation = '';
                mostrarNotificacion('Proyectos actualizados');
                location.reload();
            }, 500);
        });

        // Notificaciones
        function mostrarNotificacion(texto) {
            const notification = document.getElementById('notification');
            const notificationText = document.getElementById('notificationText');
            notificationText.textContent = texto;
            notification.style.display = 'flex';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }
            if (e.key === 'Escape') {
                contextMenu.style.display = 'none';
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                document.getElementById('refreshBtn').click();
            }
            // Resetear orden con Ctrl+Shift+R
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
                e.preventDefault();
                cardOrder = [];
                localStorage.removeItem('codehub_card_order');
                mostrarNotificacion('Orden restaurado - Recarga la página');
            }
        });

        // Inicializar todo
        initFavorites();
        initCardOrder();
        initDragAndDrop();
    </script>
</body>
</html>