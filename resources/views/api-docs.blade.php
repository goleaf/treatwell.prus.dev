@extends('layouts.app')

@section('title', 'API Documentation - Treatwell Data')

@section('content')
<div class="container my-5">
    <h1 class="mb-4">API Documentation</h1>
    
    <div class="alert alert-info">
        <p class="mb-0">All API endpoints are available at <code>{{ url('/api') }}</code></p>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">Venues</h2>
        </div>
        <div class="card-body">
            <h3 class="h5">Get Venues</h3>
            <p><code>GET /api/venues</code></p>
            
            <h4 class="h6">Parameters</h4>
            <ul>
                <li><code>per_page</code> (optional) - Number of items per page. Default: 20</li>
                <li><code>page</code> (optional) - Page number for pagination</li>
                <li><code>city_id</code> (optional) - Filter by city ID</li>
                <li><code>rating</code> (optional) - Filter by minimum rating</li>
                <li><code>search</code> (optional) - Search venues by name</li>
                <li><code>type</code> (optional) - Filter by venue type</li>
                <li><code>sort</code> (optional) - Sort results by: name, name_desc, rating, rating_asc, newest, oldest</li>
            </ul>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>{
  "data": [
    {
      "id": 1,
      "external_id": "123456",
      "name": "Example Salon",
      "description": "A beautiful salon in Vilnius",
      "type_id": "1",
      "type_name": "Spa",
      "normalised_name": "spa",
      "desktop_uri": "/salon/example-salon",
      "mobile_uri": "/salon/example-salon",
      "app_uri": "/salon/example-salon",
      "is_new_venue": false,
      "location": { ... },
      "rating": { ... },
      "images": [ ... ]
    },
    ...
  ],
  "links": { ... },
  "meta": { ... }
}</code></pre>
            
            <h3 class="h5 mt-4">Get Venue Details</h3>
            <p><code>GET /api/venues/{venue_id}</code></p>
            
            <h4 class="h6">Parameters</h4>
            <ul>
                <li><code>venue_id</code> (required) - ID of the venue</li>
            </ul>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>{
  "id": 1,
  "external_id": "123456",
  "name": "Example Salon",
  "description": "A beautiful salon in Vilnius",
  "type_id": "1",
  "type_name": "Spa",
  "normalised_name": "spa",
  "desktop_uri": "/salon/example-salon",
  "mobile_uri": "/salon/example-salon",
  "app_uri": "/salon/example-salon",
  "is_new_venue": false,
  "location": { ... },
  "rating": { ... },
  "images": [ ... ],
  "openingHours": [ ... ],
  "treatments": [ ... ]
}</code></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">Cities</h2>
        </div>
        <div class="card-body">
            <h3 class="h5">Get Cities</h3>
            <p><code>GET /api/cities</code></p>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>[
  {
    "id": 1,
    "entity_id": "vilnius-lt",
    "country_id": 1,
    "name": "Vilnius",
    "normalised_name": "vilnius-lt",
    "latitude": 54.687157,
    "longitude": 25.279652,
    "type": "city",
    "locations_count": 120
  },
  ...
]</code></pre>
            
            <h3 class="h5 mt-4">Get Venues by City</h3>
            <p><code>GET /api/cities/{city_id}/venues</code></p>
            
            <h4 class="h6">Parameters</h4>
            <ul>
                <li><code>city_id</code> (required) - ID of the city</li>
                <li><code>per_page</code> (optional) - Number of items per page. Default: 20</li>
                <li><code>page</code> (optional) - Page number for pagination</li>
                <li><code>rating</code> (optional) - Filter by minimum rating</li>
                <li><code>search</code> (optional) - Search venues by name</li>
                <li><code>type</code> (optional) - Filter by venue type</li>
                <li><code>sort</code> (optional) - Sort results by: name, name_desc, rating, rating_asc, newest, oldest</li>
            </ul>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">Treatments</h2>
        </div>
        <div class="card-body">
            <h3 class="h5">Get Treatment Categories</h3>
            <p><code>GET /api/treatments/categories</code></p>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>[
  "Haircut",
  "Massage",
  "Manicure",
  "Pedicure",
  ...
]</code></pre>
            
            <h3 class="h5 mt-4">Get Venues by Treatment</h3>
            <p><code>GET /api/treatments/venues</code></p>
            
            <h4 class="h6">Parameters</h4>
            <ul>
                <li><code>category</code> (optional) - Filter by treatment category</li>
                <li><code>name</code> (optional) - Search by treatment name</li>
                <li><code>per_page</code> (optional) - Number of items per page. Default: 20</li>
                <li><code>page</code> (optional) - Page number for pagination</li>
                <li><code>rating</code> (optional) - Filter by minimum venue rating</li>
                <li><code>city_id</code> (optional) - Filter by city ID</li>
            </ul>
            
            <h3 class="h5 mt-4">Get Venues by Price Range</h3>
            <p><code>GET /api/treatments/venues/price-range</code></p>
            
            <h4 class="h6">Parameters</h4>
            <ul>
                <li><code>min_price</code> (optional) - Minimum price for treatments</li>
                <li><code>max_price</code> (optional) - Maximum price for treatments</li>
                <li><code>per_page</code> (optional) - Number of items per page. Default: 20</li>
                <li><code>page</code> (optional) - Page number for pagination</li>
                <li><code>rating</code> (optional) - Filter by minimum venue rating</li>
                <li><code>city_id</code> (optional) - Filter by city ID</li>
            </ul>
            
            <h3 class="h5 mt-4">Get Treatment Price Statistics</h3>
            <p><code>GET /api/treatments/price-stats</code></p>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>{
  "min_price": 10,
  "max_price": 200,
  "avg_price": 45.75
}</code></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">Other Endpoints</h2>
        </div>
        <div class="card-body">
            <h3 class="h5">Get Venue Types</h3>
            <p><code>GET /api/types</code></p>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>[
  "Spa",
  "Hair Salon",
  "Nail Salon",
  "Barbershop",
  ...
]</code></pre>
            
            <h3 class="h5 mt-4">Get Statistics</h3>
            <p><code>GET /api/stats</code></p>
            
            <h4 class="h6">Example Response</h4>
            <pre class="bg-light p-3"><code>{
  "total_venues": 450,
  "total_cities": 20,
  "top_rated_venues": [ ... ],
  "cities_with_most_venues": [ ... ]
}</code></pre>
        </div>
    </div>
</div>
@endsection 