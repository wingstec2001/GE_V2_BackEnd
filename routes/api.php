<?php

use App\Http\Controllers\MaterialController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ArrivalPlanController;
use App\Http\Controllers\ArrivalActualController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractCrushedController;
use App\Http\Controllers\CrushingPlanController;
use App\Http\Controllers\CrushingActualController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VanningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerformCrushingController;
use App\Http\Controllers\PerformBlendingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockCrushedController;
use App\Http\Controllers\SwaggerTestController;
use App\Http\Controllers\ViewArrivalInfoController;
use App\Http\Controllers\ViewCrushedInfoController;
use App\Http\Controllers\ViewStockMaterialController;
use App\Http\Controllers\ViewStockProductController;
use App\Http\Controllers\ViewStockCrushedController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\GetsujiInfoController;
use App\Http\Controllers\GetsujiProductController;
use App\Http\Controllers\GetsujiMaterialController;
use App\Http\Controllers\GetsujiCrushedController;
use App\Http\Controllers\MonthlyStockController;
use App\Http\Controllers\DailyStockController;
use App\Http\Controllers\BlenderController;
use App\Http\Controllers\NowCrushingReportController;
use App\Http\Controllers\ViewArrivalAllController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ReserveController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\ContractMaterialController;
use App\Http\Controllers\ArrivalPelletController;
use App\Models\GetsujiProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use GrahamCampbell\ResultType\Success;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Scheduling\Event;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//- GET `/api/products` - get the list of existing products
// - GET `/api/products/{id}` - get a product by ID
// - POST `/api/products` - create a new product
// - PUT  `/api/products/{id}` - update a product by ID
// - DELETE `/api/products/{id}` - delete a product by ID

Route::namespace('Api')
    ->prefix('v1')
    ->middleware(['api.refresh'])
    ->group(function () {
        Route::get('/materials', [MaterialController::class, 'All'])->name('all.material');
        Route::post('/materials', [MaterialController::class, 'Add'])->name('add.material');
        Route::get('/materialIds', [MaterialController::class, 'MaterialIds']);
        Route::post('/materials/photo', [MaterialController::class, 'Photo']);
        Route::get('/materials/{id}', [MaterialController::class, 'Detail']);
        Route::put('/materials/{id}', [MaterialController::class, 'Update']);
        Route::delete('/materials/{id}', [MaterialController::class, 'Delete']);

        //products
        Route::get('/products', [ProductController::class, 'All'])->name('all.product');
        Route::post('/products', [ProductController::class, 'Add'])->name('add.products');
        Route::get('/productIds', [ProductController::class, 'ProductIds']);
        Route::post('/products/photo', [ProductController::class, 'Photo']);
        Route::get('/products/{id}', [ProductController::class, 'Detail']);
        Route::put('/products/{id}', [ProductController::class, 'Update']);
        Route::delete('/products/{id}', [ProductController::class, 'Delete']);
        //productions
        Route::get('/productions', [ProductionController::class, 'All']);
        Route::get('/productions/details', [ProductionController::class, 'Details']);
        Route::get('/production/daysum', [ProductionController::class, 'Sum']);
        Route::post('/productions', [ProductionController::class, 'Add']);
        Route::get('/productions/{id}', [ProductionController::class, 'Detail']);
        Route::put('/productions/{id}', [ProductionController::class, 'Update']);
        Route::delete('/productions/{id}', [ProductionController::class, 'Delete']);
        //productionplans
        Route::get('/productionplans', [ProductionPlanController::class, 'All']);
        Route::post('/productionplans', [ProductionPlanController::class, 'Add']);
        Route::get('/productionplans/{id}', [ProductionPlanController::class, 'Detail']);
        Route::put('/productionplans/{id}', [ProductionPlanController::class, 'Update']);
        Route::delete('/productionplans/{id}', [ProductionPlanController::class, 'Delete']);
        //countries
        Route::get('/countries', [CountryController::class, 'All']);
        Route::post('/countries', [CountryController::class, 'Add']);
        Route::get('/countryIds', [CountryController::class, 'CountryIds']);
        Route::get('/countries/{id}', [CountryController::class, 'Detail']);
        Route::put('/countries/{id}', [CountryController::class, 'Update']);
        Route::delete('/countries/{id}', [CountryController::class, 'Delete']);
        //areas
        Route::get('/areas', [AreaController::class, 'All']);
        Route::post('/areas', [AreaController::class, 'Add']);
        Route::get('/areaIds', [AreaController::class, 'AreaIds']);
        Route::get('/areas/{id}', [AreaController::class, 'Detail']);
        Route::put('/areas/{id}', [AreaController::class, 'Update']);
        Route::delete('/areas/{id}', [AreaController::class, 'Delete']);
        //customers
        Route::get('/customers', [CustomerController::class, 'All']);
        Route::post('/customers', [CustomerController::class, 'Add']);
        Route::get('/customerIds', [CustomerController::class, 'CustomerIds']);
        Route::get('/supplierIds', [CustomerController::class, 'SupplierIds']);
        Route::get('/mobileNums', [CustomerController::class, 'MobileNums']);
        Route::get('/customers/{id}', [CustomerController::class, 'Detail']);
        Route::put('/customers/{id}', [CustomerController::class, 'Update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'Delete']);
        //arrivalplans
        Route::get('/arrivalplans', [ArrivalPlanController::class, 'All']);
        Route::get('/arrivalplans/details', [ArrivalPlanController::class, 'Details']);
        Route::post('/arrivalplans', [ArrivalPlanController::class, 'Add']);
        Route::get('/arrivalplans/{id}', [ArrivalPlanController::class, 'Detail']);
        Route::put('/arrivalplans/{id}', [ArrivalPlanController::class, 'Update']);
        Route::delete('/arrivalplans/{id}', [ArrivalPlanController::class, 'Delete']);
        //arrivalactuals
        Route::get('/arrivalactuals', [ArrivalActualController::class, 'All']);
        Route::post('/arrivalactuals', [ArrivalActualController::class, 'Add']);
        Route::get('/arrivalactuals/{arrival_id}', [ArrivalActualController::class, 'Detail']);

        // Route::put('/arrivalactuals/{arrival_id}', [ArrivalActualController::class, 'Update']);
        Route::delete('/arrivalactuals/{arrival_id}', [ArrivalActualController::class, 'Delete']);
        Route::delete('/arrivalactualdetails/{aad_id}', [ArrivalActualController::class, 'ArrivalDetailDelete']);
        Route::get('/arrivalactualdetails/{aad_id}', [ArrivalActualController::class, 'ArrivalActualDetail']);
        Route::put('/arrivalactual/{arrival_id}', [ArrivalActualController::class, 'UpdateArrivalActual']);
        Route::put('/arrivalactualdetails/{aad_id}', [ArrivalActualController::class, 'UpdateArrivalDetail']);
        Route::post('/arrivalactual', [ArrivalActualController::class, 'AddArrivalActual']);
        Route::post('/arrivalactualdetails', [ArrivalActualController::class, 'AddArrivalDetail']);
        Route::get('/arrivalactual', [ArrivalActualController::class, 'ArrivalActuals']);
        Route::get('/orderbydetail/{arrival_id}', [ArrivalActualController::class, 'OrderByDetail']);
        Route::get('/arrivaldetails/', [ArrivalActualController::class, 'AllDetails']); // 2022.04.19
        Route::get('/arrivaldetails/daysum', [ArrivalActualController::class, 'DaySum']); // 2022.04.19

        //arrivalpellets  ペレット入荷
        Route::get('/arrivalpellets', [ArrivalPelletController::class, 'All']);
        Route::post('/arrivalpellets', [ArrivalPelletController::class, 'Add']);
        Route::get('/arrivalpellets/{aad_id}', [ArrivalPelletController::class, 'Detail']);
        Route::put('/arrivalpellets/{aad_id}', [ArrivalPelletController::class, 'Update']);
        Route::delete('/arrivalpellets/{aad_id}', [ArrivalPelletController::class, 'Delete']);

        //crushingplans
        Route::get('/crushingplans', [CrushingPlanController::class, 'All']);
        Route::post('/crushingplans', [CrushingPlanController::class, 'Add']);
        Route::get('/crushingplans/{id}', [CrushingPlanController::class, 'Detail']);
        Route::put('/crushingplans/{id}', [CrushingPlanController::class, 'Update']);
        Route::delete('/crushingplans/{id}', [CrushingPlanController::class, 'Delete']);
        //crushingactuals
        Route::get('/crushingactuals', [CrushingActualController::class, 'All']);
        Route::get('/crushingactuals/details', [CrushingActualController::class, 'Details']);
        Route::get('/crushingactuals/create', [CrushingActualController::class, 'create']);
        Route::post('/crushingactuals', [CrushingActualController::class, 'Add']);
        Route::get('/crushingactuals/{crushed_id}', [CrushingActualController::class, 'Detail']);
        Route::put('/crushingactuals/{crushed_id}', [CrushingActualController::class, 'Update']);
        Route::delete('/crushingactuals/{crushed_id}', [CrushingActualController::class, 'Delete']);

        //2022.04.25
        Route::get('crushed-info', [ViewCrushedInfoController::class, 'DayDetails']);
        Route::get('crushed-info/daysum', [ViewCrushedInfoController::class, 'DaySum']);

        //blender
        Route::get('/blenders', [BlenderController::class, 'All']);
        Route::post('/blenders', [BlenderController::class, 'Add']);
        Route::get('/blender/daysum', [BlenderController::class, 'DaySum']);
        Route::get('/blenders/{id}', [BlenderController::class, 'Detail']);
        Route::delete('/blenders/{id}', [BlenderController::class, 'Delete']);
        Route::post('/blender/addbyweight', [BlenderController::class, 'AddByWeight']);

        //stock-crushed  粉砕済在庫
        // Route::get('stock-crushed/inhouse', [StockCrushedController::class,'ByInhouse']);
        // Route::get('stock-crushed/arrived', [StockCrushedController::class,'ByArrival']);

        // conract-crushed
        Route::get('/contractcrushed', [ContractCrushedController::class, 'All']);
        Route::post('/contractcrushed', [ContractCrushedController::class, 'Add']);
        //   Route::get('/contractcrushed', [ContractCrushedController::class, 'ContractIds']);
        Route::get('/contractcrushed/{id}', [ContractCrushedController::class, 'Detail']);
        Route::put('/contractcrushed/{id}', [ContractCrushedController::class, 'Update']);
        Route::delete('/contractcrushed/{id}', [ContractCrushedController::class, 'Delete']);

        // conract-material 2022.11.07
        Route::get('/contractmaterial', [ContractMaterialController::class, 'All']);
        Route::post('/contractmaterial', [ContractMaterialController::class, 'Add']);
        //   Route::get('/contractcrushed', [ContractCrushedController::class, 'ContractIds']);
        Route::get('/contractmaterial/{id}', [ContractMaterialController::class, 'Detail']);
        Route::put('/contractmaterial/{id}', [ContractMaterialController::class, 'Update']);
        Route::delete('/contractmaterial/{id}', [ContractMaterialController::class, 'Delete']);

        //contracts-pellet
        Route::get('/contracts', [ContractController::class, 'All']);
        Route::post('/contracts', [ContractController::class, 'Add']);
        Route::get('/contractIds', [ContractController::class, 'ContractIds']);
        Route::get('/contracts/{id}', [ContractController::class, 'Detail']);
        Route::put('/contracts/{id}', [ContractController::class, 'Update']);
        Route::delete('/contracts/{id}', [ContractController::class, 'Delete']);

        //vannings
        Route::get('/vannings', [VanningController::class, 'All']);
        Route::post('/vannings', [VanningController::class, 'Add']);
        Route::get('/vannings/{vanning_id}', [VanningController::class, 'Detail']);
        Route::put('/vannings/{vanning_id}', [VanningController::class, 'Update']);
        //Dashboard
        Route::get('/dashboard', [DashboardController::class, 'All']);
        Route::post('/dashboard', [DashboardController::class, 'Add']);
        Route::post('/dashboard/photo', [DashboardController::class, 'Photo']);
        Route::get('/dashboard/{id}', [DashboardController::class, 'Detail']);
        Route::put('/dashboard/{id}', [DashboardController::class, 'Update']);
        Route::delete('/dashboard/{id}', [DashboardController::class, 'Delete']);
        //employees
        Route::get('/employees', [EmployeeController::class, 'All']);
        Route::post('/employees', [EmployeeController::class, 'Add']);
        Route::get('/employeeIds', [EmployeeController::class, 'EmployeeIds']);
        Route::get('/employees/{id}', [EmployeeController::class, 'Detail']);
        Route::put('/employees/{id}', [EmployeeController::class, 'Update']);
        Route::delete('/employees/{id}', [EmployeeController::class, 'Delete']);
        //photos
        Route::post('/photos', [PhotoController::class, 'Add']);
        Route::delete('/photos', [PhotoController::class, 'Delete']);

        //excelファイル出力
        Route::post('monthlyreport/{target_ym}', [MonthlyReportController::class, 'DoExport']);
        Route::get('crushedstockreport', [NowCrushingReportController::class, 'CDoExport']);

        //GetsujiInfo
        Route::get('/getsuji-info', [GetsujiInfoController::class, 'All']);
        Route::get('/getsuji-info/{id}', [GetsujiInfoController::class, 'Detail']);
        Route::put('/getsuji-info/{id}', [GetsujiInfoController::class, 'Update']);

        //GetsujiProduct
        Route::get('/getsuji-product', [GetsujiProductController::class, 'All']);
        Route::get('/getsuji-product/{id}', [GetsujiProductController::class, 'Detail']);
        Route::put('/getsuji-product/{id}', [GetsujiProductController::class, 'Update']);

        //GetsujiMaterial
        Route::get('/getsuji-material', [GetsujiMaterialController::class, 'All']);
        Route::get('/getsuji-material/{id}', [GetsujiMaterialController::class, 'Detail']);
        Route::put('/getsuji-material/{id}', [GetsujiMaterialController::class, 'Update']);

        //GetsujiCrushed
        Route::get('/getsuji-crushed', [GetsujiCrushedController::class, 'All']);
        Route::get('/getsuji-crushed/{id}', [GetsujiCrushedController::class, 'Detail']);
        Route::put('/getsuji-crushed/{id}', [GetsujiCrushedController::class, 'Update']);

        //DailyStock
        Route::get('dailystock-product', [DailyStockController::class, 'GetProdSt']);
        Route::get('recalculate-product', [DailyStockController::class, 'RecalculatePellet']);
        Route::get('dailystock-crushed', [DailyStockController::class, 'GetCruSt']);
        Route::get('recalculate-crushed', [DailyStockController::class, 'RecalculateCrushed']);
        Route::get('dailystock-material', [DailyStockController::class, 'GetMatSt']);
        Route::get('recalculate-material', [DailyStockController::class, 'RecalculateMaterial']);

        //Reserve
        Route::get('/reserve', [ReserveController::class, 'All']);
        Route::post('/reserve', [ReserveController::class, 'Add']);
        Route::post('/reserve/photo', [ReserveController::class, 'Photo']);
        Route::get('/reserve/{id}', [ReserveController::class, 'Detail']);
        Route::put('/reserve/{id}', [ReserveController::class, 'Update']);
        Route::delete('/reserve/{id}', [ReserveController::class, 'Delete']);

        //Reserved
        Route::get('/reserved', [ReservationsController::class, 'All']);
        Route::get('/reserved/{id}', [ReservationsController::class, 'Detail']);
        Route::put('/reserved/{id}', [ReservationsController::class, 'Update']);

        //Bid
        Route::get('/bid', [BidController::class, 'All']);
        Route::post('/bid', [BidController::class, 'Add']);
        Route::post('/bid/photo', [BidController::class, 'Photo']);
        Route::get('/bid/{id}', [BidController::class, 'Detail']);
        Route::put('/bid/{id}', [BidController::class, 'Update']);
        Route::delete('/bid/{id}', [BidController::class, 'Delete']);
    });
Route::group(
    [
        'prefix' => 'v1/auth',
        // 'middleware' => ['cors']
    ],
    function ($router) {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('forgot-password', [NewPasswordController::class, 'forgotPassword']);
        Route::post('reset-password', [NewPasswordController::class, 'reset'])->name('password.reset');
        Route::middleware(['api.refresh'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/user-profile', [AuthController::class, 'userInfo']);

            Route::post('email/verification-notification', [
                EmailVerificationController::class,
                'sendVerificationEmail',
            ]);
            Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name(
                'verification.verify'
            );
        });
    }
);

Route::get('v1/test/email', function () {
    $user = User::Where('email', 'zhanggqmail@gmail.com')->first();
    event(new Registered($user));
    return response()->json(
        [
            'message' => 'User successfully registered',
            'user' => $user,
        ],
        201
    );
});

Route::group(['prefix' => 'v1', 'middleware' => ['api.refresh']], function () {
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('arrive-info', ViewArrivalInfoController::class);
    // Route::resource('crushed-info', ViewCrushedInfoController::class, 'index');
    // Route::resource('crushed-info/daysum', ViewCrushedInfoController::class, 'DaySum');
    Route::resource('stock-material', ViewStockMaterialController::class);
    Route::resource('stock-product', ViewStockProductController::class);
    Route::resource('view-stock-crushed', ViewStockCrushedController::class);
    Route::resource('setting', SettingController::class);
    Route::resource('swagger', SwaggerTestController::class);
    Route::resource('stock-crushed', StockCrushedController::class)->parameters([
        'stock-crushed' => 'id',
    ]);
    Route::resource('perform-curshing', PerformCrushingController::class);
    Route::resource('perform-blending', PerformBlendingController::class);
    Route::resource('monthly-stock', MonthlyStockController::class);
    Route::resource('arrival-all', ViewArrivalAllController::class);
    // Route::resource('arrivalactual-list', ViewArrivalAllController::class);
});
