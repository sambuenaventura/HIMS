<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index() {
        return "Hello from UserController";
        
    }

    public function login() {
        if(View::exists('user.login')) {
            return view("user.login");
        }else {
            return abort(404);
            //return response()->view('errors.404');
        }
        //return view('user.login');
    }

    public function process(Request $request) {
        $validated = $request->validate([
            "email" => ['required', 'email'],
            "password" => 'required'
        ]);

        if(auth()->attempt($validated)) {
            $request->session()->regenerate();

            return redirect('/')->with('message', 'Welcome back!');
        }

        return back()->withErrors(['email' => 'Login failed'])->onlyInput('email');
    }

    public function register() {
        return view('user.register');
    }

    public function logout(Request $request) {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('message', 'Logout successful');
    }

    public function store(Request $request) {
        $validated = $request->validate([
            //"name" => ['required', 'min:1', 'max:255'],
            "first_name" => ['required', 'min:1', 'max:255'],
            "last_name" => ['required', 'min:1', 'max:255'],
            'role' => 'required|string|in:admission,nurse,radtech,medtech',
            "email" => ['required', 'email', Rule::unique('users', 'email')],
            "password" => 'required|confirmed|min:6|max:255'
        ]);
        //dd($validated);
        $validated['password'] = bcrypt($validated['password']);
        //$validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);

        //return $user;

        auth()->login($user);
        return redirect('/')->with('message', 'Successfuly registered!');
    }

    public function show($id) {

        // $data = array(
        //     "id" => $id,
        //     "name" => "Sam Buenaventura",
        //     "age" => 22,
        //     "email" => "samb@gmail.com"
        // );
        $data = ["data" => "data from database"];
        return view('user') 
        ->with('data', $data)
        ->with('name', 'Sam')
        ->with('age', '22')
        ->with('email', 'samb@gmail.com')
        ->with('id', $id);
    }
}
