import xml.etree.ElementTree as ET

def convert_xml_to_kml(xml_file, kml_file):
    tree = ET.parse(xml_file)
    root = tree.getroot()
    ns = {'c': 'http://www.uniovi.es'}
    with open(kml_file, 'w', encoding='utf-8') as f:
        f.write('<?xml version="1.0" encoding="UTF-8"?>\n')
        f.write('<kml xmlns="http://www.opengis.net/kml/2.2">\n')
        f.write('  <Document>\n')
        f.write('    <name>Circuito de Jérez</name>\n')
        
        f.write('    <Style id="yellowLine">\n')
        f.write('      <LineStyle>\n')
        f.write('        <color>7f00ffff</color>\n')
        f.write('        <width>4</width>\n')
        f.write('      </LineStyle>\n')
        f.write('    </Style>\n')
        
        centro = root.find('c:coordenadas_centro', ns)
        if centro is not None:
            lat_centro = centro.find('c:latitud', ns).text
            lon_centro = centro.find('c:longitud', ns).text
            alt_centro = centro.find('c:altitud', ns).text

            f.write('    <Placemark>\n')
            f.write('      <name>Centro del Circuito</name>\n')
            f.write('      <description>Centro del circuito de Jérez</description>\n')
            f.write('      <Point>\n')
            f.write(f'        <coordinates>{lon_centro},{lat_centro},{alt_centro}</coordinates>\n')
            f.write('      </Point>\n')
            f.write('    </Placemark>\n')

            primer_tramo = root.find('c:tramos/c:tramo', ns)
            if primer_tramo is not None:
                lat_primer = primer_tramo.find('c:coordenadas/c:latitud', ns).text
                lon_primer = primer_tramo.find('c:coordenadas/c:longitud', ns).text
                alt_primer = primer_tramo.find('c:coordenadas/c:altitud', ns).text

                f.write('    <Placemark>\n')
                f.write('      <name>Conexión al Inicio</name>\n')
                f.write('      <styleUrl>#yellowLine</styleUrl>\n')
                f.write('      <LineString>\n')
                f.write('        <coordinates>\n')
                f.write(f'          {lon_centro},{lat_centro},{alt_centro}\n') 
                f.write(f'          {lon_primer},{lat_primer},{alt_primer}\n')  
                f.write('        </coordinates>\n')
                f.write('      </LineString>\n')
                f.write('    </Placemark>\n')

        f.write('    <Placemark>\n')
        f.write('      <name>Ruta del Circuito</name>\n')
        f.write('      <styleUrl>#yellowLine</styleUrl>\n')
        f.write('      <LineString>\n')
        f.write('        <coordinates>\n')

        for tramo in root.findall('c:tramos/c:tramo', ns):
            lat = tramo.find('c:coordenadas/c:latitud', ns).text
            lon = tramo.find('c:coordenadas/c:longitud', ns).text
            alt = tramo.find('c:coordenadas/c:altitud', ns).text
            f.write(f'          {lon},{lat},{alt}\n')

        f.write('        </coordinates>\n')
        f.write('      </LineString>\n')
        f.write('    </Placemark>\n')
        
        f.write('  </Document>\n')
        f.write('</kml>\n')

convert_xml_to_kml('circuitoEsquema.xml', 'circuito.kml')

