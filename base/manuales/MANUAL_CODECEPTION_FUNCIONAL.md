# Manual de Codeception (Pruebas Funcionales)

Este manual explica cómo **usar**, **instalar** y **reinstalar** Codeception para pruebas funcionales en este proyecto.

## 1) ¿Qué quedó configurado?

Se configuraron estos archivos:

- `codeception.yml`
- `tests/functional.suite.yml`
- `tests/Support/FunctionalTester.php`
- `tests/functional/LoginPageCest.php` (ejemplo inicial)

Y estos scripts en `composer.json`:

- `composer test:functional`
- `composer test:functional:debug`

---

## 2) Requisitos previos

Antes de ejecutar pruebas funcionales:

1. Tener **PHP** y **Composer** instalados.
2. Tener el proyecto con dependencias instaladas (`composer install`).
3. Tener el servidor web levantado (XAMPP/Apache) y accesible en:
   - `http://localhost/base/`
4. Verificar que la URL base en `tests/functional.suite.yml` sea correcta:

```yml
modules:
  enabled:
    - PhpBrowser:
        url: 'http://localhost/base/'
```

---

## 3) Cómo ejecutar pruebas funcionales

### Ejecutar toda la suite funcional

```bash
composer test:functional
```

### Ejecutar con más detalle (debug)

```bash
composer test:functional:debug
```

### Ejecutar una sola prueba

```bash
vendor/bin/codecept run functional tests/functional/LoginPageCest.php --steps
```

### Generar/actualizar actor y acciones (cuando cambias suites o módulos)

```bash
vendor/bin/codecept build
```

---

## 4) Cómo crear una nueva prueba funcional

1. Crear archivo en `tests/functional/`, por ejemplo: `MiFlujoCest.php`.
2. Estructura recomendada:

```php
<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

final class MiFlujoCest
{
    public function pruebaBasica(FunctionalTester $I): void
    {
        $I->amOnPage('/login.php');
        $I->seeElement('#user');
    }
}
```

3. Ejecutar:

```bash
vendor/bin/codecept run functional tests/functional/MiFlujoCest.php --steps
```

---

## 5) Instalación inicial (si se configura desde cero)

Desde la raíz del proyecto (`base/`):

```bash
composer require --dev "codeception/codeception:5.3.*" "codeception/module-phpbrowser:4.*" "codeception/module-asserts:3.3.*" -W
```

Luego:

```bash
vendor/bin/codecept build
```

> Nota: en este proyecto se usan versiones compatibles con PHPUnit 11.

---

## 6) Reinstalación limpia (si algo se rompe)

### Opción A: Reinstalar solo dependencias de Codeception

```bash
composer remove --dev codeception/codeception codeception/module-phpbrowser codeception/module-asserts
composer require --dev "codeception/codeception:5.3.*" "codeception/module-phpbrowser:4.*" "codeception/module-asserts:3.3.*" -W
vendor/bin/codecept build
```

### Opción B: Reinstalar todo el vendor

```bash
composer install
vendor/bin/codecept build
```

---

## 7) Problemas comunes

### Error: no encuentra `codecept`

Usa la ruta del binario local:

```bash
vendor/bin/codecept run functional --steps
```

### Error de conexión a `localhost/base`

- Verifica Apache encendido.
- Verifica que la app responda en navegador.
- Revisa `url` en `tests/functional.suite.yml`.

### Error por cambios en módulos/actor

Regenera actor:

```bash
vendor/bin/codecept build
```

### Fallos por estado de sesión/datos

- Limpia sesiones de navegador/entorno.
- Asegura datos mínimos para el flujo probado.
- Evita dependencias entre tests (cada test debe ser independiente).

---

## 8) Buenas prácticas rápidas

- Mantener pruebas pequeñas y enfocadas.
- Nombrar métodos con intención clara (`loginPageLoads`, `showsErrorOnInvalidLogin`, etc.).
- Evitar lógica compleja dentro del test.
- Si varios tests repiten pasos, extraer helpers/acciones comunes.

---

## 9) Comandos útiles (resumen)

```bash
composer test:functional
composer test:functional:debug
vendor/bin/codecept build
vendor/bin/codecept run functional --steps
```

---

Si necesitas, puedo agregar una sección de "checklist de CI" para correr estas pruebas en GitHub Actions o en tu pipeline actual.