<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\API;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class AuthController extends Controller
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::post('login', [AuthController::class, 'login']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        $credentials = $request->only(["username", 'password']);
        if (Auth::attempt($credentials)) {
            $user = User::find(Auth::user()->id);
            // $user->tokens()->delete();
            $user->update(['login_times_in_day'=>$user->login_times_in_day + 1, 'last_use_at'=>Carbon::now()]);
            $user->token = $user->createToken('')->plainTextToken;
            return $this->success($user, 'Đăng nhập thành công');
        }
        return $this->failure([], 'Sai tên đăng nhập hoặc mật khẩu!');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (isset($user))
            $user->tokens()->delete();
        return $this->success();
    }

    public function userChangePassword(Request $request)
    {
        $user = $request->user();
        if (!$request->password || !$request->newPassword) {
            return $this->failure([], "password and newPassword is required");
        }

        if (Hash::check($request->password, $user->password)) {
            $user->password = Hash::make($request->newPassword);
            $user->save();
            return $this->success();
        }
        return $this->failure([], "Incorrect password");
    }
}
