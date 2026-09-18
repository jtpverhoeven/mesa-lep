<?php

namespace App\Http\Controllers;

use App\Actions\SampleBuffers\CommitSampleBuffers;
use App\Actions\SampleBuffers\UpdateSampleBuffers;
use App\Http\Requests\StoreSampleBufferRequest;
use App\Models\Client;
use App\Models\SampleBuffer;
use App\Models\SampleProcedure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SampleBufferController extends Controller
{
    public function index(): View
    {
        return view('sample-buffers.index', ['initialTab' => 'normal']);
    }

    public function tht(): View
    {
        return view('sample-buffers.index', ['initialTab' => 'staged_tht']);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tab' => ['nullable', 'in:normal,tht,legionella,rodac,staged_tht'],
            'sort' => ['nullable', 'in:sampling_date,client,project_name,project,tht_date,tht_code'],
            'direction' => ['nullable', 'in:asc,desc'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $tab = $validated['tab'] ?? 'normal';
        abort_unless(
            $tab === 'staged_tht'
                ? $request->user()->can('shelf-life-studies.view')
                : $request->user()->can('portal.access'),
            403,
        );
        $sort = $validated['sort'] ?? ($tab === 'staged_tht' ? 'tht_date' : 'sampling_date');
        $direction = $validated['direction'] ?? 'asc';
        $query = SampleBuffer::query()->with(['clientRecord:id,name', 'samplingProcedure:id,name']);
        $this->forTab($query, $tab);

        if (! empty($validated['search'])) {
            $terms = preg_split('/\s+/', trim($validated['search']));
            foreach ($terms as $term) {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('project', 'ilike', '%'.$term.'%')
                        ->orWhere('project_name', 'ilike', '%'.$term.'%')
                        ->orWhere('sample_name', 'ilike', '%'.$term.'%')
                        ->orWhere('sample_details', 'ilike', '%'.$term.'%')
                        ->orWhereHas('clientRecord', fn (Builder $clientQuery) => $clientQuery->where('name', 'ilike', '%'.$term.'%'));
                });
            }
        }

        if ($sort === 'client') {
            $query->orderBy(
                Client::query()->select('name')->whereColumn('clients.id', 'samplebuffers.client'),
                $direction,
            );
        } elseif ($sort === 'sampling_date') {
            $query->orderByRaw("case when sampling_date ~ '^\\d{2}-\\d{2}-\\d{4}$' then to_date(sampling_date, 'DD-MM-YYYY') end {$direction} nulls last");
        } else {
            $query->orderBy($sort, $direction);
        }

        return response()->json([
            'data' => $query->orderBy('id')->get()->map(fn (SampleBuffer $buffer): array => $this->serialize($buffer))->values(),
            'counts' => $this->counts(),
            'sampling_methods' => SampleProcedure::query()->where('active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function batch(
        StoreSampleBufferRequest $request,
        CommitSampleBuffers $commitSampleBuffers,
        UpdateSampleBuffers $updateSampleBuffers,
    ): JsonResponse {
        $data = $request->validated();
        $values = Arr::only($data, ['date', 'sampling_method', 'storage', 'receive_date', 'receive_time', 'meta']);
        $selectedQuery = SampleBuffer::query()->whereKey($data['ids']);
        $this->forTab($selectedQuery, $data['tab']);
        $ids = $selectedQuery->pluck('id')->all();

        if ($data['action'] === 'commit') {
            if ($data['tab'] === 'tht') {
                $count = $updateSampleBuffers->authorizeTht($ids, $values);
                $message = $count.' THT-monster(s) naar de THT-lijst verplaatst.';
            } else {
                $samples = $commitSampleBuffers->handle($ids, $request->user()->id, [
                    'receive_date' => $values['receive_date'] ?? now()->format('Y-m-d'),
                    'receive_time' => $values['receive_time'] ?? now()->format('H:i'),
                ]);
                $count = $samples->count();
                $message = $count.' monster(s) aangemeld.';
            }
        } else {
            $count = $updateSampleBuffers->handle($ids, $data['action'], $values);
            $message = $count.' bufferregel(s) bijgewerkt.';
        }

        return response()->json(['message' => $message, 'count' => $count]);
    }

    private function forTab(Builder $query, string $tab): void
    {
        if ($tab === 'staged_tht') {
            $query->where('authorized', 1)->where('tht', 1);

            return;
        }

        $query->where('authorized', 0);

        match ($tab) {
            'tht' => $query->where('tht', 1),
            'legionella' => $query->where('tht', 0)->where('sample_research_type', '3'),
            'rodac' => $query->where('tht', 0)->where('sample_research_type', '2'),
            default => $query->where('tht', 0)->where(fn (Builder $query) => $query->whereNull('sample_research_type')->orWhereNotIn('sample_research_type', ['2', '3'])),
        };
    }

    private function counts(): array
    {
        return [
            'normal' => SampleBuffer::query()->where('authorized', 0)->where('tht', 0)->where(fn (Builder $query) => $query->whereNull('sample_research_type')->orWhereNotIn('sample_research_type', ['2', '3']))->count(),
            'tht' => SampleBuffer::query()->where('authorized', 0)->where('tht', 1)->count(),
            'legionella' => SampleBuffer::query()->where('authorized', 0)->where('tht', 0)->where('sample_research_type', '3')->count(),
            'rodac' => SampleBuffer::query()->where('authorized', 0)->where('tht', 0)->where('sample_research_type', '2')->count(),
            'staged_tht' => SampleBuffer::query()->where('authorized', 1)->where('tht', 1)->count(),
        ];
    }

    private function serialize(SampleBuffer $buffer): array
    {
        $properties = collect($buffer->sample_properties ?? [])->keyBy('property_name');
        $thtDate = $buffer->tht_date?->toImmutable();

        return [
            'id' => $buffer->id,
            'project' => $buffer->project,
            'source' => $buffer->source,
            'client_name' => $buffer->clientRecord?->name,
            'project_name' => $buffer->project_name,
            'portal_follow_no' => $buffer->portal_follow_no,
            'sampling_date' => $buffer->sampling_date,
            'sampling_method' => $buffer->sampling_method,
            'sampling_method_name' => $buffer->samplingProcedure?->name,
            'sample_name' => $buffer->sample_name,
            'sample_details' => $buffer->sample_details,
            'tht_date' => $thtDate?->format('d-m-Y'),
            'tht_date_input' => $thtDate?->format('Y-m-d'),
            'tht_day_type' => $thtDate ? match ($thtDate->dayOfWeekIso) {
                6 => 'saturday', 7 => 'sunday', default => null
            } : null,
            'tht_code' => $buffer->tht_code,
            'receive_date' => $buffer->receive_date,
            'receive_time' => $buffer->receive_time,
            'misc_directions' => $buffer->misc_directions,
            'meta' => $buffer->meta ?? [],
            'water_type' => $properties->get('waterType')['value'] ?? null,
            'matrix_type' => $properties->get('matrixType')['value'] ?? null,
            'room' => $properties->get('rodacRoom')['value'] ?? null,
        ];
    }
}
