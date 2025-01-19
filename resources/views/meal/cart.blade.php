@extends('meal.main')

@section('title', 'Main')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label><strong>Select a companies</strong></label>
                <div class="select2-purple">
                    <select class="select2" multiple="multiple" name="companies[]" data-placeholder="Select a State"
                        data-dropdown-css-class="select2-purple" style="width: 100%;">
                        <option>Alabama</option>
                        <option>Alaska</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
@endsection
