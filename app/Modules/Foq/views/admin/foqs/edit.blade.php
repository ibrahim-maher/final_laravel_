{{-- resources/views/foq/admin/foqs/edit.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Edit FOQ')
@section('page-title', 'Edit FOQ')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Rich Text Editor (TinyMCE - Self-hosted version) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
@endpush

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Edit FOQ</h1>
        <p class="text-gray-600 mt-1">Update frequently offered question</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('foqs.show', $foq->id) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-eye mr-2"></i>View FOQ
        </a>
        <a href="{{ route('foqs.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to FOQs
        </a>
    </div>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <strong>Please fix the following errors:</strong>
        </div>
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form action="{{ route('foqs.update', $foq->id) }}" method="POST" id="foqForm">
        @csrf
        @method('PUT')
        
        <div class="p-6 space-y-6">
            <!-- Core Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Question -->
                <div class="lg:col-span-2">
                    <label for="question" class="block text-sm font-medium text-gray-700 mb-2">
                        Question <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="question" 
                           id="question"
                           value="{{ old('question', $foq->question) }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('question') border-red-500 @enderror"
                           placeholder="Enter the frequently asked question"
                           maxlength="500"
                           required>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">Maximum 500 characters</span>
                        <span class="text-xs text-gray-500" id="questionCount">0/500</span>
                    </div>
                    @error('question')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select name="category" 
                            id="category"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('category') border-red-500 @enderror"
                            required>
                        <option value="">Select Category</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $foq->category) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type" 
                            id="type"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('type') border-red-500 @enderror"
                            required>
                        <option value="">Select Type</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ old('type', $foq->type) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority -->
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                        Priority <span class="text-red-500">*</span>
                    </label>
                    <select name="priority" 
                            id="priority"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('priority') border-red-500 @enderror"
                            required>
                        <option value="">Select Priority</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}" {{ old('priority', $foq->priority) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('priority')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" 
                            id="status"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror"
                            required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $foq->status) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Answer -->
            <div>
                <label for="answer" class="block text-sm font-medium text-gray-700 mb-2">
                    Answer <span class="text-red-500">*</span>
                </label>
                <textarea name="answer" 
                          id="answer"
                          rows="8"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('answer') border-red-500 @enderror"
                          placeholder="Enter the detailed answer to this question"
                          required>{{ old('answer', $foq->answer) }}</textarea>
                @error('answer')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Additional Settings -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Settings</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Language -->
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                            Language
                        </label>
                        <select name="language" 
                                id="language"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="en" {{ old('language', $foq->language) === 'en' ? 'selected' : '' }}>English</option>
                            <option value="es" {{ old('language', $foq->language) === 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ old('language', $foq->language) === 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ old('language', $foq->language) === 'de' ? 'selected' : '' }}>German</option>
                        </select>
                    </div>

                    <!-- Display Order -->
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">
                            Display Order
                        </label>
                        <input type="number" 
                               name="display_order" 
                               id="display_order"
                               value="{{ old('display_order', $foq->display_order ?? 0) }}"
                               min="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="0">
                        <p class="text-xs text-gray-500 mt-1">Lower numbers appear first (0 = auto-sort)</p>
                    </div>

                    <!-- URL Slug -->
                    <div class="lg:col-span-2">
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                            URL Slug
                        </label>
                        <input type="text" 
                               name="slug" 
                               id="slug"
                               value="{{ old('slug', $foq->slug) }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="auto-generated-from-question">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from question</p>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_featured" 
                               id="is_featured"
                               value="1"
                               {{ old('is_featured', $foq->is_featured) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <label for="is_featured" class="ml-2 text-sm text-gray-700">
                            Featured FOQ
                        </label>
                    </div>
                </div>
            </div>

            <!-- Published Date -->
            <div class="border-t pt-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">
                            Publish Date
                        </label>
                        <input type="datetime-local" 
                               name="published_at" 
                               id="published_at"
                               value="{{ old('published_at', $foq->published_at ? $foq->published_at->format('Y-m-d\TH:i') : '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to publish immediately</p>
                    </div>
                </div>
            </div>

            <!-- FOQ Information -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">FOQ Information</h3>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">FOQ ID</dt>
                            <dd class="text-gray-900">{{ $foq->id }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Views</dt>
                            <dd class="text-gray-900">{{ number_format($foq->view_count ?? 0) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Helpful Votes</dt>
                            <dd class="text-gray-900">{{ $foq->helpful_count ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Created</dt>
                            <dd class="text-gray-900">{{ $foq->created_at ? $foq->created_at->format('M j, Y') : 'Unknown' }}</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-gray-50 px-6 py-3 flex justify-between items-center">
            <div class="flex gap-3">
                <button type="submit" 
                        class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                        id="submitBtn">
                    <i class="fas fa-save mr-2"></i>Update FOQ
                </button>
                <button type="button" 
                        onclick="saveDraft()"
                        class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-file-alt mr-2"></i>Save as Draft
                </button>
                <a href="{{ route('foqs.duplicate', $foq->id) }}" 
                   class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-copy mr-2"></i>Duplicate
                </a>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('foqs.show', $foq->id) }}" 
                   class="text-blue-600 hover:text-blue-800 px-4 py-2">
                    View FOQ
                </a>
                <a href="{{ route('foqs.index') }}" 
                   class="text-gray-600 hover:text-gray-800 px-4 py-2">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize TinyMCE with free version (no API key required)
        tinymce.init({
            selector: '#answer',
            height: 300,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            branding: false, // Remove "Powered by TinyMCE"
            promotion: false // Remove upgrade promotion
        });

        // Character counter for question
        const questionInput = document.getElementById('question');
        const questionCount = document.getElementById('questionCount');

        function updateQuestionCount() {
            questionCount.textContent = `${questionInput.value.length}/500`;
        }

        questionInput.addEventListener('input', updateQuestionCount);

        // Auto-generate slug from question (only if slug is empty)
        questionInput.addEventListener('blur', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value.trim()) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                slugInput.value = slug.substring(0, 100);
            }
        });

        // Initialize character count
        updateQuestionCount();
    });

    function saveDraft() {
        // Set status to draft and submit
        document.getElementById('status').value = 'draft';
        document.getElementById('foqForm').submit();
    }

    // Form validation
    document.getElementById('foqForm').addEventListener('submit', function(e) {
        const question = document.getElementById('question').value;
        const category = document.getElementById('category').value;
        const type = document.getElementById('type').value;
        const priority = document.getElementById('priority').value;
        const status = document.getElementById('status').value;

        if (!question || !category || !type || !priority || !status) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }

        // Get content from TinyMCE
        const answerContent = tinymce.get('answer').getContent();
        if (!answerContent.trim()) {
            e.preventDefault();
            alert('Please provide an answer.');
            return false;
        }

        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
    });
</script>
@endpush