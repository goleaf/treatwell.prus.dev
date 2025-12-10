{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-item title="Countries" icon="la la-question" :link="backpack_url('country')" />
<x-backpack::menu-item title="Cities" icon="la la-question" :link="backpack_url('city')" />
<x-backpack::menu-item title="Venues" icon="la la-question" :link="backpack_url('venue')" />
<x-backpack::menu-item title="Locations" icon="la la-question" :link="backpack_url('location')" />
<x-backpack::menu-item title="Treatments" icon="la la-question" :link="backpack_url('treatment')" />
<x-backpack::menu-item title="Ratings" icon="la la-question" :link="backpack_url('rating')" />