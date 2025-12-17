class Cronometro {
    #tiempo;
    #inicio;
    #corriendo;

    constructor() {
        this.#tiempo = 0;
        this.#inicio = null;
        this.#corriendo = null;

        this.#vincularEventos();
    }

    #vincularEventos() {
        const botones = document.querySelectorAll('main section button');

        if (botones.length === 3) {
            botones[0].addEventListener('click', this.arrancar.bind(this));
            botones[1].addEventListener('click', this.parar.bind(this));
            botones[2].addEventListener('click', this.reiniciar.bind(this));
        }
    }

    arrancar() {
        if (this.#corriendo) return;

        try {
            this.#inicio = Temporal.Now.instant();
        } catch (e) {
            this.#inicio = new Date();
        }

        this.#corriendo = setInterval(this.#actualizar.bind(this), 100);
    }

    #actualizar() {
        let ahora;

        try {
            if (this.#inicio instanceof Date) {
                ahora = new Date();
                this.#tiempo = ahora.getTime() - this.#inicio.getTime();
            } else if (typeof Temporal !== 'undefined' && this.#inicio instanceof Temporal.Instant) {
                ahora = Temporal.Now.instant();
                this.#tiempo = ahora.since(this.#inicio).total({ unit: 'milliseconds' });
            } else {
                return;
            }
        } catch (e) {
            if (this.#inicio instanceof Date) {
                ahora = new Date();
                this.#tiempo = ahora.getTime() - this.#inicio.getTime();
            }
        }

        this.#mostrar();
    }

    #mostrar() {
        const milisegundos = this.#tiempo;
        const totalSegundos = parseInt(milisegundos / 1000, 10);
        const minutos = parseInt(totalSegundos / 60, 10);
        const segundos = totalSegundos % 60;
        const decimas = parseInt((milisegundos % 1000) / 100, 10);

        const minutosStr = String(minutos).padStart(2, '0');
        const segundosStr = String(segundos).padStart(2, '0');
        const decimasStr = String(decimas);

        const formato = `${minutosStr}:${segundosStr}.${decimasStr}`;

        try {
            const parrafo = document.querySelector('main p');
            if (parrafo) {
                parrafo.textContent = formato;
            }
        } catch (e) {
            console.error("Error al actualizar el cronómetro:", e);
        }
    }
    parar() {
        clearInterval(this.#corriendo);
        this.#corriendo = null;
    }

    reiniciar() {
        this.parar();
        this.#tiempo = 0;
        this.#mostrar();
    }
}
