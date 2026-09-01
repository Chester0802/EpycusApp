@echo off
cd /d "%~dp0"
echo ========================================================
echo   SINCRONIZANDO EPYCUS CON HOSTINGER (PRODUCCION)
echo ========================================================
echo.

echo [1/4] Compilando frontend para produccion...
call npm run build

echo [2/4] Empaquetando backend y build (tar POSIX)...
tar -czf backend.tar.gz app resources routes database config bootstrap/app.php bootstrap/providers.php
tar -czf build.tar.gz -C public/build .

echo [3/4] Subiendo paquetes a Hostinger...
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" backend.tar.gz u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/backend.tar.gz
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" build.tar.gz u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/build.tar.gz

echo [4/4] Extrayendo, fijando permisos y optimizando cache en Hostinger...
"C:\Program Files\PuTTY\plink.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" u897008619@46.202.145.111 "cd /home/u897008619/domains/epycus.es/public_html/app && tar -xzf backend.tar.gz && mkdir -p public/build && tar -xzf build.tar.gz -C public/build && rm -f backend.tar.gz build.tar.gz && rm -f bootstrap/cache/*.php && chmod -R 755 /home/u897008619/domains/epycus.es/public_html/app && chmod -R 775 /home/u897008619/domains/epycus.es/public_html/app/storage && chmod -R 775 /home/u897008619/domains/epycus.es/public_html/app/bootstrap/cache && ln -sfn /home/u897008619/domains/epycus.es/public_html/app/storage/app/public /home/u897008619/domains/epycus.es/public_html/storage && php artisan migrate --force && php artisan optimize:clear"

echo.
echo ========================================================
echo   DESPLIEGUE A PRODUCCION COMPLETADO CON EXITO
echo ========================================================
pause
