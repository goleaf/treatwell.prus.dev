@extends('layouts.app')

@section('title', "- {$treatment->name}")

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('web.treatments.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Treatments</a></li>
                <li>→</li>
                <li class="text-gray-900 dark:text-white">{{ $treatment->name }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $treatment->name }}</h1>
                            @if($treatment->category_name)
                                <span class="inline-block bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 text-sm px-3 py-1 rounded-full">
                                    {{ $treatment->category_name }}
                                </span>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="flex items-start gap-4">
                                <div>
                                    @if($treatment->price)
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($treatment->price, 2) }}</p>
                                    @elseif($treatment->min_price && $treatment->max_price)
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                            ${{ number_format($treatment->min_price, 2) }} - ${{ number_format($treatment->max_price, 2) }}
                                        </p>
                                    @endif
                                    @if($treatment->duration)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $treatment->duration }} minutes</p>
                                    @elseif($treatment->min_duration && $treatment->max_duration)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $treatment->min_duration }}-{{ $treatment->max_duration }} minutes
                                        </p>
                                    @endif
                                </div>
                                @auth
                                    <div class="flex gap-2">
                                        <a href="{{ route('web.treatments.edit', $treatment) }}" 
                                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md text-sm font-medium">
                                            Edit Treatment
                                        </a>
                                        <form method="POST" action="{{ route('web.treatments.destroy', $treatment) }}" class="inline" onsubmit="return confirmDelete('{{ addslashes($treatment->name) }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm font-medium">
                                                Delete Treatment
                                            </button>
                                        </form>
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>

                    @if($treatment->description)
                        <div class="prose dark:prose-invert max-w-none">
                            <p>{{ $treatment->description }}</p>
                        </div>
                    @endif

                    @if($treatment->options && is_array($treatment->options) && count($treatment->options) > 0)
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Treatment Options</h3>
                            <div class="grid gap-2">
                                @foreach($treatment->options as $option)
                                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <span class="text-gray-700 dark:text-gray-300">{{ is_array($option) ? json_encode($option) : $option }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Venue Information -->
                @if($treatment->venue)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Available At</h2>
                        <div class="border dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        <a href="{{ route('web.venues.show', $treatment->venue) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $treatment->venue->name }}
                                        </a>
                                    </h3>
                                    @if($treatment->venue->city)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            📍 {{ $treatment->venue->city->name }}
                                        </p>
                                    @endif
                                    @if($treatment->venue->address)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $treatment->venue->address }}
                                        </p>
                                    @endif
                                </div>
                                @if($treatment->venue->ratingDetails && $treatment->venue->ratingDetails->rating)
                                    <div class="flex items-center bg-yellow-50 dark:bg-yellow-900/20 px-3 py-1 rounded-full">
                                        <span class="text-yellow-400">★</span>
                                        <span class="ml-1 font-semibold text-gray-900 dark:text-white">
                                            {{ number_format($treatment->venue->ratingDetails->rating, 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4 flex gap-4">
                                <a href="{{ route('web.venues.show', $treatment->venue) }}" 
                                   class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                                    View Venue Details →
                                </a>
                                @if($treatment->venue->phone)
                                    <a href="tel:{{ $treatment->venue->phone }}" 
                                       class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">
                                        📞 Call Now
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Quick Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Treatment Details</h3>
                    
                    @if($treatment->price)
                        <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Price</span>
                            <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($treatment->price, 2) }}</span>
                        </div>
                    @elseif($treatment->min_price && $treatment->max_price)
                        <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Price Range</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($treatment->min_price, 2) }} - ${{ number_format($treatment->max_price, 2) }}
                            </span>
                        </div>
                    @endif

                    @if($treatment->duration)
                        <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Duration</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $treatment->duration }} min</span>
                        </div>
                    @elseif($treatment->min_duration && $treatment->max_duration)
                        <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Duration</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $treatment->min_duration }}-{{ $treatment->max_duration }} min
                            </span>
                        </div>
                    @endif

                    @if($treatment->category_name)
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600 dark:text-gray-400">Category</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $treatment->category_name }}</span>
                        </div>
                    @endif
                </div>

                <!-- Contact Venue -->
                @if($treatment->venue)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Book This Treatment</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Contact the venue directly to book this treatment.</p>
                        
                        <div class="space-y-3">
                            @if($treatment->venue->phone)
                                <a href="tel:{{ $treatment->venue->phone }}" 
                                   class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center block">
                                    📞 Call {{ $treatment->venue->phone }}
                                </a>
                            @endif

                            @if($treatment->venue->email)
                                <a href="mailto:{{ $treatment->venue->email }}" 
                                   class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center block">
                                    ✉️ Email Venue
                                </a>
                            @endif

                            @if($treatment->venue->website)
                                <a href="{{ $treatment->venue->website }}" 
                                   target="_blank" 
                                   rel="noopener"
                                   class="w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md text-sm font-medium text-center block">
                                    🌐 Visit Website
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection