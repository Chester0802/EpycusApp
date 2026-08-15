<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import axios from 'axios';
import {
    Save,
    Camera,
    CalendarPlus,
    X,
    Bold,
    Heading1,
    Heading2,
    Loader2,
    NotebookText,
    ImagePlus,
    FileJson,
    FileText,
    Check,
    Type,
    RotateCcw,
} from '@lucide/vue';

const props = defineProps({
    show:   { type: Boolean, default: false },
    course: { type: Object,  default: null },
});

const emit = defineEmits(['close']);

// ── Estado ──────────────────────────────────────────────────────────────────
const loading        = ref(false);
const saving         = ref(false);
const saveSuccess    = ref(false);
const noteId         = ref(null);
const entries        = ref([]);
const images         = ref([]);
const activeEntryId  = ref(null);
const cameraStream   = ref(null);
const showCamera     = ref(false);
const videoEl        = ref(null);
const canvasEl       = ref(null);
const uploadingImage = ref(false);
const cameraError    = ref('');

// Referencia directa al div del editor
const editorEl       = ref(null);

const DAY_NAMES = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

function formatTime12h(timeStr) {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    const h = parseInt(parts[0], 10);
    const m = parts[1] ? parts[1].slice(0, 2) : '00';
    if (isNaN(h)) return timeStr;
    const period = h >= 12 ? 'p.m.' : 'a.m.';
    const h12 = h % 12 || 12;
    return `${String(h12).padStart(2, '0')}:${m} ${period}`;
}

// ── Cargar apunte al abrir ─────────────────────────────────────────────────
watch(() => props.show, async (val) => {
    if (val && props.course) {
        await loadNote();
    }
    if (!val) {
        stopCamera();
        cameraError.value = '';
    }
});

// Cuando cambia la entrada activa, cargar su HTML en el editor
watch(activeEntryId, async () => {
    await nextTick();
    loadEditorContent();
});

async function loadNote() {
    loading.value = true;
    try {
        const res = await axios.get(route('calendar.notes.show', { courseId: props.course.id }));
        const data = res.data;
        if (data.note) {
            noteId.value  = data.note.id;
            entries.value = data.note.content?.entries ?? [];
            images.value  = data.note.images ?? [];
        } else {
            noteId.value  = null;
            entries.value = [];
            images.value  = [];
        }
        if (entries.value.length > 0) {
            activeEntryId.value = entries.value[entries.value.length - 1].id;
        } else {
            activeEntryId.value = null;
        }
    } catch (e) {
        console.error('Error cargando apunte:', e);
    } finally {
        loading.value = false;
        await nextTick();
        loadEditorContent();
    }
}

const currentEntry = computed(() =>
    entries.value.find(e => e.id === activeEntryId.value) ?? null,
);

// Cargar el HTML de la entrada activa directamente en el DOM del editor
function loadEditorContent() {
    if (!editorEl.value) return;
    const html = currentEntry.value?.blocks?.[0]?.html ?? '';
    if (editorEl.value.innerHTML !== html) {
        editorEl.value.innerHTML = html;
    }
}

// ── Agregar nueva entrada fechada ──────────────────────────────────────────
async function addEntry() {
    syncBlocks();
    const id  = crypto.randomUUID();
    const now = new Date().toISOString();
    entries.value.push({ id, recorded_at: now, blocks: [] });
    activeEntryId.value = id;
    await nextTick();
    if (editorEl.value) {
        editorEl.value.innerHTML = '';
        editorEl.value.focus();
    }
}

// ── Sincronizar DOM del editor → datos ────────────────────────────────────
function syncBlocks() {
    if (!currentEntry.value || !editorEl.value) return;
    currentEntry.value.blocks = [{ type: 'html', html: editorEl.value.innerHTML }];
}

// ── Guardar apunte ─────────────────────────────────────────────────────────
async function saveNote() {
    if (!props.course) return;
    syncBlocks();
    saving.value = true;
    try {
        const content = { version: '1.0', entries: entries.value };
        const res = await axios.post(route('calendar.notes.upsert', { courseId: props.course.id }), {
            content,
        });
        const data = res.data;
        if (data.note) {
            noteId.value      = data.note.id;
            saveSuccess.value = true;
            setTimeout(() => (saveSuccess.value = false), 2000);
        } else {
            console.error('Error del servidor al guardar:', data);
            alert('Error al guardar el apunte.');
        }
    } catch (e) {
        console.error('Error al guardar:', e);
        const msg = e.response?.data?.message || e.response?.data?.error || e.message;
        alert('Error al guardar el apunte: ' + msg);
    } finally {
        saving.value = false;
    }
}

// ── Subir imagen ──────────────────────────────────────────────────────────
async function uploadImage(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!noteId.value) {
        if (entries.value.length === 0) {
            await addEntry();
            await nextTick();
        }
        await saveNote();
        if (!noteId.value) {
            alert('No se pudo guardar el apunte inicial para subir la imagen.');
            event.target.value = '';
            return;
        }
    }

    uploadingImage.value = true;
    const formData = new FormData();
    formData.append('note_id', noteId.value);
    formData.append('image', file);

    try {
        const res = await axios.post(route('note-images.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        const data = res.data;
        if (data.image) {
            images.value.push(data.image);
            insertImageInEditor(data.image.url, data.image.original_name);
        } else if (data.error) {
            alert('Error: ' + data.error);
        }
    } catch (e) {
        const msg = e.response?.data?.message || e.response?.data?.error || e.message;
        alert('Error subiendo imagen: ' + msg);
    } finally {
        uploadingImage.value = false;
        event.target.value   = '';
    }
}

function insertImageInEditor(url, alt) {
    if (!editorEl.value) return;
    editorEl.value.focus();
    document.execCommand('insertHTML', false, `<img src="${url}" alt="${alt}" style="max-width:100%;border-radius:8px;margin:8px 0;display:block;" />`);
    syncBlocks();
}

// ── Cámara ─────────────────────────────────────────────────────────────────
async function openCamera() {
    cameraError.value = '';

    if (!noteId.value) {
        if (entries.value.length === 0) await addEntry();
        await nextTick();
        await saveNote();
        if (!noteId.value) {
            alert('No se pudo guardar el apunte antes de abrir la cámara.');
            return;
        }
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        cameraError.value = 'Tu navegador no soporta acceso a la cámara o no está en un entorno seguro (HTTPS/localhost).';
        return;
    }

    try {
        // Solicitud de cámara flexible compatible con móviles y PC webcams
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        });
        cameraStream.value = stream;
        showCamera.value   = true;
        await nextTick();
        if (videoEl.value) {
            videoEl.value.srcObject = stream;
            await videoEl.value.play();
        }
    } catch (err) {
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            cameraError.value = 'Permiso de cámara denegado. Haz clic en el ícono de candado o configuración en la barra de direcciones del navegador y permite la cámara.';
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            cameraError.value = 'No se encontró ninguna cámara conectada en este dispositivo.';
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
            cameraError.value = 'La cámara está siendo usada por otra aplicación. Ciérrala e intenta de nuevo.';
        } else {
            cameraError.value = 'Error al acceder a la cámara: ' + err.message;
        }
    }
}

function stopCamera() {
    cameraStream.value?.getTracks().forEach(t => t.stop());
    cameraStream.value = null;
    showCamera.value   = false;
}

async function capturePhoto() {
    if (!videoEl.value || !canvasEl.value) return;
    const video  = videoEl.value;
    const canvas = canvasEl.value;
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);

    stopCamera();
    uploadingImage.value = true;

    try {
        const res = await axios.post(route('note-images.capture'), {
            note_id: noteId.value,
            image_data: dataUrl,
        });
        const data = res.data;
        if (data.image) {
            images.value.push(data.image);
            insertImageInEditor(data.image.url, data.image.original_name);
        } else if (data.error) {
            alert('Error: ' + data.error);
        }
    } catch (e) {
        const msg = e.response?.data?.message || e.response?.data?.error || e.message;
        alert('Error guardando foto: ' + msg);
    } finally {
        uploadingImage.value = false;
    }
}

// ── Exportar JSON directo (Descarga inmediata sin previsualización) ────────
function exportAndDownloadJson() {
    syncBlocks();
    const cleanCourseName = props.course?.name
        ? props.course.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '_')
        : 'curso';

    const payload = {
        export_version: '1.0',
        exported_at:    new Date().toISOString(),
        metadata: {
            course_name:   props.course?.name ?? '',
            sessions:      (props.course?.sessions ?? []).map(s => ({
                day:       DAY_NAMES[s.day_of_week] ?? s.day_of_week,
                start:     s.start_time,
                end:       s.end_time,
                classroom: s.classroom ?? null,
            })),
            total_entries: entries.value.length,
        },
        entries: entries.value,
    };

    const jsonString = JSON.stringify(payload, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `apunte_${cleanCourseName}_${new Date().toISOString().slice(0, 10)}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ── Exportar PDF (Imprimible/Descargable) ──────────────────────────────────
function exportPdf() {
    syncBlocks();
    const cleanCourseName = props.course?.name ?? 'Curso';
    const sessionsList = (props.course?.sessions ?? []).map(s =>
        `${DAY_NAMES[s.day_of_week]} ${formatTime12h(s.start_time)} – ${formatTime12h(s.end_time)}${s.classroom ? ' [' + s.classroom + ']' : ''}`
    ).join(' | ');

    let entriesHtml = '';
    for (const entry of entries.value) {
        const dateStr = formatDate(entry.recorded_at);
        const html = entry.blocks?.[0]?.html ?? '<p><em>Sin contenido</em></p>';
        entriesHtml += `
            <div class="pdf-entry">
                <div class="pdf-entry-date">📅 ${dateStr}</div>
                <div class="pdf-entry-body">${html}</div>
            </div>
        `;
    }

    const printHtml = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Apunte - ${cleanCourseName}</title>
            <style>
                @page { margin: 15mm; size: A4; }
                body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; background: #fff; margin: 0; padding: 20px; line-height: 1.6; }
                .pdf-header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; }
                .pdf-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0; }
                .pdf-subtitle { font-size: 13px; color: #64748b; margin: 0; }
                .pdf-entry { margin-bottom: 30px; page-break-inside: avoid; }
                .pdf-entry-date { font-size: 13px; font-weight: 600; color: #9333ea; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; margin-bottom: 12px; }
                .pdf-entry-body { font-size: 14px; color: #334155; }
                .pdf-entry-body h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin: 16px 0 8px; }
                .pdf-entry-body h2 { font-size: 16px; font-weight: 600; color: #1e293b; margin: 12px 0 6px; }
                .pdf-entry-body strong { font-weight: 700; }
                .pdf-entry-body img { max-width: 100%; height: auto; border-radius: 6px; margin: 10px 0; display: block; border: 1px solid #e2e8f0; }
                .pdf-footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 12px; text-align: center; font-size: 11px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class="pdf-header">
                <h1 class="pdf-title">📖 Apunte: ${cleanCourseName}</h1>
                <p class="pdf-subtitle"><strong>Horarios:</strong> ${sessionsList || 'Sin horario especificado'}</p>
            </div>
            ${entriesHtml || '<p>No hay registros guardados en este apunte.</p>'}
            <div class="pdf-footer">
                Documento exportado desde Epycus · ${new Date().toLocaleDateString('es')}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                };
            <\/script>
        </body>
        </html>
    `;

    const printWindow = window.open('', '_blank');
    if (printWindow) {
        printWindow.document.write(printHtml);
        printWindow.document.close();
    } else {
        alert('Por favor habilita las ventanas emergentes (popups) para exportar el PDF.');
    }
}

// ── Formato inline ─────────────────────────────────────────────────────────
function formatHeading(level) {
    editorEl.value?.focus();
    document.execCommand('formatBlock', false, `h${level}`);
    syncBlocks();
}
function formatBold() {
    editorEl.value?.focus();
    document.execCommand('bold', false, null);
    syncBlocks();
}
function formatColor(color) {
    editorEl.value?.focus();
    document.execCommand('foreColor', false, color);
    syncBlocks();
}
function resetFormat() {
    editorEl.value?.focus();
    document.execCommand('removeFormat', false, null);
    // Limpiar color residual en la selección para que herede el color del tema actual (claro/oscuro)
    const selection = window.getSelection();
    if (selection && selection.rangeCount > 0) {
        const node = selection.anchorNode;
        const parent = node?.nodeType === Node.ELEMENT_NODE ? node : node?.parentElement;
        if (parent && parent.closest('.note-editor')) {
            if (parent.tagName === 'FONT') {
                parent.removeAttribute('color');
            }
            if (parent.style && parent.style.color) {
                parent.style.color = '';
            }
        }
    }
    syncBlocks();
}

function formatDate(isoString) {
    try {
        return new Date(isoString).toLocaleDateString('es', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    } catch {
        return isoString;
    }
}

function close() {
    stopCamera();
    cameraError.value = '';
    emit('close');
}

onBeforeUnmount(() => stopCamera());
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200 ease-out"
            leave-to-class="opacity-0"
        >
            <div v-if="show && course" class="note-modal-backdrop" @click.self="close">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-x-full"
                    leave-active-class="transition-all duration-300 ease-out"
                    leave-to-class="opacity-0 translate-x-full"
                >
                    <div v-if="show" class="note-modal-panel">

                        <!-- Header -->
                        <div class="note-modal-header">
                            <div class="note-header-left">
                                <div class="note-course-dot" :class="`color-dot-${course.color}`"></div>
                                <div>
                                    <h2 class="note-course-title">
                                        <NotebookText :size="17" class="mr-1.5 opacity-60" />
                                        {{ course.name }}
                                    </h2>
                                    <p class="note-course-sessions">
                                        <span v-for="s in course.sessions" :key="s.id" class="note-session-badge">
                                            {{ DAY_NAMES[s.day_of_week] }} {{ formatTime12h(s.start_time) }} – {{ formatTime12h(s.end_time) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="note-header-actions">
                                <button
                                    type="button"
                                    class="note-btn note-btn-secondary"
                                    title="Exportar apunte en formato PDF / Imprimir"
                                    @click="exportPdf"
                                >
                                    <FileText :size="15" /> Exportar PDF
                                </button>
                                <button
                                    type="button"
                                    class="note-btn note-btn-secondary"
                                    title="Descargar apunte en formato JSON para IA"
                                    @click="exportAndDownloadJson"
                                >
                                    <FileJson :size="15" /> Exportar JSON
                                </button>
                                <button type="button" class="note-btn note-btn-primary" :disabled="saving" @click="saveNote">
                                    <Loader2 v-if="saving"      :size="15" class="animate-spin" />
                                    <Check   v-else-if="saveSuccess" :size="15" />
                                    <Save    v-else               :size="15" />
                                    {{ saving ? 'Guardando…' : saveSuccess ? 'Guardado' : 'Guardar' }}
                                </button>
                                <button type="button" class="note-close-btn" @click="close">
                                    <X :size="18" />
                                </button>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div v-if="loading" class="note-loading">
                            <Loader2 :size="22" class="animate-spin" /> Cargando apunte…
                        </div>

                        <template v-else>
                            <!-- Toolbar -->
                            <div class="note-toolbar">
                                <button type="button" class="toolbar-btn" title="Título H1" @click="formatHeading(1)">
                                    <Heading1 :size="16" />
                                </button>
                                <button type="button" class="toolbar-btn" title="Subtítulo H2" @click="formatHeading(2)">
                                    <Heading2 :size="16" />
                                </button>
                                <div class="toolbar-sep"></div>
                                <button type="button" class="toolbar-btn" title="Negrita" @click="formatBold">
                                    <Bold :size="16" />
                                </button>
                                <div class="toolbar-sep"></div>
                                <button type="button" class="toolbar-btn color-red" title="Texto rojo" @click="formatColor('#ef4444')">
                                    <Type :size="16" />
                                </button>
                                <button type="button" class="toolbar-btn color-blue" title="Texto azul" @click="formatColor('#3b82f6')">
                                    <Type :size="16" />
                                </button>
                                <button type="button" class="toolbar-btn" title="Restablecer color normal y formato" @click="resetFormat">
                                    <RotateCcw :size="15" />
                                </button>
                                <div class="toolbar-sep"></div>
                                <label
                                    class="toolbar-btn"
                                    :class="{ 'opacity-40 pointer-events-none': uploadingImage }"
                                    title="Insertar imagen desde archivo"
                                >
                                    <ImagePlus :size="16" />
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/avif"
                                        class="hidden"
                                        :disabled="uploadingImage"
                                        @change="uploadImage"
                                    />
                                </label>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    :class="{ 'opacity-40 pointer-events-none': uploadingImage }"
                                    title="Tomar foto con cámara"
                                    @click="openCamera"
                                >
                                    <Camera :size="16" />
                                </button>
                                <Loader2 v-if="uploadingImage" :size="15" class="animate-spin text-content-muted" />
                                <div class="toolbar-sep"></div>
                                <button type="button" class="toolbar-btn-entry" @click="addEntry">
                                    <CalendarPlus :size="15" /> Nuevo registro
                                </button>
                            </div>

                            <!-- Error de cámara -->
                            <div v-if="cameraError" class="camera-error-bar">
                                <X :size="14" class="shrink-0" />
                                <span>{{ cameraError }}</span>
                                <button type="button" class="ml-auto" @click="cameraError = ''">
                                    <X :size="13" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="note-body">
                                <!-- Sidebar -->
                                <div v-if="entries.length > 1" class="note-entries-sidebar">
                                    <p class="sidebar-title">Registros</p>
                                    <button
                                        v-for="entry in [...entries].reverse()"
                                        :key="entry.id"
                                        type="button"
                                        class="sidebar-entry-btn"
                                        :class="{ active: activeEntryId === entry.id }"
                                        @click="activeEntryId = entry.id"
                                    >
                                        {{ formatDate(entry.recorded_at) }}
                                    </button>
                                </div>

                                <!-- Editor -->
                                <div class="note-editor-area">
                                    <!-- Vacío -->
                                    <div v-if="entries.length === 0" class="note-empty">
                                        <NotebookText :size="48" class="note-empty-icon" />
                                        <p>No hay apuntes aún.</p>
                                        <button type="button" class="note-btn note-btn-primary mt-4" @click="addEntry">
                                            <CalendarPlus :size="15" /> Agregar primer registro
                                        </button>
                                    </div>

                                    <!-- Entrada activa -->
                                    <div v-if="currentEntry" class="note-entry-container">
                                        <div class="note-entry-date">
                                            <CalendarPlus :size="13" class="mr-1 opacity-60 shrink-0" />
                                            {{ formatDate(currentEntry.recorded_at) }}
                                        </div>
                                        <div
                                            ref="editorEl"
                                            class="note-editor"
                                            contenteditable="true"
                                            dir="ltr"
                                            spellcheck="true"
                                            @input="syncBlocks"
                                            @keydown.ctrl.s.prevent="saveNote"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Cámara Overlay -->
                        <div v-if="showCamera" class="note-camera-overlay">
                            <div class="note-camera-box">
                                <p class="camera-title"><Camera :size="18" class="mr-1" /> Cámara</p>
                                <video ref="videoEl" autoplay playsinline class="camera-video"></video>
                                <canvas ref="canvasEl" style="display:none"></canvas>
                                <div class="camera-actions">
                                    <button type="button" class="note-btn note-btn-primary" @click="capturePhoto">
                                        <Camera :size="16" /> Capturar
                                    </button>
                                    <button type="button" class="note-btn note-btn-secondary" @click="stopCamera">
                                        <X :size="15" /> Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.note-modal-backdrop {
    position: fixed; inset: 0; z-index: 60;
    background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);
}
.note-modal-panel {
    position: fixed; top: 0; right: 0; bottom: 0;
    width: 100%; max-width: 800px;
    background: var(--color-surface, #1a1a2e);
    border-left: 1px solid var(--color-border, rgba(255,255,255,0.08));
    display: flex; flex-direction: column; overflow: hidden;
    box-shadow: -8px 0 40px rgba(0,0,0,0.4);
}
.note-modal-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--color-border, rgba(255,255,255,0.08));
    gap: 1rem; flex-shrink: 0;
}
.note-header-left  { display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0; }
.note-header-actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.note-course-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 8px; }
.color-dot-primary   { background: var(--color-primary,   #e879f9); }
.color-dot-accent    { background: var(--color-accent,    #a855f7); }
.color-dot-success   { background: var(--color-success,   #22c55e); }
.color-dot-warning   { background: var(--color-warning,   #f59e0b); }
.color-dot-secondary { background: var(--color-content-muted, #6b7280); }
.note-course-title {
    font-size: 1.1rem; font-weight: 700;
    color: var(--color-content-primary, #f1f5f9);
    margin: 0; display: flex; align-items: center;
}
.note-course-sessions { display: flex; flex-wrap: wrap; gap: 0.375rem; margin-top: 0.375rem; }
.note-session-badge {
    font-size: 0.7rem; background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 6px;
    padding: 0.1rem 0.5rem; color: var(--color-content-secondary, #94a3b8);
}
.note-close-btn {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; border-radius: 8px; border: none;
    background: transparent; color: var(--color-content-muted, #6b7280);
    cursor: pointer; transition: background 0.15s, color 0.15s;
}
.note-close-btn:hover { background: rgba(255,255,255,0.06); color: var(--color-content-primary); }
.note-btn {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.4rem 0.875rem; border-radius: 8px;
    font-size: 0.8rem; font-weight: 600; cursor: pointer; border: none;
    transition: opacity 0.15s, transform 0.1s;
}
.note-btn:disabled { opacity: 0.5; pointer-events: none; }
.note-btn:active   { transform: scale(0.97); }
.note-btn-primary  {
    background: linear-gradient(135deg, var(--color-primary, #e879f9), var(--color-accent, #a855f7));
    color: #fff;
}
.note-btn-primary:hover { opacity: 0.88; }
.note-btn-secondary {
    background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);
    color: var(--color-content-secondary, #94a3b8);
}
.note-btn-secondary:hover { background: rgba(255,255,255,0.12); }
.note-loading {
    display: flex; align-items: center; justify-content: center;
    gap: 0.75rem; flex: 1; color: var(--color-content-muted, #6b7280); font-size: 0.9rem;
}
.animate-spin { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.note-toolbar {
    display: flex; align-items: center; gap: 0.25rem;
    padding: 0.55rem 1.25rem; flex-wrap: wrap; flex-shrink: 0;
    border-bottom: 1px solid var(--color-border, rgba(255,255,255,0.08));
    background: var(--color-surface-sunken, rgba(0,0,0,0.2));
}
.toolbar-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 32px; height: 32px; padding: 0 0.4rem; border-radius: 6px;
    border: none; background: transparent; color: var(--color-content-secondary, #94a3b8);
    cursor: pointer; transition: background 0.12s, color 0.12s;
}
.toolbar-btn:hover { background: rgba(255,255,255,0.08); color: var(--color-content-primary, #f1f5f9); }
.color-red  { color: #ef4444 !important; }
.color-blue { color: #3b82f6 !important; }
.toolbar-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.1); margin: 0 0.25rem; flex-shrink: 0; }
.toolbar-btn-entry {
    display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;
    padding: 0.3rem 0.75rem; border-radius: 6px; cursor: pointer;
    background: rgba(232,121,249,0.1); color: var(--color-primary, #e879f9);
    border: 1px solid rgba(232,121,249,0.25); transition: background 0.12s;
}
.toolbar-btn-entry:hover { background: rgba(232,121,249,0.2); }
.camera-error-bar {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.6rem 1.25rem;
    background: rgba(239,68,68,0.1); border-bottom: 1px solid rgba(239,68,68,0.2);
    color: #fca5a5; font-size: 0.8rem; flex-shrink: 0;
}
.note-body { display: flex; flex: 1; overflow: hidden; }
.note-entries-sidebar {
    width: 180px; flex-shrink: 0; overflow-y: auto; padding: 0.75rem 0.5rem;
    display: flex; flex-direction: column; gap: 0.25rem;
    border-right: 1px solid var(--color-border, rgba(255,255,255,0.08));
    background: var(--color-surface-sunken, rgba(0,0,0,0.15));
}
.sidebar-title {
    font-size: 0.68rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--color-content-muted, #6b7280);
    padding: 0 0.5rem 0.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 0.25rem;
}
.sidebar-entry-btn {
    width: 100%; text-align: left; padding: 0.4rem 0.5rem; border-radius: 6px;
    border: none; background: transparent; color: var(--color-content-secondary, #94a3b8);
    font-size: 0.7rem; cursor: pointer; transition: background 0.12s, color 0.12s; word-break: break-word;
}
.sidebar-entry-btn:hover  { background: rgba(255,255,255,0.06); }
.sidebar-entry-btn.active { background: rgba(232,121,249,0.12); color: var(--color-primary, #e879f9); font-weight: 600; }
.note-editor-area { flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; }
.note-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex: 1; gap: 0.5rem; color: var(--color-content-muted, #6b7280); text-align: center; padding: 3rem 1rem;
}
.note-empty-icon { opacity: 0.35; }
.note-empty p    { font-size: 0.9rem; }
.note-entry-container { display: flex; flex-direction: column; flex: 1; }
.note-entry-date {
    display: flex; align-items: center; font-size: 0.75rem;
    color: var(--color-content-muted, #6b7280); margin-bottom: 1rem;
    padding-bottom: 0.5rem; border-bottom: 1px dashed rgba(255,255,255,0.08);
}

.note-editor {
    flex: 1;
    min-height: 300px;
    outline: none;
    direction: ltr !important;
    unicode-bidi: normal !important;
    writing-mode: horizontal-tb !important;
    text-align: left;
    color: var(--color-content-primary, #f1f5f9);
    font-size: 0.95rem;
    line-height: 1.8;
    caret-color: var(--color-primary, #e879f9);
    word-break: break-word;
    white-space: pre-wrap;
}
.note-editor:empty::before {
    content: 'Escribe tu apunte aquí… (Ctrl+S para guardar)';
    color: var(--color-content-muted, #6b7280);
    pointer-events: none;
}
.note-editor :deep(h1)     { font-size:1.5rem; font-weight:700; margin:1rem 0 0.5rem; }
.note-editor :deep(h2)     { font-size:1.15rem; font-weight:600; margin:0.875rem 0 0.375rem; }
.note-editor :deep(strong) { font-weight:700; }
.note-editor :deep(p)      { margin:0.2rem 0; }
.note-editor :deep(img)    { max-width:100%; border-radius:8px; margin:0.75rem 0; display:block; border:1px solid rgba(255,255,255,0.1); }

.note-camera-overlay {
    position:absolute; inset:0; background:rgba(0,0,0,0.88);
    display:flex; align-items:center; justify-content:center; z-index:20;
}
.note-camera-box {
    background:var(--color-surface,#1a1a2e); border-radius:16px; padding:1.5rem;
    display:flex; flex-direction:column; align-items:center; gap:1rem;
    max-width:520px; width:92%; border:1px solid rgba(255,255,255,0.1);
}
.camera-title { font-size:0.95rem; font-weight:600; color:var(--color-content-primary,#f1f5f9); margin:0; display:flex; align-items:center; }
.camera-video { width:100%; border-radius:10px; background:#000; max-height:340px; object-fit:cover; }
.camera-actions { display:flex; gap:0.75rem; }
</style>
