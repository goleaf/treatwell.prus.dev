@extends('layouts.app')

@section('title', 'Cities and Subregions - Treatwell Data')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Cities and Subregions</h1>
</div>

<div class="row">
    <!-- Main cities list -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Main Cities</h5>
                <span class="badge bg-primary">{{ $mainCities->count() }} Cities</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>City Name</th>
                                <th>Subregions</th>
                                <th>Venues</th>
                                <th>Coordinates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mainCities as $city)
                                <tr>
                                    <td>
                                        <strong>{{ $city->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $city->subregions()->count() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $city->getAllVenues()->count() }}</span>
                                    </td>
                                    <td>
                                        @if($city->latitude && $city->longitude)
                                            {{ number_format($city->latitude, 4) }}, {{ number_format($city->longitude, 4) }}
                                        @else
                                            <em>Not available</em>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('venues.index', ['city_id' => $city->id, 'include_subregions' => 1]) }}" class="btn btn-sm btn-primary">
                                            View Venues
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary toggle-subregions" data-city-id="{{ $city->id }}">
                                            Show Subregions
                                        </button>
                                    </td>
                                </tr>
                                <tr class="subregions-row" id="subregions-{{ $city->id }}" style="display: none;">
                                    <td colspan="5" class="bg-light">
                                        <div class="subregions-container p-3">
                                            <h6>Subregions for {{ $city->name }}</h6>
                                            @if($city->subregions->isEmpty())
                                                <div class="alert alert-info">No subregions found.</div>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Subregion</th>
                                                                <th>Venues</th>
                                                                <th>Action</th>
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
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics cards -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cities Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Main Cities</h5>
                                <h2 class="mb-0">{{ $mainCitiesCount }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Subregions</h5>
                                <h2 class="mb-0">{{ $subregionsCount }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Venues</h5>
                                <h2 class="mb-0">{{ $totalVenues }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">Avg. Venues per City</h5>
                                <h2 class="mb-0">{{ number_format($avgVenuesPerCity, 1) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cities by venue count -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Cities by Venue Count</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @foreach($topCitiesByVenues as $city)
                        <a href="{{ route('venues.index', ['city_id' => $city->id, 'include_subregions' => 1]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span>{{ $city->name }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $city->venues_count }} venues</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle subregions visibility
        document.querySelectorAll('.toggle-subregions').forEach(button => {
            button.addEventListener('click', function() {
                const cityId = this.getAttribute('data-city-id');
                const subregionsRow = document.getElementById(`subregions-${cityId}`);
                
                if (subregionsRow.style.display === 'none') {
                    subregionsRow.style.display = 'table-row';
                    this.textContent = 'Hide Subregions';
                } else {
                    subregionsRow.style.display = 'none';
                    this.textContent = 'Show Subregions';
                }
            });
        });
    });
</script>
@endsection 