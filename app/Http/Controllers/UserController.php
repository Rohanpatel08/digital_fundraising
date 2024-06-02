<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseController;
use App\Http\Resources\AccountPlanResource;
use App\Http\Resources\UserResource;
use App\Jobs\VerifyEmailJob;
use App\Models\Account;
use App\Models\AccountPlan;
use App\Models\Country;
use App\Models\Plan;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                'first_name' => 'required | regex:/^[a-zA-Z ]*$/',           // /^[a-zA-Z\s]+/
                'last_name' => 'required | regex: /^[a-zA-Z ]*$/',
                'nonprofit_name' => 'required | string',
                'email' => 'required | regex: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/| unique:accounts,email',
                'password' => ['required', Password::min(8)],
                'country_id' => 'required',
                // 'country_id' => ['required', new Enum(CountryOption::class)],
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
                'email.unique' => 'This email is already in use.',
                'email.regex' => 'The email format is invalid.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be 8 characters long.',
                'country_id.required' => 'country is required.'
            ]);
            $country = Country::where('id', $request->country_id)->first();
            if (!$country) {
                return $this->responseController->responseValidationError('Failed', ["country-id" => ['Please provide valid country-id to register account.']]);
            }
            $user = Account::where('nonprofit_name', $request->nonprofit_name)->where('country', $country->country_name)->first();
            if ($user) {
                return $this->responseController->responseValidationError('Failed', ["duplicate_account" => [$request->nonprofit_name . ' Account already exists in ' . $country->country_name]]);
            } else {
                $user = new Account;
                $user->first_name = $request['first_name'];
                $user->last_name = $request['last_name'];
                $user->nonprofit_name = $request['nonprofit_name'];
                $user->email = $request['email'];
                $user->password = Hash::make($request['password']);
                $user->country = $country->country_name;
                $user->save();
                VerifyEmailJob::dispatch($user);
                // $user->sendEmailVerificationNotification();
                // Auth::login($user);
                // $user = Auth::user();
                $user = new UserResource($user);
            }
            return $this->responseController->responseValidation('User Created', $user);
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return $this->responseController->responseValidationError('Error in Registration', $error);
        } catch (UniqueConstraintViolationException $e) {
            return $this->responseController->responseValidationError('Error in Registration', ["email" => ["This email is already in use."]]);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required | regex: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/',
                'password' => 'required'
            ], [
                'email.required' => 'The email is required',
                'email.regex' => 'The email format is invalid.',
                'password.required' => 'password is required'
            ]);
            $user = Account::where("email", $request['email'])->first();
            if ($user && Hash::check($request['password'], $user->password)) {
                Auth::login($user, true);
                $token = $user->createToken($user->username . '-AuthToken')->plainTextToken;
                $user = Auth::user();
                return response()->json(['message' => 'user logged in successfully', 'attributes' => $token]);
            } else {
                return $this->responseController->responseValidationError('Error in Login', ["credentials" => ["Enter valid credentials"]]);
            }
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return $this->responseController->responseValidationError('Error in Login', $error);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();
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
                "plan_id" => "required|string"
            ], [
                'plan_id.required' => 'Plan id is required to assign plan'
            ]);
            if ($request->hasHeader('account-id')) {
                $user = Account::where('id', $request->header('account-id'))->first();
                if (!$user) {
                    return $this->responseController->responseValidationError('Failed', ["user" => ['User not found']]);
                }
                $plan = Plan::where('id', $request->plan_id)->first();
                $account_plan = new AccountPlan;
                $account_plan->id = uuid_create();
                $account_plan->plan_id = $request->plan_id;
                $account_plan->account_id = $user->id;
                if ($plan->plan_type == "1") {
                    $account_plan->campaign_limit = $plan->campaign_limit;
                    $account_plan->expires_at = now()->addWeek()->addHour();
                } elseif ($plan->plan_type == "2") {
                    $account_plan->campaign_limit = $plan->campaign_limit;
                    $account_plan->expires_at = now()->addMonth()->addHour();
                } else {
                    $account_plan->campaign_limit = $plan->campaign_limit;
                    $account_plan->expires_at = now()->addYear()->addHour();
                }
                $account_plan->save();
                $account_plan = new AccountPlanResource($account_plan);
                return $this->responseController->responseValidation('Account assigned with Plan', $account_plan);
            } else {
                return $this->responseController->responseValidationError('Failed', ['account_id' => ['Please provide account-id in header']]);
            }
        } catch (ValidationException $ex) {
            $err = $ex->validator->errors();
            return $this->responseController->responseValidationError('Error in assigning plan', $err);
        }
    }
}
