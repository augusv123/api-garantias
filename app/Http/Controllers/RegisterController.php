<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use GuzzleHttp\Client;
class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return response()->json($validator->errors());
            // return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->accessToken;
        $success['name'] =  $user->name;
   
        return $this->sendResponse($success, 'User register successfully.');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            $success['name'] =  $user->name;
   
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Unauthorized.', ['error'=>'Unauthorized']);
        } 
    }
    public function loginPC2(Request $request){
        $email = $request->id;
        $password = $request->password;
        // $client = new \GuzzleHttp\Client();
        $body = "{
            'email': '".$email."', 
            'password': '".$password."'
        }
        ";
        try{

   
             $client = new \GuzzleHttp\Client(['base_uri' => 'http://api.grupopiero.com/api/auth/login','verify' => false]);
             $response = $client->request('POST', '', ['body' => $body]);
            
        }
        catch(\Exception $e){
                $result = new \stdClass();
                $result->success= false;
            return response()->json($e);
        }
        return response()->json($response);

    
    }
    public function loginPC(Request $request){
        $email = $request->id;
        $password = $request->password;

        $client = new \GuzzleHttp\Client();
        $url = "http://api.grupopiero.com/api/auth/login";
        $url2= "http://api.grupopiero.com/api/auth/testcredentials";           
            
            
            $json = '	{
            "email": "'.$email.'",
            "password": "'.$password.'"
                }';
           
        try{
            $response = $client->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'email' => $email,
                    'password' => $password
                ])
            ]);
            $response = json_decode($response->getBody()->getContents());
            return response()->json(isset($response->access_token));
        }
        catch(\Exception $e){
           
        return response()->json(false);
        }
        }
}