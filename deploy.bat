@echo off
cd /d "%~dp0"
echo ========================================================
echo   SINCRONIZANDO EPYCUS CON HOSTINGER (PRODUCCION)
echo ========================================================
echo.

echo [1/4] Creando directorios y subiendo codigo PHP, migraciones y componentes...
"C:\Program Files\PuTTY\plink.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" u897008619@46.202.145.111 "mkdir -p /home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Application/Services"

"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Http\Controllers\FeedbackController.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Http/Controllers/FeedbackController.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" database\migrations\2026_08_19_000000_add_image_path_to_feedbacks_table.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/database/migrations/2026_08_19_000000_add_image_path_to_feedbacks_table.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Identity\Application\Services\EpaDiagnosticPresenter.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Application/Services/EpaDiagnosticPresenter.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Identity\Application\UseCases\RecordEpaPretestUseCase.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Application/UseCases/RecordEpaPretestUseCase.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Identity\Presentation\Controllers\EpaController.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Presentation/Controllers/EpaController.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Identity\Presentation\Requests\RecordEpaRequest.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Identity/Presentation/Requests/RecordEpaRequest.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" app\Modules\Wellbeing\Presentation\Controllers\WellbeingController.php u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/app/Modules/Wellbeing/Presentation/Controllers/WellbeingController.php
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" resources\js\Components\ui\BaseModal.vue u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/resources/js/Components/ui/BaseModal.vue
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" resources\js\Components\EpaPretestModal.vue u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/resources/js/Components/EpaPretestModal.vue
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" resources\js\Pages\Wellbeing\Index.vue u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/resources/js/Pages/Wellbeing/Index.vue
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" resources\js\Pages\Welcome.vue u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/resources/js/Pages/Welcome.vue

echo [2/4] Subiendo build.zip compilado...
"C:\Program Files\PuTTY\pscp.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" build.zip u897008619@46.202.145.111:/home/u897008619/domains/epycus.es/public_html/app/public/build.zip

echo [3/4] Extrayendo build, ejecutando migraciones y limpiando cache en Hostinger...
"C:\Program Files\PuTTY\plink.exe" -batch -hostkey "SHA256:5NPmo7Lsf5dX4VteyZJK2tpslJ3r/zQxyZbWxhjS5+k" -P 65002 -pw "Marco123:)" u897008619@46.202.145.111 "cd /home/u897008619/domains/epycus.es/public_html/app/public && unzip -o build.zip && rm build.zip && cd .. && php artisan migrate --force && php artisan optimize:clear"

echo.
echo ========================================================
echo   DESPLIEGUE A PRODUCCION COMPLETADO CON EXITO
echo ========================================================
pause
