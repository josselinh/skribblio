<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WelcomeController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('welcome');
    }

    /**
     * @return View
     */
    public function release(): View
    {
        return view('release');
    }
}
