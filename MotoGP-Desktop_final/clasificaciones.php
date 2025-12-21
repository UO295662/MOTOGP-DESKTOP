<?php
class Clasificacion {
    private $documento;
    private $xml;
    public function __construct() {
        $this->documento = "xml/circuitoEsquema.xml"; 
        $this->xml = null;
    }

    public function consultar() {
        if (file_exists($this->documento)) {
            $this->xml = simplexml_load_file($this->documento);
            
            if ($this->xml) {
                $this->xml->registerXPathNamespace('ns', 'http://www.uniovi.es');
            }
        } else {
            echo "<p>Error: No se encuentra el archivo XML en: " . $this->documento . "</p>";
        }
    }

 
    public function mostrarGanador() {
        if ($this->xml === null) return;
        $vencedores = $this->xml->xpath("//ns:resultados_carrera/ns:vencedor");

        echo "<section aria-label='Ganador de la carrera'>";
        echo "<h3>Ganador de la Carrera</h3>";

        if (!empty($vencedores)) {
            $ganador = $vencedores[0];
            $nombre = (string) $ganador->children('http://www.uniovi.es')->nombre;
            $tiempo = (string) $ganador->children('http://www.uniovi.es')->tiempo;

            echo "<p>Piloto: " . $nombre . "</p>";
            echo "<p>Tiempo: " . $tiempo . "</p>";
        } else {
            echo "<p>No se encontró información sobre el ganador.</p>";
        }
        echo "</section>";
    }

    public function mostrarClasificacion() {
        if ($this->xml === null) return;

        echo "<section aria-label='Clasificación del Mundial'>";
        echo "<h3>Clasificación del Mundial</h3>";
        
        $pilotosMundial = $this->xml->xpath("//ns:resultados_carrera/ns:clasificacion_mundial/ns:piloto");

        if (!empty($pilotosMundial)) {
            echo "<ol>";

            foreach ($pilotosMundial as $piloto) {
                $datos = $piloto->children('http://www.uniovi.es');
                $posicion = (string) $datos->posicion;
                $nombre = (string) $datos->nombre;
                
                echo "<li>";
                echo  $nombre;
                echo "</li>";
            }

            echo "</ol>";
        } else {
            echo "<p>No se encontró información de la clasificación mundial.</p>";
        }
        echo "</section>";
    }
}

$clasificacion = new Clasificacion();
$clasificacion->consultar();

?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8" />
    <title>MOTOGP - Clasificaciones</title>
    <meta name="author" content="Gael Horta Calzada" />
    <meta name="description" content="Clasificaciones de MotoGP leídas desde XML en el servidor" />
    <meta name="keywords" content="MotoGP, clasificaciones, xml, php" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    
    <link rel="stylesheet" type="text/css" href="estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="estilo/layout.css" />
    <link rel="icon" href="multimedia/favicon.ico">
</head>

<body>
    <!-- Datos con el contenidos que aparece en el navegador -->
    <header>
        <h1><a href="index.html" title="Ir a la página principal">MotoGP Desktop</a></h1>
        <nav>
            <a href="index.html" title="Inicio MotoGPDekstop">Inicio</a>
            <a href="piloto.html" title="Piloto MotoGPDekstop">Piloto</a>
            <a href="circuito.html" title="Circuito MotoGPDekstop">Circuito</a>
            <a href="meteorologia.html" title="Meteorología MotoGPDekstop">Meteorología</a>
            <a href="clasificaciones.php" title="Clasificaciones MotoGPDekstop" class="active">Clasificaciones</a>
            <a href="juegos.html" title="Juegos MotoGPDekstop">Juegos</a>
            <a href="ayuda.html" title="Ayuda MotoGPDekstop">Ayuda</a>
        </nav>
    </header>
    
    <p>Estás en: <a href="index.html">Inicio</a> >> Clasificaciones</p>
    
    <main>
        <h2>Resultados y Clasificaciones</h2>
        
        <p>A continuación se muestran los resultados procesados automáticamente desde el archivo XML del servidor:</p>

        <?php
            $clasificacion->mostrarGanador();
            $clasificacion->mostrarClasificacion();
        ?>
        
    </main>
</body>
</html>