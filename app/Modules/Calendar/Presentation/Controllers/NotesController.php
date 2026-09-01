<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Domain\Contracts\CalendarRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class NotesController extends Controller
{
    public function __construct(
        private readonly CalendarRepositoryInterface $calendar,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        
        // Obtenemos todos los cursos del usuario
        $courses = $this->calendar->getCoursesForUser($userId)
            ->map(fn ($c) => [
                'id'        => $c->id,
                'name'      => $c->name,
                'color'     => $c->color,
                'starts_at' => $c->starts_at?->toDateString(),
                'ends_at'   => $c->ends_at?->toDateString(),
            ])
            ->values()
            ->toArray();

        // Si se pasa un course_id por query string, lo pre-seleccionamos
        $selectedCourseId = $request->query('course_id');
        if ($selectedCourseId && !collect($courses)->contains('id', (int) $selectedCourseId)) {
            $selectedCourseId = null;
        }

        return Inertia::render('Notes/Index', [
            'courses' => $courses,
            'initialCourseId' => $selectedCourseId ? (int) $selectedCourseId : null,
        ]);
    }
}
