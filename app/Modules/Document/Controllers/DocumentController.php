<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function index()
    {
        $documents = $this->documentService->getAllDocuments();
        return view('document.index', compact('documents'));
    }

    public function create()
    {
        return view('document.create');
    }

    public function store(Request $request)
    {
        $document = $this->documentService->createDocument($request->all());
        return redirect()->route('document.index')->with('success', 'Document created successfully');
    }

    public function show($id)
    {
        $document = $this->documentService->getDocumentById($id);
        return view('document.show', compact('document'));
    }
}