function cambiarTema(nombreArchivoCss) {
    var hojaTema = document.getElementById("hoja-tema");

    if (!hojaTema) {
        return;
    }

    hojaTema.setAttribute("href", "/css/temas/" + nombreArchivoCss);

    var claveTema = nombreArchivoCss.includes("sakura") ? "claro" : "oscuro";
    localStorage.setItem("epycus_theme", claveTema);
}

document.addEventListener("DOMContentLoaded", function () {
    var temaGuardado = localStorage.getItem("epycus_theme");
    var hojaTema = document.getElementById("hoja-tema");

    if (!hojaTema) {
        return;
    }

    if (temaGuardado === "claro") {
        hojaTema.setAttribute("href", "/css/temas/tema-sakura.css");
    }
});
