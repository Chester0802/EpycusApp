using EpycusApp.Models.Entidades;

namespace EpycusApp.Datos.Semilla
{
    public static class DatosSemilla
    {
        public static async Task InicializarAsync(ContextoAplicacion contexto)
        {
            try
            {
            if (!contexto.Roles.Any())
            {
                contexto.Roles.AddRange(
                    new Rol { Nombre = "Usuario" },
                    new Rol { Nombre = "Administrador" });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.Carreras.Any())
            {
                contexto.Carreras.AddRange(
                    new Carrera { Nombre = "IngenierÃ­a de Sistemas", Area = "IngenierÃ­a", Codigo = "ing-sistemas", EstaActiva = true },
                    new Carrera { Nombre = "IngenierÃ­a Civil", Area = "IngenierÃ­a", Codigo = "ing-civil", EstaActiva = true },
                    new Carrera { Nombre = "IngenierÃ­a Industrial", Area = "IngenierÃ­a", Codigo = "ing-industrial", EstaActiva = true },
                    new Carrera { Nombre = "AdministraciÃ³n de Empresas", Area = "AdministraciÃ³n", Codigo = "administracion", EstaActiva = true },
                    new Carrera { Nombre = "Contabilidad", Area = "Negocios", Codigo = "contabilidad", EstaActiva = true },
                    new Carrera { Nombre = "Derecho", Area = "Legal", Codigo = "derecho", EstaActiva = true },
                    new Carrera { Nombre = "Medicina Humana", Area = "Salud", Codigo = "medicina", EstaActiva = true },
                    new Carrera { Nombre = "EnfermerÃ­a", Area = "Salud", Codigo = "enfermeria", EstaActiva = true },
                    new Carrera { Nombre = "PsicologÃ­a", Area = "Salud", Codigo = "psicologia", EstaActiva = true },
                    new Carrera { Nombre = "EducaciÃ³n", Area = "EducaciÃ³n", Codigo = "educacion", EstaActiva = true },
                    new Carrera { Nombre = "Arquitectura", Area = "Arquitectura", Codigo = "arquitectura", EstaActiva = true },
                    new Carrera { Nombre = "Comunicaciones", Area = "Comunicaciones", Codigo = "comunicaciones", EstaActiva = true });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.Niveles.Any())
            {
                contexto.Niveles.AddRange(
                    new Nivel { Numero = 0, Titulo = "Novato", XpRequerido = 0, Descripcion = "AÃºn no llevas el tÃ­tulo, pero el camino comienza hoy. Bienvenido." },
                    new Nivel { Numero = 1, Titulo = "Curioso", XpRequerido = 100, Descripcion = "La curiosidad es el primer paso de todo gran profesional." },
                    new Nivel { Numero = 2, Titulo = "Aprendiz", XpRequerido = 250, Descripcion = "EstÃ¡s absorbiendo conocimiento. Cada hÃ¡bito cuenta." },
                    new Nivel { Numero = 3, Titulo = "Estudiante Comprometido", XpRequerido = 450, Descripcion = "Tu constancia ya te diferencia de la mayorÃ­a." },
                    new Nivel { Numero = 4, Titulo = "Practicante", XpRequerido = 700, Descripcion = "Empiezas a aplicar lo que aprendes. El mundo te espera." },
                    new Nivel { Numero = 5, Titulo = "Asistente", XpRequerido = 1000, Descripcion = "Ya formas parte del campo profesional. Sigue creciendo." },
                    new Nivel { Numero = 6, Titulo = "Junior", XpRequerido = 1350, Descripcion = "Tienes base sÃ³lida. Los desafÃ­os reales ya no te asustan." },
                    new Nivel { Numero = 7, Titulo = "Semi-Senior", XpRequerido = 1750, Descripcion = "Tu experiencia empieza a hablar por ti." },
                    new Nivel { Numero = 8, Titulo = "Profesional", XpRequerido = 2200, Descripcion = "Dominas los fundamentos y resuelves problemas con soltura." },
                    new Nivel { Numero = 9, Titulo = "Especialista", XpRequerido = 2700, Descripcion = "Tienes un Ã¡rea en la que pocos te superan." },
                    new Nivel { Numero = 10, Titulo = "Senior", XpRequerido = 3250, Descripcion = "Mitad del camino. Tu criterio vale mÃ¡s que el de muchos." },
                    new Nivel { Numero = 11, Titulo = "Senior Avanzado", XpRequerido = 3850, Descripcion = "Lideras con el ejemplo. Otros aprenden de ti." },
                    new Nivel { Numero = 12, Titulo = "Experto", XpRequerido = 4500, Descripcion = "Tu nivel de profundidad es notable. Eres referente." },
                    new Nivel { Numero = 13, Titulo = "Consultor", XpRequerido = 5200, Descripcion = "Te buscan cuando el problema es difÃ­cil. Eso es poder." },
                    new Nivel { Numero = 14, Titulo = "LÃ­der", XpRequerido = 5950, Descripcion = "No solo resuelves: diriges, inspiras y construyes equipos." },
                    new Nivel { Numero = 15, Titulo = "Maestro", XpRequerido = 6750, Descripcion = "Tu conocimiento trasciende lo tÃ©cnico. Ya es sabidurÃ­a." },
                    new Nivel { Numero = 16, Titulo = "Arquitecto", XpRequerido = 7600, Descripcion = "DiseÃ±as sistemas, estrategias y futuros completos." },
                    new Nivel { Numero = 17, Titulo = "Eminencia", XpRequerido = 8500, Descripcion = "Tu nombre es sinÃ³nimo de excelencia en tu campo." },
                    new Nivel { Numero = 18, Titulo = "Gran Maestro", XpRequerido = 9450, Descripcion = "Has forjado a otros profesionales con tu guÃ­a." },
                    new Nivel { Numero = 19, Titulo = "Leyenda en Ascenso", XpRequerido = 10450, Descripcion = "El umbral del mÃ¡ximo poder estÃ¡ justo frente a ti." },
                    new Nivel { Numero = 20, Titulo = "Leyenda Viviente", XpRequerido = 11500, Descripcion = "Has llegado a la cima. Eres la definiciÃ³n de tu profesiÃ³n." });
                await contexto.SaveChangesAsync();
            }



            if (!contexto.Categorias.Any())
            {
                contexto.Categorias.AddRange(
                    new Categoria { Nombre = "Salud y Bienestar", Icono = "bi-heart-pulse", Tipo = "Ambos", EstaActiva = true },
                    new Categoria { Nombre = "Estudio", Icono = "bi-book", Tipo = "Ambos", EstaActiva = true },
                    new Categoria { Nombre = "Ejercicio", Icono = "bi-activity", Tipo = "Habito", EstaActiva = true },
                    new Categoria { Nombre = "SueÃ±o", Icono = "bi-moon-stars", Tipo = "Habito", EstaActiva = true },
                    new Categoria { Nombre = "HidrataciÃ³n", Icono = "bi-droplet", Tipo = "Habito", EstaActiva = true },
                    new Categoria { Nombre = "NutriciÃ³n", Icono = "bi-egg-fried", Tipo = "Habito", EstaActiva = true },
                    new Categoria { Nombre = "MeditaciÃ³n", Icono = "bi-peace", Tipo = "Habito", EstaActiva = true },
                    new Categoria { Nombre = "Tarea AcadÃ©mica", Icono = "bi-file-earmark-text", Tipo = "Mision", EstaActiva = true },
                    new Categoria { Nombre = "Proyecto", Icono = "bi-kanban", Tipo = "Mision", EstaActiva = true },
                    new Categoria { Nombre = "Lectura", Icono = "bi-book-half", Tipo = "Ambos", EstaActiva = true },
                    new Categoria { Nombre = "HÃ¡bito Personal", Icono = "bi-star", Tipo = "Habito", EstaActiva = true });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.Temas.Any())
            {
                contexto.Temas.AddRange(
                    new Tema
                    {
                        Nombre = "Noche Ã‰pica",
                        Modo = "Oscuro",
                        ArchivoCss = "tema-noche-epica.css",
                        EsPremium = false,
                        Precio = 0,
                        EstaActivo = true
                    },
                    new Tema
                    {
                        Nombre = "Sakura",
                        Modo = "Claro",
                        ArchivoCss = "tema-sakura.css",
                        EsPremium = false,
                        Precio = 0,
                        EstaActivo = true
                    });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.Personajes.Any())
            {
                var carreraSistemasId = contexto.Carreras
                    .Where(c => c.Codigo == "ing-sistemas")
                    .Select(c => c.Id)
                    .FirstOrDefault();

                contexto.Personajes.AddRange(
                    new Personaje { Nombre = "Kai", Genero = "Masculino", CarreraId = carreraSistemasId, EstaActivo = true },
                    new Personaje { Nombre = "Luna", Genero = "Femenino", CarreraId = carreraSistemasId, EstaActivo = true },
                    new Personaje { Nombre = "Ares", Genero = "Masculino", CarreraId = null, EstaActivo = true },
                    new Personaje { Nombre = "Nova", Genero = "Femenino", CarreraId = null, EstaActivo = true });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.ImagenesNivelPersonaje.Any())
            {
                var kaiId = contexto.Personajes.Where(p => p.Nombre == "Kai").Select(p => p.Id).FirstOrDefault();
                var lunaId = contexto.Personajes.Where(p => p.Nombre == "Luna").Select(p => p.Id).FirstOrDefault();
                var aresId = contexto.Personajes.Where(p => p.Nombre == "Ares").Select(p => p.Id).FirstOrDefault();
                var novaId = contexto.Personajes.Where(p => p.Nombre == "Nova").Select(p => p.Id).FirstOrDefault();

                contexto.ImagenesNivelPersonaje.AddRange(
                    new ImagenNivelPersonaje
                    {
                        PersonajeId = kaiId,
                        NivelNumero = 0,
                        ImagenUrl = "/img/personajes/ing-sistemas/masculino/IngSis_mas_nivel1.png",
                        EsPlaceholder = false
                    },
                    new ImagenNivelPersonaje
                    {
                        PersonajeId = lunaId,
                        NivelNumero = 0,
                        ImagenUrl = "/img/personajes/ing-sistemas/femenino/IngSis_fem_nivel1.png",
                        EsPlaceholder = false
                    },
                    new ImagenNivelPersonaje
                    {
                        PersonajeId = aresId,
                        NivelNumero = 0,
                        ImagenUrl = "/img/personajes/generico/masculino/placeholder.png",
                        EsPlaceholder = true
                    },
                    new ImagenNivelPersonaje
                    {
                        PersonajeId = novaId,
                        NivelNumero = 0,
                        ImagenUrl = "/img/personajes/generico/femenino/placeholder.png",
                        EsPlaceholder = true
                    });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.Logros.Any())
            {
                contexto.Logros.AddRange(
                    new Logro
                    {
                        Nombre = "Primer Paso",
                        Descripcion = "Completa tu primer hÃ¡bito.",
                        IconoUrl = "/img/logros/primer_paso.png",
                        CondicionTipo = "HabitosCompletados",
                        CondicionValor = 1,
                        XpRecompensa = 10,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Semana Perfecta",
                        Descripcion = "MantÃ©n una racha de 7 dÃ­as.",
                        IconoUrl = "/img/logros/semana_perfecta.png",
                        CondicionTipo = "RachaDias",
                        CondicionValor = 7,
                        XpRecompensa = 50,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Mes Imparable",
                        Descripcion = "MantÃ©n una racha de 30 dÃ­as.",
                        IconoUrl = "/img/logros/mes_imparable.png",
                        CondicionTipo = "RachaDias",
                        CondicionValor = 30,
                        XpRecompensa = 200,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Primera MisiÃ³n",
                        Descripcion = "Completa tu primera misiÃ³n.",
                        IconoUrl = "/img/logros/primera_mision.png",
                        CondicionTipo = "MisionesCompletadas",
                        CondicionValor = 1,
                        XpRecompensa = 20,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Productivo",
                        Descripcion = "Completa 10 misiones.",
                        IconoUrl = "/img/logros/productivo.png",
                        CondicionTipo = "MisionesCompletadas",
                        CondicionValor = 10,
                        XpRecompensa = 80,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Maestro del Foco",
                        Descripcion = "Completa 50 sesiones Pomodoro.",
                        IconoUrl = "/img/logros/maestro_foco.png",
                        CondicionTipo = "SesionesPomodoro",
                        CondicionValor = 50,
                        XpRecompensa = 100,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Asistente",
                        Descripcion = "Alcanza el nivel 5.",
                        IconoUrl = "/img/logros/nivel_5.png",
                        CondicionTipo = "NivelAlcanzado",
                        CondicionValor = 5,
                        XpRecompensa = 150,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Profesional",
                        Descripcion = "Alcanza el nivel 10.",
                        IconoUrl = "/img/logros/nivel_10.png",
                        CondicionTipo = "NivelAlcanzado",
                        CondicionValor = 10,
                        XpRecompensa = 300,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Gran Maestro",
                        Descripcion = "Alcanza el nivel 18.",
                        IconoUrl = "/img/logros/nivel_18.png",
                        CondicionTipo = "NivelAlcanzado",
                        CondicionValor = 18,
                        XpRecompensa = 700,
                        EstaActivo = true
                    },
                    new Logro
                    {
                        Nombre = "Leyenda Viviente",
                        Descripcion = "Alcanza el nivel mÃ¡ximo: nivel 20.",
                        IconoUrl = "/img/logros/leyenda.png",
                        CondicionTipo = "NivelAlcanzado",
                        CondicionValor = 20,
                        XpRecompensa = 1000,
                        EstaActivo = true
                    });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.FrasesMotivacionales.Any())
            {
                contexto.FrasesMotivacionales.AddRange(
                    new FraseMotivacional { Frase = "El secreto para avanzar es comenzar.", Autor = "Mark Twain", EstaActiva = true },
                    new FraseMotivacional { Frase = "No cuentes los dÃ­as, haz que los dÃ­as cuenten.", Autor = "Muhammad Ali", EstaActiva = true },
                    new FraseMotivacional { Frase = "SÃ© el cambio que quieres ver en el mundo.", Autor = "Mahatma Gandhi", EstaActiva = true },
                    new FraseMotivacional { Frase = "El Ã©xito es la suma de pequeÃ±os esfuerzos repetidos dÃ­a tras dÃ­a.", Autor = "Robert Collier", EstaActiva = true },
                    new FraseMotivacional { Frase = "No importa lo lento que vayas, siempre y cuando no te detengas.", Autor = "Confucio", EstaActiva = true },
                    new FraseMotivacional { Frase = "La disciplina es el puente entre metas y logros.", Autor = "Jim Rohn", EstaActiva = true },
                    new FraseMotivacional { Frase = "Cree que puedes y ya estarÃ¡s a mitad de camino.", Autor = "Theodore Roosevelt", EstaActiva = true },
                    new FraseMotivacional { Frase = "Cada dÃ­a es una nueva oportunidad para mejorar.", Autor = "AnÃ³nimo", EstaActiva = true },
                    new FraseMotivacional { Frase = "La constancia es la madre del Ã©xito.", Autor = "AnÃ³nimo", EstaActiva = true },
                    new FraseMotivacional { Frase = "Haz de cada dÃ­a tu obra maestra.", Autor = "John Wooden", EstaActiva = true });
                await contexto.SaveChangesAsync();
            }

            if (!contexto.TipsPomodoro.Any())
            {
                contexto.TipsPomodoro.AddRange(
                    new TipPomodoro
                    {
                        Tip = "Pon tu telÃ©fono en modo aviÃ³n durante el tiempo de estudio. Cada notificaciÃ³n interrumpida son 23 minutos de concentraciÃ³n perdida.",
                        EstaActivo = true
                    },
                    new TipPomodoro
                    {
                        Tip = "Prepara todo antes de empezar: agua, apuntes y la tarea especÃ­fica. No busques materiales mientras el temporizador corre.",
                        EstaActivo = true
                    },
                    new TipPomodoro
                    {
                        Tip = "En el descanso, alÃ©jate de la pantalla. Estira las manos, camina un poco o mira por la ventana. Tu cerebro lo necesita.",
                        EstaActivo = true
                    },
                    new TipPomodoro
                    {
                        Tip = "Si una idea te distrae, anÃ³tala rÃ¡pido en un papel y regresa al foco. La anotarÃ¡s despuÃ©s, no la perderÃ¡s.",
                        EstaActivo = true
                    },
                    new TipPomodoro
                    {
                        Tip = "DespuÃ©s de 4 ciclos completos, tÃ³mate 20-30 minutos de descanso largo. Comer algo ligero y caminar ayuda a resetear tu energÃ­a.",
                        EstaActivo = true
                    });
                await contexto.SaveChangesAsync();
            }
        }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"[DatosSemilla] Error: {ex.Message}");
                throw;
            }
        }
    }
}
