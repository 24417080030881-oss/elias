
<?php
// =====================================
// CONEXIÓN A LA BASE DE DATOS
// =====================================
$conexion = new mysqli("localhost", "root", "", "cine");
$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

// =====================================
// PROCESAR EL FORMULARIO
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre    = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $numero    = trim($_POST["numero"] ?? "");
    $correo    = trim($_POST["correo"] ?? "");
    $director  = trim($_POST["director"] ?? "");
    $genero    = trim($_POST["genero"] ?? "");
    $anio      = filter_input(INPUT_POST, "anio", FILTER_VALIDATE_INT);

    if (
        $nombre === "" ||
        $apellidos === "" ||
        $numero === "" ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        $director === "" ||
        $genero === "" ||
        $anio === false ||
        $anio === null ||
        $anio < 1888 ||
        $anio > (int)date("Y") + 5
    ) {
        $mensaje = "Revisa los datos del formulario.";
    } elseif (
        !isset($_FILES["img"]) ||
        $_FILES["img"]["error"] !== UPLOAD_ERR_OK
    ) {
        $mensaje = "Selecciona una imagen para la película.";
    } elseif ($_FILES["img"]["size"] > 5 * 1024 * 1024) {
        $mensaje = "La imagen no puede superar los 5 MB.";
    } else {
        $temporal = $_FILES["img"]["tmp_name"];
        $imagenInfo = getimagesize($temporal);
        $mime = $imagenInfo["mime"] ?? "";

        $tipos = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        if ($imagenInfo === false || !isset($tipos[$mime])) {
            $mensaje = "Usa una imagen JPG, PNG o WEBP.";
        } else {
            $carpeta = __DIR__ . "/uploads/";

            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            $archivo = bin2hex(random_bytes(16))
                     . "." . $tipos[$mime];

            $rutaArchivo = $carpeta . $archivo;
            $rutaBD = "uploads/" . $archivo;

            if (move_uploaded_file($temporal, $rutaArchivo)) {
                $sql = "INSERT INTO peliculas
                    (nombre, apellidos, numero, correo,
                     director, genero, anio, img)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conexion->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssssis",
                        $nombre,
                        $apellidos,
                        $numero,
                        $correo,
                        $director,
                        $genero,
                        $anio,
                        $rutaBD
                    );

                    if ($stmt->execute()) {
                        $mensaje = "¡Película registrada correctamente!";
                    } else {
                        unlink($rutaArchivo);
                        $mensaje = "No se pudo guardar el registro.";
                    }

                    $stmt->close();
                } else {
                    unlink($rutaArchivo);
                    $mensaje = "Error al preparar el registro.";
                }
            } else {
                $mensaje = "No se pudo guardar la imagen.";
            }
        }
    }
}

// =====================================
// CONSULTAR REGISTROS
// =====================================
$resultado = $conexion->query(
    "SELECT * FROM peliculas ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de películas</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px 15px;
            background: #101010;
            color: #eee;
            font-family: Arial, sans-serif;
        }

        .contenedor {
            max-width: 700px;
            margin: 0 auto 35px;
            padding: 25px;
            background: #1b1b1b;
            border: 1px solid #72572f;
            border-radius: 12px;
        }

        h1, h2 {
            color: #f0c66b;
            text-align: center;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #555;
            border-radius: 6px;
            background: #292929;
            color: white;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #c9a04f;
            color: #111;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #f0c66b;
        }

        .mensaje {
            padding: 12px;
            margin: 15px 0;
            background: #292929;
            border: 1px solid #c9a04f;
            border-radius: 6px;
            text-align: center;
        }

        .tabla {
            max-width: 1200px;
            margin: auto;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1b1b1b;
        }

        th, td {
            padding: 10px;
            border: 1px solid #45403a;
            text-align: left;
        }

        th {
            color: #f0c66b;
        }

        td img {
            width: 75px;
            height: 100px;
            object-fit: cover;
        }
    </style>
</head>

<body>

<div class="contenedor">
    <h1>Registro de películas</h1>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label for="nombre">Nombre de la película:</label>
        <input type="text" id="nombre" name="nombre"
               maxlength="100" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" id="apellidos" name="apellidos"
               maxlength="100" required>

        <label for="numero">Número:</label>
        <input type="text" id="numero" name="numero"
               maxlength="20" required>

        <label for="correo">Correo electrónico:</label>
        <input type="email" id="correo" name="correo"
               maxlength="150" required>

        <label for="director">Director:</label>
        <input type="text" id="director" name="director"
               maxlength="150" required>

        <label for="genero">Género:</label>
        <select id="genero" name="genero" required>
            <option value="">Selecciona una opción</option>
            <option value="Acción">Acción</option>
            <option value="Animación">Animación</option>
            <option value="Aventura">Aventura</option>
            <option value="Ciencia ficción">Ciencia ficción</option>
            <option value="Comedia">Comedia</option>
            <option value="Drama">Drama</option>
            <option value="Romance">Romance</option>
            <option value="Suspenso">Suspenso</option>
            <option value="Terror">Terror</option>
        </select>

        <label for="anio">Año de la película:</label>
        <select id="anio" name="anio" required>
            <option value="">Selecciona el año</option>
            <?php for ($a = (int)date("Y"); $a >= 1888; $a--): ?>
                <option value="<?= $a ?>"><?= $a ?></option>
            <?php endfor; ?>
        </select>

        <label for="img">Imagen o póster:</label>
        <input type="file" id="img" name="img"
               accept=".jpg,.jpeg,.png,.webp" required>

        <button type="submit">Guardar película</button>
    </form>
</div>

<h2>Películas registradas</h2>

<div class="tabla">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Póster</th>
                <th>Película</th>
                <th>Apellidos</th>
                <th>Número</th>
                <th>Correo</th>
                <th>Director</th>
                <th>Género</th>
                <th>Año</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <?php while ($fila = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$fila["id"] ?></td>
                    <td>
                        <img src="<?= htmlspecialchars(
                            $fila["img"], ENT_QUOTES, "UTF-8"
                        ) ?>" alt="Póster">
                    </td>
                    <td><?= htmlspecialchars($fila["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($fila["apellidos"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($fila["numero"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($fila["correo"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($fila["director"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars($fila["genero"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= (int)$fila["anio"] ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">Todavía no hay películas registradas.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conexion->close();
?>
