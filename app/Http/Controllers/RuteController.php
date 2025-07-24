<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RuteController extends Controller
{
    public function index()
{
    return view('master.rute.index');
}

}
