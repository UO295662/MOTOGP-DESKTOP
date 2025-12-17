<?php
session_start();

class Configuracion {
    private $servername;
    private $username;
    private $password;
    private $dbname;

    public function __construct() {
        $this->servername = "localhost";
        $this->username = "DBUSER2025";
        $this->password = "DBPSWD2025";
        $this->dbname = "UO295662_DB";
    }

    private function conectarServidor() {
        $conn = new mysqli($this->servername, $this->username, $this->password);
        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }
        return $conn;
    }


    public function crearBaseDatos() {
        $conn = $this->conectarServidor();
        
        $sqlFile = 'BD.sql';
        if (!file_exists($sqlFile)) {
            return "<p style='color:red'>Error: No se encuentra el archivo $sqlFile en el directorio actual.</p>";
        }
        
        $sql = file_get_contents($sqlFile);
        
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            $conn->close();
            return "<p style='color:green'>Base de datos y tablas creadas/reiniciadas correctamente.</p>";
        } else {
            $error = $conn->error;
            $conn->close();
            return "<p style='color:red'>Error creando la base de datos: " . $error . "</p>";
        }
    }


    public function borrarBaseDatos() {
        $conn = $this->conectarServidor();
        $sql = "DROP DATABASE IF EXISTS " . $this->dbname;
        
        if ($conn->query($sql) === TRUE) {
            $conn->close();
            return "<p style='color:green'>Base de datos eliminada correctamente.</p>";
        } else {
            $error = $conn->error;
            $conn->close();
            return "<p style='color:red'>Error eliminando la base de datos: " . $error . "</p>";
        }
    }
    public function exportarCSV() {
        $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        
        if ($conn->connect_error) {
            return; 
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=datos_pruebas.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, array('--- TABLA PARTICIPANTES ---'));
        fputcsv($output, array('ID', 'Código', 'Profesión', 'Edad', 'Género', 'Pericia'));
        
        $result = $conn->query("SELECT * FROM participantes");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }

        fputcsv($output, array('')); 

        fputcsv($output, array('--- TABLA PRUEBAS ---'));
        fputcsv($output, array('ID Prueba', 'ID Participante', 'Dispositivo', 'Tiempo (s)', 'Completado', 'Comentarios', 'Propuestas', 'Valoración', 'Fecha'));
        
        $result = $conn->query("SELECT * FROM pruebas");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }

        fputcsv($output, array(''));

        fputcsv($output, array('--- TABLA OBSERVACIONES ---'));
        fputcsv($output, array('ID Observación', 'ID Prueba', 'Comentario Facilitador'));
        
        $result = $conn->query("SELECT * FROM observaciones");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        $conn->close();
        exit();
    }
}

$mensaje = "";
$config = new Configuracion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear'])) {
        $mensaje = $config->crearBaseDatos();
    } elseif (isset($_POST['borrar'])) {
        $mensaje = $config->borrarBaseDatos();
    } elseif (isset($_POST['exportar'])) {
        $config->exportarCSV(); 
    }
}

?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>MOTOGP-Configuración</title>
    <meta name="author" content="Gael Horta Calzada" />
    <meta name="description" content="Configuración de BD de MotoGP" />
    <meta name="keywords" content="MotoGP,configuración, BD, PHP" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />
    <link rel="icon" href="../multimedia/favicon.ico">
</head>
<body>
    <header>
    <h1><a href="../index.html" title="Ir a la página principal">MotoGP Desktop</a></h1>
    <nav>
        <a href="../index.html" title="Inicio MotoGPDekstop">Inicio</a>
        <a href="../piloto.html" title="Piloto MotoGPDekstop">Piloto</a>
        <a href="../circuito.html" title="Circuito MotoGPDekstop">Circuito</a>
        <a href="../meteorologia.html" title="Meteorología MotoGPDekstop">Meteorología</a>
        <a href="../clasificaciones.php" title="Clasificaciones MotoGPDekstop">Clasificaciones</a>
        <a href="../juegos.html" title="Juegos MotoGPDekstop">Juegos</a>
        <a href="../ayuda.html" title="Ayuda MotoGPDekstop">Ayuda</a>
    </nav>
    </header>
    <p>Estás en: <a href="../index.html">Inicio</a> >> <a href="../juegos.html">Juegos</a>>>Configuración Test</p>
    
    <main>
        <section>
            <h2>Gestión de Datos</h2>
            <p>Utilice las siguientes opciones para administrar la base de datos de las pruebas de usabilidad.</p>
            
            <?php if (!empty($mensaje)) echo $mensaje; ?>

            <form action="#" method="post">
                <p>
                    <input type="submit" name="crear" value="Crear / Reiniciar Base de Datos" />
                    <label>Crea la base de datos inicial o borra todos los datos existentes para empezar de cero.</label>
                </p>
                <p>
                    <input type="submit" name="borrar" value="Borrar Base de Datos" />
                    <label>Elimina completamente la base de datos y todos sus registros.</label>
                </p>
                <p>
                    <input type="submit" name="exportar" value="Exportar Datos a CSV" />
                    <label>Descarga un archivo .csv con toda la información almacenada actualmente.</label>
                </p>
            </form>
        </section>
        
        <section>
            <h3>Volver</h3>
            <p><a href="../index.html">Regresar a la página de inicio</a></p>
        </section>
    </main>
</body>
</html>