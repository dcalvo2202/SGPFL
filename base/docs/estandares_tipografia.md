# Estándares de Tipografía UNA/ESCINF

## Base (Desktop)
- Párrafosbody: **1.5rem** (24px)
- Label: **1.25rem** (20px)

## Encabezados (CSS responsive - automática)
| Elemento | Desktop | Tablet→Móvil |
|----------|---------|--------------|
| h1       | 3rem    | 2.25rem |
| h2       | 2.75rem | 2rem |
| p        | 1.5rem  | 1.1rem |

## Componentes UI (Desktop)
| Elemento | Tamaño |
|----------|--------|
| Botones  | **1.4rem** (22px) |
| Inputs   | **1.4rem** (22px) |
| Tablas   | **1.25rem** (20px) |
| Labels  | **1.25rem** (20px) |
| Badges   | **1.1rem** (18px) |

## Móvil (media query)
- Todos: 1.1rem

## Tablas Responsive (móvil)
Todos los paneles now have responsive tables:
- Wrapper class: `<table-wrapper>`
- @media (max-width: 576px):
  - Hide thead
  - Convert rows to cards
  - Add ::before labels

### Archivos con tablas responsive
- admin_auditoria.php ✓
- panel_comites_asesores.php ✓ (verificado)
- panel_reporte_prorrogas.php ✓ (agregado)
- panel_student_summary.php ✓ (agregado)
- panel_gestor.php ✓ (agregado)