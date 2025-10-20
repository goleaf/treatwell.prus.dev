@extends('layouts.app')

@section('title', $venue->name . ' - Treatwell Data')

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('venues.index') }}" class="btn btn-secondary">Back to Venues</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h1>{{ $venue->name }}</h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h3>Venue Details</h3>
                    <table class="table">
                        <tr>
                            <th>Name:</th>
                            <td>{{ $venue->name }}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>{{ $venue->type_name ?? 'Not specified' }}</td>
                        </tr>
                        <tr>
                            <th>URL:</th>
                            <td>
                                @if($venue->mobile_uri)
                                    <a href="{{ $venue->mobile_uri }}" target="_blank">{{ $venue->mobile_uri }}</a>
                                @else
                                    Not available
                                @endif
                            </td>
                        </tr>
                    </table>

                    <h3 class="mt-4">Description</h3>
                    <div class="card mb-4">
                        <div class="card-body">
                            {!! nl2br(e($venue->description ?? 'No description available')) !!}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <h3>Cities</h3>
                            <div class="card">
                                <div class="card-body">
                                    @if(method_exists($venue, 'cities') && $venue->cities->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($venue->cities as $city)
                                                <span class="badge rounded-pill bg-primary">{{ $city->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No cities associated with this venue.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <h3>Procedures</h3>
                            <div class="card">
                                <div class="card-body">
                                    @if(method_exists($venue, 'procedures') && $venue->procedures->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($venue->procedures as $procedure)
                                                <span class="badge rounded-pill bg-success">{{ $procedure->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No procedures associated with this venue.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 