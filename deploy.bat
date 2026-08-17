@echo off
cd /d "%~dp0"
echo ========================================================
echo   SINCRONIZANDO EPYCUS CON HOSTINGER (PRODUCCION)
echo ========================================================
echo.

echo [1/3] Subiendo codigo PHP y componentes...
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Identity\Application\UseCases\RecordEpaPretestUseCase.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Application/UseCases/RecordEpaPretestUseCase.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Identity\Presentation\Controllers\EpaController.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Presentation/Controllers/EpaController.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" resources\js\Pages\Welcome.vue u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/resources/js/Pages/Welcome.vue

echo [2/3] Subiendo build.zip compilado...
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" build.zip u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/public/build.zip

echo [3/3] Extrayendo build y limpiando cache en Hostinger...
"C:\Program Files\PuTTY\plink.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" u897008619@46.202.145.111 "cd /home/u897008619/domains/epycus.es/public_html/app/public && unzip -o build.zip && rm build.zip && cd .. && php artisan optimize:clear"

echo.
echo ========================================================
echo   DESPLIEGUE A PRODUCCION COMPLETADO CON EXITO
echo ========================================================
pause
