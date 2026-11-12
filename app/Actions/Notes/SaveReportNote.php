<?php

namespace App\Actions\Notes;

use App\Actions\Projects\EnsureEditable;
use App\ChangeTracking\ChangeTracker;
use App\Enums\NoteKind;
use App\Keyrings\KeyringType;
use App\Models\Note;
use App\Models\Project;
use App\Models\Sample;
use App\Support\NoteHtml;
use Illuminate\Support\Facades\DB;

class SaveReportNote
{
    public function __construct(private ChangeTracker $changeTracker, private EnsureEditable $ensureEditable) {}

    /**
     * Stores the client-facing note of a project or one of its samples; a blank note removes it.
     */
    public function handle(Project $project, ?Sample $sample, string $html, ?int $userId): ?Note
    {
        $html = NoteHtml::isBlank($html) ? '' : NoteHtml::clean($html);

        return DB::transaction(function () use ($project, $sample, $html, $userId): ?Note {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);

            if ($sample === null) {
                $this->ensureEditable->handle($lockedProject, KeyringType::Project, $lockedProject->id);
                $notable = $lockedProject;
                $event = 'Globale project notities gewijzigd';
            } else {
                $notable = Sample::query()->lockForUpdate()->findOrFail($sample->id);
                abort_unless((int) $notable->project === (int) $lockedProject->id, 404);
                $this->ensureEditable->handle($lockedProject, KeyringType::Sample, $notable->id);
                $event = 'Project notitie voor monster gewijzigd, '.$notable->barcode;
            }

            $note = $notable->reportNote()->first();
            $previous = $note?->body ?? '';

            if ($previous === $html) {
                return $note;
            }

            if ($html === '') {
                $note->delete();
                $note = null;
            } elseif ($note === null) {
                $note = $notable->notes()->create(['kind' => NoteKind::Report, 'body' => $html, 'user_id' => $userId]);
            } else {
                $note->update(['body' => $html, 'user_id' => $userId, 'origin' => null]);
            }

            $this->changeTracker->changed(20, project: $lockedProject->id, event: $event, from: NoteHtml::toPlainText($previous), to: NoteHtml::toPlainText($html));
            $lockedProject->update(['last_edit' => now()->timestamp]);

            return $note;
        });
    }
}
