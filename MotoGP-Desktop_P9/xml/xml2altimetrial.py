import xml.etree.ElementTree as ET
import sys

class AltimetriaSVGGenerator:
    """
    Clase para generar un archivo SVG con el perfil de altimetría del circuito.
    """
    def __init__(self, xml_file, svg_file):
        self.xml_file = xml_file
        self.svg_file = svg_file
        self.namespace = {'c': 'http://www.uniovi.es'}
        self.puntos = []
        self.ancho_svg = 1000
        self.alto_svg = 500
        self.margen = 60 

    def _extraer_datos(self):
        """
        Extrae los puntos (distancia acumulada, altitud) del archivo XML.
        """
        tree = ET.parse(self.xml_file)
        root = tree.getroot()

        distancia_acumulada = 0.0
        tramos = root.findall('c:tramos/c:tramo', self.namespace)

           
        if tramos:
            altitud_inicial_element = tramos[0].find('.//c:altitud', self.namespace)
            if altitud_inicial_element is not None:
                altitud_inicial = float(altitud_inicial_element.text)
                self.puntos.append((0.0, altitud_inicial))

        for tramo in tramos:
            distancia_element = tramo.find('c:distancia', self.namespace)
            if distancia_element is not None:
                distancia = float(distancia_element.text)
                distancia_acumulada += distancia

            altitud_element = tramo.find('.//c:altitud', self.namespace)
            if altitud_element is not None:
                altitud = float(altitud_element.text)
                self.puntos.append((distancia_acumulada, altitud))
            
        if not self.puntos:
            print("Advertencia: No se encontraron tramos en el archivo XML.")
            return False
        return True

    def generar_svg(self):
        """
        Genera y escribe el contenido del archivo SVG.
        """
        if not self._extraer_datos():
            return

        max_distancia = self.puntos[-1][0] if self.puntos else 1
        altitudes = [p[1] for p in self.puntos]
        max_altitud = max(altitudes) if altitudes else 1
        min_altitud = min(altitudes) if altitudes else 0
        
        rango_altitud = max_altitud - min_altitud if max_altitud > min_altitud else 1

        escala_x = (self.ancho_svg - 2 * self.margen) / max_distancia
        escala_y = (self.alto_svg - 2 * self.margen) / rango_altitud

        puntos_svg = [
            (self.margen + x * escala_x, self.alto_svg - self.margen - (y - min_altitud) * escala_y)
            for x, y in self.puntos
        ]
        
        polilinea_str = " ".join(f"{x:.2f},{y:.2f}" for x, y in puntos_svg)
        
        poligono_str = (f"{self.margen},{self.alto_svg - self.margen} " +
                                 polilinea_str +
                                 f" {self.ancho_svg - self.margen},{self.alto_svg - self.margen}")

        svg_content = f"""<svg xmlns="http://www.w3.org/2000/svg" width="{self.ancho_svg}" height="{self.alto_svg}" viewBox="0 0 {self.ancho_svg} {self.alto_svg}">
    <title>Perfil de Altimetría del Circuito</title>
    <style>
        .poly {{ fill: #add8e6; stroke: #00008b; stroke-width: 2; }}
        .label {{ font-family: Arial, sans-serif; font-size: 14px; text-anchor: middle; }}
        .label-y {{ text-anchor: end; }}
    </style>

    <!-- Polígono del perfil de altimetría -->
    <polygon class="poly" points="{poligono_str}" />

    <!-- Etiquetas de los ejes -->
    <text class="label" x="{self.ancho_svg / 2}" y="{self.alto_svg - 10}">Distancia (metros)</text>
    <text class="label" transform="translate(15, {self.alto_svg / 2}) rotate(-90)">Altitud (metros)</text>
</svg>"""

        try:
            with open(self.svg_file, 'w', encoding='utf-8') as f:
                f.write(svg_content)
            print(f"Archivo '{self.svg_file}' generado correctamente.")
        except IOError as e:
            print(f"Error al escribir el archivo SVG: {e}")


def main():
    """Función principal para ejecutar el script desde la línea de comandos."""
    input_file = sys.argv[1] if len(sys.argv) > 1 else "circuitoEsquema.xml"
    output_file = sys.argv[2] if len(sys.argv) > 2 else "altimetria.svg"

    generator = AltimetriaSVGGenerator(input_file, output_file)
    generator.generar_svg()

if __name__ == "__main__":
    main()

