# Cómo correr Epycus en tu propia PC

Dos servidores tienen que estar corriendo a la vez: Laravel (backend, puerto 8000) y Vite
(assets del frontend, puerto 5173). Abre **dos ventanas de cmd** en la carpeta del proyecto.

## Requisito

Necesitas PHP 8.3 en el PATH de esa ventana de cmd. Si `php -v` no funciona, usa la ruta
completa como se muestra abajo (ajusta si tu PHP está en otro lado).

## Ventana 1 — Backend (Laravel)

```
cd /d C:\Users\marco\Videos\Epycus
set PATH=C:\Users\marco\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe;%PATH%
php artisan serve
```

Déjala corriendo. Verás `Server running on [http://127.0.0.1:8000]`.

## Ventana 2 — Frontend (Vite)

```
cd /d C:\Users\marco\Videos\Epycus
npm run dev
```

Déjala corriendo también. Verás `VITE ... ready`.

## Ver la app

Abre el navegador en:

```
http://127.0.0.1:8000
```

Regístrate con cualquier correo (no pide verificación) o entra con un usuario que ya tengas
en `epycus_local`.

## Qué probar de lo nuevo de esta sesión

1. **Navegación**: el sidebar (o la barra inferior en celular) ya no tiene "Avatar"; "Ajustes"
   está junto a los demás ítems, debajo de "Perfil"; "Salir" queda solo abajo, resaltado en rojo.
2. **Hábitos con XP real**: entra a `/habits`, crea un hábito y márcalo. El toast de "+XP"
   ahora refleja lo que Gamification realmente otorgó (no un número fijo).
3. **Progreso en el Dashboard**: en `/dashboard` (Inicio) hay una tarjeta nueva "Tu progreso"
   con Nivel, Fase, XP total y Racha — se actualiza cada vez que completas un hábito.
4. **Tope diario**: crea 6 hábitos distintos y complétalos todos el mismo día. Los primeros 5
   dan XP, el sexto no debería sumar más XP total (tope de docs/03-GAMIFICACION.md).
5. **Apagar y prender un hábito el mismo día**: no debe duplicar el XP (era un hueco real que
   se cerró esta sesión).

## Detener todo

`Ctrl+C` en cada ventana.

## Si algo no levanta

- `php artisan serve` no arranca: revisa que el PATH del PHP sea correcto y que el puerto 8000
  no esté ocupado por otra cosa.
- La página carga sin estilos / rota: `npm run dev` no está corriendo, o se cerró esa ventana.
- Error de base de datos: XAMPP/MariaDB tiene que estar corriendo con la base `epycus_local` ya
  migrada (`php artisan migrate` si es la primera vez en esta máquina).
