@extends('admin::layouts.admin')

@section('title', 'Edit Document')
@section('page-title', 'Edit Document')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-primary">Edit Document</h1>
        <p class="text-gray-600 mt-1">Update document details for ID: {{ substr($document['id'], 0, 12) }}{{ strlen($document['id']) > 12 ? '...' : '' }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.documents.index') }}" 
           class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Documents
        </a>
        <a href="{{ route('admin.documents.show', $document['id']) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-eye mr-2"></i>View Document
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

@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm border p-6">
    <form action="{{ route('admin.documents.update', $document['id']) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Driver Information (Read-only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Driver</label>
                <input type="text" value="{{ $driver['name'] ?? 'Unknown Driver' }} ({{ $driver['email'] ?? 'N/A' }})"
                       class="w-full px-4 py-2 border rounded-lg bg-gray-100" readonly>
            </div>

            <!-- Document Type -->
            <div>
                <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Document Type <span class="text-red-500">*</span>
                </label>
                <select name="document_type" id="document_type" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        required>
                    <option value="">Select document type</option>
                    @foreach ($documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('document_type', $document['document_type']) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('document_type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Name -->
            <div>
                <label for="document_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Document Name
                </label>
                <input type="text" name="document_name" id="document_name" 
                       value="{{ old('document_name', $document['document_name']) }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., Driver's License">
                @error('document_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Document Number -->
            <div>
                <label for="document_number" class="block text-sm font-medium text-gray-700 mb-2">
                    Document Number
                </label>
                <input type="text" name="document_number" id="document_number" 
                       value="{{ old('document_number', $document['document_number']) }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., ABC123456">
                @error('document_number')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issue Date -->
            <div>
                <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Issue Date
                </label>
                <input type="date" name="issue_date" id="issue_date" 
                       value="{{ old('issue_date', $document['issue_date'] ? \Carbon\Carbon::parse($document['issue_date'])->format('Y-m-d') : '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('issue_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Expiry Date -->
            <div>
                <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Expiry Date
                </label>
                <input type="date" name="expiry_date" id="expiry_date" 
                       value="{{ old('expiry_date', $document['expiry_date'] ? \Carbon\Carbon::parse($document['expiry_date'])->format('Y-m-d') : '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('expiry_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issuing Authority -->
            <div>
                <label for="issuing_authority" class="block text-sm font-medium text-gray-700 mb-2">
                    Issuing Authority
                </label>
                <input type="text" name="issuing_authority" id="issuing_authority" 
                       value="{{ old('issuing_authority', $document['issuing_authority']) }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., DMV">
                @error('issuing_authority')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issuing Country -->
            <div>
                <label for="issuing_country" class="block text-sm font-medium text-gray-700 mb-2">
                    Issuing Country
                </label>
                <input type="text" name="issuing_country" id="issuing_country" 
                       value="{{ old('issuing_country', $document['issuing_country']) }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., United States">
                @error('issuing_country')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Issuing State -->
            <div>
                <label for="issuing_state" class="block text-sm font-medium text-gray-700 mb-2">
                    Issuing State
                </label>
                <input type="text" name="issuing_state" id="issuing_state" 
                       value="{{ old('issuing_state', $document['issuing_state']) }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., California">
                @error('issuing_state')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Verification Status -->
            <div>
                <label for="verification_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Verification Status <span class="text-red-500">*</span>
                </label>
                <select name="verification_status" id="verification_status" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        required>
                    @foreach ($verificationStatuses as $key => $label)
                        <option value="{{ $key }}" {{ old('verification_status', $document['verification_status']) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('verification_status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Verification Notes -->
            <div class="md:col-span-2">
                <label for="verification_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Verification Notes
                </label>
                <textarea name="verification_notes" id="verification_notes" 
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                          rows="4">{{ old('verification_notes', $document['verification_notes']) }}</textarea>
                @error('verification_notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
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
                <i class="fas fa-save mr-2"></i>Update Document
            </button>
        </div>
    </form>
</div>
@endsection