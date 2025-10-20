@extends('layouts.app')

@section('title', 'Dashboard - Treatwell Data')

@section('content')
<h1 class="mb-4">Dashboard</h1>

<div class="row mb-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Venues</h5>
                <p class="card-text display-4">{{ $totalVenues }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Cities</h5>
                <p class="card-text display-4">{{ $totalCities }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Top Rated Venues</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Rating</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topRatedVenues as $venue)
                                <tr>
                                    <td>{{ $venue->name }}</td>
                                    <td>{{ $venue->location->city->name ?? 'Unknown location' }}</td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">
                                            {{ $venue->rating->weighted_average ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cities with Most Venues</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>City</th>
                                <th>Venues</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($citiesWithMostVenues as $city)
                                <tr>
                                    <td>{{ $city->name }}</td>
                                    <td>{{ $city->locations_count }}</td>
                                    <td>
                                        <a href="{{ route('venues.by-city', $city) }}" class="btn btn-sm btn-outline-secondary">Browse</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-5">
    <a href="{{ route('venues.index') }}" class="btn btn-primary">View All Venues</a>
</div>
@endsection 