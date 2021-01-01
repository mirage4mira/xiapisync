<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Notifications\EmailChangeNotification;
use Illuminate\Support\Facades\Notification;
class UsersController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $you = auth()->user();
        $users = User::all();
        return view('dashboard.admin.usersList', compact('users', 'you'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        return view('dashboard.admin.userShow', compact( 'user' ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        return view('settings');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {   

        if($request->tab == "user"){

            $validatedData = $request->validate([
                'name'       => 'required|min:1|max:256',
                'username'       => 'required|min:1|max:256|unique:users,username,'.auth()->id(),
                'email'      => 'required|email|max:256|unique:users,email,'.auth()->id(),
            ]);
    
            $user = auth()->user();
            $user->name       = $request->input('name');
            $user->username       = $request->input('username');
            
    
            if($user->email != $request->input('email')){
                Notification::route('mail', $request->email)
                ->notify(new EmailChangeNotification(Auth::user()->id));
            }
    
            $user->save();
            $request->session()->flash('success_msgs', ['Successfully updated user!']);
            
            return redirect()->back()->with('tab',$request->tab);
        }elseif($request->tab == "user-password"){
            $validatedData = $request->validate([
                'password'       => 'required|same:confirm_password|min:8',
                ]);
                $user = auth()->user();
                $user->password = bcrypt($request->input('password'));
                $user->save();
                
            $request->session()->flash('success_msgs', ['Successfully updated user!']);
            return redirect()->back()->with('tab',$request->tab);
        }
    }

    public function verify(Request $request, User $user, string $email)
    {
        $request->validate([
            'email' => 'required|email|unique:users'
        ]);
        
        // Change the Email
        $user->update([
            'email' => $request->email
        ]);

        // And finally return the view telling the change has been done
        return response()->view('user.email.change-complete');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if($user){
            $user->delete();
        }
        return redirect()->route('users.index');
    }
}
