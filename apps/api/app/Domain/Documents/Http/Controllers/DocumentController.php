<?php

namespace App\Domain\Documents\Http\Controllers;

use App\Domain\Documents\Http\Requests\AcknowledgeDocumentRequest;
use App\Domain\Documents\Http\Requests\StoreDocumentRequest;
use App\Domain\Documents\Http\Resources\DocumentResource;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\DocumentStorageService;
use App\Domain\Employee\Models\Employment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentStorageService $documents)
    {
    }

    public function index(Request $request, Employment $employment)
    {
        $this->assertAccess($request, $employment);

        $documents = Document::where('documentable_type', Employment::class)
            ->where('documentable_id', $employment->id)
            ->with(['category', 'currentVersion', 'uploadedBy'])
            ->get();

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request, Employment $employment)
    {
        $this->assertAccess($request, $employment, requireBackOfficeForOthers: true);

        $document = $this->documents->store(
            $employment,
            $request->integer('category_id'),
            $request->string('title'),
            $request->file('file'),
        );

        return DocumentResource::make($document->load(['category', 'currentVersion']))->response()->setStatusCode(201);
    }

    public function download(Request $request, Employment $employment, Document $document)
    {
        $this->assertAccess($request, $employment);

        $version = $document->currentVersion;
        if (! $version) {
            throw new HttpException(404, 'No version available.');
        }

        return Response::download($this->documents->download($version));
    }

    public function acknowledge(AcknowledgeDocumentRequest $request, Employment $employment, Document $document)
    {
        $this->assertAccess($request, $employment);

        $ack = $this->documents->acknowledge(
            $document,
            $employment,
            $request->string('signature_type'),
            $request->input('signature_data'),
            $request->ip(),
        );

        return response()->json(['data' => $ack], 201);
    }

    private function assertAccess(Request $request, Employment $employment, bool $requireBackOfficeForOthers = false): void
    {
        $user = $request->user();
        $isSelf = $user->employment_id === $employment->id;

        if ($isSelf && ! $requireBackOfficeForOthers) {
            return;
        }

        if ($user->hasBackOfficeAccess()) {
            return;
        }

        if ($user->role === 'people_manager') {
            $managed = $employment->currentAssignment()->first()?->manager_employment_id === $user->employment_id;
            if ($managed) {
                return;
            }
        }

        if ($isSelf) {
            return;
        }

        throw new HttpException(403);
    }
}
