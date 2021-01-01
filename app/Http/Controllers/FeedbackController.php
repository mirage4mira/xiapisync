<?php

namespace App\Http\Controllers;

use App\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
  
    public function create()
    {

        return view('feedback.create');
    }

    public function store(Request $request)
    {
        // dd($request->message);
        Feedback::create(['user_id' => auth()->id(),'message' => $request->message]);

        return redirect('/')->with('success_msgs',['Feedback have been sent!']);
    }
}
