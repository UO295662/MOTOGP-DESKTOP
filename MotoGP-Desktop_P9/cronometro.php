<?php
session_start();


class Cronometro {
    private $inicio;
    private $tiempo_acumulado;
    private $corriendo;

    public function __construct() {
        $this->inicio = 0;
        $this->tiempo_acumulado = 0;
        $this->corriendo = false;
    }

    public function arrancar() {
        if (!$this->corriendo) {
            $this->inicio = microtime(true);
            $this->corriendo = true;
        }
    }

 
    public function parar() {
        if ($this->corriendo) {
            $ahora = microtime(true);
            $this->tiempo_acumulado += ($ahora - $this->inicio);
            $this->corriendo = false;
            $this->inicio = 0;
        }
    }


        public function reiniciar() {
            $this->inicio = 0;
            $this->tiempo_acumulado = 0;
            $this->corriendo = false;
        }

    public function mostrar() {
        $tiempoTotal = $this->tiempo_acumulado;

        if ($this->corriendo) {
            $tiempoTotal += (microtime(true) - $this->inicio);
        }

        $segundosTotales = floor($tiempoTotal);
        $minutos = floor($segundosTotales / 60);
        $segundos = $segundosTotales % 60;
        
        $decimas = floor(($tiempoTotal - $segundosTotales) * 10);

        return sprintf("%02d:%02d.%d", $minutos, $segundos, $decimas);
    }
}

if (!isset($_SESSION['cronometro'])) {
    $_SESSION['cronometro'] = new Cronometro();
}

$cronometro = $_SESSION['cronometro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['arrancar'])) {
        $cronometro->arrancar();
    }
    if (isset($_POST['parar'])) {
        $cronometro->parar();
    }
    if (isset($_POST['reiniciar'])) {
        $cronometro->reiniciar();
    }
}

?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>MOTOGP - Cronómetro PHP</title>
    <meta name="author" content="Gael Horta Calzada" />
    <meta name="description" content="Cronómetro realizado con PHP en el servidor" />
    <meta name="keywords" content="MotoGP, cronometro, php" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    
    <link rel="stylesheet" type="text/css" href="estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="estilo/layout.css" />
    <link rel="icon" href="multimedia/favicon.ico"> 
</head>

<body>
    <header>
        <h1><a href="index.html" title="Ir a la página principal">MotoGP Desktop</a></h1>
        <nav>
            <a href="index.html" title="Inicio MotoGPDekstop">Inicio</a>
            <a href="piloto.html" title="Piloto MotoGPDekstop">Piloto</a>
            <a href="circuito.html" title="Circuito MotoGPDekstop">Circuito</a>
            <a href="meteorologia.html" title="Meteorología MotoGPDekstop">Meteorología</a>
            <a href="clasificaciones.php" title="Clasificaciones MotoGPDekstop">Clasificaciones</a>
            <a href="juegos.html" title="Juegos MotoGPDekstop" class="active">Juegos</a>
            <a href="ayuda.html" title="Ayuda MotoGPDekstop">Ayuda</a>
        </nav>
    </header>
    
    <p>Estás en: <a href="index.html">Inicio</a> >> <a href="juegos.html">Juegos</a> >> Cronómetro PHP</p>
    
    <main>
        <h2>Cronómetro (Servidor)</h2>
        
        <section>
            <h3>Tiempo Transcurrido</h3>
            <p>
                <?php echo $cronometro->mostrar(); ?>
            </p>
        </section>

        <section>
            <h3>Controles</h3>
            <form action="#" method="post">
                <p>
                    <input type="submit" name="arrancar" value="Arrancar" />
                    <input type="submit" name="parar" value="Parar" />
                    <input type="submit" name="reiniciar" value="Reiniciar" />
                    <input type="submit" name="actualizar" value="Consultar Tiempo" />
                </p>
            </form>
        </section>
    </main>
</body>
</html>