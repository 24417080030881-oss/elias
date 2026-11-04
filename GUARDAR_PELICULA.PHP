```php
<?php

// =====================================
// CONEXIÓN A LA BASE DE DATOS
// =====================================

$conexion = new mysqli("localhost", "root", "", "cine");

$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// =====================================
// VERIFICAR ENVÍO DEL FORMULARIO
// =====================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: formulario.php");
    exit;
}

// =====================================
// RECIBIR LOS DATOS
// =====================================

$nombre = trim($_POST["nombre"] ?? "");
$apellidos = trim($_POST["apellidos"] ?? "");
$numero = trim($_POST["numero"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$director = trim($_POST["director"] ?? "");
$genero = trim($_POST["genero"] ?? "");
$anio = filter_input(INPUT_POST, "anio", FILTER_VALIDATE_INT);

// =====================================
// VALIDAR LOS DATOS
// =====================================

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
    die("Error: revisa los datos del formulario.");
}

// =====================================
// VALIDAR LA IMAGEN
// =====================================

if (
    !isset($_FILES["img"]) ||
    $_FILES["img"]["error"] !== UPLOAD_ERR_OK
) {
    die("Error: selecciona una imagen válida.");
}

if ($_FILES["img"]["size"] > 5 * 1024 * 1024) {
    die("Error: la imagen no puede superar los 5 MB.");
}

$temporal = $_FILES["img"]["tmp_name"];

$imagenInfo = getimagesize($temporal);

$mime = $imagenInfo["mime"] ?? "";

$tipos = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

if ($imagenInfo === false || !isset($tipos[$mime])) {
    die("Error: utiliza una imagen JPG, PNG o WEBP.");
}

// =====================================
// CREAR CARPETA UPLOADS
// =====================================

$carpeta = __DIR__ . "/uploads/";

if (!is_dir($carpeta)) {
    if (!mkdir($carpeta, 0755, true)) {
        die("Error: no se pudo crear la carpeta uploads.");
    }
}

// =====================================
// GUARDAR IMAGEN
// =====================================

$archivo = bin2hex(random_bytes(16))
         . "." . $tipos[$mime];

$rutaArchivo = $carpeta . $archivo;

$rutaBD = "uploads/" . $archivo;

if (!move_uploaded_file($temporal, $rutaArchivo)) {
    die("Error: no se pudo guardar la imagen.");
}

// =====================================
// INSERTAR DATOS EN MYSQL
// =====================================

$sql = "INSERT INTO peliculas
        (nombre, apellidos, numero, correo,
         director, genero, anio, img)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    unlink($rutaArchivo);
    die("Error al preparar el registro: " . $conexion->error);
}

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

// =====================================
// EJECUTAR REGISTRO
// =====================================

if ($stmt->execute()) {

    $stmt->close();
    $conexion->close();

    // REGRESAR A LA TABLA DE REGISTROS
    header("Location: registros.php");
    exit;

} else {

    unlink($rutaArchivo);

    $error = $stmt->error;

    $stmt->close();
    $conexion->close();

    die("No se pudo guardar la película: " . htmlspecialchars($error));
}

?>
```