# Despliegue MichiSys

Checklist mínima para despliegue en producción:

1. Entorno y servidor
   - Servidor con PHP 8.2, extensiones requeridas (mbstring, xml, bcmath, pdo_mysql).
   - Node.js y npm si compilas assets en el servidor.

2. Configuración del repositorio
   - Crear un usuario/clave SSH o token GitHub para el servidor (o usar CI/CD).
   - Configurar branch de despliegue (`main` o `master`).

3. Variables de entorno
   - Copiar `.env.example` a `.env` y ajustar:
     - `APP_ENV=production`
     - `APP_DEBUG=false`
     - `APP_URL` a la URL pública
     - Configurar conexión a la base de datos (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
     - Ajustar `MAIL_*` para notificaciones si aplica

4. Dependencias y migraciones
   - Ejecutar:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate --force
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. Assets
   - Compilar assets en CI y servir `public/build`, o compilar en servidor:
   ```bash
   npm ci
   npm run build
   ```

6. Storage y permisos
   - Crear enlace de storage:
   ```bash
   php artisan storage:link
   ```
   - Ajustar permisos en `storage` y `bootstrap/cache` según el servidor web.

7. Backups y monitoreo
   - Hacer backup de la base de datos antes de migrar datos en producción.
   - Configurar logs y monitoreo (Sentry, Papertrail, etc.) si procede.

8. Rollback
   - Tener plan de rollback: backups de DB y copia del release anterior.

9. Notas específicas del proyecto
   - Comprobante de venta: la vista imprimible está en `resources/views/sales/receipt.blade.php` y la descarga PDF en la ruta `sales/{sale}/receipt.pdf`.
   - Todos los tests corren en CI; revisar resultados en Actions tras push/PR.

10. Post-despliegue
   - Verificar que la aplicación responde en `APP_URL`.
   - Crear usuario admin si es necesario para pruebas: usar seed o `php artisan tinker`.

---

Si quieres, puedo crear un script de despliegue (`deploy.sh`) o configurar GitHub Actions para deploy automático al hacer merge en `main`.
