{{-- resources/views/page/admin/pages/create.blade.php --}}

@extends('admin::layouts.admin')

@section('title', 'Create Page')
@section('page-title', 'Create Page')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Rich Text Editor (TinyMCE - Self-hosted version) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
@endpush

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Create New Page</h1>
        <p class="text-gray-600 mt-1">Add a new static page to the system</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('pages.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Pages
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

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form action="{{ route('pages.store') }}" method="POST" id="pageForm">
        @csrf

        <div class="p-6 space-y-6">
            <!-- Core Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="lg:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Page Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        name="title"
                        id="title"
                        value="{{ old('title') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror"
                        placeholder="Enter the page title"
                        maxlength="255"
                        required>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">Maximum 255 characters</span>
                        <span class="text-xs text-gray-500" id="titleCount">0/255</span>
                    </div>
                    @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Page Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type"
                        id="type"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('type') border-red-500 @enderror"
                        required>
                        <option value="">Select Page Type</option>
                        @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('type')
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
                        <option value="{{ $key }}" {{ old('status', 'active') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('status')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Template -->
                <div>
                    <label for="template" class="block text-sm font-medium text-gray-700 mb-2">
                        Template
                    </label>
                    <select name="template"
                        id="template"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        @foreach($templates as $key => $label)
                        <option value="{{ $key }}" {{ old('template', 'default') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose the display template for this page</p>
                </div>

                <!-- Language -->
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                        Language
                    </label>
                    <select name="language"
                        id="language"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="en" {{ old('language', 'en') === 'en' ? 'selected' : '' }}>English</option>
                        <option value="es" {{ old('language') === 'es' ? 'selected' : '' }}>Spanish</option>
                        <option value="fr" {{ old('language') === 'fr' ? 'selected' : '' }}>French</option>
                        <option value="de" {{ old('language') === 'de' ? 'selected' : '' }}>German</option>
                    </select>
                </div>
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                    Page Content <span class="text-red-500">*</span>
                </label>
                <textarea name="content"
                    id="content"
                    rows="12"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('content') border-red-500 @enderror"
                    placeholder="Enter the page content"
                    required>{{ old('content') }}</textarea>
                @error('content')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- SEO Settings -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Meta Title -->
                    <div class="lg:col-span-2">
                        <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Meta Title
                        </label>
                        <input type="text"
                            name="meta_title"
                            id="meta_title"
                            value="{{ old('meta_title') }}"
                            maxlength="255"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Leave empty to use page title">
                        <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                    </div>

                    <!-- Meta Description -->
                    <div class="lg:col-span-2">
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Meta Description
                        </label>
                        <textarea name="meta_description"
                            id="meta_description"
                            rows="3"
                            maxlength="500"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Enter a brief description for search engines">{{ old('meta_description') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                    </div>

                    <!-- Meta Keywords -->
                    <div class="lg:col-span-2">
                        <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                            Meta Keywords
                        </label>
                        <input type="text"
                            name="meta_keywords"
                            id="meta_keywords"
                            value="{{ old('meta_keywords') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="keyword1, keyword2, keyword3">
                        <p class="text-xs text-gray-500 mt-1">Separate keywords with commas</p>
                    </div>

                    <!-- URL Slug -->
                    <div class="lg:col-span-2">
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                            URL Slug
                        </label>
                        <input type="text"
                            name="slug"
                            id="slug"
                            value="{{ old('slug') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="auto-generated-from-title">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from title</p>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Display Settings</h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Display Order -->
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">
                            Display Order
                        </label>
                        <input type="number"
                            name="display_order"
                            id="display_order"
                            value="{{ old('display_order', 0) }}"
                            min="0"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="0">
                        <p class="text-xs text-gray-500 mt-1">Lower numbers appear first (0 = auto-sort)</p>
                    </div>

                    <!-- Settings Checkboxes -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox"
                                name="is_featured"
                                id="is_featured"
                                value="1"
                                {{ old('is_featured') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="is_featured" class="ml-2 text-sm text-gray-700">
                                Featured Page
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox"
                                name="requires_auth"
                                id="requires_auth"
                                value="1"
                                {{ old('requires_auth') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="requires_auth" class="ml-2 text-sm text-gray-700">
                                Requires Authentication
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox"
                                name="show_in_footer"
                                id="show_in_footer"
                                value="1"
                                {{ old('show_in_footer') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="show_in_footer" class="ml-2 text-sm text-gray-700">
                                Show in Footer
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox"
                                name="show_in_header"
                                id="show_in_header"
                                value="1"
                                {{ old('show_in_header') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="show_in_header" class="ml-2 text-sm text-gray-700">
                                Show in Header Menu
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Custom CSS -->
                    <div>
                        <label for="custom_css" class="block text-sm font-medium text-gray-700 mb-2">
                            Custom CSS
                        </label>
                        <textarea name="custom_css"
                            id="custom_css"
                            rows="6"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono text-sm"
                            placeholder="/* Custom CSS for this page */">{{ old('custom_css') }}</textarea>
                    </div>

                    <!-- Custom JavaScript -->
                    <div>
                        <label for="custom_js" class="block text-sm font-medium text-gray-700 mb-2">
                            Custom JavaScript
                        </label>
                        <textarea name="custom_js"
                            id="custom_js"
                            rows="6"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono text-sm"
                            placeholder="// Custom JavaScript for this page">{{ old('custom_js') }}</textarea>
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
                            value="{{ old('published_at') }}"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to publish immediately</p>
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
                    <i class="fas fa-save mr-2"></i>Create Page
                </button>
                <button type="button"
                    onclick="saveDraft()"
                    class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-file-alt mr-2"></i>Save as Draft
                </button>
            </div>
            <a href="{{ route('pages.index') }}"
                class="text-gray-600 hover:text-gray-800 px-4 py-2">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize TinyMCE with free version (no API key required)
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            branding: false,
            promotion: false
        });

        // Character counter for title
        const titleInput = document.getElementById('title');
        const titleCount = document.getElementById('titleCount');

        titleInput.addEventListener('input', function() {
            titleCount.textContent = `${this.value.length}/255`;
        });

        // Auto-generate slug from title
        titleInput.addEventListener('blur', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value) {
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
        titleCount.textContent = `${titleInput.value.length}/255`;
    });

    function saveDraft() {
        // Set status to draft and submit
        document.getElementById('status').value = 'draft';
        document.getElementById('pageForm').submit();
    }

    // Form validation
    document.getElementById('pageForm').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value;
        const type = document.getElementById('type').value;
        const status = document.getElementById('status').value;

        if (!title || !type || !status) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }

        // Get content from TinyMCE
        const contentEditor = tinymce.get('content');
        if (contentEditor) {
            const contentValue = contentEditor.getContent();
            if (!contentValue.trim()) {
                e.preventDefault();
                alert('Please provide page content.');
                return false;
            }
        }

        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
    });
</script>
@endpush