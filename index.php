<?php
$directory = 'modeles'; // Répertoire où sont stockés les fichiers STL

// Fonction pour lister récursivement les fichiers STL dans un dossier et ses sous-dossiers
function listStlFiles($dir) {
    $items = scandir($dir);
    $stlFiles = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            // Appel récursif pour explorer les sous-dossiers
            $stlFiles = array_merge($stlFiles, listStlFiles($path));
        } elseif (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'stl') {
            $stlFiles[] = $path;
        }
    }
    return $stlFiles;
}

// Vérifier si le répertoire existe
if (!is_dir($directory)) {
    die("<div class='error'>Erreur : Le dossier $directory n'existe pas !</div>");
}

// Récupérer la liste de tous les fichiers STL de manière récursive
$allStlFiles = listStlFiles($directory);

// Organiser les fichiers par dossier parent pour l'affichage
$filesByFolder = [];
foreach ($allStlFiles as $file) {
    $folder = dirname($file);
    if (!isset($filesByFolder[$folder])) {
        $filesByFolder[$folder] = [];
    }
    $filesByFolder[$folder][] = $file;
}

// Gestion de la requête pour servir le fichier STL
if (isset($_GET['view_stl'])) {
    $relativePath = $_GET['view_stl']; // Utiliser directement la valeur reçue

    $filePath = realpath($directory . DIRECTORY_SEPARATOR . $relativePath);

    error_log("Chemin complet tenté (directement depuis _GET): " . $filePath);
    error_log("Chemin réel du répertoire ($directory): " . realpath($directory));

    if ($filePath !== false) {
        error_log("Position de la racine dans le chemin: " . strpos($filePath, realpath($directory)));
    }

    if ($filePath !== false && strpos($filePath, realpath($directory)) === 0 && is_file($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else {
        error_log("Erreur : Fichier STL non trouvé ou accès non autorisé pour le chemin direct: " . $filePath);
        http_response_code(404);
        echo 'Fichier STL non trouvé ou accès non autorisé.';
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des fichiers STL</title>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/stl-loader@1.0.0/STLLoader.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { text-align: center; color: #333; }
        .folder { margin: 15px 0; padding: 10px; background: #e3e3e3; border-radius: 5px; }
        .file-list { list-style: none; padding: 0; }
        .file-list li { padding: 8px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .button { padding: 6px 10px; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .button:hover { background: #0056b3; }
        /* Styles pour la modale (initialement cachée) */
        .modal {
            display: none; /* Important : cache initialement la modale */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            max-width: 600px;
            background: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            z-index: 1000; /* Doit être supérieur à l'overlay */
        }
        /* Styles pour l'overlay (initialement caché) */
        .overlay {
            display: none; /* Important : cache initialement l'overlay */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999; /* Doit être inférieur à la modale */
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 50%;
        }
        .viewer {
            width: 100%;
            height: 400px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Liste des fichiers STL</h1>
        <?php if (empty($filesByFolder)): ?>
            <p>Aucun fichier STL trouvé dans le répertoire '<?= htmlspecialchars($directory) ?>' ou ses sous-dossiers.</p>
        <?php else: ?>
            <?php foreach ($filesByFolder as $folder => $files): ?>
                <div class="folder">
                    <strong><?= basename($folder) ?></strong>
                    <ul class="file-list">
                        <?php foreach ($files as $file): ?>
                            <?php
                            $relativePath = str_replace(realpath($directory) . DIRECTORY_SEPARATOR, '', $file);
                            $fileUrl = htmlspecialchars($relativePath);
                            ?>
                            <li>
                                <?= basename($file) ?>
                                <div>
                                    <a href="<?= urlencode($fileUrl) ?>" download class="button"><i class="fas fa-download"></i> Télécharger</a>
                                    <button class="button" onclick="viewSTL('<?= urlencode($relativePath) ?>')"><i class="fas fa-eye"></i> Voir</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="overlay" id="overlay" onclick="closeModal()"></div>
    <div class="modal" id="stlModal">
        <button class="close-btn" onclick="closeModal()">X</button>
        <div class="modal-content">
            <h2>Visualisation STL</h2>
            <div id="stlViewer" class="viewer"></div>
        </div>
    </div>

    <script>
        let scene, camera, renderer, controls;

        function initViewer() {
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
            camera.position.set(0, 0, 100);
            renderer = new THREE.WebGLRenderer({ antialias: true });

            let viewer = document.getElementById("stlViewer");
            viewer.innerHTML = ""; // Nettoyer le contenu précédent
            viewer.appendChild(renderer.domElement);

            const width = viewer.offsetWidth;
            const height = viewer.offsetHeight;
            renderer.setSize(width, height);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();

            let light = new THREE.DirectionalLight(0xffffff, 1);
            light.position.set(0, 0, 100);
            scene.add(light);
            scene.add(new THREE.AmbientLight(0x404040));
            controls = new THREE.OrbitControls(camera, renderer.domElement);
        }

        function clearScene() {
            while (scene.children.length > 0) {
                scene.remove(scene.children[0]);
            }
            const axesHelper = scene.getObjectByName('axesHelper');
            if (axesHelper) {
                scene.remove(axesHelper);
            }
        }

        function viewSTL(relativePathEncoded) {
            console.log('viewSTL appelée avec le chemin relatif encodé :', relativePathEncoded);
            const stlUrl = '?view_stl=' + relativePathEncoded; // Construction de l'URL

            if (!scene) initViewer();
            clearScene();

            let loader = new THREE.STLLoader();
            loader.load(stlUrl, function (geometry) {
                const material = new THREE.MeshNormalMaterial();
                const mesh = new THREE.Mesh(geometry, material);

                const box = new THREE.Box3().setFromObject(mesh);
                const center = box.getCenter(new THREE.Vector3());
                mesh.position.sub(center);
                const size = box.getSize(new THREE.Vector3()).length();
                const scaleFactor = 100 / size;
                mesh.scale.set(scaleFactor, scaleFactor, scaleFactor);

                scene.add(mesh);

                const axesHelper = new THREE.AxesHelper(50);
                axesHelper.name = 'axesHelper';
                scene.add(axesHelper);

                camera.position.set(0, 0, 150);
                controls.target.set(0, 0, 0);
                controls.update();

                animate();
            }, (xhr) => {
                console.log((xhr.loaded / xhr.total * 100) + '% loaded');
            }, (error) => {
                console.error('Une erreur est survenue lors du chargement du fichier STL', error);
            });

            document.getElementById("stlModal").style.display = "block";
            document.getElementById("overlay").style.display = "block";
        }

        function closeModal() {
            document.getElementById("stlModal").style.display = "none";
            document.getElementById("overlay").style.display = "none";
            clearScene();
        }

        function animate() {
            requestAnimationFrame(animate);
            controls.update();
            renderer.render(scene, camera);
        }
    </script>
</body>
</html>