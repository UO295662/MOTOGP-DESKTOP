<?php
ob_start();
require_once("../cronometro.php"); 
ob_end_clean();

class GestorResultados {
    private $servername = "localhost";
    private $username = "DBUSER2025";
    private $password = "DBPSWD2025";
    private $dbname = "UO295662_DB"; 
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }

    public function guardarTodo($datosUsuario, $datosPrueba, $comentariosObservador) {
        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare("INSERT INTO participantes (codigo_usuario, edad, genero, pericia_informatica) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisi", 
                $datosUsuario['codigo'],
                $datosUsuario['edad'],
                $datosUsuario['genero'],
                $datosUsuario['pericia']
            );
            $stmt->execute();
            $id_participante = $this->conn->insert_id;
            $stmt->close();

            $respuestas = "Respuestas: " . implode("; ", $datosPrueba['respuestas']);
            $comentarios_totales = $respuestas . " | Comentarios User: " . $datosPrueba['comentarios'];
            $completado = 1; 

            $stmt2 = $this->conn->prepare("INSERT INTO pruebas (id_participante, dispositivo, tiempo_empleado, completado, comentarios_usuario, propuestas_mejora, valoracion_aplicacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("isdissi", 
                $id_participante, 
                $datosPrueba['dispositivo'], 
                $datosPrueba['tiempo'], 
                $completado, 
                $comentarios_totales, 
                $datosPrueba['propuestas'], 
                $datosPrueba['valoracion']
            );
            $stmt2->execute();
            $id_prueba = $this->conn->insert_id;
            $stmt2->close();

            if (!empty($comentariosObservador)) {
                $stmt3 = $this->conn->prepare("INSERT INTO observaciones (id_prueba, comentario_facilitador) VALUES (?, ?)");
                $stmt3->bind_param("is", $id_prueba, $comentariosObservador);
                $stmt3->execute();
                $stmt3->close();
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            return "Error al guardar en BD: " . $e->getMessage();
        }
    }
}


if (!isset($_SESSION['cronometro_prueba'])) {
    $_SESSION['cronometro_prueba'] = new Cronometro();
}
$cronometro = $_SESSION['cronometro_prueba'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['estado_test']) && ($_SESSION['estado_test'] === 'OBSERVADOR' || $_SESSION['estado_test'] === 'FINALIZADO')) {
    session_destroy();
    session_start();
    $_SESSION['cronometro_prueba'] = new Cronometro();
    $cronometro = $_SESSION['cronometro_prueba'];
    $_SESSION['estado_test'] = 'INICIO';
}

if (!isset($_SESSION['estado_test'])) {
    $_SESSION['estado_test'] = 'INICIO';
}

if (isset($_POST['resetear_prueba'])) {
    session_destroy();
    session_start();
    $_SESSION['estado_test'] = 'INICIO';
    header("Location: prueba.php");
    exit();
}

if (isset($_POST['comenzar'])) {
    $_SESSION['estado_test'] = 'CUESTIONARIO';
    $cronometro->reiniciar(); 
    $cronometro->arrancar();
}

if (isset($_POST['finalizar_cuestionario'])) {
    $cronometro->parar();
    
    $reflector = new ReflectionClass($cronometro);
    $propiedad = $reflector->getProperty('tiempo_acumulado');
    $propiedad->setAccessible(true);
    $tiempo_total = $propiedad->getValue($cronometro);
    
    $respuestas = [];
    for ($i=1; $i<=10; $i++) {
        $respuestas[] = "P$i: " . ($_POST["p$i"] ?? "N/A");
    }

    $_SESSION['datos_usuario'] = [
        'codigo' => $_POST['codigo'],
        'edad' => $_POST['edad'],
        'genero' => $_POST['genero'],
        'pericia' => $_POST['pericia']
    ];

    $_SESSION['datos_prueba'] = [
        'dispositivo' => $_POST['dispositivo'],
        'tiempo' => $tiempo_total,
        'tiempo_formato' => $cronometro->mostrar(),
        'respuestas' => $respuestas,
        'comentarios' => $_POST['comentarios'],
        'propuestas' => $_POST['propuestas'],
        'valoracion' => $_POST['valoracion']
    ];

    $_SESSION['estado_test'] = 'OBSERVADOR';
}

$mensaje_resultado = "";
if (isset($_POST['guardar_bd'])) {
    $gestor = new GestorResultados();
    $resultado = $gestor->guardarTodo(
        $_SESSION['datos_usuario'], 
        $_SESSION['datos_prueba'], 
        $_POST['notas_observador']
    );

    if ($resultado === true) {
        $mensaje_resultado = "¡Datos guardados correctamente en la base de datos!";
        $cronometro->reiniciar();
        $_SESSION['estado_test'] = 'FINALIZADO'; 
        $_SESSION['mensaje_final'] = $mensaje_resultado;
    } else {
        $mensaje_resultado = "Error: " . $resultado;
    }
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Prueba de Usabilidad - MotoGP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />
    <link rel="icon" href="../multimedia/favicon.ico">
</head>
<body>
    <header>
        <h1>Prueba de Usabilidad</h1>
    </header>

    <main>
        <?php if ($_SESSION['estado_test'] === 'INICIO'): ?>
            <section>
                <h2>Bienvenido a la Prueba</h2>
                <p>El objetivo de esta prueba es evaluar la usabilidad de la aplicación MotoGP Desktop.</p>
                <form action="#" method="post">
                    <input type="submit" name="comenzar" value="Iniciar Prueba">
                </form>
            </section>

        <?php elseif ($_SESSION['estado_test'] === 'CUESTIONARIO'): ?>
            <section>
                <h2>Cuestionario de Evaluación</h2>
                <form action="#" method="post">
                    
                    <h3>1. Datos del Participante</h3>
                    <p>
                        <label>Código de Usuario: <input type="text" name="codigo" required /></label>
                        <label>Edad: <input type="number" name="edad" required min="10" max="100" /></label>
                    </p>
                    <p>
                        <label for="genero">Género:
                            <select name="genero" id="genero">
                                <option value="Hombre">Hombre</option>
                                <option value="Mujer">Mujer</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </label>
                        <label>Pericia Informática (0-10): <input type="number" name="pericia" min="0" max="10" required /></label>
                    </p>
                    <p>
                        <label for="dispositivo">Dispositivo utilizado:
                            <select name="dispositivo" id="dispositivo">
                                <option value="Ordenador">Ordenador</option>
                                <option value="Tableta">Tableta</option>
                                <option value="Móvil">Móvil</option>
                            </select>
                        </label>
                    </p>

                    <h3>2. Tareas y Preguntas</h3>
                    <p>Por favor, realice las comprobaciones y conteste:</p>
                    
                    <label>1. ¿Es útil la ayuda? <input type="text" name="p1" required></label><br>
                    <label>2. ¿Cómo valora la carga de imágenes? <input type="text" name="p2" required></label><br>
                    <label>3. ¿Es legible el tamaño de letra? <input type="text" name="p3" required></label><br>
                    <label>4. ¿Funcionó el mapa dinámico? <input type="text" name="p4" required></label><br>
                    <label>5. ¿Jugó al juego de memoria sin errores? <input type="text" name="p5" required></label><br>
                    <label>6. ¿Se entiende la meteorología? <input type="text" name="p6" required></label><br>
                    <label>7. ¿Qué opina del menú principal? <input type="text" name="p7" required></label><br>
                    <label>8. ¿Encontró enlaces rotos? <input type="text" name="p8" required></label><br>
                    <label>9. ¿Es útil la sección de noticias? <input type="text" name="p9" required></label><br>
                    <label>10. Diseño visual (opinión): <input type="text" name="p10" required></label><br>

                    <h3>3. Valoración Final</h3>
                    <label>Puntuación Global (0-10): <input type="number" name="valoracion" min="0" max="10" required></label><br>
                    <label>Comentarios:<br><textarea name="comentarios"></textarea></label><br>
                    <label>Propuestas de mejora:<br><textarea name="propuestas"></textarea></label>

                    <p>
                        <input type="submit" name="finalizar_cuestionario" value="Terminar Prueba">
                    </p>
                </form>
            </section>

        <?php elseif ($_SESSION['estado_test'] === 'OBSERVADOR'): ?>
            <section>
                <h2>Zona del Observador</h2>
                <p>La prueba ha finalizado.</p>
                <p>Tiempo empleado: <?php echo $_SESSION['datos_prueba']['tiempo_formato']; ?></p>
                
                <form action="#" method="post">
                    <label for="notas_observador">Observaciones del facilitador:</label>
                    <br>
                    <textarea name="notas_observador" id="notas_observador"></textarea>
                    <p>
                        <input type="submit" name="guardar_bd" value="Guardar Informe en Base de Datos">
                    </p>
                </form>
                
                <form action="#" method="post">
                    <input type="submit" name="resetear_prueba" value="Cancelar y Reiniciar">
                </form>
            </section>
        
        <?php else: ?>
            <section>
                <h2>Resultado</h2>
                <p><?php echo $_SESSION['mensaje_final'] ?? $mensaje_resultado; ?></p>
                <p><a href="../index.html">Volver al Inicio</a></p>
                <form action="#" method="post">
                    <input type="submit" name="resetear_prueba" value="Nueva Prueba">
                </form>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>