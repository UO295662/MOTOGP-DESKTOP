class Carrusel {
    #busqueda;
    #actual;
    #maximo;
    #fotos;
    #selectorContainer;

    constructor(circuito) {
        this.#busqueda = "MotoGP, " + circuito;
        this.#actual = 0;
        this.#maximo = 5; 
        this.#fotos = [];
        
        this.#selectorContainer = 'main > section:first-of-type';

        $(this.#selectorContainer).html('<p>Cargando fotos...</p>');
        
        this.#getFotografias();
    }

    #getFotografias() {
        const flickrAPI = "https://api.flickr.com/services/feeds/photos_public.gne?jsoncallback=?";
        
        $.getJSON(flickrAPI, {
            tags: this.#busqueda,
            tagmode: "all",
            format: "json"
        })
        .done(this.#procesarJSONFotografias.bind(this))
        .fail((jqXHR, textStatus, errorThrown) => {
            this.#mostrarError(`Error de AJAX: ${textStatus} - ${errorThrown}`);
        });
    }

    #procesarJSONFotografias(data) {
        if (data.items && data.items.length > 0) {
            
            this.#fotos = data.items.slice(0, this.#maximo).map(item => ({
                url: item.media.m,
                title: item.title
            }));
            
            this.#maximo = this.#fotos.length;

            if (this.#maximo > 0) {
                this.#mostrarFotografias();
            } else {
                this.#mostrarError("No se encontraron fotos en el feed público.");
            }
        } else {
            this.#mostrarError("El feed de Flickr no devolvió imágenes.");
        }
    }

    #mostrarFotografias() {
        const $container = $(this.#selectorContainer);
        $container.empty();

        const circuito = this.#busqueda.split(', ')[1] || "Circuito";
        

        const $article = $("<article>");
        
        const $h2 = $("<h2>").text(`Imágenes del circuito de ${circuito}`);
        const fotoActual = this.#fotos[this.#actual];
        const $img = $("<img>").attr({
            "src": fotoActual.url,
            "alt": fotoActual.title || "Foto de Flickr"
        });

        $article.append($h2);
        $article.append($img);
        
        $container.append($article);
        setInterval(this.#cambiarFotografia.bind(this), 3000);
    }

    #cambiarFotografia() {
        this.#actual++;

        if (this.#actual >= this.#maximo) {
            this.#actual = 0;
        }

        const fotoNueva = this.#fotos[this.#actual];

        const $img = $(this.#selectorContainer).find('article img');

        $img.attr({
            "src": fotoNueva.url,
            "alt": fotoNueva.title || "Foto de Flickr"
        });
    }

    #mostrarError(mensaje) {
        console.error(mensaje);
        const $carrusel = $(this.#selectorContainer);
        if ($carrusel.length) {
            $carrusel.html(`<p>${mensaje}</p>`);
        }
    }
}