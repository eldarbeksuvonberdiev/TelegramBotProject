<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
        $companies = Company::where('status', 1)->get();
        return view('meal.cart', compact('companies'));
    }

    public function send(Request $request)
    {
        dd($request->all());
    }
}
