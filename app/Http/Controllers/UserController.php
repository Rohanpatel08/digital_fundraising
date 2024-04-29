<?php

namespace App\Http\Controllers;


use App\CountryOption;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseController;
use App\Http\Resources\UserResource;
use App\Models\AccountPlan;
use App\Models\Plan;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;
use \Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $responseController;

    public function __construct(ResponseController $responseController)
    {
        $this->responseController = $responseController;
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required | string',
                'last_name' => 'required | string',
                'nonprofit_name' => 'required | string',
                'email' => 'required | email | unique:users,email',
                'password' => ['required', Password::min(8)],
                'country' => ['required', new Enum(CountryOption::class)],
            ], [
                'first_name.required' => 'First name is required.',
                'first_name.string' => 'First name must be containing characters.',
                'last_name.required' => 'Last name is required.',
                'last_name.string' => 'First name must be containing characters.',
                'nonprofit_name.required' => 'Non profit name is required.',
                'nonprofit_name.string' => 'Non profit name must be containing characters.',
                'email.required' => 'Email is required.',
                'email.email' => 'Enter valid email address.',
                'email.unique' => 'This email is already in use.',
                'country.required' => 'country is required.'
            ]);

            $user = User::where('nonprofit_name', $request->nonprofit_name)->where('country', $request->country)->first();
            if (!$user) {
                $user = new User;
                $user->first_name = $request['first_name'];
                $user->last_name = $request['last_name'];
                $user->nonprofit_name = $request['nonprofit_name'];
                $user->email = $request['email'];
                $user->password = Hash::make($request['password']);
                $user->country = $request['country'];
                $user->save();
                $user->sendEmailVerificationNotification();
                Auth::login($user, true);
                $user = new UserResource($user);
            } else {
                return $this->responseController->responseValidationError('Error in Registration', "Account '" . $request->nonprofit_name . "' is already created in " . $request->country);
            }
            return $this->responseController->responseValidation('User Created', $user);
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return $this->responseController->responseValidationError('Error in Registration', $error);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required | email',
                'password' => ['required', Password::min(8)->numbers()]
            ]);
            $user = User::where("email", $request['email'])->first();
            if ($user) {
                if (Hash::check($request['password'], $user->password)) {
                    $token = $user->createToken($user->username . '-AuthToken')->plainTextToken;
                    Auth::login($user, true);
                    return response()->json(['message' => 'user logged in successfully', 'attributes' => $token]);
                } else {
                    return response()->json(['error' => 'Wrong password. Enter correct password.']);
                }
            } else {
                return response()->json(['error' => 'This email is not found or might be wrong.']);
            }
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return response()->json(['error' => $error]);
        }
    }

    public function logout()
    {
        try {
            Auth::logout();
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->responseController->responseValidationError('Error in logout', $error);
        }
        return $this->responseController->responseValidation('Logged out');
    }

    public function assignPlan(Request $request)
    {
        try {
            $request->validate([
                "plan_id" => "required|integer"
            ], [
                'plan_id.required' => 'Plan id is required to assign plan'
            ]);
            if ($request->hasHeader('nonprofit_name')) {
                $user = User::where('nonprofit_name', $request->header('nonprofit_name'))->first();
                if (!$user) {
                    return $this->responseController->responseValidationError('Failed', 'User not found');
                }
                $plan = Plan::where('id', $request->plan_id)->first();
                $current_plan = AccountPlan::where('plan_id', $plan->id)->first();
                if ($current_plan) {
                    return $this->responseController->responseValidationError('Failed', 'You have already subscribed ' . $plan->plan_name . ' plan');
                }
                $account_plan = new AccountPlan;
                $account_plan->plan_id = $request->plan_id;
                $account_plan->user_id = $user->id;
                if ($plan->plan_type == "1") {
                    $account_plan->campaign_limit = 10;
                } elseif ($plan->plan_type == "2") {
                    $account_plan->campaign_limit = 20;
                } else {
                    $account_plan->campaign_limit = 500;
                }
                $account_plan->save();
                return $this->responseController->responseValidation('Account assigned with Plan', $account_plan);
            } else {
                return $this->responseController->responseValidationError('Failed', 'Please provide nonprofit_name in header');
            }
        } catch (Exception $ex) {
            $err = $ex->getMessage();
            return $this->responseController->responseValidationError('Error in assigning plan', $err);
        }
    }
}