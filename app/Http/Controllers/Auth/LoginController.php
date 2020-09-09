<?php

namespace App\Http\Controllers\Auth;

use Google_Client;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Socialite;
use Illuminate\Support\Facades\Auth;
use App\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    private $gclient;
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectToProvider()
    {
        return Socialite::driver('google')
            ->scopes([
                'openid','profile','email',
                \Google_Service_Sheets::SPREADSHEETS,
            ])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('google')->stateless()->user();
        // dd($user);

        $authuser = $this->findOrCreateUser($user);
        Auth::login($authuser);
        // $user->token;

        return redirect('/user/home');
    }

    public function findOrCreateUser($user)
    {
        $cek = User::where('google_id', $user->id)->count();

        $google_client_token = [
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'expires_in' => $user->expiresIn,
        ];


        if ($cek > 0) {
            $data = User::where('google_id', $user->id)->first();
        }else{
            $data = new User;
            $data->fullname = $user->name;
            $data->email = !empty($user->email) ? $user->email : '';
            $data->google_id = $user->id;
        }


        $data->avatar = $user->avatar;
        $data->token = json_encode($google_client_token);
        $data->save();

        return $data;
    }
}
