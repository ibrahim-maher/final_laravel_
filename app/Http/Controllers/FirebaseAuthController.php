<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Exception\FirebaseException;

class FirebaseAuthController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    // Show login form
    public function showLogin()
    {
        Log::info('Login form accessed');
        return view('auth.login');
    }

    // Show register form
    public function showRegister()
    {
        Log::info('Register form accessed');
        return view('auth.register');
    }

    // Handle login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        try {
            Log::info('Login attempt for email: ' . $request->email);
            
            // Sign in user with email and password
            $signInResult = $this->auth->signInWithEmailAndPassword(
                $request->email, 
                $request->password
            );

            $user = $signInResult->data();
            
            // For now, make everyone an admin (you can add proper admin logic later)
            $isAdmin = true; // Simplified for testing
            
            // Store user info in session
            session([
                'firebase_user' => [
                    'uid' => $user['localId'],
                    'email' => $user['email'],
                    'token' => $user['idToken'],
                    'is_admin' => $isAdmin,
                    'admin_role' => $isAdmin ? 'admin' : null,
                ]
            ]);

            Log::info('User logged in successfully: ' . $user['email'] . ($isAdmin ? ' (Admin)' : ' (Regular User)'));
            
            // Always redirect to admin dashboard for now
            return redirect()->route('admin.dashboard')->with('success', 'Welcome to Admin Dashboard!');

        } catch (InvalidPassword $e) {
            Log::warning('Invalid password for email: ' . $request->email);
            return back()->withErrors(['password' => 'Invalid password'])->withInput();
        } catch (UserNotFound $e) {
            Log::warning('User not found: ' . $request->email);
            return back()->withErrors(['email' => 'User not found'])->withInput();
        } catch (FirebaseException $e) {
            Log::error('Firebase login error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Login failed. Please try again.'])->withInput();
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred. Please try again.'])->withInput();
        }
    }

    // Handle registration
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ]);

        try {
            Log::info('Registration attempt for email: ' . $request->email);

            // Create user in Firebase
            $userProperties = [
                'email' => $request->email,
                'password' => $request->password,
                'displayName' => $request->name,
                'emailVerified' => false,
            ];

            $createdUser = $this->auth->createUser($userProperties);
            
            Log::info('User created successfully in Firebase: ' . $createdUser->uid);

            // Sign in the newly created user
            $signInResult = $this->auth->signInWithEmailAndPassword(
                $request->email, 
                $request->password
            );

            $userData = $signInResult->data();

            // For now, make everyone an admin (you can add proper admin logic later)
            $isAdmin = true; // Simplified for testing

            // Store user info in session
            session([
                'firebase_user' => [
                    'uid' => $userData['localId'],
                    'email' => $userData['email'],
                    'token' => $userData['idToken'],
                    'name' => $request->name,
                    'is_admin' => $isAdmin,
                    'admin_role' => $isAdmin ? 'admin' : null,
                ]
            ]);

            Log::info('User registered and logged in successfully: ' . $userData['email']);

            // Always redirect to admin dashboard for now
            return redirect()->route('admin.dashboard')->with('success', 'Welcome to Admin Dashboard!');

        } catch (FirebaseException $e) {
            Log::error('Firebase registration error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred during registration.'])->withInput();
        }
    }

    // Handle logout
    public function logout(Request $request)
    {
        $userEmail = session('firebase_user.email', 'Unknown user');
        
        Log::info('User logging out: ' . $userEmail);
        
        session()->forget('firebase_user');
        
        return redirect()->route('login')->with('success', 'Logged out successfully!');
    }

    // Legacy dashboard method - redirect to admin dashboard
    public function dashboard()
    {
        if (!session('firebase_user')) {
            Log::warning('Unauthorized dashboard access attempt');
            return redirect()->route('login');
        }

        Log::info('Legacy dashboard accessed by: ' . session('firebase_user.email'));
        
        // Always redirect to admin dashboard for now
        return redirect()->route('admin.dashboard');
    }
}