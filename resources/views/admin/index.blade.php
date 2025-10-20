@extends('layouts.app')

@section('title', 'Admin Panel - Treatwell Data')

@section('content')
<h1 class="mb-4">Admin Panel</h1>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Scrape by City</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.scrape') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="city" class="form-label">City</label>
                        <select class="form-select" id="city" name="city" required>
                            <option value="">Select a city</option>
                            <option value="vilnius-lt">Vilnius</option>
                            <option value="kaunas-lt">Kaunas</option>
                            <option value="klaipeda-lt">Klaipėda</option>
                            <option value="siauliai-lt">Šiauliai</option>
                            <option value="panevezys-lt">Panevėžys</option>
                            <option value="alytus-lt">Alytus</option>
                            <option value="marijampole-lt">Marijampolė</option>
                            <option value="mazeikiai-lt">Mažeikiai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="page_limit" class="form-label">Page Limit (optional)</label>
                        <input type="number" class="form-control" id="page_limit" name="page_limit" min="1">
                        <div class="form-text">Leave empty to scrape all pages</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Start Scraping</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Scrape All Cities</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.scrape-all') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="all_page_limit" class="form-label">Page Limit Per City (optional)</label>
                        <input type="number" class="form-control" id="all_page_limit" name="page_limit" min="1">
                        <div class="form-text">Leave empty to scrape all pages for each city</div>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This will scrape data for all Lithuanian cities and may take a long time to complete.
                    </div>
                    <button type="submit" class="btn btn-primary">Start Scraping All Cities</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Database Stats</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <h6>Venues</h6>
                            <span class="h3">{{ \App\Models\Venue::count() }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <h6>Cities</h6>
                            <span class="h3">{{ \App\Models\City::count() }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <h6>Images</h6>
                            <span class="h3">{{ \App\Models\Image::count() }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <h6>Treatments</h6>
                            <span class="h3">{{ \App\Models\Treatment::count() }}</span>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Last Updated Venues</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>City</th>
                                <th>Rating</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\Venue::with(['location.city', 'rating'])->latest('updated_at')->take(5)->get() as $venue)
                                <tr>
                                    <td>{{ $venue->name }}</td>
                                    <td>{{ $venue->location->city->name ?? 'Unknown' }}</td>
                                    <td>{{ $venue->rating->weighted_average ?? 'N/A' }}</td>
                                    <td>{{ $venue->updated_at->diffForHumans() }}</td>
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
</div>
@endsection 