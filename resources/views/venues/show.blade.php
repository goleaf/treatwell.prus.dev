@extends('layouts.app')

@section('title', $venue->name . ' - Treatwell Data')

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('venues.index') }}" class="btn btn-secondary">Back to Venues</a>
    </div>
    
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h1 class="h3 mb-1">{{ $venue->name }}</h1>
                    <p class="text-muted mb-0">
                        <i class="fa-solid fa-map-marker-alt me-1"></i>
                        {{ $venue->getCityName() }}
                        @if($venue->address)
                            · {{ $venue->address }}
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if($venue->desktop_uri)
                        <a href="{{ $venue->desktop_uri }}" class="btn btn-primary" target="_blank" rel="noopener">Open on Treatwell</a>
                    @endif
                    @if($venue->mobile_uri && $venue->desktop_uri !== $venue->mobile_uri)
                        <a href="{{ $venue->mobile_uri }}" class="btn btn-outline-primary" target="_blank" rel="noopener">Mobile view</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row gy-4">
                <div class="col-lg-7">
                    @if($venue->description)
                        <h3 class="h5">About</h3>
                        <div class="mb-4 text-muted">{!! $venue->description !!}</div>
                    @endif

                    @if($venue->treatments->isNotEmpty())
                        <h3 class="h5">Treatments</h3>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr class="text-muted">
                                        <th scope="col">Treatment</th>
                                        <th scope="col" class="text-end">Price</th>
                                        <th scope="col" class="text-end">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($venue->treatments as $treatment)
                                        <tr>
                                            <td>{{ $treatment->name }}</td>
                                            <td class="text-end">{{ $treatment->formatted_price }}</td>
                                            <td class="text-end">{{ $treatment->formatted_duration }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($venue->openingHours->isNotEmpty())
                        <h3 class="h5">Opening hours</h3>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm">
                                <tbody>
                                    @foreach($venue->openingHours as $hour)
                                        <tr>
                                            <td class="text-capitalize">{{ $hour->day_of_week }}</td>
                                            <td>
                                                @if($hour->is_open)
                                                    {{ $hour->opening_time }} - {{ $hour->closing_time }}
                                                @else
                                                    <span class="text-muted">Closed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="col-lg-5">
                    <div class="mb-4">
                        <h3 class="h5">Quick info</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">Type</span>
                                <span>{{ $venue->type_name ?? 'Not specified' }}</span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">City</span>
                                <span>{{ $venue->getCityName() }}</span>
                            </li>
                            @if($venue->rating)
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span class="text-muted">Rating</span>
                                    <span><i class="fa-solid fa-star text-warning me-1"></i>{{ number_format($venue->rating->weighted_average, 1) }} ({{ number_format($venue->rating->count) }})</span>
                                </li>
                            @endif
                            @if($venue->phone)
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span class="text-muted">Phone</span>
                                    <a href="tel:{{ $venue->phone }}">{{ $venue->phone }}</a>
                                </li>
                            @endif
                            @if($venue->email)
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span class="text-muted">Email</span>
                                    <a href="mailto:{{ $venue->email }}">{{ $venue->email }}</a>
                                </li>
                            @endif
                            @if($venue->website)
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span class="text-muted">Website</span>
                                    <a href="{{ $venue->website }}" target="_blank" rel="noopener">Visit site</a>
                                </li>
                            @endif
                        </ul>
                    </div>

                    @if($venue->procedures->isNotEmpty())
                        <div class="mb-4">
                            <h3 class="h6 text-uppercase text-muted">Procedures</h3>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($venue->procedures as $procedure)
                                    <span class="badge bg-success-subtle text-success fw-semibold">{{ $procedure->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($venue->images->isNotEmpty())
                        <h3 class="h6 text-uppercase text-muted">Gallery</h3>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($venue->images->take(6) as $image)
                                @if($image->preferred_url)
                                    <img src="{{ $image->preferred_url }}" alt="{{ $venue->name }} image" class="rounded" style="object-fit: cover; width: 90px; height: 90px;">
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($venue->cities->isNotEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Associated cities</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($venue->cities as $city)
                        <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $city->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection