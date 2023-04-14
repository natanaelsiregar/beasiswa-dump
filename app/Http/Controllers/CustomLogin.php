<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class CustomLogin extends Controller
{
    //
    function login(Request $request) {
        $user = Http::asForm()->post('https://cis-dev.del.ac.id/api/jwt-api/do-auth?',[
            'username' => $request->email,
            'password' => $request->password
        ]);

        $json = json_decode($user->body(), true);
        dd($json);
    
        if($json['result'] == true){

            $token = $json['token'];
            $id_user = $json['user']['user_id'];
            $username = $json['user']['username'];
            $email = $json['user']['email'];
            $role = $json['user']['role'];
            $remember_token = $json['token'];
            
            $userDetail = Http::withToken($token)->asForm()->post('https://cis-dev.del.ac.id/api/library-api/mahasiswa?username='.$username)->body();
            $jsonDetail = json_decode($userDetail, true);
            
            // dd($jsonDetail);
            // Cek apakah data user terdapat di database
            $exist = User::where('user_id', $id_user)->exists();
            $users = new User;
            $users->user_id = $id_user;
            $users->username = $username;
            $users->email = $email;
            $users->role = $role;
            $users->remember_token = $remember_token;

            if(!$exist){
                // Save user to User table
                $users->save();

                // Save user detail to User_detail table
                $userDetail = new UserDetail;
                $userDetail->id_user = $id_user;
                $userDetail->username = $username;
                $userDetail->nim = $jsonDetail['data']['mahasiswa'][0]['nim'];
                $userDetail->nama = $jsonDetail['data']['mahasiswa'][0]['nama'];
                $userDetail->email = $jsonDetail['data']['mahasiswa'][0]['email'];
                $userDetail->prodi = $jsonDetail['data']['mahasiswa'][0]['prodi_name'];
                $userDetail->angkatan = $jsonDetail['data']['mahasiswa'][0]['angkatan'];
                $userDetail->status = $jsonDetail['data']['mahasiswa'][0]['status'];
                $userDetail->save();

            }

            $dt = User::where('user_id', $id_user)->first();
            Auth::login($dt, $remember_token);
        }
    }
}
