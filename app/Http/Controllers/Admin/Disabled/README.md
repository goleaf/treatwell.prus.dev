# Disabled Admin Controllers

These admin controllers have been disabled as part of removing authentication and admin functionality from the application.

## Disabled Controllers:
- CityCrudController.php
- CountryCrudController.php  
- LocationCrudController.php
- RatingCrudController.php
- TreatmentCrudController.php
- VenueCrudController.php

All admin routes in `routes/backpack/custom.php` have also been commented out.

If you need to re-enable admin functionality:
1. Move these controllers back to `app/Http/Controllers/Admin/`
2. Uncomment the routes in `routes/backpack/custom.php`
3. Re-enable authentication middleware in `bootstrap/app.php`
4. Restore the CheckIfAdmin middleware