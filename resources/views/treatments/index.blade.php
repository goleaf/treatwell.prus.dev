@extends('layouts.app')

@section('title', '- Treatments')

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Treatments</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Browse available treatments and services</p>
                </div>
                @auth
                    <a href="{{ route('web.treatments.create') }}" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Add New Treatment
                    </a>
                @endauth
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form method="GET" action="{{ route('web.treatments.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Search treatments..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="min-w-48">
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select name="category" 
                            id="category"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="min-w-32">
                    <label for="min_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Price</label>
                    <input type="number" 
                           name="min_price" 
                           id="min_price"
                           value="{{ request('min_price') }}"
                           placeholder="0"
                           min="0"
                           step="0.01"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="min-w-32">
                    <label for="max_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Price</label>
                    <input type="number" 
                           name="max_price" 
                           id="max_price"
                           value="{{ request('max_price') }}"
                           placeholder="1000"
                           min="0"
                           step="0.01"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'category', 'min_price', 'max_price']))
                        <a href="{{ route('web.treatments.index') }}" 
                           class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Results -->
        @if($treatments->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($treatments as $treatment)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('web.treatments.show', $treatment) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $treatment->name }}
                                    </a>
                                </h3>
                                <div class="text-right">
                                    @if($treatment->price)
                                        <p class="font-semibold text-gray-900 dark:text-white">${{ number_format($treatment->price, 2) }}</p>
                                    @elseif($treatment->min_price && $treatment->max_price)
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">
                                            ${{ number_format($treatment->min_price, 2) }} - ${{ number_format($treatment->max_price, 2) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            
                            @if($treatment->category_name)
                                <span class="inline-block bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 text-xs px-2 py-1 rounded-full mb-3">
                                    {{ $treatment->category_name }}
                                </span>
                            @endif
                            
                            @if($treatment->venue)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    📍 <a href="{{ route('web.venues.show', $treatment->venue) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $treatment->venue->name }}
                                    </a>
                                    @if($treatment->venue->city)
                                        - {{ $treatment->venue->city->name }}
                                    @endif
                                </p>
                            @endif
                            
                            @if($treatment->description)
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-3">
                                    {{ Str::limit($treatment->description, 120) }}
                                </p>
                            @endif
                            
                            <div class="flex items-center justify-between">
                                <a href="{{ route('web.treatments.show', $treatment) }}" 
                                   class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                                    View Details →
                                </a>
                                <div class="flex items-center gap-2">
                                    @if($treatment->duration)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $treatment->duration }} min
                                        </span>
                                    @elseif($treatment->min_duration && $treatment->max_duration)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $treatment->min_duration }}-{{ $treatment->max_duration }} min
                                        </span>
                                    @endif
                                    @auth
                                        <div class="flex gap-1">
                                            <a href="{{ route('web.treatments.edit', $treatment) }}" 
                                               class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('web.treatments.destroy', $treatment) }}" class="inline" onsubmit="return confirmDelete('{{ addslashes($treatment->name) }}')">
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
                {{ $treatments->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-400 dark:text-gray-500 text-6xl mb-4">💆</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No treatments found</h3>
                <p class="text-gray-600 dark:text-gray-400">Try adjusting your search criteria.</p>
                @if(request()->hasAny(['search', 'category', 'min_price', 'max_price']))
                    <a href="{{ route('web.treatments.index') }}" 
                       class="mt-4 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        View All Treatments
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection