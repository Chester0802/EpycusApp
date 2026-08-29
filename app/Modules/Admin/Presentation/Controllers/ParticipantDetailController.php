<?php

declare(strict_types=1);

namespace App\Modules\Admin\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ParticipantDetailController extends Controller
{
    public function show(int $id, Request $request): Response
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            abort(404, 'Participant not found');
        }

        $participant = DB::table('participants')->where('user_id', $id)->first();
        $progress = DB::table('user_progress')->where('user_id', $id)->first();

        // Obtener historial de pomodoros
        $pomodoros = DB::table('pomodoro_sessions')
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Obtener misiones
        $missions = DB::table('missions')
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener wellbeing (estado de ánimo)
        $journal = DB::table('journal_entries')
            ->where('user_id', $id)
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        // Obtener consultas a IA
        $aiQueries = DB::table('ai_messages')
            ->join('ai_conversations', 'ai_messages.conversation_id', '=', 'ai_conversations.id')
            ->where('ai_conversations.user_id', $id)
            ->where('ai_messages.role', 'user')
            ->select('ai_messages.*', 'ai_conversations.title')
            ->orderBy('ai_messages.created_at', 'desc')
            ->limit(50)
            ->get();

        return Inertia::render('Admin/ParticipantDetail', [
            'user' => $user,
            'participant' => $participant,
            'progress' => $progress,
            'pomodoros' => $pomodoros,
            'missions' => $missions,
            'journal' => $journal,
            'aiQueries' => $aiQueries,
        ]);
    }
}
