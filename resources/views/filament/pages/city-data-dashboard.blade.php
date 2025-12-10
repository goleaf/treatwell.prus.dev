<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    Welcome to City Data Parser
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        This interface provides comprehensive access to parsed city data including venues, treatments, services, and location information collected from various APIs.
                    </p>
                </div>
                <div class="mt-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-building-office class="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                                Venues
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                                {{ \App\Models\Venue::count() }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-map-pin class="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                                Cities
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                                {{ \App\Models\City::count() }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-heart class="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                                Treatments
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                                {{ \App\Models\Treatment::count() }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    Available Data Views
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        Navigate through the sidebar to explore different data categories. All data is read-only and updated through the parsing system.
                    </p>
                </div>
                <div class="mt-5">
                    <ul class="space-y-2">
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-check class="h-4 w-4 text-green-500 mr-2" />
                            Venues - Complete venue information with locations and services
                        </li>
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-check class="h-4 w-4 text-green-500 mr-2" />
                            Cities - Geographic data with venue relationships
                        </li>
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-check class="h-4 w-4 text-green-500 mr-2" />
                            Treatments - Service offerings with pricing and duration
                        </li>
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-check class="h-4 w-4 text-green-500 mr-2" />
                            Locations - Address and coordinate information
                        </li>
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <x-heroicon-o-check class="h-4 w-4 text-green-500 mr-2" />
                            Ratings - Review scores and feedback data
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>