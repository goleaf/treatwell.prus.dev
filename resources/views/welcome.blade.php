@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-8">
                Welcome to {{ config('app.name', 'Laravel') }}
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400 mb-12">
                Discover amazing venues and treatments in your area
            </p>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-2xl mx-auto mb-12">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <div class="text-4xl mb-4">🏢</div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Browse Venues</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Find the perfect venue for your needs
                    </p>
                    <a href="{{ route('web.venues.index') }}" 
                       class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md font-medium">
                        View Venues
                    </a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <div class="text-4xl mb-4">💆</div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Explore Treatments</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Discover treatments and services available
                    </p>
                    <a href="{{ route('web.treatments.index') }}" 
                       class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md font-medium">
                        View Treatments
                    </a>
                </div>
            </div>

            <!-- Admin Links -->
            <div class="border-t dark:border-gray-700 pt-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Admin Access</h3>
                <div class="flex justify-center gap-4">
                    <a href="/admin" 
                       class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                        Admin Panel
                    </a>
                    <span class="text-gray-400">•</span>
                    <a href="/api/documentation" 
                       class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                        API Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
