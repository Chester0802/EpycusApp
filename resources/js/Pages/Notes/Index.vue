<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import axios from 'axios';
import {
    Save,
    Camera,
    CalendarPlus,
    X,
    Bold,
    Italic,
    Underline,
    List,
    ListOrdered,
    Highlighter,
    Heading1,
    Heading2,
    Heading3,
    Pilcrow,
    Loader2,
    NotebookText,
    ImagePlus,
    FileJson,
    FileText,
    Check,
    Type,
    RotateCcw,
    Trash2,
    Network,
    PanelLeft,
    ChevronLeft,
} from '@lucide/vue';

import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Menu, ChevronRight } from '@lucide/vue';

const props = defineProps({
    courses: { type: Array, default: () => [] },
    initialCourseId: { type: Number, default: null },
});

const isSidebarOpen = ref(false);
const selectedCourse = computed(() => props.courses.find(c => c.id === activeCourseId.value) || null);
const activeCourseId = ref(props.initialCourseId || (props.courses.length > 0 ? props.courses[0].id : null));

// ── Estado ──────────────────────────────────────────────────────────────────
const loading        = ref(false);
const saving         = ref(false);
const saveSuccess    = ref(false);
const autoSaveStatus = ref('saved'); // 'saved' | 'saving' | 'unsaved' | 'error'
const noteId         = ref(null);
const entries        = ref([]);
const showEntriesSidebar = ref(true);
const images         = ref([]);
const activeEntryId  = ref(null);
const cameraStream   = ref(null);
const showCamera     = ref(false);
const videoEl        = ref(null);
const canvasEl       = ref(null);
const uploadingImage = ref(false);
const cameraError    = ref('');

let autoSaveTimer = null;

// Menú contextual (click derecho)
const showContextMenu = ref(false);
const contextMenuPos  = ref({ x: 0, y: 0 });
let savedRange = null;

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

function getDraftKey(courseId) {
    return `epycus_note_draft_${courseId}`;
}

function saveLocalDraft() {
    if (!activeCourseId.value) return;
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            const draft = {
                timestamp: Date.now(),
                entries: entries.value,
            };
            localStorage.setItem(getDraftKey(activeCourseId.value), JSON.stringify(draft));
        }
    } catch {
        // Silencioso
    }
}

function clearLocalDraft() {
    if (!activeCourseId.value) return;
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            localStorage.removeItem(getDraftKey(activeCourseId.value));
        }
    } catch {
        // Silencioso
    }
}

// ── Cargar apunte al abrir o cambiar curso ───────────────────────────────────
watch(
    activeCourseId,
    async (newCourseId) => {
        if (newCourseId) {
            if (autoSaveTimer) clearTimeout(autoSaveTimer);
            await loadNote(newCourseId);
            isSidebarOpen.value = false;
        }
    },
    { immediate: true },
);

// Cuando cambia la entrada activa, cargar su HTML en el editor
watch(activeEntryId, async () => {
    await nextTick();
    loadEditorContent();
});

async function loadNote(courseId) {
    if (!courseId) return;
    loading.value = true;
    try {
        const res = await axios.get(route('calendar.notes.show', { courseId }), {
            timeout: 15000,
        });
        const data = res.data;
        if (data.note) {
            noteId.value  = data.note.id;
            entries.value = Array.isArray(data.note.content?.entries) ? data.note.content.entries : [];
            images.value  = Array.isArray(data.note.images) ? data.note.images : [];
        } else {
            noteId.value  = null;
            entries.value = [];
            images.value  = [];
        }

        // Revisar si existe borrador local más reciente
        if (typeof window !== 'undefined' && window.localStorage) {
            try {
                const rawDraft = localStorage.getItem(getDraftKey(courseId));
                if (rawDraft) {
                    const parsed = JSON.parse(rawDraft);
                    const serverTime = data.note?.updated_at ? new Date(data.note.updated_at).getTime() : 0;
                    if (parsed.timestamp && parsed.timestamp > serverTime && Array.isArray(parsed.entries) && parsed.entries.length > 0) {
                        entries.value = parsed.entries;
                        autoSaveStatus.value = 'unsaved';
                    }
                }
            } catch {
                // Silencioso
            }
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
    const id  = crypto.randomUUID ? crypto.randomUUID() : 'entry_' + Date.now();
    const now = new Date().toISOString();
    entries.value.push({
        id,
        recorded_at: now,
        blocks: [{ type: 'html', html: '' }],
    });
    activeEntryId.value = id;
    triggerAutoSave();
    await nextTick();
    if (editorEl.value) {
        editorEl.value.innerHTML = '';
        editorEl.value.focus();
    }
}

// ── Cambiar de entrada de forma segura ──────────────────────────────────────
function selectEntry(id) {
    if (activeEntryId.value === id) return;
    syncBlocks();
    activeEntryId.value = id;
}

// ── Eliminar entrada ────────────────────────────────────────────────────────
async function deleteEntry(entryId) {
    if (!confirm('¿Deseas eliminar este registro de apunte?')) return;
    syncBlocks();
    const idx = entries.value.findIndex(e => e.id === entryId);
    if (idx !== -1) {
        entries.value.splice(idx, 1);
        if (entries.value.length > 0) {
            const newIdx = Math.max(0, idx - 1);
            activeEntryId.value = entries.value[newIdx].id;
        } else {
            activeEntryId.value = null;
        }
        await saveNote(false);
    }
}

// ── Sincronizar DOM del editor → datos ────────────────────────────────────
function syncBlocks() {
    if (!currentEntry.value || !editorEl.value) return;
    currentEntry.value.blocks = [{ type: 'html', html: editorEl.value.innerHTML }];
}

// ── Manejo de entrada con auto-guardado ──────────────────────────
function onEditorInput() {
    syncBlocks();
    saveLocalDraft();
    autoSaveStatus.value = 'unsaved';
    triggerAutoSave();
}

function triggerAutoSave() {
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        saveNote(true);
    }, 2500);
}

// ── Guardar apunte con soporte para timeout, auto-guardado y reintentos ────
async function saveNote(isAutoSave = false) {
    if (!activeCourseId.value) return;
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    syncBlocks();
    saveLocalDraft();

    saving.value = true;
    autoSaveStatus.value = 'saving';

    const payload = {
        content: { version: '1.0', entries: entries.value },
    };

    const makeRequest = () =>
        axios.post(route('calendar.notes.upsert', { courseId: activeCourseId.value }), payload, {
            timeout: 15000,
        });

    try {
        let res;
        try {
            res = await makeRequest();
        } catch (firstErr) {
            const status = firstErr.response?.status;
            if (status === 504 || status === 502 || firstErr.code === 'ECONNABORTED' || !firstErr.response) {
                console.warn('Reintentando guardado de apunte tras corte o timeout 504...');
                await new Promise(r => setTimeout(r, 600));
                res = await makeRequest();
            } else {
                throw firstErr;
            }
        }

        const data = res.data;
        if (data.note) {
            noteId.value = data.note.id;
            if (Array.isArray(data.note.images)) {
                images.value = data.note.images;
            }
            saveSuccess.value    = true;
            autoSaveStatus.value = 'saved';
            clearLocalDraft();
            setTimeout(() => (saveSuccess.value = false), 2200);
        } else {
            autoSaveStatus.value = 'error';
            if (!isAutoSave) alert('Error al guardar el apunte en el servidor.');
        }
    } catch (e) {
        console.error('Error al guardar apunte:', e);
        autoSaveStatus.value = 'error';
        const msg = e.response?.data?.message || e.response?.data?.error || e.message;
        if (!isAutoSave) {
            alert('No se pudo sincronizar el apunte: ' + msg + '. Tus cambios están respaldados localmente.');
        }
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
    const imgHtml = `<img src="${url}" alt="${alt}" style="max-width:100%;border-radius:8px;margin:8px 0;display:block;" />`;
    const success = document.execCommand('insertHTML', false, imgHtml);
    if (!success) {
        editorEl.value.innerHTML += imgHtml;
    }
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
            cameraError.value = 'Permiso de cámara denegado. Permite el acceso a la cámara en tu navegador.';
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

// ── Exportar JSON directo (Descarga inmediata para IA / Respaldo) ─────────
function exportAndDownloadJson() {
    syncBlocks();
    const cleanCourseName = selectedCourse.value?.name
        ? selectedCourse.value.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '_')
        : 'curso';

    const payload = {
        export_version: '1.0',
        exported_at:    new Date().toISOString(),
        metadata: {
            course_name:   selectedCourse.value?.name ?? '',
            sessions:      (selectedCourse.value?.sessions ?? []).map(s => ({
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
    const cleanCourseName = selectedCourse.value?.name ?? 'Curso';
    const sessionsList = (selectedCourse.value?.sessions ?? []).map(s =>
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
                .pdf-entry-body h3 { font-size: 14px; font-weight: 600; color: #334155; margin: 10px 0 4px; }
                .pdf-entry-body strong { font-weight: 700; }
                .pdf-entry-body ul { padding-left: 20px; margin: 8px 0; }
                .pdf-entry-body ol { padding-left: 20px; margin: 8px 0; }
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
                    const imgs = document.images;
                    let loaded = 0;
                    const total = imgs.length;
                    if (total === 0) {
                        window.print();
                    } else {
                        const done = () => {
                            loaded++;
                            if (loaded >= total) window.print();
                        };
                        for (let i = 0; i < total; i++) {
                            if (imgs[i].complete) {
                                done();
                            } else {
                                imgs[i].onload = done;
                                imgs[i].onerror = done;
                            }
                        }
                        setTimeout(() => window.print(), 1500);
                    }
                };
            ${'<' + '/script>'}
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
function formatParagraph() {
    editorEl.value?.focus();
    document.execCommand('formatBlock', false, 'p');
    syncBlocks();
}
function formatBold() {
    editorEl.value?.focus();
    document.execCommand('bold', false, null);
    syncBlocks();
}
function formatItalic() {
    editorEl.value?.focus();
    document.execCommand('italic', false, null);
    syncBlocks();
}
function formatUnderline() {
    editorEl.value?.focus();
    document.execCommand('underline', false, null);
    syncBlocks();
}
function formatList(ordered = false) {
    editorEl.value?.focus();
    document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
    syncBlocks();
}
function formatHighlight(color = '#fef08a') {
    editorEl.value?.focus();
    document.execCommand('hiliteColor', false, color);
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
    // Limpiar color residual en la selección
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
            if (parent.style && parent.style.backgroundColor) {
                parent.style.backgroundColor = '';
            }
        }
    }
    syncBlocks();
}

// ── Menú Contextual (Click Derecho) ────────────────────────────────────────
function saveSelection() {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
        savedRange = sel.getRangeAt(0).cloneRange();
    } else {
        savedRange = null;
    }
}

function restoreSelection() {
    if (savedRange) {
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }
}

function openContextMenu(event) {
    event.preventDefault();
    saveSelection();

    const menuWidth = 230;
    const menuHeight = 360;
    let x = event.clientX;
    let y = event.clientY;

    if (x + menuWidth > window.innerWidth) {
        x = Math.max(10, window.innerWidth - menuWidth - 15);
    }
    if (y + menuHeight > window.innerHeight) {
        y = Math.max(10, window.innerHeight - menuHeight - 15);
    }

    contextMenuPos.value = { x, y };
    showContextMenu.value = true;
}

function closeContextMenu() {
    showContextMenu.value = false;
}

function applyMenuAction(actionFn, ...args) {
    editorEl.value?.focus();
    restoreSelection();
    actionFn(...args);
    closeContextMenu();
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

function formatShortDate(isoString) {
    try {
        const d = new Date(isoString);
        return d.toLocaleDateString('es', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return isoString;
    }
}

function close() {
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    stopCamera();
    cameraError.value = '';
    emit('close');
}

onBeforeUnmount(() => {
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    stopCamera();
});
</script>

<template>
    <AppLayout>
        <Head title="Bloc de Apuntes" />
        <div class="flex h-[calc(100vh-64px)] w-full overflow-hidden bg-surface">
            <!-- Sidebar / Panel de Cursos -->
            <div 
                class="w-64 shrink-0 border-r border-border bg-surface-sunken flex flex-col transition-all duration-300 z-20"
                :class="isSidebarOpen ? 'absolute inset-y-0 left-0 shadow-xl lg:static lg:shadow-none' : 'hidden lg:flex'"
            >
                <div class="p-4 border-b border-border flex items-center justify-between">
                    <h2 class="font-display font-semibold text-content-primary">Mis Cursos</h2>
                    <button class="lg:hidden text-content-muted" @click="isSidebarOpen = false">
                        <X :size="18" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    <button
                        v-for="c in courses"
                        :key="c.id"
                        @click="activeCourseId = c.id"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center gap-2"
                        :class="activeCourseId === c.id ? 'bg-primary/10 text-primary-strong font-medium' : 'text-content-secondary hover:bg-surface hover:text-content-primary'"
                    >
                        <div class="w-2 h-2 rounded-full shrink-0" :class="`bg-color-${c.color}`"></div>
                        <span class="truncate">{{ c.name }}</span>
                    </button>
                    <div v-if="courses.length === 0" class="text-xs text-content-muted p-2 text-center">
                        No hay cursos activos
                    </div>
                </div>
            </div>

            <!-- Editor Principal -->
            <div class="flex-1 flex flex-col min-w-0 bg-surface relative h-full">
                <!-- Overlay for mobile when sidebar is open -->
                <div 
                    v-if="isSidebarOpen" 
                    class="absolute inset-0 bg-black/20 z-10 lg:hidden"
                    @click="isSidebarOpen = false"
                ></div>

                <div v-if="selectedCourse" class="flex-1 flex flex-col h-full">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-border bg-surface shrink-0 flex flex-col sm:flex-row sm:items-center justify-between gap-2 z-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <button class="lg:hidden text-content-secondary shrink-0" @click="isSidebarOpen = true">
                                <Menu :size="20" />
                            </button>
                            <div class="w-3 h-3 rounded-full shrink-0" :class="`bg-color-${selectedCourse.color}`"></div>
                            <h2 class="font-bold text-content-primary truncate">
                                {{ selectedCourse.name }}
                            </h2>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Indicador de estado de sincronización / auto-guardado -->
                                <div class="note-sync-indicator mr-1">
                                    <span v-if="autoSaveStatus === 'saving'" class="text-[11px] text-primary-strong flex items-center gap-1 font-semibold animate-pulse">
                                        <Loader2 :size="11" class="animate-spin" /> Guardando…
                                    </span>
                                    <span v-else-if="autoSaveStatus === 'saved' || saveSuccess" class="text-[11px] text-success flex items-center gap-1 font-medium opacity-90">
                                        <Check :size="12" /> Sincronizado
                                    </span>
                                    <span v-else-if="autoSaveStatus === 'unsaved'" class="text-[11px] text-content-muted flex items-center gap-1">
                                        ✏️ Borrador local
                                    </span>
                                    <span v-else-if="autoSaveStatus === 'error'" class="text-[11px] text-danger-text flex items-center gap-1 font-semibold">
                                        ⚠️ Reintentando…
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="note-btn note-btn-secondary"
                                    title="Exportar apunte en formato PDF / Imprimir"
                                    @click="exportPdf"
                                >
                                    <FileText :size="14" />
                                    <span>PDF</span>
                                </button>
                                <button type="button" class="note-btn note-btn-primary" :disabled="saving" @click="saveNote(false)">
                                    <Loader2 v-if="saving"      :size="14" class="animate-spin" />
                                    <Check   v-else-if="saveSuccess" :size="14" />
                                    <Save    v-else               :size="14" />
                                    <span>{{ saving ? 'Guardando…' : saveSuccess ? 'Guardado' : 'Guardar' }}</span>
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
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Título H1"
                                    @mousedown.prevent
                                    @click="formatHeading(1)"
                                >
                                    <Heading1 :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Subtítulo H2"
                                    @mousedown.prevent
                                    @click="formatHeading(2)"
                                >
                                    <Heading2 :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Encabezado H3"
                                    @mousedown.prevent
                                    @click="formatHeading(3)"
                                >
                                    <Heading3 :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn text-xs font-semibold px-2"
                                    title="Texto normal (Párrafo)"
                                    @mousedown.prevent
                                    @click="formatParagraph"
                                >
                                    <Pilcrow :size="15" />
                                    <span class="ml-1 text-[11px] font-normal">Texto</span>
                                </button>
                                <div class="toolbar-sep"></div>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Negrita (Ctrl+B)"
                                    @mousedown.prevent
                                    @click="formatBold"
                                >
                                    <Bold :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Cursiva (Ctrl+I)"
                                    @mousedown.prevent
                                    @click="formatItalic"
                                >
                                    <Italic :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Subrayado (Ctrl+U)"
                                    @mousedown.prevent
                                    @click="formatUnderline"
                                >
                                    <Underline :size="16" />
                                </button>
                                <div class="toolbar-sep"></div>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Lista con viñetas"
                                    @mousedown.prevent
                                    @click="formatList(false)"
                                >
                                    <List :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Lista numerada"
                                    @mousedown.prevent
                                    @click="formatList(true)"
                                >
                                    <ListOrdered :size="16" />
                                </button>
                                <div class="toolbar-sep"></div>
                                <button
                                    type="button"
                                    class="toolbar-btn color-red"
                                    title="Texto rojo"
                                    @mousedown.prevent
                                    @click="formatColor('#ef4444')"
                                >
                                    <Type :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn color-blue"
                                    title="Texto azul"
                                    @mousedown.prevent
                                    @click="formatColor('#3b82f6')"
                                >
                                    <Type :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn color-green"
                                    title="Texto verde"
                                    @mousedown.prevent
                                    @click="formatColor('#22c55e')"
                                >
                                    <Type :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn color-yellow"
                                    title="Texto amarillo"
                                    @mousedown.prevent
                                    @click="formatColor('#eab308')"
                                >
                                    <Type :size="16" />
                                </button>
                                <div class="toolbar-sep"></div>
                                <button
                                    type="button"
                                    class="toolbar-btn color-highlight-yellow"
                                    title="Resaltar amarillo"
                                    @mousedown.prevent
                                    @click="formatHighlight('#fef08a')"
                                >
                                    <Highlighter :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn color-highlight-green"
                                    title="Resaltar verde"
                                    @mousedown.prevent
                                    @click="formatHighlight('#bbf7d0')"
                                >
                                    <Highlighter :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="toolbar-btn"
                                    title="Restablecer color y formato"
                                    @mousedown.prevent
                                    @click="resetFormat"
                                >
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
                                <button
                                    v-if="entries.length > 1"
                                    type="button"
                                    class="toolbar-btn"
                                    :class="{ 'active': showEntriesSidebar }"
                                    :title="showEntriesSidebar ? 'Ocultar panel de registros' : 'Mostrar panel de registros'"
                                    @click="showEntriesSidebar = !showEntriesSidebar"
                                >
                                    <PanelLeft :size="15" />
                                    <span class="text-xs hidden md:inline">{{ showEntriesSidebar ? 'Ocultar Registros' : 'Ver Registros' }} ({{ entries.length }})</span>
                                </button>
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
                                <!-- Sidebar de Registros Colapsable -->
                                <div v-if="entries.length > 1 && showEntriesSidebar" class="note-entries-sidebar">
                                    <div class="flex items-center justify-between px-1 pb-1 mb-1 border-b border-border/50">
                                        <p class="sidebar-title !p-0 !border-0 !m-0">Registros ({{ entries.length }})</p>
                                        <button
                                            type="button"
                                            class="text-content-muted hover:text-content-primary p-1 rounded hover:bg-surface transition-colors"
                                            title="Ocultar panel de registros"
                                            @click="showEntriesSidebar = false"
                                        >
                                            <ChevronLeft :size="13" />
                                        </button>
                                    </div>
                                    <button
                                        v-for="entry in [...entries].reverse()"
                                        :key="entry.id"
                                        type="button"
                                        class="sidebar-entry-btn"
                                        :class="{ active: activeEntryId === entry.id }"
                                        @click="selectEntry(entry.id)"
                                    >
                                        {{ formatShortDate(entry.recorded_at) }}
                                    </button>
                                </div>

                                <!-- Editor -->
                                <div class="note-editor-area">
                                    <!-- Botón flotante para restaurar registros si está oculto -->
                                    <button
                                        v-if="!showEntriesSidebar && entries.length > 1"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-surface-raised border border-border text-content-secondary hover:text-content-primary hover:border-primary-strong/40 transition mb-3 w-fit shadow-xs font-semibold"
                                        title="Mostrar panel de registros"
                                        @click="showEntriesSidebar = true"
                                    >
                                        <PanelLeft :size="13" class="text-primary-strong" />
                                        <span>Mostrar Registros ({{ entries.length }})</span>
                                    </button>
                                    <!-- Vacío -->
                                    <div v-if="entries.length === 0" class="note-empty">
                                        <NotebookText :size="48" class="note-empty-icon" />
                                        <p>No hay apuntes aún para este curso.</p>
                                        <button type="button" class="note-btn note-btn-primary mt-4" @click="addEntry">
                                            <CalendarPlus :size="15" /> Agregar primer registro
                                        </button>
                                    </div>

                                    <!-- Entrada activa -->
                                    <div v-if="currentEntry" class="note-entry-container">
                                        <div class="note-entry-date">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <CalendarPlus :size="14" class="opacity-60 shrink-0" />
                                                <span class="truncate">{{ formatDate(currentEntry.recorded_at) }}</span>
                                            </div>
                                            <button
                                                type="button"
                                                class="note-delete-entry-btn"
                                                title="Eliminar este registro"
                                                @click="deleteEntry(currentEntry.id)"
                                            >
                                                <Trash2 :size="13" />
                                                <span>Eliminar registro</span>
                                            </button>
                                        </div>
                                        <div
                                            ref="editorEl"
                                            class="note-editor"
                                            contenteditable="true"
                                            dir="ltr"
                                            spellcheck="true"
                                            @input="onEditorInput"
                                            @contextmenu="openContextMenu($event)"
                                            @keydown.ctrl.s.prevent="saveNote(false)"
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

                        <!-- Menú Contextual (Click Derecho al seleccionar texto) -->
                        <div
                            v-if="showContextMenu"
                            class="note-context-overlay"
                            @click="closeContextMenu"
                            @contextmenu.prevent="closeContextMenu"
                        >
                            <div
                                class="note-context-menu"
                                :style="{ top: `${contextMenuPos.y}px`, left: `${contextMenuPos.x}px` }"
                                @click.stop
                            >
                                <div class="note-context-header">
                                    Formato Rápido
                                </div>
                                <button
                                    type="button"
                                    class="note-context-item"
                                    @click="applyMenuAction(formatUnderline)"
                                >
                                    <Underline :size="14" class="text-primary" />
                                    <span class="underline font-semibold">Subrayar</span>
                                </button>
                                <button
                                    type="button"
                                    class="note-context-item"
                                    @click="applyMenuAction(formatHighlight, '#fef08a')"
                                >
                                    <Highlighter :size="14" class="text-amber-400" />
                                    <span>Resaltador Amarillo</span>
                                </button>
                                <button
                                    type="button"
                                    class="note-context-item"
                                    @click="applyMenuAction(formatHighlight, '#bbf7d0')"
                                >
                                    <Highlighter :size="14" class="text-emerald-400" />
                                    <span>Resaltador Verde</span>
                                </button>
                                <div class="note-context-divider"></div>
                                <button
                                    type="button"
                                    class="note-context-item"
                                    @click="applyMenuAction(formatBold)"
                                >
                                    <Bold :size="14" />
                                    <span class="font-bold">Negrita</span>
                                </button>
                                <button
                                    type="button"
                                    class="note-context-item"
                                    @click="applyMenuAction(formatItalic)"
                                >
                                    <Italic :size="14" />
                                    <span class="italic">Cursiva</span>
                                </button>
                                <button
                                    type="button"
                                    class="note-context-item"
                                    @click="applyMenuAction(formatParagraph)"
                                >
                                    <Pilcrow :size="14" />
                                    <span>Texto normal</span>
                                </button>
                                <div class="note-context-divider"></div>
                                <div class="note-context-colors">
                                    <span class="note-context-label">Color texto:</span>
                                    <div class="note-color-dots">
                                        <button
                                            type="button"
                                            class="note-color-swatch bg-red"
                                            title="Rojo"
                                            @click="applyMenuAction(formatColor, '#ef4444')"
                                        ></button>
                                        <button
                                            type="button"
                                            class="note-color-swatch bg-blue"
                                            title="Azul"
                                            @click="applyMenuAction(formatColor, '#3b82f6')"
                                        ></button>
                                        <button
                                            type="button"
                                            class="note-color-swatch bg-green"
                                            title="Verde"
                                            @click="applyMenuAction(formatColor, '#22c55e')"
                                        ></button>
                                        <button
                                            type="button"
                                            class="note-color-swatch bg-yellow"
                                            title="Amarillo"
                                            @click="applyMenuAction(formatColor, '#eab308')"
                                        ></button>
                                    </div>
                                </div>
                                <div class="note-context-divider"></div>
                                <button
                                    type="button"
                                    class="note-context-item note-context-item-muted"
                                    @click="applyMenuAction(resetFormat)"
                                >
                                    <RotateCcw :size="13" />
                                    <span>Quitar formato</span>
                                </button>
                            </div>
                        </div>
                    </div> <!-- CIERRE DE v-if="selectedCourse" -->

                <!-- Estado vacío sin curso -->
                <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-6 h-full">
                    <button class="lg:hidden text-content-secondary mb-4 p-2 bg-surface-sunken rounded-lg" @click="isSidebarOpen = true">
                        <Menu :size="24" class="mx-auto" />
                        <span class="text-xs mt-1 block">Abrir panel</span>
                    </button>
                    <NotebookText :size="48" class="text-content-muted/30 mb-4" />
                    <h3 class="text-lg font-bold text-content-primary mb-2">Bloc de Apuntes</h3>
                    <p class="text-content-secondary max-w-sm">
                        Selecciona un curso de la barra lateral para ver o escribir apuntes.
                        Esta vista está optimizada para usar Epycus en pantalla dividida (split-screen).
                    </p>
                </div>
            </div> <!-- CIERRE DE Editor Principal -->
        </div> <!-- CIERRE DE Main Flex Layout -->
    </AppLayout>
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
    display: flex; flex-direction: column;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--color-border, rgba(255,255,255,0.08));
    gap: 0.75rem; flex-shrink: 0;
}
@media (min-width: 640px) {
    .note-modal-header {
        flex-direction: row; align-items: flex-start; justify-content: space-between; gap: 1rem;
        padding: 1.25rem 1.5rem;
    }
}
.note-header-top   { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; width: 100%; min-width: 0; }
@media (min-width: 640px) {
    .note-header-top { width: auto; flex: 1; }
}
.note-header-left  { display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0; flex: 1; }
.note-header-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; flex-shrink: 0; justify-content: flex-end; }
.note-course-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 6px; }
.color-dot-primary   { background: var(--color-primary,   #e879f9); }
.color-dot-accent    { background: var(--color-accent,    #a855f7); }
.color-dot-success   { background: var(--color-success,   #22c55e); }
.color-dot-warning   { background: var(--color-warning,   #f59e0b); }
.color-dot-secondary { background: var(--color-content-muted, #6b7280); }
.note-course-title {
    font-size: 1.05rem; font-weight: 700;
    color: var(--color-content-primary, #f1f5f9);
    margin: 0; display: flex; align-items: center;
}
.note-course-sessions { display: flex; flex-wrap: wrap; gap: 0.375rem; margin-top: 0.25rem; }
.note-session-badge {
    font-size: 0.68rem; background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 6px;
    padding: 0.1rem 0.45rem; color: var(--color-content-secondary, #94a3b8);
}
.note-close-btn {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; border: none;
    background: transparent; color: var(--color-content-muted, #6b7280);
    cursor: pointer; transition: background 0.15s, color 0.15s; flex-shrink: 0;
}
.note-close-btn:hover { background: rgba(255,255,255,0.06); color: var(--color-content-primary); }
.note-btn {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.4rem 0.75rem; border-radius: 8px;
    font-size: 0.78rem; font-weight: 600; cursor: pointer; border: none;
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
    padding: 0.5rem 1rem; flex-wrap: wrap; flex-shrink: 0;
    border-bottom: 1px solid var(--color-border, rgba(255,255,255,0.08));
    background: var(--color-surface-sunken, rgba(0,0,0,0.2));
    overflow-x: auto;
}
.toolbar-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 32px; height: 32px; padding: 0 0.4rem; border-radius: 6px;
    border: none; background: transparent; color: var(--color-content-secondary, #94a3b8);
    cursor: pointer; transition: background 0.12s, color 0.12s;
}
.toolbar-btn:hover { background: rgba(255,255,255,0.08); color: var(--color-content-primary, #f1f5f9); }
.color-red              { color: #ef4444 !important; }
.color-blue             { color: #3b82f6 !important; }
.color-green            { color: #22c55e !important; }
.color-yellow           { color: #eab308 !important; }
.color-highlight-yellow { color: #facc15 !important; }
.color-highlight-green  { color: #4ade80 !important; }
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

@media (max-width: 640px) {
    .note-body {
        flex-direction: column;
    }
    .note-entries-sidebar {
        width: 100%;
        max-height: 60px;
        flex-direction: row;
        overflow-x: auto;
        border-right: none;
        border-bottom: 1px solid var(--color-border, rgba(255,255,255,0.08));
        padding: 0.35rem 0.75rem;
        align-items: center;
    }
    .sidebar-title {
        display: none;
    }
    .sidebar-entry-btn {
        width: auto;
        white-space: nowrap;
        flex-shrink: 0;
        padding: 0.3rem 0.6rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .note-editor-area {
        padding: 1rem;
    }
}
.note-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex: 1; gap: 0.5rem; color: var(--color-content-muted, #6b7280); text-align: center; padding: 3rem 1rem;
}
.note-empty-icon { opacity: 0.35; }
.note-empty p    { font-size: 0.9rem; }
.note-entry-container { display: flex; flex-direction: column; flex: 1; }
.note-entry-date {
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
    font-size: 0.75rem; color: var(--color-content-muted, #6b7280);
    margin-bottom: 1rem; padding-bottom: 0.5rem;
    border-bottom: 1px dashed rgba(255,255,255,0.08);
}
.note-delete-entry-btn {
    display: inline-flex; align-items: center; gap: 0.25rem;
    padding: 0.2rem 0.5rem; border-radius: 4px; border: none;
    background: transparent; color: var(--color-danger, #ef4444);
    font-size: 0.7rem; cursor: pointer; opacity: 0.75;
    transition: opacity 0.15s, background 0.15s;
}
.note-delete-entry-btn:hover {
    opacity: 1; background: rgba(239,68,68,0.12);
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
    content: 'Escribe tu apunte aquí… (Click derecho para formato rápido, Ctrl+S para guardar)';
    color: var(--color-content-muted, #6b7280);
    pointer-events: none;
}
.note-editor :deep(h1)     { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; }
.note-editor :deep(h2)     { font-size: 1.15rem; font-weight: 600; margin: 0.875rem 0 0.375rem; }
.note-editor :deep(h3)     { font-size: 1.02rem; font-weight: 600; margin: 0.75rem 0 0.25rem; }
.note-editor :deep(strong) { font-weight: 700; }
.note-editor :deep(em), .note-editor :deep(i) { font-style: italic; }
.note-editor :deep(u)      { text-decoration: underline; }
.note-editor :deep(ul)     { list-style-type: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
.note-editor :deep(ol)     { list-style-type: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
.note-editor :deep(li)     { margin: 0.15rem 0; }
.note-editor :deep(p)      { margin: 0.2rem 0; }
.note-editor :deep(img)    { max-width: 100%; border-radius: 8px; margin: 0.75rem 0; display: block; border: 1px solid rgba(255,255,255,0.1); }

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

/* ── Menú Contextual (Click Derecho) ── */
.note-context-overlay {
    position: fixed; inset: 0; z-index: 100;
}
.note-context-menu {
    position: absolute;
    min-width: 210px;
    background: var(--color-surface-raised, #1e1e38);
    border: 1px solid var(--color-border, rgba(255,255,255,0.16));
    border-radius: 12px;
    padding: 0.35rem;
    box-shadow: 0 12px 35px rgba(0,0,0,0.5);
    display: flex; flex-direction: column; gap: 0.12rem;
    backdrop-filter: blur(10px);
}
.note-context-header {
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--color-content-muted, #6b7280);
    padding: 0.3rem 0.6rem 0.2rem;
}
.note-context-item {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.4rem 0.6rem; border-radius: 6px;
    border: none; background: transparent;
    color: var(--color-content-secondary, #cbd5e1);
    font-size: 0.78rem; text-align: left; cursor: pointer;
    transition: background 0.12s, color 0.12s;
}
.note-context-item:hover {
    background: rgba(255,255,255,0.08);
    color: var(--color-content-primary, #ffffff);
}
.note-context-item-muted {
    font-size: 0.72rem;
    color: var(--color-content-muted, #94a3b8);
}
.note-context-divider {
    height: 1px; background: var(--color-border, rgba(255,255,255,0.08));
    margin: 0.25rem 0.4rem;
}
.note-context-colors {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.25rem 0.6rem;
}
.note-context-label {
    font-size: 0.7rem; color: var(--color-content-muted, #6b7280);
}
.note-color-dots {
    display: flex; align-items: center; gap: 0.375rem;
}
.note-color-swatch {
    width: 18px; height: 18px; border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.2);
    cursor: pointer; transition: transform 0.12s;
}
.note-color-swatch:hover {
    transform: scale(1.2);
}
.bg-red    { background: #ef4444; }
.bg-blue   { background: #3b82f6; }
.bg-green  { background: #22c55e; }
.bg-yellow { background: #eab308; }
</style>
