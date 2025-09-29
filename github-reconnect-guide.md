# Pasos para reconectar SGPFL con GitHub

## Información necesaria:
- URL del repositorio: https://github.com/[TU-USUARIO]/[NOMBRE-REPO].git
- Nombre del branch: [TU-BRANCH]
- Usuario GitHub: [TU-USUARIO]

## Comandos a ejecutar:

# 1. Inicializar Git en la carpeta actual
git init

# 2. Configurar usuario (reemplaza con tus datos)
git config user.name "Tu Nombre"
git config user.email "tu-email@example.com"

# 3. Agregar el repositorio remoto
git remote add origin https://github.com/[TU-USUARIO]/[NOMBRE-REPO].git

# 4. Crear y agregar archivos al staging
git add .

# 5. Hacer el primer commit
git commit -m "Restaurar proyecto SGPFL con sistema completo"

# 6. Crear/cambiar al branch específico
git checkout -b [TU-BRANCH]

# 7. Subir al branch remoto
git push -u origin [TU-BRANCH]

## Si el branch ya existe remotamente:
git fetch origin
git checkout [TU-BRANCH]
git pull origin [TU-BRANCH]
git add .
git commit -m "Sincronizar proyecto local"
git push origin [TU-BRANCH]