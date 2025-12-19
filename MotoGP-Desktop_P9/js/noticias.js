class Noticias {
    #busqueda;
    #url;
    #apiKey;
    #selectorContainer; 

    constructor() {
        this.#busqueda = "MotoGP";
        this.#url = "https://api.thenewsapi.com/v1/news/all";
        this.#apiKey = "zi6RuaWD6tcv7ubg2LnuiXRdMfvl8YwCHpOSreuH";
        
        this.#selectorContainer = 'main > section:nth-of-type(2)';
        this.#buscar();
    }
    #buscar() {
        const params = new URLSearchParams({
            api_token: this.#apiKey,
            search: this.#busqueda,
            language: 'es',
            limit: 3
        });
        const urlCompleta = `${this.#url}?${params.toString()}`;

        fetch(urlCompleta)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                const noticias = this.#procesarInformacion(data);
                this.#mostrarNoticias(noticias);
            })
            .catch(error => {
                console.error("Error al buscar noticias:", error);
            });
    }

    #procesarInformacion(jsonData) {
        if (!jsonData || !jsonData.data || jsonData.data.length === 0) {
            console.warn("No se encontraron noticias para la búsqueda.");
            return []; 
        }
        return jsonData.data; 
    }
    #mostrarNoticias(noticias) {
        const $container = $(this.#selectorContainer);
        $container.empty();
        $container.append($("<h2>").text("Últimas Noticias de MotoGP"));

        if (!noticias || noticias.length === 0) {
            $container.append($("<p>").text("No se encontraron noticias."));
            return;
        }

        // Crear y añadir cada noticia
        noticias.forEach(noticia => {
            
            const $article = $("<article>");

            if (noticia.title) {
                $article.append($("<h3>").text(noticia.title));
            }

            if (noticia.snippet) {
                $article.append($("<p>").text(noticia.snippet));
            }

            if (noticia.source) {
                $article.append($("<p>").html(`Fuente: ${noticia.source}`));
            }

            if (noticia.url) {
                const $enlace = $("<a>").attr({
                    "href": noticia.url,
                    "target": "_blank", 
                    "rel": "noopener noreferrer" 
                }).text("Leer más");
                $article.append($enlace);
            }
            
            $container.append($article);
        });
    }
}  