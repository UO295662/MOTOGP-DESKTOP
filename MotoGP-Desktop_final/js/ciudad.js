class Ciudad {
    #nombre;
    #pais;
    #gentilicio;
    #poblacion;
    #coordenadas;

    constructor(nombre, pais, gentilicio) {
        this.#nombre = nombre;
        this.#pais = pais;
        this.#gentilicio = gentilicio;
        this.#poblacion = null;
        this.#coordenadas = null; 
    }

    rellenarAtrib(poblacion, coordenadas) {
        this.#poblacion = poblacion;
        this.#coordenadas = coordenadas;
    }

    getNombre() {
        return this.#nombre;
    }

    getPais() {
        return this.#pais;
    }

    getInfoSecundariaHTML() {
        const ul = document.createElement('ul');

        const liGentilicio = document.createElement('li');
        liGentilicio.textContent = `Gentilicio: ${this.#gentilicio}`;
        ul.appendChild(liGentilicio);

        const liPoblacion = document.createElement('li');
        if (this.#poblacion !== null) {
            liPoblacion.textContent = `Población: ${this.#poblacion.toLocaleString('es-ES')}`; 
        } else {
            liPoblacion.textContent = `Población: (Dato no disponible)`;
        }
        ul.appendChild(liPoblacion);

        return ul; 
    }


    escribirCoordenadas() {
        const p = document.createElement('p');

        if (this.#coordenadas && this.#coordenadas.lat !== undefined && this.#coordenadas.lon !== undefined) {
            p.textContent = `Coordenadas: (Lat: ${this.#coordenadas.lat}, Lon: ${this.#coordenadas.lon})`;
        } else {
            p.textContent = "Coordenadas: (Datos no disponibles)";
        }
        
        return p;
    }


    getMeteorologiaCarrera() {
        if (!this.#coordenadas || this.#coordenadas.lat === undefined || this.#coordenadas.lon === undefined) {
            return $.Deferred().reject("No se han proporcionado coordenadas para la ciudad.").promise();
        }

        const baseURL = "https://api.open-meteo.com/v1/forecast";
        const lat = this.#coordenadas.lat;
        const lon = this.#coordenadas.lon;
        
        const hourlyParams = [
            "temperature_2m",
            "apparent_temperature",
            "rain",
            "relative_humidity_2m",
            "wind_speed_10m",
            "wind_direction_10m"
        ].join(",");

        const dailyParams = [
            "sunrise",
            "sunset"
        ].join(",");

        const url = `${baseURL}?latitude=${lat}&longitude=${lon}&hourly=${hourlyParams}&daily=${dailyParams}&timezone=auto&forecast_days=1`;

        return $.ajax({
            url: url,
            method: "GET",
            dataType: "json"
        });
    }
    procesarJSONCarrera(data) {
        const $elementos = $();

        const $dailyList = $("<ul>");
        const sunrise = new Date(data.daily.sunrise[0]).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        const sunset = new Date(data.daily.sunset[0]).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        
        $dailyList.append($("<li>").text(`Salida del sol: ${sunrise}`));
        $dailyList.append($("<li>").text(`Puesta del sol: ${sunset}`));

        const $tituloHoras = $("<h4>").text("Pronóstico para la Carrera (14:00)");
        const $hourlyList = $("<ul>");

        const datosHorarios = data.hourly;
        const unidades = data.hourly_units;

        for (let i = 0; i < datosHorarios.time.length; i++) {   
            const fechaHora = new Date(datosHorarios.time[i]);
            const hora = fechaHora.getHours();

            if (hora === 14) {
                const timeString = fechaHora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                
                const $itemHora = $("<li>");
                $itemHora.append($("<h5>").text(timeString)); 

                const $detailsList = $("<ul>");
                $detailsList.append($("<li>").text(
                    `Temp.: ${datosHorarios.temperature_2m[i]} ${unidades.temperature_2m}`
                ));
                $detailsList.append($("<li>").text(
                    `Sens. T.: ${datosHorarios.apparent_temperature[i]} ${unidades.apparent_temperature}`
                ));
                $detailsList.append($("<li>").text(
                    `Lluvia: ${datosHorarios.rain[i]} ${unidades.rain}`
                ));
                $detailsList.append($("<li>").text(
                    `Humedad: ${datosHorarios.relative_humidity_2m[i]} ${unidades.relative_humidity_2m}`
                ));
                $detailsList.append($("<li>").text(
                    `Viento: ${datosHorarios.wind_speed_10m[i]} ${unidades.wind_speed_10m} (Dir: ${datosHorarios.wind_direction_10m[i]}${unidades.wind_direction_10m})`
                ));

                $itemHora.append($detailsList);
                $hourlyList.append($itemHora);
            }
        }
        
        return $elementos.add($dailyList).add($tituloHoras).add($hourlyList);
    }
    getMeteorologiaEntrenos() {
        if (!this.#coordenadas || this.#coordenadas.lat === undefined || this.#coordenadas.lon === undefined) {
            return $.Deferred().reject("No se han proporcionado coordenadas para la ciudad.").promise();
        }

        const baseURL = "https://api.open-meteo.com/v1/forecast";
        const lat = this.#coordenadas.lat;
        const lon = this.#coordenadas.lon;
        
        const hourlyParams = [
            "temperature_2m",
            "rain",
            "relative_humidity_2m",
            "wind_speed_10m"
        ].join(",");

        const url = `${baseURL}?latitude=${lat}&longitude=${lon}&hourly=${hourlyParams}&timezone=auto&forecast_days=3`;

        return $.ajax({
            url: url,
            method: "GET",
            dataType: "json"
        });
    }

    procesarJSONEntrenos(data) {
        
        const calcularMedia = (arr) => {
            if (!arr || arr.length === 0) return 0;
            const sum = arr.reduce((acc, val) => acc + val, 0);
            return (sum / arr.length);
        };

        const resultados = [];
        const datosPorDia = 24; 

        const metricas = [
            "temperature_2m",
            "rain",
            "relative_humidity_2m",
            "wind_speed_10m"
        ];

        for (let i = 0; i < 3; i++) {
            const dia = {
                dia: i + 1,
                datos: {},
                unidades: {}
            };

            const inicio = i * datosPorDia;
            const fin = (i + 1) * datosPorDia;

            metricas.forEach(metrica => {
                if (data.hourly[metrica]) {
                    const datosDelDia = data.hourly[metrica].slice(inicio, fin);
                    const media = calcularMedia(datosDelDia);
                    
                    dia.datos[metrica] = parseFloat(media.toFixed(2));
                    
                    if (data.hourly_units[metrica]) {
                        dia.unidades[metrica] = data.hourly_units[metrica];
                    }
                }
            });
            
            resultados.push(dia);
        }
        const $mainList = $("<ul>");

        resultados.forEach(dia => {
            const $diaItem = $("<li>");
            $diaItem.append($("<h4>").text(`Día ${dia.dia}`));

            const $datosList = $("<ul>");
            
            $datosList.append($("<li>").text(
                `Temperatura Media: ${dia.datos.temperature_2m} ${dia.unidades.temperature_2m}`
            ));
            $datosList.append($("<li>").text(
                `Lluvia (media total): ${dia.datos.rain} ${dia.unidades.rain}`
            ));
            $datosList.append($("<li>").text(
                `Humedad Media: ${dia.datos.relative_humidity_2m} ${dia.unidades.relative_humidity_2m}`
            ));
            $datosList.append($("<li>").text(
                `Viento Medio: ${dia.datos.wind_speed_10m} ${dia.unidades.wind_speed_10m}`
            ));
            
            $diaItem.append($datosList);
            $mainList.append($diaItem);
        });
        return $mainList;
    }
    
}