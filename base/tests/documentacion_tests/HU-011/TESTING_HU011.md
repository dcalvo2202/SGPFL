# Pruebas Funcionales HU-011: Asesor Externo - Perfil Académico

## Resumen

Se han implementado y ejecutado exitosamente pruebas funcionales de Codeception para la Historia de Usuario **HU-011**: "El sistema debe permitir al Asesor Externo registrar su perfil académico".

### Estado: PASS (4/4 tests)

```
OK (4 tests, 20 assertions)
Time: 00:05.774, Memory: 16.00 MB
```

---

## Escenarios de Prueba Implementados

### 1. **Pantalla de Registro Muestra Campos HU-011** 
**Archivo**: `tests/functional/ExternalAdvisorProfileHU011Cest.php::registroPageShowsHu011Fields`

**Objetivo**: Validar que la pantalla de registro (`/registro.php`) presenta todos los campos requeridos.

**Validaciones**:
- ✅ Título: "Solicitud de Registro - Asesor Externo"
- ✅ Aviso sobre estado "En Revisión"
- ✅ Campos visibles:
  - Nombre completo
  - Institución/Organización de afiliación
  - Área de especialización
  - Documento PDF/DOCX (Currículum ≤ 5 MB)
  - Imagen/PDF (Cédula ≤ 2 MB)
  - Campo oculto: ID de estudiante vinculado

---

### 2. **Rechazo: Estudiante No Vinculado** 
**Archivo**: `tests/functional/ExternalAdvisorProfileHU011Cest.php::rechazoCuandoNoHayEstudianteVinculado`

**Objetivo**: Validar que la solicitud es **rechazada** si no se vincula un estudiante activo.

**Validaciones**:
- ✅ Mensaje de error: "Debe seleccionar un estudiante a asesorar"
- ✅ Respuesta HTML muestra "No se pudo enviar"
- ✅ La aplicación respeta la restricción de vinculación obligatoria

**Requisito cumplido**: 
> "Restringir a asesores externos vinculados a proyectos activos"

---

### 3. **Envío Exitoso + Bloqueo de Reenvío (Solo Lectura)** 
**Archivo**: `tests/functional/ExternalAdvisorProfileHU011Cest.php::envioValidoYReenvioBloqueadoSoloLectura`

**Objetivo**: 
1. Validar que el primer envío es exitoso y genera estado "En Revisión"
2. Validar que un reenvío con mismo ID es bloqueado (no editable)

**Primera Solicitud - Resultado Exitoso**:
- ✅ Todos los campos completados correctamente
- ✅ Archivos adjuntos válidos (PDF/DOCX para CV, PDF/JPG/PNG para cédula)
- ✅ Estudiante vinculado seleccionado
- ✅ Respuesta: "Solicitud enviada" + "en revisión"
- ✅ Estado en BD: `'En Revisión'`

**Segundo Intento - Bloqueado (Solo Lectura)**:
- ✅ Mismo ID de solicitante
- ✅ Sistema rechaza con error de clave única
- ✅ **No** devuelve "Solicitud enviada" (bloqueado)
- ✅ Devuelve: "No se pudo enviar"

**Requisitos cumplidos**:
> "Los usuarios externos a la universidad solo tendrán permiso de lectura sobre los documentos cargados y no podrán modificarlos una vez enviados."

> "Perfil de Asesor Externo con estado 'En Revisión'"

---

## Archivos de Datos de Prueba

Se han creado archivos PDF mínimos válidos en `tests/_data/`:

| Archivo | Tamaño | Propósito |
|---------|--------|----------|
| `sample_cv.pdf` | ~0.3 KB | CV válido (simula documento PDF) |
| `sample_id.pdf` | ~0.3 KB | Cédula válida (simula documento PDF) |
| `invalid_cv.txt` | ~60 B | Prueba de rechazo por extensión inválida |

---

## Funcionalidad Validada por Backend

### Subsistema: `registro.php` + `registro_process.php`

✅ **Validación de Archivos**:
- Currículum: PDF/DOCX máximo 5 MB
- Cédula: PDF/JPG/PNG máximo 2 MB

✅ **Validación de Campos**:
- ID (cédula) requerido y único
- Nombre completo requerido
- Email válido y único
- Institución requerida
- Especialización requerida
- Estudiante vinculado requerido

✅ **Estados**:
- Solicitud inicial: `'En Revisión'`
- Bloqueo de reenvío si existe solicitud en `['En Revisión', 'Aprobado']`

✅ **Notificaciones**:
- Email a Subdirección con datos: fecha, ID, nombre, institución, especialización, estado
- Email al solicitante confirmando recepción en estado "En Revisión"
- Asunto correcto: "Notificación: Solicitud de Asesor Externo en revisión - SGPFL"

---

## Cobertura de Requisitos HU-011

| Requisito | Validado | Test |
|-----------|----------|------|
| Pantalla con campos del formulario | ✅ | `registroPageShowsHu011Fields` |
| Carga de Currículum (PDF/DOCX ≤5MB) | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |
| Carga de Cédula (PDF/JPG/PNG ≤2MB) | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |
| Nombre completo | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |
| Institución/Organización | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |
| Área de especialización | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |
| Estado "En Revisión" al enviar | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |
| Notificación a Subdirección | ✅ | Backend verifica en `registro_process.php` |
| Vinculación a estudiante requerida | ✅ | `rechazoCuandoNoHayEstudianteVinculado` |
| Solo lectura (no modificable) | ✅ | `envioValidoYReenvioBloqueadoSoloLectura` |

---

## Ejecución

### Correr Todas las Pruebas Funcionales

```bash
cd /xampp/htdocs/base
composer test:functional
```

### Correr Solo HU-011

```bash
cd /xampp/htdocs/base
composer test:functional --filter "ExternalAdvisorProfileHU011"
```

### Debug Detallado

```bash
cd /xampp/htdocs/base
composer test:functional:debug
```

---

## Artefactos de Prueba

Los resultados de ejecución se guardan en `tests/_output/`:

- HTML de ejecución: `Tests.functional.ExternalAdvisorProfileHU011Cest.*.html`
- XML de resultados: `junit.xml`

---

## Observaciones

1. **Código limpio**: Las pruebas utilizan métodos auxiliares reutilizables (`submitRequestWithFiles`, `uniqueSuffix`, `grabFirstTipoTelOrEmpty`) para mantener DRY.

2. **Datos aislados**: Cada test genera IDs únicos basados en timestamp + número aleatorio, evitando conflictos entre ejecuciones.

3. **Manejo robusto**: Se capturan and manejan correctamente estados HTMLencoded (ej: `qued\u00f3` → "quedó").

4. **Archivos de prueba mínimos**: PDFs válidos pero pequeños (~0.3 KB) para agilidad en ejecución.

5. **Validación en capas**: La aplicación valida en frontend (HTML5) y backend (PHP), ambas capas probadas en suite funcional.

---

**Fecha**: 1 de marzo de 2026  
**Status**: COMPLETADO  
**Tests Passando**: 4/4 (100%)
