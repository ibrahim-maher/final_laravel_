@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <h1>System Settings</h1>
            
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Application Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('core.settings.update') }}">
                                @csrf
                                @method('PUT')
                                
                                <div class="form-group mb-3">
                                    <label for="app_name">Application Name</label>
                                    <input type="text" class="form-control" id="app_name" name="app_name" value="{{ $settings['app_name'] }}">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="app_url">Application URL</label>
                                    <input type="url" class="form-control" id="app_url" name="app_url" value="{{ $settings['app_url'] }}">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="timezone">Timezone</label>
                                    <select class="form-control" id="timezone" name="timezone">
                                        <option value="UTC" {{ $settings['timezone'] === 'UTC' ? 'selected' : '' }}>UTC</option>
                                        <option value="America/New_York" {{ $settings['timezone'] === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                        <option value="America/Chicago" {{ $settings['timezone'] === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                        <option value="America/Denver" {{ $settings['timezone'] === 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                        <option value="America/Los_Angeles" {{ $settings['timezone'] === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="locale">Default Language</label>
                                    <select class="form-control" id="locale" name="locale">
                                        <option value="en" {{ $settings['locale'] === 'en' ? 'selected' : '' }}>English</option>
                                        <option value="es" {{ $settings['locale'] === 'es' ? 'selected' : '' }}>Spanish</option>
                                        <option value="fr" {{ $settings['locale'] === 'fr' ? 'selected' : '' }}>French</option>
                                    </select>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" {{ $settings['debug_mode'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="debug_mode">
                                        Enable Debug Mode
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>System Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>PHP Version:</strong> {{ phpversion() }}</p>
                            <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
                            <p><strong>Environment:</strong> {{ app()->environment() }}</p>
                            <p><strong>Debug Mode:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>System Actions</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-warning btn-sm mb-2 w-100">Clear Cache</button>
                            <button class="btn btn-info btn-sm mb-2 w-100">Optimize System</button>
                            <button class="btn btn-secondary btn-sm w-100">Generate Backup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection