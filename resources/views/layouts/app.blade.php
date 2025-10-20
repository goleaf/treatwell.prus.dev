<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Treatwell Data Scraper')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @yield('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">Treatwell Data</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('venues.*') ? 'active' : '' }}" href="{{ route('venues.index') }}">Venues</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('cities.*') ? 'active' : '' }}" href="#" id="citiesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Cities & Regions
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="citiesDropdown">
                            <li><a class="dropdown-item" href="{{ route('cities.index') }}">Cities Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('cities.main') }}">Main Cities</a></li>
                            <li><a class="dropdown-item" href="{{ route('cities.subregions') }}">Subregions</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @php
                                $topCities = \App\Models\City::where('is_main_city', true)->withMostVenues(5)->get();
                            @endphp
                            @foreach($topCities as $city)
                                <li><a class="dropdown-item" href="{{ route('venues.by-city', $city) }}">{{ $city->name }} ({{ $city->locations_count }})</a></li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.index') }}">Admin</a>
                    </li>
                </ul>
                <div class="ms-auto d-flex">
                    <form action="{{ route('venues.index') }}" method="GET" class="d-flex">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search venues..." aria-label="Search">
                        <button class="btn btn-outline-light" type="submit">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        @yield('content')
    </div>
    
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-md-end">
                    <p class="mb-0">made by prus for 7yes.lt with <i class="text-danger fa-solid fa-heart"></i></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html> 