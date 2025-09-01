@extends('layouts.app')

@section('title', 'Create Document')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Create New Document</h1>
            
            <div class="card mt-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('document.store') }}">
                        @csrf
                        
                        <div class="form-group mb-3">
                            <label for="title">Document Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Select Category</option>
                                <option value="general">General</option>
                                <option value="legal">Legal</option>
                                <option value="financial">Financial</option>
                                <option value="technical">Technical</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="content">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="tags">Tags (comma separated)</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="tag1, tag2, tag3">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public">
                            <label class="form-check-label" for="is_public">
                                Make this document public
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('document.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection