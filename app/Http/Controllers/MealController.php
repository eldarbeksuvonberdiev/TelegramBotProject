<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use Illuminate\Http\Request;

class MealController extends Controller
{
    public function index()
    {
        $meals = Meal::all();
        return view('meal.meal', compact('meals'));
    }

    public function create()
    {
        return view('meal.create');
    }
}
