import xml.etree.ElementTree as ET
import os

class Html:
    """
    Clase para generar un archivo HTML5 semántico y adaptable.
    """
    def __init__(self, output_file):
        """
        Prepara el archivo de salida.
        """
        self.output_file = output_file
        # Abre el archivo en modo escritura (w) con codificación UTF-8
        self.file = open(self.output_file, 'w', encoding='utf-8')
        self.indent_level = 0

    def open(self, title):
        """
        Escribe la cabecera HTML (head) y abre la etiqueta body.
        Incluye viewport para adaptabilidad y enlace a estilo.css.
        """
        self.write_line('<!DOCTYPE html>')
        self.write_line('<html lang="es">')
        self.write_line('<head>')
        self.indent()
        self.write_line('<meta charset="UTF-8" />')
        self.write_line('<meta name="viewport" content="width=device-width, initial-scale=1.0" />')
        self.write_line(f'<title>{title}</title>')
        self.write_line('<link rel="stylesheet" type="text/css" href="estilo.css" />')
        self.write_line('<style>')
        self.write_line('  body { font-family: sans-serif; line-height: 1.6; padding: 1em; max-width: 900px; margin: auto; }')
        self.write_line('  h1, h2, h3 { color: #333; }')
        self.write_line('  section { background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 1.5em; padding: 1em; }')
        self.write_line('  img, video { max-width: 100%; height: auto; border-radius: 4px; }')
        self.write_line('  figure { margin: 0; }')
        self.write_line('  ul { padding-left: 20px; }')
        self.write_line('</style>')
        self.unindent()
        self.write_line('</head>')
        self.write_line('<body>')
        self.indent()

    def close(self):
        """
        Cierra las etiquetas body y html, y el archivo.
        """
        self.unindent()
        self.write_line('</body>')
        self.write_line('</html>')
        self.file.close()

    def add_element(self, content):
        """
        Añade una línea de contenido HTML al body.
        """
        self.write_line(content)

    def write_line(self, line):
        """
        Escribe una línea en el archivo con la indentación correcta.
        """
        indent = '    ' * self.indent_level
        self.file.write(f"{indent}{line}\n")

    def indent(self):
        """Aumenta el nivel de indentación."""
        self.indent_level += 1

    def unindent(self):
        """Reduce el nivel de indentación."""
        self.indent_level -= 1

def parse_xml_to_html(xml_file, html_file):
    """
    Función principal que parsea el XML y genera el HTML.
    Usa XPath para extraer los datos, manejando el namespace.
    """
    try:
        tree = ET.parse(xml_file)
        root = tree.getroot()

        ns = {'circ': 'http://www.uniovi.es'}

        html_gen = Html(html_file)

        circuito_nombre = root.find('circ:nombre', ns).text
        html_gen.open(f"Información de: {circuito_nombre}")

        html_gen.add_element('<main>')
        html_gen.indent()
        
        html_gen.add_element(f'<h3>{circuito_nombre}</h3>')

        html_gen.add_element('<section>')
        html_gen.indent()
        html_gen.add_element('<h4>Datos Generales</h4>')
        html_gen.add_element('<ul>')
        html_gen.indent()
        
        longitud = root.find('circ:longitud_pista', ns)
        anchura = root.find('circ:anchura', ns)
        
        html_gen.add_element(f"<li><strong>Longitud:</strong> {longitud.text} {longitud.get('unidad')}</li>")
        html_gen.add_element(f"<li><strong>Anchura:</strong> {anchura.text} {anchura.get('unidad')}</li>")
        html_gen.add_element(f"<li><strong>Fecha:</strong> {root.findtext('circ:fecha', '', ns)}</li>")
        html_gen.add_element(f"<li><strong>Hora:</strong> {root.findtext('circ:hora', '', ns)}</li>")
        html_gen.add_element(f"<li><strong>Vueltas:</strong> {root.findtext('circ:vueltas', '', ns)}</li>")
        html_gen.add_element(f"<li><strong>Localidad:</strong> {root.findtext('circ:localidad', '', ns)}</li>")
        html_gen.add_element(f"<li><strong>País:</strong> {root.findtext('circ:pais', '', ns)}</li>")
        html_gen.add_element(f"<li><strong>Patrocinador:</strong> {root.findtext('circ:patrocinador', '', ns)}</li>")
        
        html_gen.unindent()
        html_gen.add_element('</ul>')
        html_gen.unindent()
        html_gen.add_element('</section>')

        coords_node = root.find('circ:coordenadas_centro', ns)
        if coords_node is not None:
            html_gen.add_element('<section>')
            html_gen.indent()
            html_gen.add_element('<h4>Coordenadas del Centro</h4>')
            html_gen.add_element('<ul>')
            html_gen.indent()
            html_gen.add_element(f"<li><strong>Latitud:</strong> {coords_node.findtext('circ:latitud', '', ns)}</li>")
            html_gen.add_element(f"<li><strong>Longitud:</strong> {coords_node.findtext('circ:longitud', '', ns)}</li>")
            html_gen.add_element(f"<li><strong>Altitud:</strong> {coords_node.findtext('circ:altitud', '', ns)}</li>")
            html_gen.unindent()
            html_gen.add_element('</ul>')
            html_gen.unindent()
            html_gen.add_element('</section>')

        refs = root.findall('circ:bibliografia/circ:referencia', ns)
        if refs:
            html_gen.add_element('<section>')
            html_gen.indent()
            html_gen.add_element('<h4>Bibliografía</h4>')
            html_gen.add_element('<ul>')
            html_gen.indent()
            for ref in refs:
                html_gen.add_element(f'<li><a href="{ref.text}">{ref.text}</a></li>')
            html_gen.unindent()
            html_gen.add_element('</ul>')
            html_gen.unindent()
            html_gen.add_element('</section>')

        fotos = root.findall('circ:galeria_fotos/circ:foto', ns)
        if fotos:
            html_gen.add_element('<section>')
            html_gen.indent()
            html_gen.add_element('<h4>Galería de Fotos</h4>')
            for foto in fotos:
                desc = foto.findtext('circ:descripcion', '', ns)
                arch = foto.findtext('circ:archivo', '', ns)
                html_gen.add_element('<figure>')
                html_gen.indent()
                html_gen.add_element(f'<img src="{arch}" alt="{desc}">') 
                html_gen.add_element(f'<figcaption>{desc}</figcaption>')
                html_gen.unindent()
                html_gen.add_element('</figure>')
            html_gen.unindent()
            html_gen.add_element('</section>')

        videos = root.findall('circ:galeria_videos/circ:video', ns)
        if videos:
            html_gen.add_element('<section>')
            html_gen.indent()
            html_gen.add_element('<h4>Galería de Videos</h4>')
            for video in videos:
                desc = video.findtext('circ:descripcion', '', ns)
                arch = video.findtext('circ:archivo', '', ns)
                html_gen.add_element('<figure>')
                html_gen.indent()
                html_gen.add_element(f'<video controls src="{arch}">')
                html_gen.indent()
                html_gen.add_element(f'Tu navegador no soporta el video. Descripción: {desc}')
                html_gen.unindent()
                html_gen.add_element('</video>')
                html_gen.add_element(f'<figcaption>{desc}</figcaption>')
                html_gen.unindent()
                html_gen.add_element('</figure>')
            html_gen.unindent()
            html_gen.add_element('</section>')

        resultados_node = root.find('circ:resultados_carrera', ns)
        if resultados_node is not None:
            html_gen.add_element('<section>')
            html_gen.indent()
            html_gen.add_element('<h4>Resultados de la Carrera</h4>')
            
            vencedor = resultados_node.find('circ:vencedor', ns)
            if vencedor is not None:
                html_gen.add_element('<h5>Vencedor</h5>')
                html_gen.add_element(f"<p>{vencedor.findtext('circ:nombre', '', ns)} (Tiempo: {vencedor.findtext('circ:tiempo', '', ns)})</p>")

            pilotos = resultados_node.findall('circ:clasificacion_mundial/circ:piloto', ns)
            if pilotos:
                html_gen.add_element('<h4>Clasificación Mundial</h4>')
                html_gen.add_element('<ul>')
                html_gen.indent()
                for piloto in pilotos:
                    pos = piloto.findtext('circ:posicion', '', ns)
                    nom = piloto.findtext('circ:nombre', '', ns)
                    html_gen.add_element(f'<li>{pos}. {nom}</li>')
                html_gen.unindent()
                html_gen.add_element('</ul>')
            
            html_gen.unindent()
            html_gen.add_element('</section>')

        html_gen.unindent()
        html_gen.add_element('</main>')

        html_gen.close()
        print(f"Archivo '{html_file}' generado exitosamente.")

    except ET.ParseError as e:
        print(f"Error al parsear el XML: {e}")
    except IOError as e:
        print(f"Error de E/S (¿no se encontró el archivo?): {e}")
    except Exception as e:
        print(f"Ocurrió un error inesperado: {e}")

if __name__ == "__main__":
    xml_input = "circuitoEsquema.xml"
    html_output = "InfoCircuito.html"
    
    if os.path.exists(xml_input):
        parse_xml_to_html(xml_input, html_output)
    else:
        print(f"Error: No se encontró el archivo de entrada '{xml_input}'")