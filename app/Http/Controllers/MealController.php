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

    public function store(Request $request)
    {
       $data = $request->validate([
            'name' => 'required',
            'price' => 'required'
       ]);

       Meal::create($data);
       return redirect()->route('meal');
    }


    public function addToCart(Meal $meal)
    {
        dd($meal);
    }

    public function cart()
    {
        return view('meal.cart');
    }
}
