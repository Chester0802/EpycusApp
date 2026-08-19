<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    /**
     * Procesa y registra un mensaje del buzón de la comunidad (ideas, errores, agradecimiento).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:suggestion,bug,idea,gratitude',
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'message' => 'required|string|min:5|max:2000',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $typeLabels = [
            'idea' => '💡 Nueva Idea / Función',
            'bug' => '🐞 Reporte de Error',
            'suggestion' => '🚀 Sugerencia de Mejora',
            'gratitude' => '❤️ Agradecimiento / Mensaje',
        ];

        $typeTitle = $typeLabels[$validated['type']] ?? 'Mensaje de la Comunidad';

        // 1. Guardar imagen si fue adjuntada
        $imagePath = null;
        $imageFile = $request->file('image');
        if ($imageFile && $imageFile->isValid()) {
            $imagePath = $imageFile->store('feedbacks', 'public');
        }

        // 2. Guardar en Base de Datos
        $insertData = [
            'type' => $validated['type'],
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('feedbacks', 'image_path')) {
            $insertData['image_path'] = $imagePath;
        }

        $feedbackId = DB::table('feedbacks')->insertGetId($insertData);

        // 3. Notificar por Correo Electrónico a contacto@soltectos.com
        try {
            $senderName = !empty($validated['name']) ? $validated['name'] : 'Estudiante Anónimo';
            $senderEmail = !empty($validated['email']) ? $validated['email'] : 'No especificado';
            $hasImageNotice = $imagePath ? "Sí (Adjunta en este correo y guardada en el servidor: /storage/{$imagePath})" : "No";

            $body = "Nuevo mensaje recibido en el Buzón de Epycus:\n\n"
                  . "Categoría: {$typeTitle}\n"
                  . "De: {$senderName} ({$senderEmail})\n"
                  . "Fecha: " . now()->format('d/m/Y H:i:s') . "\n"
                  . "Captura/Imagen adjunta: {$hasImageNotice}\n\n"
                  . "Mensaje:\n"
                  . "--------------------------------------------------\n"
                  . $validated['message'] . "\n"
                  . "--------------------------------------------------\n\n"
                  . "ID de Registro: #{$feedbackId}";

            Mail::raw($body, function ($mail) use ($typeTitle, $senderName, $imageFile) {
                $mail->to('contacto@soltectos.com')
                     ->subject("[Buzón Epycus] {$typeTitle} - {$senderName}");

                if ($imageFile && $imageFile->isValid()) {
                    $mail->attach($imageFile->getRealPath(), [
                        'as' => $imageFile->getClientOriginalName(),
                        'mime' => $imageFile->getMimeType(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Si el servicio SMTP temporalmente falla, el mensaje ya quedó registrado en la BD de forma segura
        }

        return response()->json([
            'success' => true,
            'message' => '¡Muchas gracias por tu aporte! Tu mensaje ha sido recibido con éxito en nuestro buzón.',
        ]);
    }
}
