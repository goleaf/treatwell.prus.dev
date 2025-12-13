@extends('layouts.app')

@section('title', "- {$venue->name}")

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('web.venues.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Venues</a></li>
                <li>→</li>
                <li class="text-gray-900 dark:text-white">{{ $venue->name }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $venue->name }}</h1>
                        <div class="flex items-center gap-4">
                            @if($venue->rating)
                                <div class="flex items-center bg-yellow-50 dark:bg-yellow-900/20 px-3 py-1 rounded-full">
                                    <span class="text-yellow-400 text-lg">★</span>
                                    <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ number_format($venue->rating, 1) }}</span>
                                    @if($venue->rating_count)
                                        <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">({{ $venue->rating_count }})</span>
                                    @endif
                                </div>
                            @endif
                            @auth
                                <div class="flex gap-2">
                                    <a href="{{ route('web.venues.edit', $venue) }}" 
                                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md text-sm font-medium">
                                        Edit Venue
                                    </a>
                                    <form method="POST" action="{{ route('web.venues.destroy', $venue) }}" class="inline" onsubmit="return confirmDelete('{{ addslashes($venue->name) }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm font-medium">
                                            Delete Venue
                                        </button>
                                    </form>
                                </div>
                            @endauth
                        </div>
                    </div>

                    @if($venue->city)
                        <p class="text-lg text-gray-600 dark:text-gray-400 mb-2">
                            📍 {{ $venue->city->name }}
                        </p>
                    @endif

                    @if($venue->address)
                        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $venue->address }}</p>
                    @endif

                    @if($venue->description)
                        <div class="prose dark:prose-invert max-w-none">
                            <p>{{ $venue->description }}</p>
                        </div>
                    @endif
                </div>

                <!-- Treatments -->
                @if($venue->treatments->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Available Treatments</h2>
                        <div class="grid gap-4">
                            @foreach($venue->treatments as $treatment)
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900 dark:text-white">
                                                <a href="{{ route('web.treatments.show', $treatment) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                    {{ $treatment->name }}
                                                </a>
                                            </h3>
                                            @if($treatment->category_name)
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $treatment->category_name }}</p>
                                            @endif
                                            @if($treatment->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ Str::limit($treatment->description, 100) }}</p>
                                            @endif
                                        </div>
                                        <div class="ml-4 text-right">
                                            @if($treatment->price)
                                                <p class="font-semibold text-gray-900 dark:text-white">${{ number_format($treatment->price, 2) }}</p>
                                            @elseif($treatment->min_price && $treatment->max_price)
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    ${{ number_format($treatment->min_price, 2) }} - ${{ number_format($treatment->max_price, 2) }}
                                                </p>
                                            @endif
                                            @if($treatment->duration)
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $treatment->duration }} min</p>
                                            @elseif($treatment->min_duration && $treatment->max_duration)
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $treatment->min_duration }}-{{ $treatment->max_duration }} min
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Contact Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h3>
                    
                    @if($venue->phone)
                        <div class="flex items-center mb-3">
                            <span class="text-gray-400 mr-3">📞</span>
                            <a href="tel:{{ $venue->phone }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $venue->phone }}
                            </a>
                        </div>
                    @endif

                    @if($venue->email)
                        <div class="flex items-center mb-3">
                            <span class="text-gray-400 mr-3">✉️</span>
                            <a href="mailto:{{ $venue->email }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $venue->email }}
                            </a>
                        </div>
                    @endif

                    @if($venue->website)
                        <div class="flex items-center mb-3">
                            <span class="text-gray-400 mr-3">🌐</span>
                            <a href="{{ $venue->website }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                Visit Website
                            </a>
                        </div>
                    @endif

                    @if($venue->address)
                        <div class="flex items-start">
                            <span class="text-gray-400 mr-3 mt-1">📍</span>
                            <div>
                                <p class="text-gray-700 dark:text-gray-300">{{ $venue->address }}</p>
                                @if($venue->city)
                                    <p class="text-gray-600 dark:text-gray-400">{{ $venue->city->name }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Opening Hours -->
                @if($venue->openingHours->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Opening Hours</h3>
                        <div class="space-y-2">
                            @foreach($venue->openingHours as $hour)
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $hour->day_of_week }}</span>
                                    <span class="text-gray-900 dark:text-white">
                                        @if($hour->is_closed)
                                            Closed
                                        @else
                                            {{ $hour->open_time }} - {{ $hour->close_time }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection