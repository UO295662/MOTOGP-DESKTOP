<?php
session_start();

// --- CLASE PARA GESTIÓN DE BASE DE DATOS ---
class GestorResultados {
    private $servername = "localhost";
    private $username = "DBUSER2025";
    private $password = "DBPSWD2025";
    private $dbname = "UO295662_DB"; // ¡CAMBIA ESTO POR TU UO!
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
    }

    /**
     * Guarda toda la sesión de prueba en la base de datos.
     * Inserta en 'participantes', luego en 'pruebas' y finalmente en 'observaciones'.
     */
    public function guardarTodo($datosUsuario, $datosPrueba, $comentariosObservador) {
        // Usamos transacciones para asegurar que se guarda todo o nada
        $this->conn->begin_transaction();

        try {
            // 1. Insertar Participante
            $stmt = $this->conn->prepare("INSERT INTO participantes (codigo_usuario, profesion, edad, genero, pericia_informatica) VALUES (?, ?, ?, ?, ?)");
            // Generamos un código único si no existe (ej. DNI o Random)
            $codigo = $datosUsuario['dni']; 
            $stmt->bind_param("ssisi", $codigo, $datosUsuario['profesion'], $datosUsuario['edad'], $datosUsuario['genero'], $datosUsuario['pericia']);
            $stmt->execute();
            $id_participante = $this->conn->insert_id;
            $stmt->close();

            // 2. Insertar Prueba
            // Concatenamos las respuestas del test en un solo campo de texto
            $respuestas = "Respuestas: " . implode("; ", $datosPrueba['respuestas']);
            $comentarios_totales = $respuestas . " | Comentarios User: " . $datosPrueba['comentarios'];
            $completado = 1; // True

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

            // 3. Insertar Observaciones del Facilitador
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

// --- LÓGICA DE ESTADOS ---

// Estado inicial por defecto
if (!isset($_SESSION['estado_test'])) {
    $_SESSION['estado_test'] = 'INICIO';
}

// TRANSICIÓN 1: De INICIO a CUESTIONARIO (Arrancar Cronómetro)
if (isset($_POST['comenzar'])) {
    $_SESSION['estado_test'] = 'CUESTIONARIO';
    $_SESSION['tiempo_inicio'] = microtime(true); // Tarea 2: Iniciar cronómetro
}

// TRANSICIÓN 2: De CUESTIONARIO a OBSERVADOR (Parar Cronómetro y guardar datos temporales)
if (isset($_POST['finalizar_cuestionario'])) {
    $tiempo_fin = microtime(true);
    $tiempo_total = $tiempo_fin - $_SESSION['tiempo_inicio']; // Tarea 2: Calcular tiempo

    // Recoger respuestas de las 10 preguntas
    $respuestas = [];
    for ($i=1; $i<=10; $i++) {
        $respuestas[] = "P$i: " . ($_POST["p$i"] ?? "N/A");
    }

    // Guardar datos en sesión
    $_SESSION['datos_usuario'] = [
        'dni' => $_POST['dni'],
        'edad' => $_POST['edad'],
        'genero' => $_POST['genero'],
        'profesion' => $_POST['profesion'],
        'pericia' => $_POST['pericia']
    ];

    $_SESSION['datos_prueba'] = [
        'dispositivo' => $_POST['dispositivo'],
        'tiempo' => $tiempo_total,
        'respuestas' => $respuestas,
        'comentarios' => $_POST['comentarios'],
        'propuestas' => $_POST['propuestas'],
        'valoracion' => $_POST['valoracion']
    ];

    $_SESSION['estado_test'] = 'OBSERVADOR';
}

// TRANSICIÓN 3: De OBSERVADOR a FINAL (Guardar en BD)
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
        // Reiniciar estado para la siguiente persona
        session_destroy(); 
        // Pequeño truco para mostrar el mensaje antes de perder la sesión visualmente
        $_SESSION['estado_test'] = 'FINALIZADO'; 
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
    <!-- Reutilizamos estilos, pero SIN menú de navegación (Ejercicio 3) -->
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />
    <link rel="icon" href="../multimedia/favicon.ico">
</head>
<body>
    <header>
        <h1>Prueba de Usabilidad</h1>
    </header>

    <main>
        <!-- VISTA 1: PORTADA -->
        <?php if (!isset($_SESSION['estado_test']) || $_SESSION['estado_test'] === 'INICIO'): ?>
            <section>
                <h2>Bienvenido a la Prueba</h2>
                <p>El objetivo de esta prueba es evaluar la usabilidad de la aplicación MotoGP Desktop.</p>
                <p>Por favor, cuando esté listo, pulse el botón para comenzar. Se le realizarán una serie de preguntas sobre la aplicación.</p>
                
                <form action="#" method="post">
                    <input type="submit" name="comenzar" value="Iniciar Prueba" style="font-size: 1.5em; padding: 10px 20px;">
                </form>
            </section>

        <!-- VISTA 2: FORMULARIO USUARIO (Tarea 1) -->
        <?php elseif ($_SESSION['estado_test'] === 'CUESTIONARIO'): ?>
            <section>
                <h2>Cuestionario de Evaluación</h2>
                <form action="#" method="post">
                    
                    <h3>1. Datos del Participante</h3>
                    <p>
                        <label>DNI / Código: <input type="text" name="dni" required /></label>
                        <label>Edad: <input type="number" name="edad" required /></label>
                    </p>
                    <p>
                        <label>Género: 
                            <select name="genero">
                                <option value="Hombre">Hombre</option>
                                <option value="Mujer">Mujer</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </label>
                        <label>Profesión: <input type="text" name="profesion" /></label>
                    </p>
                    <p>
                        <label>Pericia Informática (0-10): <input type="number" name="pericia" min="0" max="10" required /></label>
                        <label>Dispositivo usado: 
                            <select name="dispositivo">
                                <option value="Ordenador">Ordenador</option>
                                <option value="Tableta">Tableta</option>
                                <option value="Móvil">Móvil</option>
                            </select>
                        </label>
                    </p>

                    <h3>2. Tareas y Preguntas</h3>
                    <p>Por favor, realice las siguientes comprobaciones en la web y conteste:</p>
                    
                    <label>1. ¿Ha encontrado fácilmente el calendario de carreras? <input type="text" name="p1" required></label><br>
                    <label>2. ¿Cómo valora la velocidad de carga de las imágenes? <input type="text" name="p2" required></label><br>
                    <label>3. ¿Es legible el tamaño de letra en la sección de pilotos? <input type="text" name="p3" required></label><br>
                    <label>4. ¿Funcionó correctamente el mapa dinámico en Circuito? <input type="text" name="p4" required></label><br>
                    <label>5. ¿Ha podido jugar al juego de memoria sin errores? <input type="text" name="p5" required></label><br>
                    <label>6. ¿La información meteorológica se entiende bien? <input type="text" name="p6" required></label><br>
                    <label>7. ¿Qué opina de la estructura del menú principal? <input type="text" name="p7" required></label><br>
                    <label>8. ¿Ha encontrado enlaces rotos o imágenes fallidas? <input type="text" name="p8" required></label><br>
                    <label>9. ¿Considera útil la sección de noticias? <input type="text" name="p9" required></label><br>
                    <label>10. Del 0 al 10, ¿cuánto le gusta el diseño visual? <input type="number" name="p10" min="0" max="10" required></label><br>

                    <h3>3. Valoración Final</h3>
                    <label>Puntuación Global (0-10): <input type="number" name="valoracion" min="0" max="10" required></label><br>
                    <label>Comentarios:<br><textarea name="comentarios"></textarea></label><br>
                    <label>Propuestas de mejora:<br><textarea name="propuestas"></textarea></label>

                    <p style="margin-top: 20px;">
                        <input type="submit" name="finalizar_cuestionario" value="Terminar Prueba">
                    </p>
                </form>
            </section>

        <!-- VISTA 3: ZONA DEL FACILITADOR (Tarea 3) -->
        <?php elseif ($_SESSION['estado_test'] === 'OBSERVADOR'): ?>
            <section style="background-color: #f9f9f9; border: 2px solid #ccc; padding: 20px;">
                <h2>Zona del Facilitador</h2>
                <p>La prueba ha finalizado para el usuario.</p>
                
                <!-- Mostramos el tiempo solo ahora (Tarea 2) -->
                <p><strong>Tiempo empleado:</strong> <?php echo round($_SESSION['datos_prueba']['tiempo'], 2); ?> segundos.</p>
                
                <form action="#" method="post">
                    <label>Observaciones del facilitador sobre la prueba:<br>
                    <textarea name="notas_observador" style="width: 100%; height: 100px;"></textarea>
                    </label>
                    <p>
                        <input type="submit" name="guardar_bd" value="Guardar Informe en Base de Datos">
                    </p>
                </form>
            </section>
        
        <?php else: ?>
            <section>
                <h2>Resultado</h2>
                <p><?php echo $mensaje_resultado; ?></p>
                <p><a href="../index.html">Volver al Inicio</a> | <a href="prueba.php">Nueva Prueba</a></p>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>