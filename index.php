<?php
$carpetas = array_diff(scandir(__DIR__), ['.', '..', 'index.php', 'index.html']);
$mostrarVolver = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR) !== rtrim(realpath($_SERVER['DOCUMENT_ROOT']), DIRECTORY_SEPARATOR);

// Ruta base real
$rutaBase = str_replace('\\', '/', realpath(__DIR__)) . '/';

// Acciones para abrir desde PHP sin recargar página
if (isset($_GET['accion']) && isset($_GET['carpeta'])) {
    $carpetaAbrir = realpath($rutaBase . $_GET['carpeta']);
    if ($carpetaAbrir && is_dir($carpetaAbrir)) {
        if ($_GET['accion'] === 'explorer') {
            exec("explorer " . escapeshellarg($carpetaAbrir));
        }
        if ($_GET['accion'] === 'vscode') {
            exec("code --new-window " . escapeshellarg($carpetaAbrir));
        }
    }
    exit; // No devolver HTML, solo ejecutar
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Localhost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="cafe.png" type="image/x-icon">
    <style>
        /* (estilos igual que antes, sin cambios) */
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', sans-serif;
            color: white;
            overflow-x: hidden;
            background: transparent;
        }

        #particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(circle at center, #030617 0%, #000 100%);
        }

        header {
            text-align: center;
            padding: 50px 20px 30px;
            animation: fadeIn 1s ease-in-out;
            position: relative;
            z-index: 1;
        }

        h1 {
            font-size: 3em;
            font-weight: 700;
            color: #00f0ff;
            text-shadow: 0 0 15px rgba(0, 240, 255, 0.8);
        }

        p {
            color: #94a3b8;
            font-size: 1.1em;
            margin-top: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            padding: 30px;
            max-width: 1200px;
            margin: auto;
            animation: fadeIn 1.2s ease-in-out;
            position: relative;
            z-index: 1;
        }

        .card {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: transform 0.5s ease, box-shadow 0.5s ease, background 0.5s ease;
            animation: slideUp 0.5s ease forwards;
            cursor: pointer;
        }

        .card:hover {
            transform: rotateY(10deg) rotateX(5deg) scale(1.05);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 25px rgba(0, 255, 200, 0.7), 0 0 50px rgba(255, 0, 200, 0.5);
        }

        .icon {
            font-size: 3.8em;
            margin-bottom: 12px;
            color: #00ff9d;
            filter: drop-shadow(0 0 8px rgba(0, 255, 157, 0.6));
            transition: color 0.4s ease;
        }

        .card:hover .icon {
            color: #ffea00;
            filter: drop-shadow(0 0 8px rgba(255, 234, 0, 0.6));
        }

        a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            font-size: 1.15em;
            display: block;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }

        .card:hover a {
            color: #ffea00;
        }

        .back-arrow {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #00f0ff;
            font-size: 1.5em;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
            z-index: 2;
            text-shadow: 0 0 15px rgba(0, 240, 255, 0.8);
        }

        .back-arrow:hover {
            color: #ffea00;
            text-shadow: 0 0 15px rgba(255, 234, 0, 0.8);
        }

        #contextMenu {
            position: absolute;
            display: none;
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            padding: 5px 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        #contextMenu div {
            padding: 8px 15px;
            cursor: pointer;
            color: white;
            font-size: 0.95em;
        }

        #contextMenu div:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>

    <div id="particles-js"></div>

    <header>
        <?php if ($mostrarVolver): ?>
            <a href=".." class="back-arrow">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        <?php endif; ?>
        <h1>Panel de Proyectos</h1>
        <p>Selecciona un proyecto</p>
    </header>

    <div class="grid">
        <?php foreach ($carpetas as $carpeta): ?>
            <?php if (is_dir($carpeta)): ?>
                <div class="card" data-carpeta="<?= $carpeta ?>" onclick="window.location.href='<?= $carpeta ?>'">
                    <div class="icon"><i class="fas fa-folder-open"></i></div>
                    <a href="<?= $carpeta ?>"><?= $carpeta ?></a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Menú contextual -->
    <div id="contextMenu">
        <div id="abrirVscode"><i class="fas fa-code"></i> Abrir con VS Code</div>
        <div id="abrirExplorer"><i class="fas fa-folder-open"></i> Abrir en Explorador</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
            particles: {
                number: {
                    value: 90,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: ["#00f0ff", "#00ff9d", "#ff00d4", "#ffea00"]
                },
                shape: {
                    type: "circle"
                },
                opacity: {
                    value: 0.8,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 1,
                        opacity_min: 0.4
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 2,
                        size_min: 0.5
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: "#00f0ff",
                    opacity: 0.3,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 1.5,
                    random: true,
                    direction: "none",
                    out_mode: "out"
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: {
                        enable: true,
                        mode: "grab"
                    },
                    onclick: {
                        enable: true,
                        mode: "repulse"
                    }
                },
                modes: {
                    grab: {
                        distance: 200,
                        line_linked: {
                            opacity: 0.6
                        }
                    },
                    repulse: {
                        distance: 150
                    }
                }
            },
            retina_detect: true
        });

        let contextMenu = document.getElementById('contextMenu');
        let selectedFolder = '';

        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('contextmenu', e => {
                e.preventDefault();
                selectedFolder = card.getAttribute('data-carpeta');
                contextMenu.style.top = e.pageY + 'px';
                contextMenu.style.left = e.pageX + 'px';
                contextMenu.style.display = 'block';
            });
        });

        document.addEventListener('click', () => {
            contextMenu.style.display = 'none';
        });

        function ejecutarAccion(accion) {
            fetch(`?accion=${accion}&carpeta=${encodeURIComponent(selectedFolder)}`);
        }

        document.getElementById('abrirVscode').addEventListener('click', () => {
            ejecutarAccion('vscode');
        });
        document.getElementById('abrirExplorer').addEventListener('click', () => {
            ejecutarAccion('explorer');
        });
    </script>

</body>

</html>