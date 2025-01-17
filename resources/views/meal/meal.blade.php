@extends('meal.main')

@section('title', 'Main')

@section('content')
    <div class="row">
        <div class="col-12">
            <a href="{{ route('meal.create') }}" class="btn btn-primary">Create</a>
        </div>
        <div class="col-12">
            <table class="table table-bordered table-hover table-striped mt-5">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meals as $meal)
                        <tr>
                            <td>{{ $meal->id }}</td>
                            <td>{{ $meal->name }}</td>
                            <td>{{ $meal->price }}</td>
                            <td>
                                <a href="{{ route('meal.addToCart',$meal->id) }}" class="btn btn-outline-primary">To Cart <i class="bi bi-cart2"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
