<?php

namespace App\Http\Controllers;


use App\CountryOption;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseController;
use App\Http\Resources\UserResource;
use App\Models\Account;
use App\Models\AccountPlan;
use App\Models\Plan;
use App\Models\User;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
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
                'first_name' => 'required | string | regex:/^[a-zA-Z\s]+/',
                'last_name' => 'required | string | regex:/^[a-zA-Z\s]+/',
                'nonprofit_name' => 'required | string',
                'email' => 'required | regex:/^([a-z0-9+-]+)(.[a-z0-9+-]+)*@([a-z0-9-]+.)+[a-z]{2,6}$/ix | unique:users,email',
                'password' => ['required', Password::min(8)],
                'country' => ['required', new Enum(CountryOption::class)],
            ], [
                'first_name.required' => 'First name is required.',
                'first_name.string' => 'First name must be containing characters.',
                'first_name.regex' => 'First name must be containing characters.',
                'last_name.required' => 'Last name is required.',
                'last_name.string' => 'Last name must be containing characters.',
                'last_name.regex' => 'Last name must be containing characters.',
                'nonprofit_name.required' => 'Non profit name is required.',
                'nonprofit_name.string' => 'Non profit name must be containing characters.',
                'email.required' => 'Email is required.',
                'email.regex' => 'Enter valid email address.',
                'email.unique' => 'This email is already in use.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be 8 characters long.',
                'country.required' => 'country is required.'
            ]);
            $user = Account::where('nonprofit_name', $request->nonprofit_name)->where('country', $request->country)->first();
            if ($user) {
                return $this->responseController->responseValidationError('Failed', $request->nonprofit_name . ' Account already exists in ' . $request->country);
            } else {
                $user = new Account;
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
            }
            return $this->responseController->responseValidation('User Created', $user);
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return $this->responseController->responseValidationError('Error in Registration', $error);
        } catch (UniqueConstraintViolationException $e) {
            return $this->responseController->responseValidationError('Error in Registration', ["This email is already in use."]);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required | email',
                'password' => ['required']
            ]);
            $user = Account::where("email", $request['email'])->first();
            if ($user) {
                if (Hash::check($request['password'], $user->password)) {
                    Auth::login($user, true);
                    $user = Auth::user();
                    return response()->json(['message' => 'user logged in successfully', 'attributes' => $user]);
                } else {
                    return $this->responseController->responseValidationError('Error in Login', 'Wrong password. Enter correct password.');
                }
            } else {
                return $this->responseController->responseValidationError('Error in Login', 'This email is not found or might be wrong.');
            }
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return $this->responseController->responseValidationError('Error in Login', $error);
        }
    }

    public function logout(Request $request)
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
                $user = Account::where('nonprofit_name', $request->header('nonprofit_name'))->first();
                if (!$user) {
                    return $this->responseController->responseValidationError('Failed', 'User not found');
                }
                $plan = Plan::where('id', $request->plan_id)->first();
                $account_plan = new AccountPlan;
                $account_plan->plan_id = $request->plan_id;
                $account_plan->account_id = $user->id;
                if ($plan->plan_type == "1") {
                    $account_plan->campaign_limit = 10;
                    $account_plan->expires_at = now()->addWeek()->addHour();
                } elseif ($plan->plan_type == "2") {
                    $account_plan->campaign_limit = 20;
                    $account_plan->expires_at = now()->addMonth()->addHour();
                } else {
                    $account_plan->campaign_limit = 500;
                    $account_plan->expires_at = now()->addYear()->addHour();
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