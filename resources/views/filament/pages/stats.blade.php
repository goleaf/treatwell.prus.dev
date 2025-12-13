<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Venues</h3>
                <p class="mt-2 text-3xl font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($this->getStats()['total_venues']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getStats()['active_venues'] }} active ({{ $this->getStats()['active_venues_percentage'] }}%) / {{ $this->getStats()['inactive_venues'] }} inactive
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Cities</h3>
                <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($this->getStats()['total_cities']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getStats()['cities_with_venues'] }} with venues / {{ $this->getStats()['cities_without_venues'] }} without venues
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Treatments</h3>
                <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($this->getStats()['total_treatments']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getStats()['active_treatments'] }} active / {{ $this->getStats()['inactive_treatments'] }} inactive
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Ratings</h3>
                <p class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($this->getStats()['total_ratings']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getStats()['verified_ratings'] }} verified / {{ $this->getStats()['unverified_ratings'] }} unverified
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Average Venue Rating</h3>
                <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ number_format($this->getStats()['average_rating'], 2) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Based on venue aggregate ratings
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Average Overall Rating</h3>
                <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ number_format($this->getStats()['average_rating_all'], 2) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Based on all individual ratings
                </p>
            </div>

            @if($this->getStats()['top_rated_venue'])
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Rated Venue</h3>
                    <p class="mt-2 text-xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ $this->getStats()['top_rated_venue']['name'] }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Rating: {{ number_format($this->getStats()['top_rated_venue']['rating'], 2) }}
                    </p>
                </div>
            @endif

            @if($this->getStats()['most_ratings_venue'])
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Most Rated Venue</h3>
                    <p class="mt-2 text-xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $this->getStats()['most_ratings_venue']['name'] }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Reviews: {{ number_format($this->getStats()['most_ratings_venue']['count']) }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
