<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\CourseNoteModel;
use App\Modules\Calendar\Infrastructure\Models\NoteImageModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class NoteImageController extends Controller
{
    /**
     * Tipos MIME permitidos (imagen → magic bytes esperados).
     * @var array<string, array<string>>
     */
    private const ALLOWED_MIMES = [
        'image/jpeg' => ['ffd8ff'],
        'image/png'  => ['89504e47'],
        'image/gif'  => ['474946383761', '474946383961'],
        'image/webp' => ['52494646'],
        'image/bmp'  => ['424d'],
        'image/avif' => ['0000'],  // validado por mime_type string también
    ];

    private const MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/bmp'  => 'bmp',
        'image/avif' => 'avif',
    ];

    private const MAX_SIZE_BYTES = 5 * 1024 * 1024; // 5 MB

    /**
     * Subir imagen desde archivo (validación doble: extensión + magic bytes).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $request->validate([
            'note_id' => ['required', 'integer', 'exists:course_notes,id'],
            'image'   => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,bmp,avif'],
        ]);

        $note = CourseNoteModel::query()->findOrFail((int) $request->input('note_id'));

        // Verificar ownership del apunte
        if ($note->user_id !== $userId) {
            return response()->json(['error' => 'Sin permiso.'], 403);
        }

        $file = $request->file('image');

        // ── Validación por magic bytes (anti-hack) ────────────────────────────
        $realMime = $this->detectRealMime($file->getRealPath());

        if (! $realMime || ! array_key_exists($realMime, self::MIME_TO_EXT)) {
            return response()->json([
                'error' => 'El archivo no es una imagen válida. Se detectó contenido malicioso.',
            ], 422);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return response()->json(['error' => 'La imagen supera el límite de 5 MB.'], 422);
        }

        $filename  = Str::uuid()->toString();
        $extension = self::MIME_TO_EXT[$realMime];
        $path      = "note-images/{$userId}/{$filename}.{$extension}";

        Storage::disk('private')->put($path, file_get_contents($file->getRealPath()));

        $image = NoteImageModel::query()->create([
            'note_id'       => $note->id,
            'user_id'       => $userId,
            'filename'      => "{$filename}.{$extension}",
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $realMime,
            'extension'     => $extension,
            'size'          => $file->getSize(),
        ]);

        return response()->json([
            'image' => [
                'id'            => $image->id,
                'url'           => route('note-images.show', ['id' => $image->id]),
                'original_name' => $image->original_name,
                'mime_type'     => $realMime,
                'size'          => $image->size,
            ],
        ], 201);
    }

    /**
     * Guardar captura de cámara (base64 → archivo en disco).
     */
    public function capture(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $request->validate([
            'note_id'    => ['required', 'integer', 'exists:course_notes,id'],
            'image_data' => ['required', 'string'],  // base64 data URL
        ]);

        $note = CourseNoteModel::query()->findOrFail((int) $request->input('note_id'));

        if ($note->user_id !== $userId) {
            return response()->json(['error' => 'Sin permiso.'], 403);
        }

        $dataUrl = $request->input('image_data');

        // Extraer el contenido binario del data URL (data:image/jpeg;base64,...)
        if (! preg_match('/^data:(image\/[a-zA-Z]+);base64,(.+)$/', $dataUrl, $matches)) {
            return response()->json(['error' => 'Formato de imagen inválido.'], 422);
        }

        $mimeType  = $matches[1];
        $binaryData = base64_decode($matches[2], strict: true);

        if ($binaryData === false) {
            return response()->json(['error' => 'Datos de imagen corruptos.'], 422);
        }

        if (strlen($binaryData) > self::MAX_SIZE_BYTES) {
            return response()->json(['error' => 'La imagen supera el límite de 5 MB.'], 422);
        }

        if (! array_key_exists($mimeType, self::MIME_TO_EXT)) {
            return response()->json(['error' => 'Formato de imagen no permitido.'], 422);
        }

        // Validar magic bytes también en la captura
        $realMime = $this->detectRealMimeFromBinary($binaryData);
        if (! $realMime || ! array_key_exists($realMime, self::MIME_TO_EXT)) {
            return response()->json(['error' => 'Los datos de imagen no son válidos.'], 422);
        }

        $filename  = Str::uuid()->toString();
        $extension = self::MIME_TO_EXT[$realMime];
        $path      = "note-images/{$userId}/{$filename}.{$extension}";

        Storage::disk('private')->put($path, $binaryData);

        $image = NoteImageModel::query()->create([
            'note_id'       => $note->id,
            'user_id'       => $userId,
            'filename'      => "{$filename}.{$extension}",
            'original_name' => "camara_{$filename}.{$extension}",
            'mime_type'     => $realMime,
            'extension'     => $extension,
            'size'          => strlen($binaryData),
        ]);

        return response()->json([
            'image' => [
                'id'            => $image->id,
                'url'           => route('note-images.show', ['id' => $image->id]),
                'original_name' => $image->original_name,
                'mime_type'     => $realMime,
                'size'          => $image->size,
            ],
        ], 201);
    }

    /**
     * Servir imagen autenticada — verifica que el usuario sea el dueño.
     */
    public function show(int $id): Response
    {
        $userId = (int) Auth::id();

        $image = NoteImageModel::query()->findOrFail($id);

        // Seguridad: solo el dueño puede ver su imagen
        if ($image->user_id !== $userId) {
            abort(403, 'Sin permiso para acceder a esta imagen.');
        }

        $path = "note-images/{$userId}/{$image->filename}";

        if (! Storage::disk('private')->exists($path)) {
            abort(404, 'Imagen no encontrada.');
        }

        return response(
            Storage::disk('private')->get($path),
            200,
            [
                'Content-Type'        => $image->mime_type,
                'Content-Disposition' => 'inline; filename="' . $image->original_name . '"',
                'Cache-Control'       => 'private, max-age=3600',
            ]
        );
    }

    /**
     * Detecta el tipo MIME real leyendo los magic bytes del archivo.
     */
    private function detectRealMime(string $filePath): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($filePath);

        return $mime ?: null;
    }

    /**
     * Detecta el tipo MIME real desde datos binarios en memoria.
     */
    private function detectRealMimeFromBinary(string $binaryData): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($binaryData);

        return $mime ?: null;
    }
}
