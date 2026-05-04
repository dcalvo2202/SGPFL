#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Script para reorganizar firmas en DOCX de actas
Patrón deseado:
1. Presidente solo (centrado)
2. Director + Tutor (dos columnas)
3. Asesor solo (centrado)
4. Postulantes 1 + 2 (dos columnas)
5. Observaciones
6. Espacios en blanco
7. Líneas decorativas (firma)
"""

import zipfile
import os
import shutil
from lxml import etree

# Rutas
DOCX_PATH = r'C:\xampp\htdocs\base\templates\ACTA PRES.PUB-BORRADOR.docx'
EXTRACT_PATH = r'C:\xampp\htdocs\base\templates\temp_docx'

# Namespaces
NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
}

# Registrar namespaces principales
for prefix, uri in NS.items():
    etree.register_namespace(prefix, uri)

doc_path = os.path.join(EXTRACT_PATH, 'word', 'document.xml')
tree = etree.parse(doc_path)
root = tree.getroot()

def find_para_by_text(body, text_search):
    """Busca un párrafo que contenga cierto texto"""
    for para in body.findall('.//w:p', NS):
        text_nodes = para.findall('.//w:t', NS)
        full_text = ''.join([t.text for t in text_nodes if t.text])
        if text_search in full_text:
            return para
    return None

body = root.find('.//w:body', NS)
if body is None:
    print("ERROR: No se encontró body")
    exit(1)

# Obtener la tabla (para extraer sus párrafos)
table = body.find('.//w:tbl', NS)
if table is None:
    print("ERROR: No se encontró tabla")
    exit(1)

# Obtener todos los párrafos de la tabla
table_paragraphs = []
for cell in table.findall('.//w:tc', NS):
    for para in cell.findall('.//w:p', NS):
        text_nodes = para.findall('.//w:t', NS)
        full_text = ''.join([t.text for t in text_nodes if t.text]).strip()
        print(f"Párrafo en tabla: '{full_text}'")
        table_paragraphs.append((para, full_text))

# Encontrar párrafos clave después de la tabla
print("\n--- Buscando párrafos fuera de tabla ---")
all_para_list = body.findall('.//w:p', NS)
para_index = all_para_list.index(table) if table in all_para_list else -1
print(f"Índice de tabla: {para_index}")

# Búsquedas específicas
anchor = find_para_by_text(body, 'Se da lectura a esta acta')
pres_para = find_para_by_text(body, '{{presidente_nombre}}')
director_para = find_para_by_text(body, '{{director_nombre}}')
tutor_para = find_para_by_text(body, '{{tutor_nombre}}')
asesor_para = find_para_by_text(body, '{{asesor_nombre}}')
post1_para = find_para_by_text(body, '{{postulante1_nombre}}')
obs_para = find_para_by_text(body, 'OBSERVACIONES')

print(f"\nEncontrados:")
print(f"  Anchor (Se da lectura): {anchor is not None}")
print(f"  Presidente: {pres_para is not None}")
print(f"  Director: {director_para is not None}")
print(f"  Tutor: {tutor_para is not None}")
print(f"  Asesor: {asesor_para is not None}")
print(f"  Postulante1: {post1_para is not None}")
print(f"  Observaciones: {obs_para is not None}")

# Si encontramos los elementos clave, comenzar a reorganizar
if anchor and pres_para:
    print("\n✓ Elementos clave encontrados. Estructura lista para reorganización.")
else:
    print("\n✗ Elementos faltantes. Revisión manual requerida.")

# Guardar el documento por ahora sin cambios
tree.write(doc_path, encoding='UTF-8', xml_declaration=True, standalone=True)
print("Documento guardado.")
