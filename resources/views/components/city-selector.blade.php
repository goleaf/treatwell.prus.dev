@props(['mainCities', 'selectedCity' => null, 'subregions' => null, 'includeSubregions' => true])

<div class="city-selector-container">
    <div class="mb-4">
        <label for="city_id" class="block text-sm font-medium text-gray-700">City</label>
        <select id="city_id" name="city_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm city-selector">
            <option value="">All Cities</option>
            @foreach($mainCities as $city)
                <option value="{{ $city->id }}" @if($selectedCity && $selectedCity->id == $city->id) selected @endif>
                    {{ $city->name }} ({{ $city->locations_count ?? $city->locations()->count() }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-4">
        <div class="flex items-center">
            <input type="checkbox" id="include_subregions" name="include_subregions" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" @if($includeSubregions) checked @endif>
            <label for="include_subregions" class="ml-2 block text-sm text-gray-900">
                Include all subregions
            </label>
        </div>
    </div>

    @if($subregions && count($subregions) > 0)
        <div class="mb-4 subregion-selector" @if($includeSubregions) style="display: none;" @endif>
            <label for="subregion" class="block text-sm font-medium text-gray-700">Subregion</label>
            <select id="subregion" name="subregion" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">All Subregions</option>
                @foreach($subregions as $subregion)
                    <option value="{{ $subregion }}" @if(request('subregion') == $subregion) selected @endif>
                        {{ $subregion }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
</div>

<script>
    // Toggle subregion visibility based on include_subregions checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const includeSubregionsCheckbox = document.getElementById('include_subregions');
        const subregionSelector = document.querySelector('.subregion-selector');
        
        if (includeSubregionsCheckbox && subregionSelector) {
            includeSubregionsCheckbox.addEventListener('change', function() {
                subregionSelector.style.display = this.checked ? 'none' : 'block';
            });
        }
        
        // Handle city selection change - fetch subregions via AJAX
        const citySelector = document.querySelector('.city-selector');
        
        if (citySelector) {
            citySelector.addEventListener('change', function() {
                const cityId = this.value;
                
                if (!cityId) {
                    // Reset subregion selector if "All Cities" is selected
                    if (subregionSelector) {
                        subregionSelector.style.display = 'none';
                        const subregionSelect = document.getElementById('subregion');
                        if (subregionSelect) {
                            subregionSelect.innerHTML = '<option value="">All Subregions</option>';
                        }
                    }
                    return;
                }
                
                // Fetch subregions for selected city via AJAX
                fetch(`/api/main-cities/${cityId}/subregions`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0 && subregionSelector) {
                            // Update subregion options
                            const subregionSelect = document.getElementById('subregion');
                            if (subregionSelect) {
                                subregionSelect.innerHTML = '<option value="">All Subregions</option>';
                                
                                // Group subregions by subregion name
                                const subregionGroups = {};
                                data.forEach(city => {
                                    if (city.subregion) {
                                        if (!subregionGroups[city.subregion]) {
                                            subregionGroups[city.subregion] = [];
                                        }
                                        subregionGroups[city.subregion].push(city);
                                    }
                                });
                                
                                // Add options for each subregion
                                Object.keys(subregionGroups).sort().forEach(subregion => {
                                    const option = document.createElement('option');
                                    option.value = subregion;
                                    option.textContent = subregion;
                                    subregionSelect.appendChild(option);
                                });
                                
                                // Show subregion selector if not including all subregions
                                if (!includeSubregionsCheckbox.checked) {
                                    subregionSelector.style.display = 'block';
                                }
                            }
                        } else if (subregionSelector) {
                            subregionSelector.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching subregions:', error);
                    });
            });
        }
    });
</script> 