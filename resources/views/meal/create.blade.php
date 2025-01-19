@extends('meal.main')

@section('title', 'Meal create')

@section('content')
    <div class="row">
        <div class="col-12">
            <a href="{{ route('meal') }}" class="btn btn-primary">Back</a>
        </div>
        <div class="col-12 mt-5">
            <h3 class="mb-5">Meal creation</h3>
            <form action="{{ route('meal.store') }}" method="post">
                @csrf
                <input type="text" name="name"class="form-control" placeholder="Meal name">
                @error('name')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror
                <input type="number" name="price"class="form-control mt-2" placeholder="Meal price">
                @error('price')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror
                <input type="submit"class="btn btn-primary mt-3" value="Store">
            </form>
        </div>
    </div>
@endsection
