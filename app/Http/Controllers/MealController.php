<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $cart = session()->get('cart', []);

        $cart[$meal->id] = ['name' => $meal->name];

        session()->put('cart', $cart);

        return back();
    }

    public function cart()
    {
        $companies = Company::where('status', 1)->get();
        return view('meal.cart', compact('companies'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'companies' => 'required|exists:companies,id'
        ]);
        foreach ($data['companies'] as $val) {
            $user = User::where('role', 'company_admin')->where('company_id',$val)->first();
            // dd($user);
            // Log::info([$val, $user]);
        }
        return back();
    }
}
