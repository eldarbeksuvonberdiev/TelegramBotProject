@extends('meal.main')

@section('title', 'Main')

@section('content')
    <div class="row">
        <div class="row">
            @if (empty($cart))
                <p>Your cart is currently empty.</p>
            @else
                <form action="{{ route('cart.update') }}" method="POST">
                    @csrf
                    <table style="width: 100%; border-collapse: collapse;" border="1">
                        <thead>
                            <tr>
                                <th style="padding: 8px; text-align: left;">Meal Name</th>
                                <th style="padding: 8px; text-align: center;">Quantity</th>
                                <th style="padding: 8px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart as $id => $meal)
                                <tr>
                                    <td style="padding: 8px;">{{ $meal['name'] }}</td>
                                    <td style="padding: 8px; text-align: center;">
                                        <input type="number" name="quantity[{{ $id }}]"
                                            value="{{ $meal['quantity'] }}" min="1"
                                            style="width: 50px; text-align: center;">
                                    </td>
                                    <td style="padding: 8px; text-align: center;">
                                        <a href="{{ route('cart.remove', $id) }}" class="btn btn-danger">Remove</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="submit"
                        style="margin-top: 15px; background: green; color: white; border: none; padding: 10px 20px; cursor: pointer;">Update
                        Cart</button>
                </form>

                <form action="{{ route('cart.clear') }}" method="POST" style="margin-top: 15px;">
                    @csrf
                    <button type="submit"
                        style="background: orange; color: white; border: none; padding: 10px 20px; cursor: pointer;">Clear
                        Cart</button>
                </form>
                <form action="{{ route('cart.placeOrder') }}" method="POST" style="margin-top: 15px;">
                    @csrf
                    <div class="mt-3">
                        <label for="form-label" for="deliver"><strong>Choose a person to deliver the meals</strong></label>
                        <select class="form-select" name="deliver_id" id="deliver" aria-label="Default select example">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('deliver_id')
                            <div class="text-danger">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <input type="date" name="delivery_time" class="form-control mt-3">
                    @error('delivery_time')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                    @enderror

                    <strong class="text-warning mt-2">Latitude(Kenglik)</strong>
                    <input type="number" step="0.000001" name="latitude" placeholder="Location latitude"
                        class="form-control">
                    @error('latitude')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                    @enderror
                    <strong class="text-warning mt-2">Longitude(Uzunlik)</strong>
                    <input type="number" step="0.000001" name="longitude" placeholder="Location longitude"
                        class="form-control">
                    @error('longitude')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                    @enderror

                    <button type="submit"
                        style="background: blue; color: white; border: none; padding: 10px 20px; cursor: pointer; margin-top:20px;">Place
                        Order</button><br>
                    <span class="text-danger">Remember that, before placing the order you should update the order details by
                        pushing the update
                        cart button in case of change the count of the meals!</span>
                </form>

            @endif
        </div>
    </div>
@endsection
