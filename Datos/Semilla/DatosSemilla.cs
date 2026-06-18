using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

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
                        new Carrera { Nombre = "Ingeniería de Sistemas", Area = "Ingeniería", Codigo = "ing-sistemas", EstaActiva = true },
                        new Carrera { Nombre = "Ingeniería Civil", Area = "Ingeniería", Codigo = "ing-civil", EstaActiva = true },
                        new Carrera { Nombre = "Ingeniería Industrial", Area = "Ingeniería", Codigo = "ing-industrial", EstaActiva = true },
                        new Carrera { Nombre = "Administración de Empresas", Area = "Administración", Codigo = "administracion", EstaActiva = true },
                        new Carrera { Nombre = "Contabilidad", Area = "Negocios", Codigo = "contabilidad", EstaActiva = true },
                        new Carrera { Nombre = "Derecho", Area = "Legal", Codigo = "derecho", EstaActiva = true },
                        new Carrera { Nombre = "Medicina Humana", Area = "Salud", Codigo = "medicina", EstaActiva = true },
                        new Carrera { Nombre = "Enfermería", Area = "Salud", Codigo = "enfermeria", EstaActiva = true },
                        new Carrera { Nombre = "Psicología", Area = "Salud", Codigo = "psicologia", EstaActiva = true },
                        new Carrera { Nombre = "Educación", Area = "Educación", Codigo = "educacion", EstaActiva = true },
                        new Carrera { Nombre = "Arquitectura", Area = "Arquitectura", Codigo = "arquitectura", EstaActiva = true },
                        new Carrera { Nombre = "Comunicaciones", Area = "Comunicaciones", Codigo = "comunicaciones", EstaActiva = true });
                    await contexto.SaveChangesAsync();
                }

                if (!contexto.Niveles.Any())
                {
                    contexto.Niveles.AddRange(
                        new Nivel { Numero = 0, Titulo = "Novato", XpRequerido = 0, Descripcion = "Aún no llevas el título, pero el camino comienza hoy. Bienvenido." },
                        new Nivel { Numero = 1, Titulo = "Curioso", XpRequerido = 100, Descripcion = "La curiosidad es el primer paso de todo gran profesional." },
                        new Nivel { Numero = 2, Titulo = "Aprendiz", XpRequerido = 250, Descripcion = "Estás absorbiendo conocimiento. Cada hábito cuenta." },
                        new Nivel { Numero = 3, Titulo = "Estudiante Comprometido", XpRequerido = 450, Descripcion = "Tu constancia ya te diferencia de la mayoría." },
                        new Nivel { Numero = 4, Titulo = "Practicante", XpRequerido = 700, Descripcion = "Empiezas a aplicar lo que aprendes. El mundo te espera." },
                        new Nivel { Numero = 5, Titulo = "Asistente", XpRequerido = 1000, Descripcion = "Ya formas parte del campo profesional. Sigue creciendo." },
                        new Nivel { Numero = 6, Titulo = "Junior", XpRequerido = 1350, Descripcion = "Tienes base sólida. Los desafíos reales ya no te asustan." },
                        new Nivel { Numero = 7, Titulo = "Semi-Senior", XpRequerido = 1750, Descripcion = "Tu experiencia empieza a hablar por ti." },
                        new Nivel { Numero = 8, Titulo = "Profesional", XpRequerido = 2200, Descripcion = "Dominas los fundamentos y resuelves problemas con soltura." },
                        new Nivel { Numero = 9, Titulo = "Especialista", XpRequerido = 2700, Descripcion = "Tienes un área en la que pocos te superan." },
                        new Nivel { Numero = 10, Titulo = "Senior", XpRequerido = 3250, Descripcion = "Mitad del camino. Tu criterio vale más que el de muchos." },
                        new Nivel { Numero = 11, Titulo = "Senior Avanzado", XpRequerido = 3850, Descripcion = "Lideras con el ejemplo. Otros aprenden de ti." },
                        new Nivel { Numero = 12, Titulo = "Experto", XpRequerido = 4500, Descripcion = "Tu nivel de profundidad es notable. Eres referente." },
                        new Nivel { Numero = 13, Titulo = "Consultor", XpRequerido = 5200, Descripcion = "Te buscan cuando el problema es difícil. Eso es poder." },
                        new Nivel { Numero = 14, Titulo = "Líder", XpRequerido = 5950, Descripcion = "No solo resuelves: diriges, inspiras y construyes equipos." },
                        new Nivel { Numero = 15, Titulo = "Maestro", XpRequerido = 6750, Descripcion = "Tu conocimiento trasciende lo técnico. Ya es sabiduría." },
                        new Nivel { Numero = 16, Titulo = "Arquitecto", XpRequerido = 7600, Descripcion = "Diseñas sistemas, estrategias y futuros completos." },
                        new Nivel { Numero = 17, Titulo = "Eminencia", XpRequerido = 8500, Descripcion = "Tu nombre es sinónimo de excelencia en tu campo." },
                        new Nivel { Numero = 18, Titulo = "Gran Maestro", XpRequerido = 9450, Descripcion = "Has forjado a otros profesionales con tu guía." },
                        new Nivel { Numero = 19, Titulo = "Leyenda en Ascenso", XpRequerido = 10450, Descripcion = "El umbral del máximo poder está justo frente a ti." },
                        new Nivel { Numero = 20, Titulo = "Leyenda Viviente", XpRequerido = 11500, Descripcion = "Has llegado a la cima. Eres la definición de tu profesión." });
                    await contexto.SaveChangesAsync();
                }

                if (!contexto.Categorias.Any())
                {
                    contexto.Categorias.AddRange(
                        new Categoria { Nombre = "Salud y Bienestar", Icono = "bi-heart-pulse", Tipo = "Ambos", EstaActiva = true },
                        new Categoria { Nombre = "Estudio", Icono = "bi-book", Tipo = "Ambos", EstaActiva = true },
                        new Categoria { Nombre = "Ejercicio", Icono = "bi-activity", Tipo = "Habito", EstaActiva = true },
                        new Categoria { Nombre = "Sueño", Icono = "bi-moon-stars", Tipo = "Habito", EstaActiva = true },
                        new Categoria { Nombre = "Hidratación", Icono = "bi-droplet", Tipo = "Habito", EstaActiva = true },
                        new Categoria { Nombre = "Nutrición", Icono = "bi-egg-fried", Tipo = "Habito", EstaActiva = true },
                        new Categoria { Nombre = "Meditación", Icono = "bi-peace", Tipo = "Habito", EstaActiva = true },
                        new Categoria { Nombre = "Tarea Académica", Icono = "bi-file-earmark-text", Tipo = "Mision", EstaActiva = true },
                        new Categoria { Nombre = "Proyecto", Icono = "bi-kanban", Tipo = "Mision", EstaActiva = true },
                        new Categoria { Nombre = "Lectura", Icono = "bi-book-half", Tipo = "Ambos", EstaActiva = true },
                        new Categoria { Nombre = "Hábito Personal", Icono = "bi-star", Tipo = "Habito", EstaActiva = true });
                    await contexto.SaveChangesAsync();
                }

                if (!contexto.Temas.Any())
                {
                    contexto.Temas.AddRange(
                        new Tema { Nombre = "Noche Épica", Modo = "Oscuro", ArchivoCss = "tema-noche-epica.css", EsPremium = false, Precio = 0, EstaActivo = true },
                        new Tema { Nombre = "Sakura", Modo = "Claro", ArchivoCss = "tema-sakura.css", EsPremium = false, Precio = 0, EstaActivo = true });
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

                    var imagenes = new List<ImagenNivelPersonaje>
                    {
                        new() { PersonajeId = kaiId, NivelNumero = 0, ImagenUrl = "/img/personajes/ing-sistemas/masculino/IngSis_mas_nivel1.png", EsPlaceholder = false },
                        new() { PersonajeId = lunaId, NivelNumero = 0, ImagenUrl = "/img/personajes/ing-sistemas/femenino/IngSis_fem_nivel1.png", EsPlaceholder = false },
                        new() { PersonajeId = aresId, NivelNumero = 0, ImagenUrl = "/img/personajes/generico/masculino/placeholder.png", EsPlaceholder = true },
                        new() { PersonajeId = novaId, NivelNumero = 0, ImagenUrl = "/img/personajes/generico/femenino/placeholder.png", EsPlaceholder = true }
                    };

                    var todasCarreras = await contexto.Carreras.Where(c => c.EstaActiva).ToListAsync();
                    var generos = new[] { "masculino", "femenino" };

                    foreach (var carrera in todasCarreras)
                    {
                        if (carrera.Codigo == "ing-sistemas") continue;

                        foreach (var genero in generos)
                        {
                            var carpeta = carrera.Codigo;
                            var prefijo = CarreraPrefijoImagen(carrera.Codigo);
                            var gen = genero.Substring(0, 3);
                            var nombreArchivo = $"{prefijo}_{gen}_nivel1.png";

                            var webRoot = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot");
                            var rutaArchivo = Path.Combine(webRoot, "img", "personajes", carpeta, genero, nombreArchivo);
                            var existeArchivo = File.Exists(rutaArchivo);

                            imagenes.Add(new ImagenNivelPersonaje
                            {
                                PersonajeId = genero == "masculino" ? aresId : novaId,
                                NivelNumero = 0,
                                ImagenUrl = $"/img/personajes/{carpeta}/{genero}/{nombreArchivo}",
                                EsPlaceholder = !existeArchivo
                            });
                        }
                    }

                    contexto.ImagenesNivelPersonaje.AddRange(imagenes);
                    await contexto.SaveChangesAsync();
                }

                if (!contexto.Logros.Any())
                {
                    contexto.Logros.AddRange(
                        new Logro { Nombre = "Primer Paso", Descripcion = "Completa tu primer hábito.", IconoUrl = "/img/logros/primer_paso.png", CondicionTipo = "HabitosCompletados", CondicionValor = 1, XpRecompensa = 10, EstaActivo = true },
                        new Logro { Nombre = "Semana Perfecta", Descripcion = "Mantén una racha de 7 días.", IconoUrl = "/img/logros/semana_perfecta.png", CondicionTipo = "RachaDias", CondicionValor = 7, XpRecompensa = 50, EstaActivo = true },
                        new Logro { Nombre = "Mes Imparable", Descripcion = "Mantén una racha de 30 días.", IconoUrl = "/img/logros/mes_imparable.png", CondicionTipo = "RachaDias", CondicionValor = 30, XpRecompensa = 200, EstaActivo = true },
                        new Logro { Nombre = "Primera Misión", Descripcion = "Completa tu primera misión.", IconoUrl = "/img/logros/primera_mision.png", CondicionTipo = "MisionesCompletadas", CondicionValor = 1, XpRecompensa = 20, EstaActivo = true },
                        new Logro { Nombre = "Productivo", Descripcion = "Completa 10 misiones.", IconoUrl = "/img/logros/productivo.png", CondicionTipo = "MisionesCompletadas", CondicionValor = 10, XpRecompensa = 80, EstaActivo = true },
                        new Logro { Nombre = "Maestro del Foco", Descripcion = "Completa 50 sesiones Pomodoro.", IconoUrl = "/img/logros/maestro_foco.png", CondicionTipo = "SesionesPomodoro", CondicionValor = 50, XpRecompensa = 100, EstaActivo = true },
                        new Logro { Nombre = "Asistente", Descripcion = "Alcanza el nivel 5.", IconoUrl = "/img/logros/nivel_5.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 5, XpRecompensa = 150, EstaActivo = true },
                        new Logro { Nombre = "Profesional", Descripcion = "Alcanza el nivel 10.", IconoUrl = "/img/logros/nivel_10.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 10, XpRecompensa = 300, EstaActivo = true },
                        new Logro { Nombre = "Gran Maestro", Descripcion = "Alcanza el nivel 18.", IconoUrl = "/img/logros/nivel_18.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 18, XpRecompensa = 700, EstaActivo = true },
                        new Logro { Nombre = "Leyenda Viviente", Descripcion = "Alcanza el nivel máximo: nivel 20.", IconoUrl = "/img/logros/leyenda.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 20, XpRecompensa = 1000, EstaActivo = true },
                        // Logros ODS-3 — Bienestar
                        new Logro { Nombre = "Ánimo Estable", Descripcion = "Mantén 7 días consecutivos sin registrar ánimo negativo.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "RachaAnimoPositivo", CondicionValor = 7, XpRecompensa = 30, EstaActivo = true },
                        new Logro { Nombre = "Autoconsciente", Descripcion = "Registra tu estado de ánimo 30 veces.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "TotalRegistrosAnimo", CondicionValor = 30, XpRecompensa = 50, EstaActivo = true },
                        new Logro { Nombre = "Alerta Superada", Descripcion = "Sigue una recomendación de alerta de bienestar.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "AlertasAtendidas", CondicionValor = 1, XpRecompensa = 20, EstaActivo = true },
                        new Logro { Nombre = "Bienestar Constante", Descripcion = "Registra tu estado de ánimo por 14 días consecutivos.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "RachaRegistroAnimo", CondicionValor = 14, XpRecompensa = 80, EstaActivo = true },
                        new Logro { Nombre = "Maestro del Equilibrio", Descripcion = "Completa 5 pausas activas recomendadas.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "PausasActivasCompletadas", CondicionValor = 5, XpRecompensa = 40, EstaActivo = true });
                    await contexto.SaveChangesAsync();
                }

                if (!contexto.FrasesMotivacionales.Any())
                {
                    contexto.FrasesMotivacionales.AddRange(
                        new FraseMotivacional { Frase = "El secreto para avanzar es comenzar.", Autor = "Mark Twain", Categoria = "Motivación", EstaActiva = true },
                        new FraseMotivacional { Frase = "No cuentes los días, haz que los días cuenten.", Autor = "Muhammad Ali", Categoria = "Motivación", EstaActiva = true },
                        new FraseMotivacional { Frase = "Sé el cambio que quieres ver en el mundo.", Autor = "Mahatma Gandhi", Categoria = "Reflexión", EstaActiva = true },
                        new FraseMotivacional { Frase = "El éxito es la suma de pequeños esfuerzos repetidos día tras día.", Autor = "Robert Collier", Categoria = "Constancia", EstaActiva = true },
                        new FraseMotivacional { Frase = "No importa lo lento que vayas, siempre y cuando no te detengas.", Autor = "Confucio", Categoria = "Persistencia", EstaActiva = true },
                        new FraseMotivacional { Frase = "La disciplina es el puente entre metas y logros.", Autor = "Jim Rohn", Categoria = "Disciplina", EstaActiva = true },
                        new FraseMotivacional { Frase = "Cree que puedes y ya estarás a mitad de camino.", Autor = "Theodore Roosevelt", Categoria = "Confianza", EstaActiva = true },
                        new FraseMotivacional { Frase = "Cada día es una nueva oportunidad para mejorar.", Autor = "Anónimo", Categoria = "Motivación", EstaActiva = true },
                        new FraseMotivacional { Frase = "La constancia es la madre del éxito.", Autor = "Anónimo", Categoria = "Constancia", EstaActiva = true },
                        new FraseMotivacional { Frase = "Haz de cada día tu obra maestra.", Autor = "John Wooden", Categoria = "Motivación", EstaActiva = true },
                        // Frases adicionales de bienestar universitario (B-INC-04)
                        new FraseMotivacional { Frase = "Tu salud mental es tan importante como tus notas. Priorízate.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                        new FraseMotivacional { Frase = "Descansar no es perder el tiempo. Es invertir en tu rendimiento.", Autor = "Anónimo", Categoria = "Descanso", EstaActiva = true },
                        new FraseMotivacional { Frase = "Está bien no estar bien. Lo importante es buscar ayuda cuando la necesites.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                        new FraseMotivacional { Frase = "Duerme bien, come bien, muévete. Tu cerebro te lo agradecerá en los exámenes.", Autor = "Anónimo", Categoria = "Bienestar", EstaActiva = true },
                        new FraseMotivacional { Frase = "Una mente descansada es una mente creativa. No olvides tus pausas.", Autor = "Anónimo", Categoria = "Descanso", EstaActiva = true },
                        new FraseMotivacional { Frase = "La universidad no es una carrera de velocidad, es una maratón. Cuida tu paso.", Autor = "Anónimo", Categoria = "Reflexión", EstaActiva = true },
                        new FraseMotivacional { Frase = "Hidrátate, estira las piernas y respira. Tu cuerpo es tu herramienta más valiosa.", Autor = "Anónimo", Categoria = "Bienestar", EstaActiva = true },
                        new FraseMotivacional { Frase = "Compararte con otros es robarle la alegría a tu propio progreso.", Autor = "Theodore Roosevelt", Categoria = "Confianza", EstaActiva = true },
                        new FraseMotivacional { Frase = "El descanso no es un lujo, es una necesidad biológica.", Autor = "Matthew Walker", Categoria = "Descanso", EstaActiva = true },
                        new FraseMotivacional { Frase = "Hablar de lo que sientes no te hace débil. Te hace humano.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                        new FraseMotivacional { Frase = "No necesitas hacer más. A veces necesitas hacer menos, pero mejor.", Autor = "Greg McKeown", Categoria = "Reflexión", EstaActiva = true },
                        new FraseMotivacional { Frase = "Tomar agua, dormir bien y moverte: tres hábitos que cambian tu vida.", Autor = "Anónimo", Categoria = "Bienestar", EstaActiva = true },
                        new FraseMotivacional { Frase = "Un paso a la vez. No tienes que tener todo resuelto hoy.", Autor = "Anónimo", Categoria = "Motivación", EstaActiva = true },
                        new FraseMotivacional { Frase = "El estrés es temporal. Tu capacidad para superarlo también.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                        new FraseMotivacional { Frase = "Respira. Todo lo que estás atravesando te está preparando para algo mejor.", Autor = "Anónimo", Categoria = "Reflexión", EstaActiva = true });
                    await contexto.SaveChangesAsync();
                }

                if (!contexto.TipsPomodoro.Any())
                {
                    contexto.TipsPomodoro.AddRange(
                        new TipPomodoro { Tip = "Pon tu teléfono en modo avión durante el tiempo de estudio. Cada notificación interrumpida son 23 minutos de concentración perdida.", EstaActivo = true },
                        new TipPomodoro { Tip = "Prepara todo antes de empezar: agua, apuntes y la tarea específica. No busques materiales mientras el temporizador corre.", EstaActivo = true },
                        new TipPomodoro { Tip = "En el descanso, aléjate de la pantalla. Estira las manos, camina un poco o mira por la ventana. Tu cerebro lo necesita.", EstaActivo = true },
                        new TipPomodoro { Tip = "Si una idea te distrae, anótala rápido en un papel y regresa al foco. La anotarás después, no la perderás.", EstaActivo = true },
                        new TipPomodoro { Tip = "Después de 4 ciclos completos, tómate 20-30 minutos de descanso largo. Comer algo ligero y caminar ayuda a resetear tu energía.", EstaActivo = true });
                    await contexto.SaveChangesAsync();
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"[DatosSemilla] Error: {ex.Message}");
                throw;
            }
        }

        private static string CarreraPrefijoImagen(string codigo) => codigo switch
        {
            "ing-civil" => "IngCiv",
            "ing-industrial" => "IngInd",
            "administracion" => "Admin",
            "contabilidad" => "Contab",
            "derecho" => "Derec",
            "medicina" => "Medici",
            "enfermeria" => "Enferm",
            "psicologia" => "Psicol",
            "educacion" => "Educac",
            "arquitectura" => "Arquit",
            "comunicaciones" => "Comunic",
            _ => codigo.Length > 6 ? codigo[..6] : codigo
        };
    }
}
