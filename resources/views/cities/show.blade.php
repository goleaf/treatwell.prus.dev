@extends('layouts.app')

@section('title', $city->name . ' - City Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>{{ $city->name }}</h1>
    <div>
        <a href="{{ route('cities.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Cities
        </a>
    </div>
</div>

<div class="row">
    <!-- City info -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">City Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Name:</span>
                        <strong>{{ $city->name }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Type:</span>
                        <strong>{{ $city->is_main_city ? 'Main City' : 'Subregion' }}</strong>
                    </li>
                    @if($city->subregions->isNotEmpty())
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subregions:</span>
                            <strong>{{ $city->subregions->count() }}</strong>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Venues:</span>
                        <strong>{{ $venues->total() }}</strong>
                    </li>
                    @if($city->latitude && $city->longitude)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Coordinates:</span>
                            <strong>{{ number_format($city->latitude, 4) }}, {{ number_format($city->longitude, 4) }}</strong>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Subregions -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Subregions</h5>
                <span class="badge bg-primary">{{ $city->subregions->count() }}</span>
            </div>
            <div class="card-body">
                @if($city->subregions->isEmpty())
                    <div class="alert alert-info">No subregions found for this city.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Subregion</th>
                                    <th>Venues</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($city->subregions as $subregion)
                                    <tr>
                                        <td>{{ $subregion->subregion }}</td>
                                        <td>
                                            <span class="badge bg-success">{{ $subregion->locations()->count() }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('venues.index', ['city_id' => $city->id, 'include_subregions' => 0, 'subregion' => $subregion->subregion]) }}" class="btn btn-sm btn-outline-primary">
                                                View Venues
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Venues -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Venues in {{ $city->name }}</h5>
                <span class="badge bg-primary">{{ $venues->total() }} Total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px">Image</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Subregion</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($venues as $venue)
                                <tr>
                                    <td>
                                        @if($venue->images->isNotEmpty())
                                            <img src="{{ $venue->images->first()->uri_small }}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;" alt="{{ $venue->name }}">
                                        @else
                                            <div class="bg-light text-center" style="width: 60px; height: 60px;">No Image</div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $venue->name }}</strong>
                                    </td>
                                    <td>{{ $venue->type_name }}</td>
                                    <td>
                                        @if($venue->location && $venue->location->city && $venue->location->city->subregion)
                                            {{ $venue->location->city->subregion }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($venue->rating)
                                            <span class="badge bg-primary">
                                                {{ $venue->rating->weighted_average }} ⭐ ({{ $venue->rating->count }})
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">No ratings</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @if($venue->mobile_uri)
                                            <a href="{{ $venue->mobile_uri }}" target="_blank" class="btn btn-sm btn-outline-secondary">Treatwell</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="alert alert-info mb-0">
                                            No venues found in this city.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-center mt-4">
            {{ $venues->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection 