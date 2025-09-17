@extends('admin::layouts.admin')

@section('title', 'Create Tax Setting')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title mb-1">Create Tax Setting</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('tax-settings.index') }}">Tax Settings</a></li>
                            <li class="breadcrumb-item active">Create</li>
                        </ol>
                    </nav>
                </div>
                <div class="page-title-right">
                    <a href="{{ route('tax-settings.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Tax Setting Information
                    </h5>
                </div>

                <form action="{{ route('tax-settings.store') }}" method="POST" id="taxSettingForm" novalidate>
                    @csrf

                    <div class="card-body">
                        <!-- Error Display -->
                        @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Please correct the following errors:</strong>
                            </div>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        <!-- Step Progress Indicator -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="step-item active">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Basic Info</div>
                                </div>
                                <div class="step-line"></div>
                                <div class="step-item">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Tax Config</div>
                                </div>
                                <div class="step-line"></div>
                                <div class="step-item">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Applicability</div>
                                </div>
                                <div class="step-line"></div>
                                <div class="step-item">
                                    <div class="step-number">4</div>
                                    <div class="step-label">Settings</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 1: Basic Information -->
                        <div class="form-step" id="step-1">
                            <div class="section-header">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Basic Information
                                </h6>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}"
                                            placeholder="Enter tax setting name" required>
                                        <label for="name">Tax Setting Name <span class="text-danger">*</span></label>
                                        @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="number" class="form-control @error('priority_order') is-invalid @enderror"
                                            id="priority_order" name="priority_order" value="{{ old('priority_order', 1) }}"
                                            min="1" placeholder="1">
                                        <label for="priority_order">Priority Order</label>
                                        @error('priority_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Lower numbers have higher priority</div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                            id="description" name="description"
                                            placeholder="Enter tax setting description" style="height: 100px">{{ old('description') }}</textarea>
                                        <label for="description">Description</label>
                                        @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Tax Configuration -->
                        <div class="form-step" id="step-2" style="display: none;">
                            <div class="section-header">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-calculator me-2"></i>Tax Configuration
                                </h6>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select @error('tax_type') is-invalid @enderror"
                                            id="tax_type" name="tax_type" required>
                                            <option value="">Select Tax Type</option>
                                            @foreach($taxTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('tax_type') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <label for="tax_type">Tax Type <span class="text-danger">*</span></label>
                                        @error('tax_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select @error('calculation_method') is-invalid @enderror"
                                            id="calculation_method" name="calculation_method" required>
                                            <option value="">Select Method</option>
                                            @foreach($calculationMethods as $key => $label)
                                            <option value="{{ $key }}" {{ old('calculation_method') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <label for="calculation_method">Calculation Method <span class="text-danger">*</span></label>
                                        @error('calculation_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Tax Amount Fields -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="tax-field" id="rate_field" style="display: none;">
                                        <div class="form-floating">
                                            <input type="number" class="form-control @error('rate') is-invalid @enderror"
                                                id="rate" name="rate" value="{{ old('rate') }}"
                                                min="0" max="100" step="0.01" placeholder="0.00">
                                            <label for="rate">Tax Rate (%) <span class="text-danger rate-required" style="display: none;">*</span></label>
                                            @error('rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="tax-field" id="fixed_amount_field" style="display: none;">
                                        <div class="form-floating">
                                            <input type="number" class="form-control @error('fixed_amount') is-invalid @enderror"
                                                id="fixed_amount" name="fixed_amount" value="{{ old('fixed_amount') }}"
                                                min="0" step="0.01" placeholder="0.00">
                                            <label for="fixed_amount">Fixed Amount ($) <span class="text-danger fixed-required" style="display: none;">*</span></label>
                                            @error('fixed_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Limits -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control @error('minimum_taxable_amount') is-invalid @enderror"
                                            id="minimum_taxable_amount" name="minimum_taxable_amount"
                                            value="{{ old('minimum_taxable_amount') }}" min="0" step="0.01" placeholder="0.00">
                                        <label for="minimum_taxable_amount">Minimum Taxable Amount ($)</label>
                                        @error('minimum_taxable_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control @error('maximum_tax_amount') is-invalid @enderror"
                                            id="maximum_tax_amount" name="maximum_tax_amount"
                                            value="{{ old('maximum_tax_amount') }}" min="0" step="0.01" placeholder="No limit">
                                        <label for="maximum_tax_amount">Maximum Tax Amount ($)</label>
                                        @error('maximum_tax_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Leave empty for no limit</div>
                                </div>
                            </div>

                            <!-- Tax Preview Card -->
                            <div class="mt-4">
                                <div class="card border-info bg-light">
                                    <div class="card-header bg-info text-white py-2">
                                        <h6 class="mb-0">
                                            <i class="fas fa-eye me-2"></i>Tax Calculation Preview
                                        </h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="number" class="form-control" id="preview_amount"
                                                        value="100" min="0" step="0.01" placeholder="100.00">
                                                    <label for="preview_amount">Test Amount ($)</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-info w-100" id="calculatePreview">
                                                    <i class="fas fa-calculator me-2"></i>Calculate
                                                </button>
                                            </div>
                                            <div class="col-md-5">
                                                <div id="previewResult" class="result-card" style="display: none;">
                                                    <div class="row g-2 text-center">
                                                        <div class="col-6">
                                                            <div class="result-item">
                                                                <div class="result-label">Tax Amount</div>
                                                                <div class="result-value text-warning" id="taxAmount">$0.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="result-item">
                                                                <div class="result-label">Total</div>
                                                                <div class="result-value text-success" id="totalAmount">$0.00</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Applicability -->
                        <div class="form-step" id="step-3" style="display: none;">
                            <div class="section-header">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-filter me-2"></i>Applicability Rules
                                </h6>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <select class="form-select @error('applicable_to') is-invalid @enderror"
                                            id="applicable_to" name="applicable_to" required>
                                            <option value="">Select Application Scope</option>
                                            @foreach($applicableToOptions as $key => $label)
                                            <option value="{{ $key }}" {{ old('applicable_to') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <label for="applicable_to">Applicable To <span class="text-danger">*</span></label>
                                        @error('applicable_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Zone Configuration -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('applicable_zones') is-invalid @enderror"
                                            id="applicable_zones" name="applicable_zones"
                                            placeholder="zone1, zone2, zone3" style="height: 100px">{{ is_array(old('applicable_zones')) ? implode(', ', old('applicable_zones')) : old('applicable_zones') }}</textarea>
                                        <label for="applicable_zones">Applicable Zones</label>
                                        @error('applicable_zones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Leave empty to apply to all zones</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('excluded_zones') is-invalid @enderror"
                                            id="excluded_zones" name="excluded_zones"
                                            placeholder="zone4, zone5" style="height: 100px">{{ is_array(old('excluded_zones')) ? implode(', ', old('excluded_zones')) : old('excluded_zones') }}</textarea>
                                        <label for="excluded_zones">Excluded Zones</label>
                                        @error('excluded_zones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Vehicle Type Configuration -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('applicable_vehicle_types') is-invalid @enderror"
                                            id="applicable_vehicle_types" name="applicable_vehicle_types"
                                            placeholder="car, bike, truck" style="height: 100px">{{ is_array(old('applicable_vehicle_types')) ? implode(', ', old('applicable_vehicle_types')) : old('applicable_vehicle_types') }}</textarea>
                                        <label for="applicable_vehicle_types">Applicable Vehicle Types</label>
                                        @error('applicable_vehicle_types')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Leave empty to apply to all vehicle types</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('excluded_vehicle_types') is-invalid @enderror"
                                            id="excluded_vehicle_types" name="excluded_vehicle_types"
                                            placeholder="luxury, premium" style="height: 100px">{{ is_array(old('excluded_vehicle_types')) ? implode(', ', old('excluded_vehicle_types')) : old('excluded_vehicle_types') }}</textarea>
                                        <label for="excluded_vehicle_types">Excluded Vehicle Types</label>
                                        @error('excluded_vehicle_types')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Service Configuration -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('applicable_services') is-invalid @enderror"
                                            id="applicable_services" name="applicable_services"
                                            placeholder="ride, delivery, rental" style="height: 100px">{{ is_array(old('applicable_services')) ? implode(', ', old('applicable_services')) : old('applicable_services') }}</textarea>
                                        <label for="applicable_services">Applicable Services</label>
                                        @error('applicable_services')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Leave empty to apply to all services</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control @error('excluded_services') is-invalid @enderror"
                                            id="excluded_services" name="excluded_services"
                                            placeholder="rush, premium" style="height: 100px">{{ is_array(old('excluded_services')) ? implode(', ', old('excluded_services')) : old('excluded_services') }}</textarea>
                                        <label for="excluded_services">Excluded Services</label>
                                        @error('excluded_services')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Settings -->
                        <div class="form-step" id="step-4" style="display: none;">
                            <div class="section-header">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-cog me-2"></i>Date Range & Settings
                                </h6>
                            </div>

                            <!-- Date Range -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror"
                                            id="starts_at" name="starts_at" value="{{ old('starts_at') }}">
                                        <label for="starts_at">Start Date</label>
                                        @error('starts_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Leave empty to start immediately</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror"
                                            id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                                        <label for="expires_at">Expiry Date</label>
                                        @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Leave empty for no expiry</div>
                                </div>
                            </div>

                            <!-- Settings Toggles -->
                            <div class="mt-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="setting-card">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_inclusive"
                                                    name="is_inclusive" value="1" {{ old('is_inclusive') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_inclusive">
                                                    <strong>Tax Inclusive</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">Tax is included in the base amount</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="setting-card">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_active"
                                                    name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    <strong>Active</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">Tax setting is active and ready to use</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="setting-card">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="sync_immediately"
                                                    name="sync_immediately" value="1" {{ old('sync_immediately') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="sync_immediately">
                                                    <strong>Immediate Firebase Sync</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">Sync to Firebase immediately, otherwise queued</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" id="prevStep" style="display: none;">
                                    <i class="fas fa-chevron-left me-2"></i>Previous
                                </button>
                            </div>

                            <div class="step-counter">
                                <span class="current-step">1</span> of <span class="total-steps">4</span>
                            </div>

                            <div>
                                <button type="button" class="btn btn-primary" id="nextStep">
                                    Next<i class="fas fa-chevron-right ms-2"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info" id="previewBtn" style="display: none;">
                                    <i class="fas fa-eye me-2"></i>Preview
                                </button>
                                <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                                    <i class="fas fa-save me-2"></i>Create Tax Setting
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Tax Setting Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
                <button type="button" class="btn btn-success" onclick="$('#taxSettingForm').submit();">
                    <i class="fas fa-check me-2"></i>Confirm & Create
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root {
        --primary-color: #4f46e5;
        --success-color: #10b981;
        --info-color: #06b6d4;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --border-radius: 8px;
    }

    .page-title-box {
        margin-bottom: 1.5rem;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .card {
        border-radius: var(--border-radius);
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
    }

    .card-header {
        border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Step Indicator */
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        flex: 1;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .step-item.active .step-number {
        background-color: var(--primary-color);
        color: white;
    }

    .step-item.completed .step-number {
        background-color: var(--success-color);
        color: white;
    }

    .step-label {
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 500;
    }

    .step-item.active .step-label {
        color: var(--primary-color);
        font-weight: 600;
    }

    .step-line {
        height: 2px;
        background-color: #e5e7eb;
        flex: 1;
        margin: 0 20px;
        margin-top: 20px;
    }

    .step-item.completed~.step-line {
        background-color: var(--success-color);
    }

    /* Form Styling */
    .form-floating>.form-control,
    .form-floating>.form-select {
        border-radius: var(--border-radius);
        border: 1.5px solid #d1d5db;
        transition: all 0.3s ease;
    }

    .form-floating>.form-control:focus,
    .form-floating>.form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
    }

    .form-floating>label {
        color: #6b7280;
        font-weight: 500;
    }

    .section-header {
        border-bottom: 2px solid #f3f4f6;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }

    /* Tax Preview Styling */
    .result-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: var(--border-radius);
        padding: 1rem;
        border: 1px solid #e2e8f0;
    }

    .result-item {
        padding: 0.5rem;
        border-radius: 4px;
        background: white;
        border: 1px solid #e2e8f0;
    }

    .result-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .result-value {
        font-size: 1.25rem;
        font-weight: 700;
        margin-top: 0.25rem;
    }

    /* Setting Cards */
    .setting-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius);
        padding: 1rem;
        transition: all 0.3s ease;
    }

    .setting-card:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .form-check-input {
        width: 2rem;
        height: 1rem;
        border-radius: 1rem;
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    /* Step Counter */
    .step-counter {
        background: #f3f4f6;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        color: #374151;
    }

    /* Animation Effects */
    .form-step {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Tax Field Animation */
    .tax-field {
        transition: all 0.3s ease-in-out;
    }

    .tax-field.show {
        animation: slideIn 0.3s ease-in-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Button Styling */
    .btn {
        border-radius: var(--border-radius);
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-success {
        background-color: var(--success-color);
        border-color: var(--success-color);
    }

    .btn-info {
        background-color: var(--info-color);
        border-color: var(--info-color);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .step-item {
            margin-bottom: 1rem;
        }

        .step-line {
            display: none;
        }

        .card-footer .d-flex {
            flex-direction: column;
            gap: 1rem;
        }

        .step-counter {
            order: -1;
        }
    }

    /* Alert Improvements */
    .alert {
        border-radius: var(--border-radius);
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .alert-danger {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: #991b1b;
        border-left: 4px solid var(--danger-color);
    }

    /* Form Text Improvements */
    .form-text {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    /* Invalid Feedback */
    .invalid-feedback {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
                let currentStep = 1;
                const totalSteps = 4;

                // Step Navigation
                function showStep(step) {
                    // Hide all steps
                    $('.form-step').hide();

                    // Show current step
                    $(`#step-${step}`).show();

                    // Update step indicators
                    $('.step-item').removeClass('active completed');
                    for (let i = 1; i <= step; i++) {
                        if (i === step) {
                            $(`.step-item:nth-child(${i * 2 - 1})`).addClass('active');
                        } else if (i < step) {
                            $(`.step-item:nth-child(${i * 2 - 1})`).addClass('completed');
                        }
                    }

                    // Update step counter
                    $('.current-step').text(step);

                    // Show/hide navigation buttons
                    if (step === 1) {
                        $('#prevStep').hide();
                    } else {
                        $('#prevStep').show();
                    }

                    if (step === totalSteps) {
                        $('#nextStep').hide();
                        $('#previewBtn, #submitBtn').show();
                    } else {
                        $('#nextStep').show();
                        $('#previewBtn, #submitBtn').hide();
                    }
                }

                // Next step
                $('#nextStep').click(function() {
                    if (validateCurrentStep()) {
                        if (currentStep < totalSteps) {
                            currentStep++;
                            showStep(currentStep);
                        }
                    }
                });

                // Previous step
                $('#prevStep').click(function() {
                    if (currentStep > 1) {
                        currentStep--;
                        showStep(currentStep);
                    }
                });

                // Step validation
                function validateCurrentStep() {
                    let isValid = true;
                    const currentStepElement = $(`#step-${currentStep}`);

                    // Clear previous validation
                    currentStepElement.find('.is-invalid').removeClass('is-invalid');

                    // Validate required fields in current step
                    currentStepElement.find('input[required], select[required]').each(function() {
                        if (!$(this).val()) {
                            $(this).addClass('is-invalid');
                            isValid = false;
                        }
                    });

                    // Special validation for tax type dependent fields
                    if (currentStep === 2) {
                        const taxType = $('#tax_type').val();

                        if (taxType === 'percentage' || taxType === 'hybrid') {
                            if (!$('#rate').val()) {
                                $('#rate').addClass('is-invalid');
                                isValid = false;
                            }
                        }

                        if (taxType === 'fixed' || taxType === 'hybrid') {
                            if (!$('#fixed_amount').val()) {
                                $('#fixed_amount').addClass('is-invalid');
                                isValid = false;
                            }
                        }
                    }

                    if (!isValid) {
                        // Show error message
                        showNotification('Please fill in all required fields', 'error');
                    }

                    return isValid;
                }

                // Handle tax type change
                $('#tax_type').change(function() {
                    const taxType = $(this).val();
                    const rateField = $('#rate_field');
                    const fixedField = $('#fixed_amount_field');
                    const rateRequired = $('.rate-required');
                    const fixedRequired = $('.fixed-required');

                    // Hide all fields first
                    rateField.hide().removeClass('show');
                    fixedField.hide().removeClass('show');
                    rateRequired.hide();
                    fixedRequired.hide();

                    // Remove required attributes
                    $('#rate, #fixed_amount').removeAttr('required');

                    // Show relevant fields based on tax type
                    setTimeout(() => {
                        switch (taxType) {
                            case 'percentage':
                                rateField.show().addClass('show');
                                rateRequired.show();
                                $('#rate').attr('required', 'required');
                                break;
                            case 'fixed':
                                fixedField.show().addClass('show');
                                fixedRequired.show();
                                $('#fixed_amount').attr('required', 'required');
                                break;
                            case 'hybrid':
                                rateField.show().addClass('show');
                                fixedField.show().addClass('show');
                                rateRequired.show();
                                fixedRequired.show();
                                $('#rate, #fixed_amount').attr('required', 'required');
                                break;
                        }
                    }, 50);
                });

                // Trigger change event on page load
                $('#tax_type').trigger('change');

                // Date validation
                $('#starts_at').change(function() {
                    const startDate = $(this).val();
                    if (startDate) {
                        $('#expires_at').attr('min', startDate);
                    }
                });

                // Form submission preparation
                $('#taxSettingForm').submit(function() {
                    const arrayFields = [
                        'applicable_zones', 'excluded_zones',
                        'applicable_vehicle_types', 'excluded_vehicle_types',
                        'applicable_services', 'excluded_services'
                    ];

                    arrayFields.forEach(function(field) {
                        const textarea = $(`#${field}`);
                        const value = textarea.val().trim();
                        if (value) {
                            const array = value.split(',').map(item => item.trim()).filter(item => item);
                            // Create a hidden input with JSON data
                            $('<input>').attr({
                                type: 'hidden',
                                name: field,
                                value: JSON.stringify(array)
                            }).appendTo('#taxSettingForm');
                            // Clear the textarea to avoid double submission
                            textarea.attr('name', '');
                        }
                    });
                });

                // Calculate tax preview
                $('#calculatePreview').click(function() {
                            const amount = parseFloat($('#preview_amount').val()) || 0;
                            const taxType = $('#tax_type').val();
                            const rate = parseFloat($('#rate').val()) || 0;
                            const fixedAmount = parseFloat($('#fixed_amount').val()) || 0;
                            const minAmount = parseFloat($('#minimum_taxable_amount').val()) || 0;
                            const maxTax = parseFloat($('#maximum_tax_amount').val()) || null;

                            if (!taxType) {
                                showNotification('Please select a tax type first', 'warning');
                                return;
                            }

                            if (amount < minAmount) {
                                $('#previewResult').removeClass('alert-info').addClass('alert-warning').show();
                                $('#taxAmount').text('$0.00').parent().find('.result-label').text('Tax Amount (Below Minimum)');
                                $('#totalAmount').text(' + amount.toFixed(2));
                                    return;
                                }

                                let taxAmount = 0;

                                switch (taxType) {
                                    case 'percentage':
                                        if (!rate) {
                                            showNotification('Please enter tax rate', 'warning');
                                            return;
                                        }
                                        taxAmount = (amount * rate) / 100;
                                        break;
                                    case 'fixed':
                                        if (!fixedAmount) {
                                            showNotification('Please enter fixed amount', 'warning');
                                            return;
                                        }
                                        taxAmount = fixedAmount;
                                        break;
                                    case 'hybrid':
                                        if (!rate || !fixedAmount) {
                                            showNotification('Please enter both rate and fixed amount', 'warning');
                                            return;
                                        }
                                        taxAmount = (amount * rate) / 100 + fixedAmount;
                                        break;
                                }

                                // Apply maximum limit if set
                                if (maxTax && taxAmount > maxTax) {
                                    taxAmount = maxTax;
                                }

                                const total = amount + taxAmount;

                                $('#previewResult').removeClass('alert-warning').show();
                                $('#taxAmount').text(' + taxAmount.toFixed(2)).parent().find('.result - label ').text('
                                    Tax Amount ');
                                    $('#totalAmount').text(' + total.toFixed(2));
                                    });

                                // Auto-calculate when values change
                                $('#rate, #fixed_amount, #minimum_taxable_amount, #maximum_tax_amount').on('input', function() {
                                    if ($('#previewResult').is(':visible')) {
                                        $('#calculatePreview').click();
                                    }
                                });

                                // Preview modal
                                $('#previewBtn').click(function() {
                                    if (!validateCurrentStep()) {
                                        return;
                                    }

                                    const formData = new FormData($('#taxSettingForm')[0]);
                                    let previewHtml = '';

                                    // Basic Info Section
                                    previewHtml += `
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="preview-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                        <div class="preview-item">
                            <strong>Name:</strong> ${formData.get('name') || 'N/A'}
                        </div>
                        <div class="preview-item">
                            <strong>Description:</strong> ${formData.get('description') || 'None'}
                        </div>
                        <div class="preview-item">
                            <strong>Priority:</strong> ${formData.get('priority_order') || '1'}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="preview-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-calculator me-2"></i>Tax Configuration</h6>
                        <div class="preview-item">
                            <strong>Type:</strong> ${$('#tax_type option:selected').text() || 'N/A'}
                        </div>
                        <div class="preview-item">
                            <strong>Method:</strong> ${$('#calculation_method option:selected').text() || 'N/A'}
                        </div>
                        ${formData.get('rate') ? `<div class="preview-item"><strong>Rate:</strong> ${formData.get('rate')}%</div>` : ''}
                        ${formData.get('fixed_amount') ? `<div class="preview-item"><strong>Fixed Amount:</strong> ${formData.get('fixed_amount')}</div>` : ''}
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mt-3">
                <div class="col-12">
                    <div class="preview-section">
                        <h6 class="text-primary mb-3"><i class="fas fa-cog me-2"></i>Settings</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="preview-item">
                                    <strong>Applicable To:</strong> ${$('#applicable_to option:selected').text() || 'N/A'}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="preview-item">
                                    <strong>Active:</strong> ${formData.get('is_active') ? 'Yes' : 'No'}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="preview-item">
                                    <strong>Tax Inclusive:</strong> ${formData.get('is_inclusive') ? 'Yes' : 'No'}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="preview-item">
                                    <strong>Start Date:</strong> ${formData.get('starts_at') || 'Immediate'}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="preview-item">
                                    <strong>Expiry Date:</strong> ${formData.get('expires_at') || 'No expiry'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

                                    $('#previewContent').html(previewHtml);
                                    $('#previewModal').modal('show');
                                });

                                // Initialize first step
                                showStep(currentStep);

                                // Notification function
                                function showNotification(message, type = 'info') {
                                    const alertClass = {
                                        'success': 'alert-success',
                                        'error': 'alert-danger',
                                        'warning': 'alert-warning',
                                        'info': 'alert-info'
                                    };

                                    const notification = $(`
            <div class="alert ${alertClass[type]} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

                                    $('body').append(notification);

                                    // Auto-hide after 5 seconds
                                    setTimeout(() => {
                                        notification.alert('close');
                                    }, 5000);
                                }
                            });
</script>
@endpush