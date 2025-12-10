<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\VenueRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class VenueCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class VenueCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Venue::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/venue');
        CRUD::setEntityNameStrings('venue', 'venues');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('city')->type('relationship')->label('City');
        CRUD::column('address');
        CRUD::column('rating')->type('number');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(VenueRequest::class);

        CRUD::field('city_id')->type('relationship')->label('City');
        CRUD::field('name')->type('text');
        CRUD::field('slug')->type('text');
        CRUD::field('description')->type('textarea');
        CRUD::field('address')->type('text');
        CRUD::field('latitude')->type('number')->attributes(['step' => 'any']);
        CRUD::field('longitude')->type('number')->attributes(['step' => 'any']);
        CRUD::field('phone')->type('text');
        CRUD::field('email')->type('email');
        CRUD::field('website')->type('url');
        CRUD::field('is_active')->type('boolean')->default(1);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
