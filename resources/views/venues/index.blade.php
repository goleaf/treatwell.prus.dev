@extends('layouts.app')

@section('title', 'Venues - Treatwell Data')

@section('content')
<div class="container">
    <h1>Venues</h1>
    
    <div class="card mb-4">
        <div class="card-header">Filter Venues</div>
        <div class="card-body">
            <form action="{{ route('venues.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="type">Venue Type</label>
                            <select name="type" id="type" class="form-control">
                                <option value="">All Types</option>
                                @php
                                    $types = \App\Models\Venue::select('type_name')
                                        ->whereNotNull('type_name')
                                        ->distinct()
                                        ->orderBy('type_name')
                                        ->pluck('type_name');
                                @endphp
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        @forelse($venues as $venue)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $venue->name }}</h5>
                        <p class="card-text">
                            <small class="text-muted">
                                <strong>Type:</strong> {{ $venue->type_name ?? 'Not specified' }}
                            </small>
                        </p>
                        <a href="{{ $venue->mobile_uri }}" class="btn btn-primary" target="_blank">View on Treatwell</a>
                        <a href="{{ route('venues.show', $venue->normalised_name) }}" class="btn btn-secondary">Details</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    No venues found matching your criteria.
                </div>
            </div>
        @endforelse
    </div>
    
    <div class="d-flex justify-content-center mt-4">
        {{ $venues->appends(request()->query())->links() }}
    </div>
</div>
@endsection 