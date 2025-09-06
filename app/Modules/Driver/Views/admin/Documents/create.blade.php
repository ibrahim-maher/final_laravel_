@extends('admin::layouts.admin')

@section('title', 'Upload New Document')
@section('page-title', 'Upload New Document')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Upload New Document</h1>
        <p class="text-gray-600 mt-1">Upload a new document for a driver</p>
    </div>
    <div>
        <a href="{{ route('admin.documents.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Documents
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border p-6">
    <form action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Driver Selection -->
            <div>
                <label for="driver_firebase_uid" class="block text-sm font-medium text-gray-700">Driver</label>
                <select name="driver_firebase_uid" id="driver_firebase_uid" 
                        class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('driver_firebase_uid') border-red-500 @enderror" required>
                    <option value="">Select a driver</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver['firebase_uid'] }}" {{ old('driver_firebase_uid', $driver ? $driver['firebase_uid'] : '') == $driver['firebase_uid'] ? 'selected' : '' }}>
                            {{ $driver['name'] }} ({{ $driver['email'] }})
                        </option>
                    @endforeach
                </select>
                @error('driver_firebase_uid')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Type -->
            <div>
                <label for="document_type" class="block text-sm font-medium text-gray-700">Document Type</label>
                <select name="document_type" id="document_type" 
                        class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('document_type') border-red-500 @enderror" required>
                    <option value="">Select document type</option>
                    @foreach ($documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('document_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('document_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Name -->
            <div>
                <label for="document_name" class="block text-sm font-medium text-gray-700">Document Name</label>
                <input type="text" name="document_name" id="document_name" value="{{ old('document_name') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('document_name') border-red-500 @enderror">
                @error('document_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Number -->
            <div>
                <label for="document_number" class="block text-sm font-medium text-gray-700">Document Number</label>
                <input type="text" name="document_number" id="document_number" value="{{ old('document_number') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('document_number') border-red-500 @enderror">
                @error('document_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issue Date -->
            <div>
                <label for="issue_date" class="block text-sm font-medium text-gray-700">Issue Date</label>
                <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('issue_date') border-red-500 @enderror">
                @error('issue_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Expiry Date -->
            <div>
                <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('expiry_date') border-red-500 @enderror">
                @error('expiry_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issuing Authority -->
            <div>
                <label for="issuing_authority" class="block text-sm font-medium text-gray-700">Issuing Authority</label>
                <input type="text" name="issuing_authority" id="issuing_authority" value="{{ old('issuing_authority') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('issuing_authority') border-red-500 @enderror">
                @error('issuing_authority')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issuing Country -->
            <div>
                <label for="issuing_country" class="block text-sm font-medium text-gray-700">Issuing Country</label>
                <input type="text" name="issuing_country" id="issuing_country" value="{{ old('issuing_country') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('issuing_country') border-red-500 @enderror">
                @error('issuing_country')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issuing State -->
            <div>
                <label for="issuing_state" class="block text-sm font-medium text-gray-700">Issuing State</label>
                <input type="text" name="issuing_state" id="issuing_state" value="{{ old('issuing_state') }}"
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('issuing_state') border-red-500 @enderror">
                @error('issuing_state')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Verification Status -->
            <div>
                <label for="verification_status" class="block text-sm font-medium text-gray-700">Verification Status</label>
                <select name="verification_status" id="verification_status" 
                        class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('verification_status') border-red-500 @enderror" required>
                    @foreach ($verificationStatuses as $key => $label)
                        <option value="{{ $key }}" {{ old('verification_status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('verification_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- File Upload -->
            <div class="md:col-span-2">
                <label for="file" class="block text-sm font-medium text-gray-700">Document File</label>
                <input type="file" name="file" id="file" 
                       class="mt-1 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('file') border-red-500 @enderror" 
                       accept="image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                <p class="mt-1 text-sm text-gray-500">Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB)</p>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.documents.index') }}" 
               class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-upload mr-2"></i>Upload Document
            </button>
        </div>
    </form>
</div>
@endsection