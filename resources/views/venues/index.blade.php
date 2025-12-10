@extends('layouts.app')

@section('title', '- Venues')

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Venues</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Discover amazing venues in your area</p>
                </div>
                @auth
                    <a href="{{ route('web.venues.create') }}" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Add New Venue
                    </a>
                @endauth
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form method="GET" action="{{ route('web.venues.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Search venues..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="min-w-48">
                    <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                    <select name="city" 
                            id="city"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Cities</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->slug }}" {{ request('city') === $city->slug ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'city']))
                        <a href="{{ route('web.venues.index') }}" 
                           class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Results -->
        @if($venues->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($venues as $venue)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('web.venues.show', $venue) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $venue->name }}
                                    </a>
                                </h3>
                                @if($venue->rating)
                                    <div class="flex items-center">
                                        <span class="text-yellow-400">★</span>
                                        <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">{{ number_format($venue->rating, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            @if($venue->city)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    📍 {{ $venue->city->name }}
                                </p>
                            @endif
                            
                            @if($venue->address)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {{ $venue->address }}
                                </p>
                            @endif
                            
                            @if($venue->description)
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-3">
                                    {{ Str::limit($venue->description, 120) }}
                                </p>
                            @endif
                            
                            <div class="flex items-center justify-between">
                                <a href="{{ route('web.venues.show', $venue) }}" 
                                   class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                                    View Details →
                                </a>
                                <div class="flex items-center gap-2">
                                    @if($venue->treatments_count ?? 0 > 0)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $venue->treatments_count }} treatments
                                        </span>
                                    @endif
                                    @auth
                                        <div class="flex gap-1">
                                            <a href="{{ route('web.venues.edit', $venue) }}" 
                                               class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('web.venues.destroy', $venue) }}" class="inline" onsubmit="return confirmDelete('{{ addslashes($venue->name) }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $venues->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-400 dark:text-gray-500 text-6xl mb-4">🏢</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No venues found</h3>
                <p class="text-gray-600 dark:text-gray-400">Try adjusting your search criteria.</p>
                @if(request()->hasAny(['search', 'city']))
                    <a href="{{ route('web.venues.index') }}" 
                       class="mt-4 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        View All Venues
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection