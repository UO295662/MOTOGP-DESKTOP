class Memoria {
    #primera_carta;
    #segunda_carta;
    #tablero_bloqueado;
    #cronometro;

    constructor() {
        this.#primera_carta = null;
        this.#segunda_carta = null;
        this.#tablero_bloqueado = true;

        this.#barajarCartas();
        this.#tablero_bloqueado = false;

        this.#cronometro = new Cronometro();
        this.#cronometro.arrancar();

        this.#vincularEventos(); 
    }

    #vincularEventos() {
        const cartas = document.querySelectorAll('main article');
        cartas.forEach(carta => {
            carta.addEventListener('click', () => this.#voltearCarta(carta));
        });
    }

    #voltearCarta(carta) {
        if (this.#tablero_bloqueado ||
            carta.dataset.estado === 'volteada' ||
            carta.dataset.estado === 'revelada' ||
            carta === this.#primera_carta) {
            return;
        }

        carta.dataset.estado = "volteada";

        if (!this.#primera_carta) {
            this.#primera_carta = carta;
            return;
        }

        this.#segunda_carta = carta;
        this.#comprobarPareja();
    }

    #barajarCartas() {
        const main = document.querySelector('main');
        const cartas = main.querySelectorAll('article');
        const arrayCartas = Array.from(cartas);

        for (let i = arrayCartas.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arrayCartas[i], arrayCartas[j]] = [arrayCartas[j], arrayCartas[i]];
        }

        arrayCartas.forEach(carta => main.appendChild(carta));
    }

    #reiniciarAtributos() {
        this.#primera_carta = null;
        this.#segunda_carta = null;
        this.#tablero_bloqueado = false;
    }

    #deshabilitarCartas() {
        this.#primera_carta.dataset.estado = 'revelada';
        this.#segunda_carta.dataset.estado = 'revelada';

        this.#comprobarJuego();
        this.#reiniciarAtributos();
    }

    #comprobarJuego() {
        const cartasReveladas = document.querySelectorAll('article[data-estado="revelada"]');
        const totalCartas = document.querySelectorAll('article');

        if (cartasReveladas.length === totalCartas.length) {
            this.#cronometro.parar();
        }
    }

    #cubrirCartas() {
        this.#tablero_bloqueado = true;

        setTimeout(() => {
            delete this.#primera_carta.dataset.estado;
            delete this.#segunda_carta.dataset.estado;
            this.#reiniciarAtributos();
        }, 1500);
    }

    #comprobarPareja() {
        const carta1 = this.#primera_carta;
        const carta2 = this.#segunda_carta;

        const img1 = carta1.querySelector('img').getAttribute('src');
        const img2 = carta2.querySelector('img').getAttribute('src');

        img1 === img2 ? this.#deshabilitarCartas() : this.#cubrirCartas();
    }
}
