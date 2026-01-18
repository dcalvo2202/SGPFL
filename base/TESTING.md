
# Testing (PHPUnit) – guía del proyecto

Este proyecto usa **PHPUnit** vía **Composer** para ejecutar tests (principalmente unitarios).

> Contexto: durante la puesta a punto se detectó que Composer no podía instalar dependencias correctamente en Windows porque el PHP del **CLI** no tenía habilitado `ext-zip`. Eso dejaba `vendor/` incompleto y PHPUnit no arrancaba.

---

## 1) Qué hay actualmente (configuración)

### Dependencias (Composer)
Archivo: `composer.json`

- `require-dev`
	- `phpunit/phpunit:^11.0` (runner de tests)
	- `php-mock/php-mock-phpunit:^2.7` (mock de funciones built-in vía namespace fallback)
- `require`
	- `php-webdriver/webdriver:^1.1` (para tests de tipo Selenium/funcionales; **ojo**: esta dependencia suele requerir `ext-zip` en el PHP del CLI para instalarse correctamente)

### Script de test
En `composer.json`:

- `composer test` ejecuta `phpunit --colors=always --testdox`

### Config de PHPUnit
Archivo: `phpunit.xml`

- Usa el autoloader de Composer: `bootstrap="vendor/autoload.php"`
- Cache: `cacheDirectory=".phpunit.cache"`
- Suite configurada:
	- `Unit` → carpeta `tests/unit`

Notas:
- Hay una copia creada por la migración de PHPUnit: `phpunit.xml.bak`.
- PHPUnit genera cache en `.phpunit.cache/` (normal).

---

## 2) Cómo ejecutar los tests

Desde la raíz del proyecto:

```powershell
composer test
```

Esto equivale a correr **unit tests** (por defecto) con la config `phpunit.unit.xml`.

### 2.1 Unit tests (rápidos)

```powershell
composer test:unit
```

### 2.2 Integration tests (Selenium / WebDriver)

Estos tests requieren un **Selenium Server/Grid** accesible. En este repo, el test de ejemplo usa:

- `http://10.251.34.229:4444/wd/hub`

Para ejecutarlos:

```powershell
composer test:integration
```

Alternativa directa:

```powershell
vendor\bin\phpunit -c phpunit.unit.xml --testdox
```

---

## 3) Problema que impedía correr PHPUnit (y cómo se resolvió)

### 3.1 Síntoma

- `composer test` fallaba o no encontraba el runner porque `vendor/` estaba incompleto.
- `composer install` podía fallar (o quedar a medias) por falta de extensiones del PHP **CLI**.

### 3.2 Causa raíz

- Faltaba habilitar **ZIP** en el PHP que usa Composer/CLI.

### 3.3 Verificaciones recomendadas (Windows)

Comprobar qué PHP está usando tu terminal:

```powershell
php -v
php --ini
```

Comprobar si ZIP está cargado:

```powershell
php -m | findstr /i zip
```

### 3.4 Fix aplicado

1) Editar el `php.ini` que realmente usa el CLI (el que muestra `php --ini`).
2) Asegurar que esté habilitado ZIP:

```ini
extension=zip
```

3) Reinstalar dependencias cuando `vendor/` quedó “roto”:

```powershell
# borra vendor/ (si estaba incompleto)
Remove-Item -Recurse -Force .\vendor

# reinstala
composer install
```

---

## 4) Ajustes realizados para estabilizar los unit tests

Después de arreglar dependencias, PHPUnit arrancó pero había fallos de tests por dos causas típicas:

1) **Mismatches** entre lo que el test esperaba y lo que el código realmente hace (firmas/SQL/flujo).
2) Estrategias de mock demasiado agresivas (por ejemplo intentar redeclarar `mysqli`, lo cual provoca fatal errors).

### 4.1 Cambio en código productivo (para hacerlo testeable)

Archivo: `mod/admin/users/process_final_document_review.php`

Se añadió un “seam” (punto de inyección) para permitir que los unit tests usen una conexión falsa:

- Si existe `$GLOBALS['__mysqli_mock']`, se usa eso como conexión.
- Si no, se crea la conexión real con `new mysqli(...)`.

Además:

- Se evitó depender de `$result->num_rows` y se pasó a validar existencia vía `fetch_assoc()`.
- Se protegió el acceso a `connect_error` para no romper con mocks que no implementen la propiedad.

**Motivo**: evitar hacks como redeclarar clases internas y permitir mocks consistentes.

### 4.2 Cambios en tests unitarios

- `tests/unit/FinalDocumentReviewTest.php`
	- Se reemplazó la estrategia inválida de redeclarar `mysqli` por un stub (`DummyMysqli`) + mocks de `prepare()`/statements.
	- Se alineó el orden de `prepare()` con el flujo real del script.

- `tests/unit/TFGProposalHistoryLimitTest.php`
	- Se actualizaron SQL/mocks para corresponder con la implementación actual de la función probada.
	- Se evitó retornar `null` en callbacks de `prepare()` (mejor retornar `false` cuando no coincide).

- `tests/unit/TFGVersionLimitTest.php`
	- Se ajustó el test para contemplar los deletes reales que hace la función (incluyendo tablas relacionadas).

---

## 5) Limpieza de configuración (deprecations de PHPUnit)

### Síntoma

PHPUnit avisaba:

- “Your XML configuration validates against a deprecated schema…”

### Fix aplicado

- Se migró `phpunit.xml` para que valide contra el XSD local:

```xml
xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
```

- Se configuró `cacheDirectory=".phpunit.cache"`.
- PHPUnit dejó un backup: `phpunit.xml.bak`.

---

## 6) Estado final verificado

- Los unit tests pasan correctamente.
- La configuración ya no muestra el warning de schema deprecated.

---

## 7) Mejoras opcionales (si quieres que lo deje más “pro”)

1) Separar suites:
	 - `tests/unit` (rápidos, sin Selenium)
	 - `tests/integration` (funcionales con Selenium/webdriver)

2) Mover `php-webdriver/webdriver` a `require-dev` si solo se usa para tests.

3) Añadir `composer.lock` (si no existe) y decidir si `vendor/` debe versionarse o ignorarse.

Si me confirmas que quieres separar unit/integration, te creo `phpunit.unit.xml`, `phpunit.integration.xml` y scripts `composer test:unit` / `composer test:integration`.
