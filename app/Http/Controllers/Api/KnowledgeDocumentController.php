<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreKnowledgeDocumentRequest;
use App\Http\Resources\KnowledgeDocumentResource;
use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KnowledgeDocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if (! $request->user()->agency_id) {
            throw new HttpResponseException(response()->json(['message' => 'User is not assigned to an agency.'], 422));
        }

        $documents = KnowledgeDocument::query()
            ->where('agency_id', $request->user()->agency_id)
            ->latest()
            ->paginate(20);

        return KnowledgeDocumentResource::collection($documents);
    }

    public function store(StoreKnowledgeDocumentRequest $request): KnowledgeDocumentResource
    {
        $user = $request->user();

        if (! $user->agency_id) {
            throw new HttpResponseException(response()->json(['message' => 'User is not assigned to an agency.'], 422));
        }

        $document = KnowledgeDocument::query()->create([
            'agency_id' => $user->agency_id,
            'created_by_user_id' => $user->id,
            'title' => $request->string('title')->toString(),
            'content' => $request->string('content')->toString(),
            'status' => 'pending',
            'metadata' => $request->input('metadata'),
        ]);

        ProcessKnowledgeDocumentJob::dispatch($document->id)->onQueue('ai');

        return new KnowledgeDocumentResource($document);
    }
}
