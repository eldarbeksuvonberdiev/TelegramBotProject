@extends('meal.main')

@section('title', 'Main')

@section('content')
    <div class="row">
        @if (session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        <div class="col-12">
            <a href="{{ route('meal.create') }}" class="btn btn-primary">Create</a>
        </div>

        <div class="col-12">
            <table class="table table-bordered table-hover table-striped mt-5">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>To cart</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meals as $meal)
                        <tr>
                            <td>{{ $meal->id }}</td>
                            <td>{{ $meal->name }}</td>
                            <td>{{ $meal->price }}</td>
                            <td>
                                <a href="{{ route('meal.toCart', $meal->id) }}" class="btn btn-outline-primary">To Cart <i
                                        class="bi bi-cart2"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
