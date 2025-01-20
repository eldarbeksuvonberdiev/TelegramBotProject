@extends('meal.main')

@section('title', 'Main')

@section('content')
    <div class="row">
        <div class="col-12">
            <a href="{{ route('meal') }}" class="btn btn-primary">Back</a>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label><strong>Select a companies</strong></label>
                @if (session('message'))
                    <div class="alert alert-danger">
                        {{ session('message') }}
                    </div>
                @endif
                <form action="{{ route('meal.send') }}" method="post">
                    @csrf
                    <div class="select2-purple">
                        <select class="select2" multiple="multiple" name="companies[]" data-placeholder="Select a State"
                            data-dropdown-css-class="select2-purple" style="width: 100%;">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        <input type="submit" value="Send" class="btn btn-primary mt-2">
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
