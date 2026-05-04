#!/usr/bin/env python
# -*- coding: utf-8 -*-

import zipfile
import os
import shutil
from lxml import etree

DOCX_PATH = r'C:\xampp\htdocs\base\templates\ACTA PRES.PUB-BORRADOR.docx'
BACKUP_PATH = r'C:\xampp\htdocs\base\templates\ACTA_PRES_BACKUP.docx'
EXTRACT_PATH = r'C:\xampp\htdocs\base\templates\temp_docx'

# Crear backup
if os.path.exists(DOCX_PATH):
    shutil.copy2(DOCX_PATH, BACKUP_PATH)
    print("Backup created")

# Limpiar directorio temporal si existe
if os.path.exists(EXTRACT_PATH):
    shutil.rmtree(EXTRACT_PATH)
os.makedirs(EXTRACT_PATH, exist_ok=True)

# Extraer DOCX
with zipfile.ZipFile(DOCX_PATH, 'r') as zip_ref:
    zip_ref.extractall(EXTRACT_PATH)
print("DOCX extracted")

# Namespaces
namespaces = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
}

# Registrar namespaces
for prefix, uri in namespaces.items():
    etree.register_namespace(prefix, uri)

doc_path = os.path.join(EXTRACT_PATH, 'word', 'document.xml')
tree = etree.parse(doc_path)
root = tree.getroot()

# Funciones auxiliares
def find_paragraph_by_text(paragraphs, text_contains):
    for p in paragraphs:
        all_text = ' '.join([t.text for t in p.findall('.//w:t', namespaces) if t.text])
        if text_contains in all_text:
            return p
    return None

def get_element_text(elem):
    texts = [t.text for t in elem.findall('.//w:t', namespaces) if t.text]
    return ' '.join(texts)

# Obtener todos los párrafos
body = root.find('.//w:body', namespaces)
paragraphs = body.findall('.//w:p', namespaces)

# Identificar párrafos clave
anchor = find_paragraph_by_text(paragraphs, 'Se da lectura a esta acta')
pres_p = find_paragraph_by_text(paragraphs, 'Presidente')
obs_p = find_paragraph_by_text(paragraphs, 'Observaciones')

print(f"Anchor found: {anchor is not None}")
print(f"Presidente found: {pres_p is not None}")
print(f"Observaciones found: {obs_p is not None}")

if anchor and pres_p and obs_p:
    # Reordenar - crear nueva estructura
    # Por ahora, solo verificar que encontramos los elementos
    print("All key elements found. Structure is ready for reorganization.")
else:
    print("Some elements not found. Manual review needed.")

# Guardar documento
tree.write(doc_path, encoding='UTF-8', xml_declaration=True, standalone=True)
print("Document modified")

# Reempacar DOCX
def zipdir(path, ziph):
    for root_dir, dirs, files in os.walk(path):
        for file in files:
            file_path = os.path.join(root_dir, file)
            arcname = os.path.relpath(file_path, path)
            ziph.write(file_path, arcname)

with zipfile.ZipFile(DOCX_PATH, 'w', zipfile.ZIP_DEFLATED) as zipf:
    zipdir(EXTRACT_PATH, zipf)
print("DOCX repackaged")

print("Done!")
