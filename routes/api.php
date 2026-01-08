<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookHallController;
use App\Http\Controllers\BookRoomController;
use App\Http\Controllers\MassageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PoolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RestaurantOrderController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['middleware' => 'auth:sanctum'], function ()
 {
Route::get('/logout', [UserController::class, 'logout']);
});

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/refresh',  [UserController::class, 'refresh']);
Route::post('/auth/verify-otp', [UserController::class, 'verifyOtp']);
Route::post('/auth/resend-otp', [UserController::class, 'resendOtp']);

Route::get('room-booking/payment/success/{bookingId}', [PaymentController::class,'paymentRoomBookingSuccess'])->name('room.payment.success');
Route::get('massage-booking/payment/success/{massageRequestId}', [PaymentController::class, 'paymentSuccessMassage'])->name('massage.payment.success');
Route::get('pool-booking/payment/success/{reservationId}', [PaymentController::class,'paymentSuccessPool'])->name('pool.payment.success');
Route::get('hall-booking/payment/success/{bookingId}', [PaymentController::class, 'paymentSuccessHall'])->name('hall.payment.success');
Route::get('/restaurant-orders/payment/success/{orderId}', [PaymentController::class, 'paymentSuccessRestaurant'])->name('restaurant.payment.success');






Route::middleware('auth:sanctum', 'role:Guest', 'email.verified')->group(function () {
    Route::get('GetRoomTypes',[BookRoomController::class,'getRoomTypes']);
    Route::get('GetRoomsByType/{TypeId}',[BookRoomController::class,'getRoomsByType']);
    Route::get('getRandomRooms',[BookRoomController::class,'getRandomRooms']);
    Route::get('getRoomDetails/{roomId}',[BookRoomController::class,'getRoomDetails']);
    Route::post('BookRoom',[BookRoomController::class,'BookRoom']);
    Route::post('CancelBookingByGuest/{bookingId}',[BookRoomController::class,'cancelBooking']);
    Route::post('getAvailableRoomsByDate',[BookRoomController::class,'getAvailableRoomsByDate']);
    Route::get('getMyInvoicesByStatus/{status}',[UserController::class,'getMyInvoicesByStatus']);
    Route::get('room-booking/pay/{bookingId}', [PaymentController::class,'payRoomBooking']);
    Route::get('/room-checkout/verify/{token}', [BookRoomController::class, 'verifyCheckout'])
    ->name('room.checkout.verify');
    Route::post('uploadMyPhoto',[UserController::class,'uploadMyPhoto']);
    Route::get('profile',[UserController::class,'profile']);
    Route::get('getMyBookingsByStatus/{status}',[BookRoomController::class,'getMyBookingsByStatus']);
    Route::post('RequestOrderByUser', [RestaurantOrderController::class, 'RequestOrderByUser']);
    Route::post('cancelOrdersByUser/{id}', [RestaurantOrderController::class, 'cancelByUser']);
    Route::get('getListMenuItemsByUser', [RestaurantOrderController::class, 'getListMenuItems']);
    Route::get('getUserOrdersByUser', [RestaurantOrderController::class, 'getUserOrders']);
    Route::get('getMenuItemByType/{type}', [RestaurantOrderController::class, 'getMenuItemByType']);
    Route::get('/restaurant-orders/pay/{orderId}', [PaymentController::class, 'payRestaurantOrder']);
    Route::get('getavailableEmployees', [MassageController::class, 'getavailableEmployees']);
    Route::post('RequestMassage', [MassageController::class, 'RequestMassage']);
    Route::get('getMyMassReqByStatus', [MassageController::class, 'getUserMassageRequestsByStatus']);
    Route::post('cancelMassReqByUser/{id}', [MassageController::class, 'cancelMassageRequestByUser']);
    Route::get('massage-booking/pay/{massageRequestId}', [PaymentController::class, 'payMassage']);
    Route::post('RequestPoolReservation', [PoolController::class, 'requestPoolReservation']);
    Route::post('cancelMyPoolReservation/{id}',[PoolController::class,'cancelPoolReservation']);
    Route::get('checkPoolAvailability',[PoolController::class,'checkPoolAvailability']);
    Route::get('getMyPoolReservationsByStatus',[PoolController::class,'getMyPoolReservationsByStatus']);
    Route::get('pool-booking/pay/{reservationId}', [PaymentController::class,'payPool']);
    Route::get('getOccasionTypes', [BookHallController::class, 'getOccasionTypes']);
    Route::post('BookHallByUser', [BookHallController::class, 'BookHall']);
    Route::get('getHospitalitiesByOccasionByUser/{occasionType}', [BookHallController::class, 'getHospitalitiesByOccasion']);
    Route::get('getDecorationsByOccasionByUser/{occasionType}', [BookHallController::class, 'getDecorationsByOccasion']);
    Route::post('cancelHallBookingByUser/{id}', [BookHallController::class, 'cancelHallBookingByUser']);
    Route::post('updateHallBooking/{id}', [BookHallController::class, 'updateHallBooking']);
    Route::get('hall-booking/pay/{bookingId}', [PaymentController::class, 'payHallBooking']);
    Route::post('addComplaintByUser',[UserController::class,'addComplaintByUser']);
    Route::post('addRatingByUser',[UserController::class,'addRatingByUser']);
});

Route::middleware('auth:sanctum', 'role:Receptionist', 'email.verified')->group(function () {
    Route::get('getRoomTypes',[BookRoomController::class,'getRoomTypes']);
    Route::get('getRoomsByType/{TypeId}',[BookRoomController::class,'getRoomsByType']);
    Route::post('ApproveBooking/{bookingId}',[BookRoomController::class,'ApproveBooking']);
    Route::post('CancelBookingByReceptionist/{bookingId}',[BookRoomController::class,'cancelBooking']);
    Route::post('checkAvailability',[BookRoomController::class,'checkAvailability']);
    Route::get('showRoomBookingInvoice/{bookingId}',[BookRoomController::class,'showRoomBookingInvoice']);
    Route::post('assignRoomToBooking',[BookRoomController::class,'assignRoomToBooking']);
    Route::post('BookRoomByReceptionist',[BookRoomController::class,'bookRoomByReceptionist']);
    Route::get('getBookedRoomsByDate',[BookRoomController::class,'getBookedRoomsByDate']);
    Route::get('getUserBookingsByStatus/{status}',[BookRoomController::class,'getUserBookingsByStatus']);
    Route::get('getReceptionBookingsByStatus/{status}',[BookRoomController::class,'getReceptionBookingsByStatus']);
    Route::get('booking-checkout-qr/{bookingId}', [BookRoomController::class,'generateCheckoutQr']);
    Route::post('checkoutReceptionBooking/{bookingId}',[BookRoomController::class,'checkoutReceptionBooking']);
    Route::post('cancelMassReqByReception/{id}', [MassageController::class, 'cancelMassageRequestByReception']);
        Route::post('approveMassageRequest/{id}', [MassageController::class, 'approveMassageRequest']);
    Route::get('getMassReqByStatus',[MassageController::class,'getMassageRequestsByStatus']);
    Route::post('BookMassageByReception',[MassageController::class,'bookMassageByReception']);
    Route::post('ApprovePoolReservation/{id}',[PoolController::class,'approvePoolReservation']);
    Route::post('cancelPoolReservation/{id}',[PoolController::class,'cancelPoolReservation']);
    Route::get('getPoolReservationsByStatus',[PoolController::class,'getPoolReservationsByStatus']);
    Route::post('ReservePoolByReception',[PoolController::class,'reservePoolByReception']);
    Route::get('CheckPoolAvailability',[PoolController::class,'checkPoolAvailability']);
    Route::put('acceptHallBookingByReceptionist/{id}', [BookHallController::class, 'acceptHallBookingByReceptionist']);
    Route::put('rejectHallBookingByReceptionist/{id}', [BookHallController::class, 'rejectHallBookingByReceptionist']);
    Route::get('getHallBookingsByStatus/{status}',[BookHallController::class,'getHallBookingsByStatus']);
    Route::post('BookHallByReceptionistForUser', [BookHallController::class, 'BookHallByReceptionistForUser']);
    Route::post('previewHallBookingForReceptionist', [BookHallController::class, 'previewHallBookingForReceptionist']);
});


Route::middleware(['auth:sanctum', 'role:Restaurant_Supervisor', 'email.verified'])->group(function () {
    Route::get('getAllOrders', [RestaurantOrderController::class, 'getAllOrders']);
    Route::put('approveOrderBYSupervisor/{id}', [RestaurantOrderController::class, 'approveOrderBYSupervisor']);
    Route::put('rejectOrderBYSupervisor/{id}', [RestaurantOrderController::class, 'rejectOrderBYSupervisor']);
    Route::get('ordersByStatus/{status}', [RestaurantOrderController::class, 'ordersByStatus']);
    Route::get('getOrdersByDateRange', [RestaurantOrderController::class, 'getOrdersByDateRange']);
    Route::get('getAllInvoices', [RestaurantOrderController::class, 'getAllInvoices']);
    Route::get('invoicesByDateRange', [RestaurantOrderController::class, 'invoicesByDateRange']);
    Route::get('invoicesByStatus/{status}', [RestaurantOrderController::class, 'invoicesByStatus']);
    Route::get('getListMenuItems', [RestaurantOrderController::class, 'getListMenuItems']);
    Route::post('AddMenuItem', [RestaurantOrderController::class, 'addMenuItem']);
    Route::delete('DeleteMenuItem/{id}', [RestaurantOrderController::class, 'deleteMenuItem']);
});

Route::middleware('auth:sanctum', 'role:admin', 'email.verified')->group(function () {
    Route::post('/AddRoom',[AdminController::class,'AddRoom']);
    Route::post('AddRoomImage/{id}',[AdminController::class,'addRoomImage']);
    Route::post('addEmployee',[AdminController::class,'addEmployee']);
    Route::post('updateEmployee/{id}',[AdminController::class,'updateEmployee']);
    Route::delete('deleteEmployee/{id}',[AdminController::class,'deleteEmployee']);
    Route::post('updateRoom/{id}',[AdminController::class,'updateRoom']);
    Route::get('getEmployees',[AdminController::class,'getEmployees']);
    Route::get('getRooms',[AdminController::class,'getRooms']);
    Route::get('getCurrentGuests',[AdminController::class,'getCurrentGuests']);
    Route::get('getGuestBookingArchive',[AdminController::class,'getGuestBookingArchive']);
    Route::get('getOverviewStats',[AdminController::class,'getOverviewStats']);
    Route::get('getRevenueStats',[AdminController::class,'getRevenueStats']);
    Route::get('getOccupancyStatistics',[AdminController::class,'getOccupancyStatistics']);
    Route::get('getRestaurantWeeklyStats',[AdminController::class,'getRestaurantWeeklyStats']);
    Route::post('AddServicePrice',[AdminController::class,'addServicePrice']);
    Route::post('activateOldServicePrice/{PriceId}',[AdminController::class,'activateOldServicePrice']);
    Route::get('getServicePriceByType/{serviceType}',[AdminController::class,'getServicePriceByType']);
    Route::delete('deleteServicePrice/{PriceId}',[AdminController::class,'deleteServicePrice']);
    Route::post('addPromotion',[AdminController::class,'addPromotion']);
    Route::post('activateOldPromotion/{id}',[AdminController::class,'activateOldPromotion']);
    Route::get('getPromotionsByType/{promotion_type}',[AdminController::class,'getPromotionsByType']);
    Route::delete('deletePromotion/{id}',[AdminController::class,'deletePromotion']);
    Route::get('getMonthlyRevenueReport',[AdminController::class,'getMonthlyRevenueReport']);
    Route::get('getInvoiceReport',[AdminController::class,'getInvoiceReport']);
    Route::get('getRoomOccupancyReport',[AdminController::class,'getRoomOccupancyReport']);
    Route::get('getActivityReport',[AdminController::class,'getActivityReport']);
    Route::get('getUserInvoices/{userId}',[AdminController::class,'getUserInvoices']);
    Route::get('getAllComplaints',[AdminController::class,'getAllComplaints']);
    Route::get('getGuestsWithInvoices', [AdminController::class, 'getGuestsWithInvoices']);//نزلاء مع فواتير
});

