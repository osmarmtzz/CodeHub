<?php
$carpetas = array_diff(scandir(__DIR__), ['.', '..', 'index.php', 'index.html']);
$mostrarVolver = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR) !== rtrim(realpath($_SERVER['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);

// Ruta base real
$rutaBase = str_replace('\\', '/', realpath(__DIR__)) . '/';

// Cargar configuraciones personalizadas
$configFile = __DIR__ . '/.codehub_config.json';
$customConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

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
    
    // Crear carpeta
    if ($_GET['accion'] === 'crear_carpeta' && isset($_POST['nombre'])) {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['nombre']);
        $rutaCarpeta = $rutaBase . $nombre;
        
        if (!file_exists($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0755, true);
            echo json_encode(['success' => true, 'mensaje' => 'Carpeta creada correctamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'La carpeta ya existe']);
        }
        exit;
    }
    
    // Crear archivo
    if ($_GET['accion'] === 'crear_archivo' && isset($_POST['nombre']) && isset($_POST['carpeta'])) {
        $carpeta = realpath($rutaBase . $_POST['carpeta']);
        $nombre = basename($_POST['nombre']);
        
        if ($carpeta && is_dir($carpeta)) {
            $rutaArchivo = $carpeta . '/' . $nombre;
            file_put_contents($rutaArchivo, '');
            echo json_encode(['success' => true, 'mensaje' => 'Archivo creado correctamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Carpeta no válida']);
        }
        exit;
    }
    
    // Guardar configuración personalizada
    if ($_GET['accion'] === 'guardar_config' && isset($_POST['carpeta'])) {
        $configFile = __DIR__ . '/.codehub_config.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        
        $carpeta = $_POST['carpeta'];
        $config[$carpeta] = [
            'icono' => $_POST['icono'] ?? null,
            'color' => $_POST['color'] ?? null,
            'imagen' => $_POST['imagen'] ?? null,
            'descripcion' => $_POST['descripcion'] ?? null
        ];
        
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'mensaje' => 'Configuración guardada']);
        exit;
    }
    
    // Subir imagen
    if ($_GET['accion'] === 'subir_imagen' && isset($_FILES['imagen']) && isset($_POST['carpeta'])) {
        $carpeta = $_POST['carpeta'];
        $dirImagenes = __DIR__ . '/.codehub_images';
        
        if (!file_exists($dirImagenes)) {
            mkdir($dirImagenes, 0755, true);
        }
        
        $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreArchivo = md5($carpeta . time()) . '.' . $extension;
        $rutaDestino = $dirImagenes . '/' . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            echo json_encode([
                'success' => true, 
                'imagen' => '.codehub_images/' . $nombreArchivo,
                'mensaje' => 'Imagen subida correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al subir imagen']);
        }
        exit;
    }
    
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
    <title>CodeHub - Project Dashboard</title>
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

        .btn-action.btn-create {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            color: white;
            font-weight: 600;
        }

        .btn-action.btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-action i {
            font-size: 1em;
        }

        .filter-dropdown {
            position: relative;
        }

        .dropdown-toggle {
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

        /* Eliminado - ahora está en card-actions */

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
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 10px;
            animation: fadeInUp 0.8s ease;
        }
        

        .grid.list-view {
            grid-template-columns: 1fr;
        }

        .card {
            background: rgba(255, 255, 255, 0.04);
            border-radius: 24px;
            padding: 0;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            min-height: 280px;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--card-gradient);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(
                circle at var(--mouse-x, 50%) var(--mouse-y, 50%), 
                var(--card-glow, rgba(59, 130, 246, 0.15)), 
                transparent 60%
            );
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .card:hover {
            transform: translateY(-18px) scale(1.03);
            border-color: var(--card-border, rgba(59, 130, 246, 0.6));
            background: rgba(255, 255, 255, 0.09);
            box-shadow: 
                0 32px 95px var(--card-shadow, rgba(59, 130, 246, 0.38)), 
                0 18px 45px rgba(0, 0, 0, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card:hover::after {
            opacity: 1;
        }

        .card:hover .card-actions {
            opacity: 1;
            transform: translateY(0);
        }

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
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8b949e;
            font-size: 1.1em;
            opacity: 0;
            transition: all 0.4s ease;
            cursor: grab;
            z-index: 10;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .card:hover .drag-handle {
            opacity: 0.8;
            transform: translateY(0);
        }

        .drag-handle:hover {
            opacity: 1 !important;
            color: var(--card-color, #3b82f6);
            background: var(--card-drag-bg, rgba(59, 130, 246, 0.25));
            border-color: var(--card-border, rgba(59, 130, 246, 0.5));
            transform: scale(1.1) rotate(5deg);
        }

        .drag-handle:active {
            cursor: grabbing;
            transform: scale(0.95);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 32px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .card:hover .card-header {
            padding-top: 36px;
        }

        .card-icon-wrapper {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            flex-shrink: 0;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            background-size: cover;
            background-position: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .card-icon-wrapper::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 22px;
            background: var(--card-gradient);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
            filter: blur(8px);
        }

        .card:hover .card-icon-wrapper {
            transform: scale(1.22) rotate(-11deg);
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.55);
        }

        .card:hover .card-icon-wrapper::before {
            opacity: 0.8;
        }

        .card.dragging .card-icon-wrapper {
            transform: scale(1.25) rotate(15deg);
        }

        .card-content {
            flex: 1;
            min-width: 0;
        }

        .card-title {
            font-size: 1.6em;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            word-break: break-word;
            transition: all 0.3s ease;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .card:hover .card-title {
            color: var(--card-color, #60a5fa);
            transform: translateX(7px);
            text-shadow: 0 0 22px var(--card-glow, rgba(96, 165, 250, 0.55));
        }

        .card-description {
            font-size: 0.95em;
            color: #b0b7c3;
            margin-top: 12px;
            line-height: 1.7;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-badges {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85em;
            font-weight: 600;
            background: var(--badge-bg, rgba(59, 130, 246, 0.15));
            color: var(--badge-color, #60a5fa);
            border: 1px solid var(--badge-border, rgba(59, 130, 246, 0.3));
            transition: all 0.3s ease;
        }

        .card:hover .badge {
            background: var(--badge-bg-hover, rgba(59, 130, 246, 0.25));
            border-color: var(--badge-border-hover, rgba(59, 130, 246, 0.5));
            transform: translateY(-4px);
            box-shadow: 0 8px 20px var(--badge-shadow, rgba(59, 130, 246, 0.4));
        }

        .card-info {
            display: flex;
            gap: 32px;
            padding: 24px 32px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.2);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #9ca3af;
            font-size: 0.95em;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .card:hover .info-item {
            color: #e5e7eb;
        }

        .info-item i {
            width: 22px;
            color: #6b7280;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }

        .card:hover .info-item i {
            color: var(--card-color, #3b82f6);
            transform: scale(1.2);
        }

        /* Acciones de la Card */
        .card-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.4s ease;
            z-index: 15;
        }

        .card-action-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1em;
        }

        .card-action-btn:hover {
            transform: scale(1.15) translateY(-3px);
        }

        .card-action-btn.customize {
            background: rgba(139, 92, 246, 0.7);
            border-color: rgba(139, 92, 246, 0.8);
        }

        .card-action-btn.customize:hover {
            background: rgba(139, 92, 246, 0.95);
            border-color: rgba(139, 92, 246, 1);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.5);
        }

        .card-action-btn.favorite {
            background: rgba(0, 0, 0, 0.6);
            border-color: rgba(251, 191, 36, 0.4);
        }

        .card-action-btn.favorite.active {
            background: rgba(251, 191, 36, 0.3);
            color: #fbbf24;
            border-color: rgba(251, 191, 36, 0.8);
        }

        .card-action-btn.favorite:hover {
            background: rgba(251, 191, 36, 0.4);
            border-color: rgba(251, 191, 36, 1);
            color: #fbbf24;
            box-shadow: 0 10px 30px rgba(251, 191, 36, 0.4);
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1001;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: #161b22;
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h2 {
            font-size: 1.5em;
            color: white;
        }

        .modal-close {
            background: none;
            border: none;
            color: #8b949e;
            font-size: 1.5em;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .modal-close:hover {
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #c9d1d9;
            margin-bottom: 8px;
            font-size: 0.95em;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 1em;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .color-icon-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .color-option,
        .icon-option {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
        }

        .color-option:hover,
        .icon-option:hover {
            transform: scale(1.1);
        }

        .color-option.selected,
        .icon-option.selected {
            border-color: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .icon-option {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .image-upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .image-upload-area:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .image-upload-area.has-image {
            padding: 0;
            border: none;
        }

        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #c9d1d9;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
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

            .color-icon-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .card {
                min-height: 240px;
            }

            .card-icon-wrapper {
                width: 70px;
                height: 70px;
                font-size: 2em;
            }

            .card-title {
                font-size: 1.3em;
            }

            .card-header {
                padding: 24px;
            }

            .card-info {
                padding: 20px 24px;
                gap: 20px;
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

        /* Eliminado - ahora está en card-actions */
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
                <span>Back</span>
            </a>
        <?php endif; ?>

        <header>
            <div class="logo-container">
                <i class="fas fa-code logo-icon"></i>
                <h1>CodeHub</h1>
            </div>
            <p class="subtitle">Project Control Center - Drag to reorder</p>
        </header>

        <div class="controls">
            <div class="controls-left">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search projects..." autocomplete="off">
                    <i class="fas fa-search"></i>
                </div>
           
                <div class="action-buttons">
                    <button class="btn-action btn-create" id="createFolderBtn">
                        <i class="fas fa-folder-plus"></i>
                        New Folder
                    </button>
                     
                    <div class="filter-dropdown" hidden>
                        <button class="dropdown-toggle" id="sortToggle" >
                            <i class="fas fa-sort"></i>
                            <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-left: 4px;"></i>
                        </button>
                        <div class="dropdown-menu" id="sortMenu">
                            <div class="dropdown-item active" data-sort="name-asc">
                                <i class="fas fa-sort-alpha-down"></i>
                                Name (A-Z)
                            </div>
                            <div class="dropdown-item" data-sort="name-desc">
                                <i class="fas fa-sort-alpha-up"></i>
                                Name (Z-A)
                            </div>
                            <div class="dropdown-item" data-sort="date-new">
                                <i class="fas fa-clock"></i>
                                Most Recent
                            </div>
                            <div class="dropdown-item" data-sort="date-old">
                                <i class="fas fa-history"></i>
                                Oldest
                            </div>
                        </div>
                    </div>
                    <button class="btn-action" id="refreshBtn" title="Refresh projects">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="view-controls">
                        <button class="btn-view active" id="gridView" title="Grid view">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="btn-view" id="listView" title="List view">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="stats">
                <span><i class="fas fa-folder"></i> <span id="projectCount"><?= count($carpetas) ?></span> projects</span>
            </div>
        </div>

        <div class="grid" id="projectGrid">
            <?php foreach ($carpetas as $carpeta): ?>
                <?php if (is_dir($carpeta)): ?>
                    <?php 
                        $info = detectarTipoProyecto($carpeta);
                        $numArchivos = contarArchivos($carpeta);
                        $ultimaMod = ultimaModificacion($carpeta);
                        
                        // Cargar configuración personalizada
                        $customInfo = $customConfig[$carpeta] ?? [];
                        $icono = $customInfo['icono'] ?? $info['icono'];
                        $color = $customInfo['color'] ?? $info['color'];
                        $imagen = $customInfo['imagen'] ?? null;
                        $descripcion = $customInfo['descripcion'] ?? null;
                        
                        // Calcular colores derivados para efectos
                        $colorRGB = sscanf($color, "#%02x%02x%02x");
                        $glowColor = "rgba({$colorRGB[0]}, {$colorRGB[1]}, {$colorRGB[2]}, 0.15)";
                        $borderColor = "rgba({$colorRGB[0]}, {$colorRGB[1]}, {$colorRGB[2]}, 0.6)";
                        $shadowColor = "rgba({$colorRGB[0]}, {$colorRGB[1]}, {$colorRGB[2]}, 0.35)";
                        $badgeBg = "rgba({$colorRGB[0]}, {$colorRGB[1]}, {$colorRGB[2]}, 0.15)";
                        $badgeBorder = "rgba({$colorRGB[0]}, {$colorRGB[1]}, {$colorRGB[2]}, 0.3)";
                    ?>
                    <div class="card" draggable="true" 
                         data-carpeta="<?= htmlspecialchars($carpeta) ?>" 
                         data-tipo="<?= strtolower($info['tipo']) ?>" 
                         data-fecha="<?= filemtime($carpeta) ?>"
                         style="--card-color: <?= $color ?>; 
                                --card-gradient: linear-gradient(90deg, <?= $color ?>, <?= $color ?>dd);
                                --card-glow: <?= $glowColor ?>;
                                --card-border: <?= $borderColor ?>;
                                --card-shadow: <?= $shadowColor ?>;
                                --badge-bg: <?= $badgeBg ?>;
                                --badge-border: <?= $badgeBorder ?>;
                                --badge-bg-hover: rgba(<?= $colorRGB[0] ?>, <?= $colorRGB[1] ?>, <?= $colorRGB[2] ?>, 0.25);
                                --badge-border-hover: rgba(<?= $colorRGB[0] ?>, <?= $colorRGB[1] ?>, <?= $colorRGB[2] ?>, 0.5);
                                --badge-shadow: <?= $shadowColor ?>;
                                --badge-color: <?= $color ?>;
                                --card-drag-bg: rgba(<?= $colorRGB[0] ?>, <?= $colorRGB[1] ?>, <?= $colorRGB[2] ?>, 0.25);">
                        <div class="drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></div>
                        
                        <div class="card-actions">
                            <div class="card-action-btn customize" onclick="abrirModalPersonalizar('<?= htmlspecialchars($carpeta) ?>', event)" title="Customize">
                                <i class="fas fa-paint-brush"></i>
                            </div>
                            <div class="card-action-btn favorite" data-carpeta="<?= htmlspecialchars($carpeta) ?>" title="Favorite">
                                <i class="far fa-star"></i>
                            </div>
                        </div>

                        <div class="card-header" onclick="window.location.href='<?= htmlspecialchars($carpeta) ?>'">
                            <div class="card-icon-wrapper" 
                                 style="<?= $imagen ? 'background-image: url(' . htmlspecialchars($imagen) . ');' : 'background: linear-gradient(135deg, ' . $color . '22, ' . $color . '44);' ?>">
                                <?php if (!$imagen): ?>
                                    <i class="fas <?= $icono ?>" style="color: <?= $color ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-content">
                                <a href="<?= htmlspecialchars($carpeta) ?>" class="card-title"><?= htmlspecialchars($carpeta) ?></a>
                                <div class="card-badges">
                                    <span class="badge"><i class="fas fa-tag"></i> <?= $info['tipo'] ?></span>
                                </div>
                                <?php if ($descripcion): ?>
                                    <div class="card-description"><?= htmlspecialchars($descripcion) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-info">
                            <div class="info-item">
                                <i class="fas fa-file-code"></i>
                                <span><?= $numArchivos ?> files</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span><?= $ultimaMod ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if (count($carpetas) == 0): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No projects to display</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Carpeta -->
    <div class="modal" id="modalCrearCarpeta">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-folder-plus"></i> New Folder</h2>
                <button class="modal-close" onclick="cerrarModal('modalCrearCarpeta')">&times;</button>
            </div>
            <form id="formCrearCarpeta">
                <div class="form-group">
                    <label>Folder name</label>
                    <input type="text" id="nombreCarpeta" placeholder="my-project" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check"></i> Create Folder
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Crear Archivo -->
    <div class="modal" id="modalCrearArchivo">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-code"></i> New File</h2>
                <button class="modal-close" onclick="cerrarModal('modalCrearArchivo')">&times;</button>
            </div>
            <form id="formCrearArchivo">
                <div class="form-group">
                    <label>File name</label>
                    <input type="text" id="nombreArchivo" placeholder="index.html" required>
                </div>
                <input type="hidden" id="carpetaArchivo">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check"></i> Create File
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Personalizar -->
    <div class="modal" id="modalPersonalizar">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-paint-brush"></i> Customize Project</h2>
                <button class="modal-close" onclick="cerrarModal('modalPersonalizar')">&times;</button>
            </div>
            <form id="formPersonalizar">
                <input type="hidden" id="carpetaPersonalizar">
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="descripcionPersonalizar" placeholder="Describe your project..."></textarea>
                </div>

                <div class="form-group">
                    <label>Icon color</label>
                    <div class="color-icon-grid" id="coloresGrid"></div>
                </div>

                <div class="form-group">
                    <label>Icon</label>
                    <div class="color-icon-grid" id="iconosGrid"></div>
                </div>

                <div class="form-group">
                    <label>Custom image (optional)</label>
                    <div class="image-upload-area" id="imageUploadArea">
                        <i class="fas fa-image" style="font-size: 2em; color: #8b949e; margin-bottom: 10px;"></i>
                        <p style="color: #8b949e;">Click to upload an image</p>
                        <input type="file" id="imagenPersonalizar" accept="image/*" style="display: none;">
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalPersonalizar')">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- Menú Contextual -->
    <div id="contextMenu">
        <div class="menu-item" id="personalizarCard">
            <i class="fas fa-paint-brush"></i>
            <span>Customize</span>
        </div>
        <div class="menu-item" id="crearArchivo">
            <i class="fas fa-file-code"></i>
            <span>Create File</span>
        </div>
        <div class="menu-item" id="abrirVscode">
            <i class="fas fa-code"></i>
            <span>Open with VS Code</span>
        </div>
        <div class="menu-item" id="abrirExplorer">
            <i class="fas fa-folder-open"></i>
            <span>Open in Explorer</span>
        </div>
        <div class="menu-item" id="abrirTerminal">
            <i class="fas fa-terminal"></i>
            <span>Open Terminal</span>
        </div>
        <div class="menu-item" id="copiarRuta">
            <i class="fas fa-copy"></i>
            <span>Copy path</span>
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

        // Colores e iconos disponibles
        const colores = [
            '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', 
            '#10b981', '#06b6d4', '#ef4444', '#6366f1',
            '#14b8a6', '#f97316', '#a855f7', '#22c55e'
        ];

        const iconos = [
            'fa-code', 'fa-laptop-code', 'fa-terminal', 'fa-database',
            'fa-server', 'fa-mobile-alt', 'fa-globe', 'fa-rocket',
            'fa-cog', 'fa-palette', 'fa-chart-line', 'fa-shopping-cart',
           
        ];

        let selectedColor = colores[0];
        let selectedIcon = iconos[0];
        let uploadedImage = null;

        // Inicializar orden de cards
        function initCardOrder() {
            const cards = Array.from(document.querySelectorAll('.card'));
            
            if (cardOrder.length === 0) {
                cardOrder = cards.map(card => card.getAttribute('data-carpeta'));
                localStorage.setItem('codehub_card_order', JSON.stringify(cardOrder));
            } else {
                const grid = document.getElementById('projectGrid');
                const orderedCards = [];
                
                cardOrder.forEach(carpeta => {
                    const card = cards.find(c => c.getAttribute('data-carpeta') === carpeta);
                    if (card) orderedCards.push(card);
                });
                
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
                    mostrarNotificacion('Order saved successfully');
                }, 100);
            }
            
            return false;
        }

        // Modales
        function abrirModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function abrirModalPersonalizar(carpeta, event) {
            event.stopPropagation();
            document.getElementById('carpetaPersonalizar').value = carpeta;
            generarColoresGrid();
            generarIconosGrid();
            abrirModal('modalPersonalizar');
        }

        // Generar grids de colores e iconos
        function generarColoresGrid() {
            const grid = document.getElementById('coloresGrid');
            grid.innerHTML = '';
            colores.forEach(color => {
                const div = document.createElement('div');
                div.className = 'color-option';
                div.style.background = color;
                div.onclick = () => {
                    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
                    div.classList.add('selected');
                    selectedColor = color;
                };
                grid.appendChild(div);
            });
            grid.firstChild.classList.add('selected');
        }

        function generarIconosGrid() {
            const grid = document.getElementById('iconosGrid');
            grid.innerHTML = '';
            iconos.forEach(icono => {
                const div = document.createElement('div');
                div.className = 'icon-option';
                div.innerHTML = `<i class="fas ${icono}"></i>`;
                div.onclick = () => {
                    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
                    div.classList.add('selected');
                    selectedIcon = icono;
                };
                grid.appendChild(div);
            });
            grid.firstChild.classList.add('selected');
        }

        // Upload de imagen
        document.getElementById('imageUploadArea').addEventListener('click', () => {
            document.getElementById('imagenPersonalizar').click();
        });

        document.getElementById('imagenPersonalizar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const area = document.getElementById('imageUploadArea');
                    area.innerHTML = `<img src="${e.target.result}" class="image-preview">`;
                    area.classList.add('has-image');
                    uploadedImage = file;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form crear carpeta
        document.getElementById('createFolderBtn').addEventListener('click', () => {
            abrirModal('modalCrearCarpeta');
        });

        document.getElementById('formCrearCarpeta').addEventListener('submit', async (e) => {
            e.preventDefault();
            const nombre = document.getElementById('nombreCarpeta').value;
            
            const formData = new FormData();
            formData.append('nombre', nombre);
            
            const response = await fetch('?accion=crear_carpeta', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            mostrarNotificacion(result.mensaje);
            
            if (result.success) {
                cerrarModal('modalCrearCarpeta');
                setTimeout(() => location.reload(), 1000);
            }
        });

        // Form crear archivo
        document.getElementById('formCrearArchivo').addEventListener('submit', async (e) => {
            e.preventDefault();
            const nombre = document.getElementById('nombreArchivo').value;
            const carpeta = document.getElementById('carpetaArchivo').value;
            
            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('carpeta', carpeta);
            
            const response = await fetch('?accion=crear_archivo', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            mostrarNotificacion(result.mensaje);
            
            if (result.success) {
                cerrarModal('modalCrearArchivo');
            }
        });

        // Form personalizar
        document.getElementById('formPersonalizar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const carpeta = document.getElementById('carpetaPersonalizar').value;
            const descripcion = document.getElementById('descripcionPersonalizar').value;
            
            let imagenUrl = null;
            
            // Si hay imagen, subirla primero
            if (uploadedImage) {
                const formData = new FormData();
                formData.append('imagen', uploadedImage);
                formData.append('carpeta', carpeta);
                
                const uploadResponse = await fetch('?accion=subir_imagen', {
                    method: 'POST',
                    body: formData
                });
                
                const uploadResult = await uploadResponse.json();
                if (uploadResult.success) {
                    imagenUrl = uploadResult.imagen;
                }
            }
            
            // Guardar configuración
            const configData = new FormData();
            configData.append('carpeta', carpeta);
            configData.append('icono', selectedIcon);
            configData.append('color', selectedColor);
            configData.append('descripcion', descripcion);
            if (imagenUrl) {
                configData.append('imagen', imagenUrl);
            }
            
            const response = await fetch('?accion=guardar_config', {
                method: 'POST',
                body: configData
            });
            
            const result = await response.json();
            mostrarNotificacion(result.mensaje);
            
            if (result.success) {
                cerrarModal('modalPersonalizar');
                setTimeout(() => location.reload(), 1000);
            }
        });

        // Inicializar favoritos
        function initFavorites() {
            favorites.forEach(fav => {
                const favBtn = document.querySelector(`.card-action-btn.favorite[data-carpeta="${fav}"]`);
                if (favBtn) {
                    favBtn.classList.add('active');
                    const icon = favBtn.querySelector('i');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
            });
        }

        // Toggle favorito
        document.querySelectorAll('.card-action-btn.favorite').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const carpeta = btn.getAttribute('data-carpeta');
                const icon = btn.querySelector('i');
                
                if (favorites.includes(carpeta)) {
                    favorites = favorites.filter(f => f !== carpeta);
                    btn.classList.remove('active');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    mostrarNotificacion('Removed from favorites');
                } else {
                    favorites.push(carpeta);
                    btn.classList.add('active');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    mostrarNotificacion('Added to favorites');
                }
                
                localStorage.setItem('codehub_favorites', JSON.stringify(favorites));
            });
        });

        // Efecto de mouse en las cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                card.style.setProperty('--mouse-x', `${x}%`);
                card.style.setProperty('--mouse-y', `${y}%`);
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
            
            cardOrder = cards.map(card => card.getAttribute('data-carpeta'));
            localStorage.setItem('codehub_card_order', JSON.stringify(cardOrder));
        }

        // Funciones del menú contextual
        function ejecutarAccion(accion) {
            fetch(`?accion=${accion}&carpeta=${encodeURIComponent(selectedFolder)}`)
                .then(response => response.json())
                .then(() => {
                    mostrarNotificacion(`Opening ${accion}...`);
                })
                .catch(() => {
                    mostrarNotificacion('Error executing action');
                });
        }

        document.getElementById('personalizarCard').addEventListener('click', () => {
            contextMenu.style.display = 'none';
            abrirModalPersonalizar(selectedFolder, new Event('click'));
        });

        document.getElementById('crearArchivo').addEventListener('click', () => {
            contextMenu.style.display = 'none';
            document.getElementById('carpetaArchivo').value = selectedFolder;
            abrirModal('modalCrearArchivo');
        });

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
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                document.getElementById('refreshBtn').click();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                abrirModal('modalCrearCarpeta');
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
                e.preventDefault();
                cardOrder = [];
                localStorage.removeItem('codehub_card_order');
                mostrarNotificacion('Orden restaurado - Recarga la página');
            }
        });

        // Cerrar modales al hacer click fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
        });

        // Inicializar todo
        initFavorites();
        initCardOrder();
        initDragAndDrop();
    </script>
</body>
</html>
