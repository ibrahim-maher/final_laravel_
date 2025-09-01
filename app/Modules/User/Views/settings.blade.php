@extends('layouts.app')

@section('title', 'User Settings')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Account Settings</h1>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.password.change') }}">
                        @csrf
                        
                        <div class="form-group mb-3">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="new_password_confirmation">Confirm New Password</label>
                            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Notification Preferences</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.notifications.update') }}">
                        @csrf
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                            <label class="form-check-label" for="email_notifications">
                                Email Notifications
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications">
                            <label class="form-check-label" for="sms_notifications">
                                SMS Notifications
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection