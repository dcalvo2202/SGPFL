# LDAP Docker - SGPFL

Contenedor OpenLDAP + phpLDAPadmin para desarrollo local.

## Estructura

```
dc=una,dc=ac,dc=cr
├── ou=Groups
│   ├── cn=Administrador
│   ├── cn=Asesor Externo
│   ├── cn=CTFG
│   ├── cn=Estudiante
│   └── cn=Gestor Academico
└── ou=People
    ├── uid=105710421  (Administrador)
    ├── uid=110600492  (Gestor Academico)
    ├── uid=111710169  (CTFG)
    ├── uid=118440202  (Estudiante)
    └── uid=205610158  (Asesor Externo)
```

## Levantar

```bash
docker-compose up -d
```

## Accesos

| Servicio      | URL                        | Usuario                          | Contraseña |
|---------------|----------------------------|----------------------------------|------------|
| phpLDAPadmin  | http://localhost:8090       | cn=admin,dc=una,dc=ac,dc=cr      | admin      |
| LDAP          | ldap://localhost:389        | cn=admin,dc=una,dc=ac,dc=cr      | admin      |

## Usuarios de prueba

| UID       | Rol              | Contraseña     |
|-----------|------------------|----------------|
| 105710421 | Administrador    | admin123       |
| 110600492 | Gestor Academico | gestor123      |
| 111710169 | CTFG             | ctfg123        |
| 118440202 | Estudiante       | estudiante123  |
| 205610158 | Asesor Externo   | asesor123      |

## Detener y limpiar

```bash
# Solo detener
docker-compose down

# Detener y eliminar datos (reset completo)
docker-compose down -v
```
