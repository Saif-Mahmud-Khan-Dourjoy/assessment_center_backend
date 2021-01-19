<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\Institute;
use App\Round;
use App\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstituteController extends Controller
{
    public $successStatus = 200;
    public $failedStatus = 500;
    public $invalidStatus = 400;
    public $out;
    function __construct()
    {
//        $this->middleware('api_permission:institute-list|institute-create|institute-edit|institute-delete', ['only' => ['index','show']]);
//        $this->middleware('api_permission:institute-create', ['only' => ['store']]);
//        $this->middleware('api_permission:institute-edit', ['only' => ['update']]);
//        $this->middleware('api_permission:institute-delete', ['only' => ['destroy']]);
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {

        $this->out->writeln('Fetching all the Institutes');
        $user = Auth::user();
        $user_profile = UserProfile::where('user_id','=',$user->id)->first();
        if($user->can('super-admin')){
            $institutes = Institute::all();
            return response()->json(['success'=>true,'rounds'=>$institutes],$this->successStatus);
        }
        if($user_profile->institute_id){
            $institutes = Institute::where('id','=',$user_profile->institute_id)->get();
            return response()->json(['success'=>true,'rounds'=>$institutes],$this->successStatus);
        }
        return response()->json(['success'=>true,'rounds'=>[]],$this->successStatus);
//        $institutes = Institute::all();
//        return response()->json(['success' => true, 'institutes' => $institutes], $this-> successStatus);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        request()->validate([
            'name' => 'required|unique:institutes',
        ]);
        $input = $request->all();
        $data = [
            'name' => $input['name'],
            'contact_no' => (!empty($_POST["contact_no"])) ? $input['contact_no'] : '',
            'email' => (!empty($_POST["email"])) ? $input['email'] : '',
            'website' => (!empty($_POST["website"])) ? $input['website'] : '',
            'address' => (!empty($_POST["address"])) ? $input['address'] : '',
            'logo' => (!empty($_POST["logo"]))? $input['logo']:'',
            'icon' => (!empty($_POST["icon"]))? $input["icon"]:'',
        ];
        $institute = Institute::create($data);
        if( $institute )
            return response()->json(['success' => true, 'institute' => $institute], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Institute added fail'], $this->failedStatus);
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $institute = Institute::find($id);
        if ( !$institute )
            return response()->json(['success' => false, 'message' => 'Institute not found'], $this->invalidStatus);
        else
            return response()->json(['success' => true, 'institute' => $institute], $this->successStatus);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $institute = Institute::find($id);
        request()->validate([
            'name' => 'required|unique:institutes,name,'.$id,
        ]);
        $input = $request->all();
        $data = [
            'name' => $input['name'],
            'contact_no' =>  $input['contact_no'],
            'email' =>  $input['email'],
            'website' =>  $input['website'],
            'address' =>  $input['address'],
        ];
        $institute = $institute->update($data);
        if( $institute )
            return response()->json(['success' => true, 'message' => 'Institute update successfully'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Institute update failed'], $this->failedStatus);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id)
    {
        $institute = Institute::find($id);
        if ( !$institute )
            return response()->json(['success' => false, 'message' => 'Institute not found'], $this->invalidStatus);

        if ( $institute->delete() )
            return response()->json(['success' => true, 'message' => 'Institute deleted'], $this->successStatus);
        else
            return response()->json(['success' => false, 'message' => 'Institute can not be deleted'], $this->failedStatus);

    }
}
