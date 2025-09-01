@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Documents</h1>
                <a href="{{ route('document.create') }}" class="btn btn-primary">Create New Document</a>
            </div>
            
            <div class="row">
                @forelse($documents as $document)
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $document->title }}</h5>
                            <p class="card-text text-muted">Created: {{ $document->created_at->format('M d, Y') }}</p>
                            <a href="{{ route('document.show', $document->id) }}" class="btn btn-primary btn-sm">View</a>
                            <button class="btn btn-secondary btn-sm">Edit</button>
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center">
                        <p>No documents found.</p>
                        <a href="{{ route('document.create') }}" class="btn btn-primary">Create Your First Document</a>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection