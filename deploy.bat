@echo off
cd /d "%~dp0"
echo ========================================================
echo   SINCRONIZANDO EPYCUS CON HOSTINGER (PRODUCCION)
echo ========================================================
echo.

echo [1/4] Compilando frontend para produccion...
call npm run build

echo [2/4] Empaquetando backend y build...
powershell -Command "Compress-Archive -Path app, resources, routes, database, config, bootstrap -DestinationPath backend.zip -Force"
powershell -Command "Compress-Archive -Path public/build/* -DestinationPath build.zip -Force"

echo [3/4] Subiendo paquetes a Hostinger...
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" backend.zip u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/backend.zip
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" build.zip u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/public/build.zip

echo [4/4] Extrayendo, fijando permisos y optimizando cache en Hostinger...
"C:\Program Files\PuTTY\plink.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" u897008619@46.202.145.111 "cd /home/u897008619/domains/epycus.es/public_html/app && unzip -o backend.zip && mkdir -p public/build && cd public/build && unzip -o ../build.zip && cd ../.. && rm -f bootstrap/cache/*.php && chmod -R 755 /home/u897008619/domains/epycus.es/public_html/app && chmod -R 775 /home/u897008619/domains/epycus.es/public_html/app/storage && chmod -R 775 /home/u897008619/domains/epycus.es/public_html/app/bootstrap/cache && php artisan migrate --force && php artisan optimize:clear"

echo.
echo ========================================================
echo   DESPLIEGUE A PRODUCCION COMPLETADO CON EXITO
echo ========================================================
pause
