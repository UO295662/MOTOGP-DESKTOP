class Circuito {

    #selectorContenido;

    constructor() {
        this.#selectorContenido = 'main > section:first-of-type';
        
        this.#comprobarApiFile();
        
        this.#vincularEventos();
    }

    #comprobarApiFile() {
        if (window.File && window.FileReader && window.FileList && window.Blob) {
        } else {
            const main = document.querySelector('main');
            if (main) {
                const errorP = document.createElement('p');
                errorP.textContent = "Tu navegador no soporta la API File.";
                main.prepend(errorP);
            }
        }
    }

    #vincularEventos() {
        const inputs = document.querySelectorAll('main input[type="file"]');
        if (inputs.length > 0) {
            inputs[0].addEventListener('change', this.#leerArchivoHTML.bind(this));
        }
    }

    #leerArchivoHTML(event) {
        const archivo = event.target.files[0];
        
        if (!archivo) {
            return;
        }

        const lector = new FileReader();
        
        lector.onload = (e) => {
            const contenido = e.target.result;
            this.#mostrarContenido(contenido);
        };
        
        lector.onerror = () => {
            const contenedor = document.querySelector(this.#selectorContenido);
            if (contenedor) contenedor.innerHTML = "<>Error al leer el archivo.</p>";
        };

        lector.readAsText(archivo);
    }

    #mostrarContenido(contenidoHTML) {
        const contenedor = document.querySelector(this.#selectorContenido);
        if (!contenedor) return;

        contenedor.innerHTML = "";

        const parser = new DOMParser();
        const doc = parser.parseFromString(contenidoHTML, "text/html");

        const mainImportado = doc.querySelector('main');
        const elementos = mainImportado ? mainImportado.children : doc.body.children;

        Array.from(elementos).forEach(elemento => {
            const nodoImportado = document.importNode(elemento, true);
            
            const imagenes = nodoImportado.querySelectorAll('img, video');
            imagenes.forEach(img => {
                const src = img.getAttribute('src');
                if (src && src.startsWith('../multimedia')) {
                    img.setAttribute('src', src.replace('../multimedia', 'multimedia'));
                }
            });

            contenedor.appendChild(nodoImportado);
        });

    }
}
class CargadorSVG {
    #selectorContenedorSVG;

    constructor() {
        this.#selectorContenedorSVG = 'main > section:last-of-type';
        
        this.#vincularEventos();
    }

    #vincularEventos() {
        const inputs = document.querySelectorAll('main input[type="file"]');
        if (inputs.length > 1) {
            inputs[1].addEventListener('change', this.#leerArchivoSVG.bind(this));
        }
    }


    #leerArchivoSVG(event) {
        const archivo = event.target.files[0];
        
        if (!archivo) {
            return;
        }

        if (archivo.type && !archivo.type.startsWith('image/svg')) {
        }

        const lector = new FileReader();
        
        lector.onload = (e) => {
            const contenido = e.target.result;
            this.#insertarSVG(contenido);
        };

        lector.readAsText(archivo);
    }


    #insertarSVG(contenidoSVG) {
        const contenedor = document.querySelector(this.#selectorContenedorSVG);
        
        if (contenedor) {
            contenedor.innerHTML = ""; 
            
            const h3 = document.createElement('h3');
            h3.textContent = "Altimetría del Circuito";
            contenedor.appendChild(h3);
            
            contenedor.insertAdjacentHTML('beforeend', contenidoSVG);
            
            const svgElement = contenedor.querySelector('svg');
            if (svgElement) {
                svgElement.setAttribute('width', '100%');
                svgElement.setAttribute('height', 'auto');
            }
            
        }
    }
}
class CargadorKML {
    #mapa; 
    
    constructor() {
        mapboxgl.accessToken = 'pk.eyJ1IjoidW8yOTU2NjIiLCJhIjoiY21pZWc3OTVpMDBtbTNkczc3cmxlc3pwNiJ9.OHVq_-6T5rEVofcADuk6OQ'; 
        this.#vincularEventos();
    }

    #vincularEventos() {
        const inputs = document.querySelectorAll('main input[type="file"]');
        if (inputs.length > 2) {
            inputs[2].addEventListener('change', this.#leerArchivoKML.bind(this));
        }
    }

    #leerArchivoKML(event) {
        const archivo = event.target.files[0];
        if (!archivo) return;

        const lector = new FileReader();
        lector.onload = (e) => {
            this.#insertarCapaKML(e.target.result);
        };
        lector.readAsText(archivo);
    }

    #insertarCapaKML(kmlString) {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(kmlString, "text/xml");

        let centro = [0, 0];
        const placemarks = xmlDoc.querySelectorAll("Placemark");
        
        for (const placemark of placemarks) {
            const point = placemark.querySelector("Point coordinates");
            if (point) {
                const coords = point.textContent.trim().split(",");
                centro = [parseFloat(coords[0]), parseFloat(coords[1])];
                break; 
            }
        }

        const rutaCoordenadas = [];
        for (const placemark of placemarks) {
            const lineString = placemark.querySelector("LineString coordinates");
            if (lineString) {
                const rawCoords = lineString.textContent.trim().split(/\s+/);
                rawCoords.forEach(coordPair => {
                    const coords = coordPair.split(",");
                    if (coords.length >= 2) {
                        rutaCoordenadas.push([parseFloat(coords[0]), parseFloat(coords[1])]);
                    }
                });
            }
        }

        this.#mostrarMapa(centro, rutaCoordenadas);
    }

    #mostrarMapa(centro, ruta) {
        const contenedorMapa = document.querySelector('main > div'); 
        
        if (contenedorMapa) {
            contenedorMapa.style.display = 'block';
            
            this.#mapa = new mapboxgl.Map({
                container: contenedorMapa,
                style: 'mapbox://styles/mapbox/streets-v11', 
                center: centro, 
                zoom: 14
            });

            new mapboxgl.Marker({ color: 'red' })
                .setLngLat(centro)
                .setPopup(new mapboxgl.Popup().setHTML("<h3>Inicio del Circuito</h3>"))
                .addTo(this.#mapa);

            this.#mapa.on('load', () => {
                this.#mapa.addSource('ruta', {
                    'type': 'geojson',
                    'data': {
                        'type': 'Feature',
                        'properties': {},
                        'geometry': {
                            'type': 'LineString',
                            'coordinates': ruta
                        }
                    }
                });

                this.#mapa.addLayer({
                    'id': 'ruta',
                    'type': 'line',
                    'source': 'ruta',
                    'layout': {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    'paint': {
                        'line-color': '#FF0000',
                        'line-width': 4
                    }
                });
            });
        }
    }
}