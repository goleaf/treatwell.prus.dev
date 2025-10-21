@extends('layouts.app')

@section('title', 'Venues - Treatwell Data')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h1 class="mb-0">Venues</h1>
        <span class="text-muted">{{ number_format($venues->total()) }} results</span>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">Filter Venues</div>
        <div class="card-body">
            <form action="{{ route('venues.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Name, description...">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="city" class="form-label">City</label>
                        <select name="city" id="city" class="form-select">
                            <option value="">All cities</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->slug }}" {{ request('city') == $city->slug ? 'selected' : '' }}>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="procedure" class="form-label">Procedure</label>
                        <select name="procedure" id="procedure" class="form-select">
                            <option value="">All procedures</option>
                            @foreach($procedures as $procedure)
                                <option value="{{ $procedure->slug }}" {{ request('procedure') == $procedure->slug ? 'selected' : '' }}>
                                    {{ $procedure->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label for="type" class="form-label">Venue Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">All types</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-lg-2 col-md-4">
                        <label for="rating" class="form-label">Min rating</label>
                        <select name="rating" id="rating" class="form-select">
                            <option value="">Any</option>
                            @foreach([5, 4.5, 4, 3.5, 3] as $rating)
                                <option value="{{ $rating }}" {{ request('rating') == $rating ? 'selected' : '' }}>{{ number_format($rating, 1) }}+</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="min_price" class="form-label">Min price (€)</label>
                        <input type="number" min="0" step="1" name="min_price" id="min_price" value="{{ request('min_price') }}" class="form-control">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="max_price" class="form-label">Max price (€)</label>
                        <input type="number" min="0" step="1" name="max_price" id="max_price" value="{{ request('max_price') }}" class="form-control">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="sort" class="form-label">Sort by</label>
                        <select name="sort" id="sort" class="form-select">
                            @php($sortOptions = [
                                'name_asc' => 'Name (A-Z)',
                                'name_desc' => 'Name (Z-A)',
                                'rating_desc' => 'Rating (high to low)',
                                'rating_asc' => 'Rating (low to high)',
                                'price_low_high' => 'Price (low to high)',
                                'price_high_low' => 'Price (high to low)',
                                'newest' => 'Newest first',
                                'oldest' => 'Oldest first',
                            ])
                        @endphp
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('sort', 'name_asc') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="per_page" class="form-label">Per page</label>
                        <select name="per_page" id="per_page" class="form-select">
                            @foreach([12, 18, 24, 36, 48, 60] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', 18) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="new" name="new" {{ request()->boolean('new') ? 'checked' : '' }}>
                            <label class="form-check-label" for="new">
                                Show only new venues
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Apply filters</button>
                    <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse($venues as $venue)
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    @if($venue->getPrimaryImageUrl())
                        <img src="{{ $venue->getPrimaryImageUrl() }}" alt="{{ $venue->name }} image" class="card-img-top" style="object-fit: cover; height: 180px;">
                    @endif
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $venue->name }}</h5>
                            @if($venue->is_new_venue)
                                <span class="badge bg-success">New</span>
                            @endif
                        </div>

                        <p class="text-muted small mb-2">
                            <i class="fa-solid fa-map-marker-alt me-1"></i>
                            {{ $venue->getCityName() }}
                        </p>

                        <p class="text-muted small mb-2">
                            <strong>Type:</strong> {{ $venue->type_name ?? 'Not specified' }}
                        </p>

                        @if($venue->rating)
                            <p class="small mb-2">
                                <strong>Rating:</strong>
                                <span class="text-warning"><i class="fa-solid fa-star"></i></span>
                                {{ number_format($venue->rating->weighted_average, 1) }}
                                <span class="text-muted">({{ number_format($venue->rating->count) }})</span>
                            </p>
                        @endif

                        @php
                            $minPrice = $venue->minimum_price;
                            $maxPrice = $venue->maximum_price;
                        @endphp
                        @if($minPrice || $maxPrice)
                            <p class="small mb-2">
                                <strong>Price range:</strong>
                                @if($minPrice && $maxPrice && $minPrice !== $maxPrice)
                                    €{{ number_format($minPrice, 2) }} - €{{ number_format($maxPrice, 2) }}
                                @else
                                    €{{ number_format($minPrice ?? $maxPrice, 2) }}
                                @endif
                            </p>
                        @endif

                        @if($venue->treatments->isNotEmpty())
                            <div class="mb-3">
                                <p class="fw-semibold small text-uppercase text-muted mb-1">Popular treatments</p>
                                <ul class="list-unstyled small mb-0">
                                    @foreach($venue->treatments->take(3) as $treatment)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ $treatment->name }}</span>
                                            <span class="text-muted">{{ $treatment->formatted_price }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

        @php($excerpt = \Illuminate\Support\Str::limit(strip_tags($venue->description ?? ''), 140))
                        @if($excerpt)
                            <p class="text-muted small mb-3">{{ $excerpt }}</p>
                        @endif

                        <div class="mt-auto d-flex gap-2">
                            @if($venue->desktop_uri)
                                <a href="{{ $venue->desktop_uri }}" class="btn btn-outline-primary" target="_blank" rel="noopener">View on Treatwell</a>
                            @endif
                            <a href="{{ route('venues.show', $venue->normalised_name ?? $venue->slug) }}" class="btn btn-primary">Details</a>
                        </div>
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