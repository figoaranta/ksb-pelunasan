<?php

namespace App\Http\Controllers\Api\v1;
use App\Http\Controllers\Controller;
use App\Giro;
use Illuminate\Http\Request;

class GiroController extends Controller
{
    public function index()
    {
    	return Giro::all();
    }
    public function show($giro)
    {
    	$giro = Giro::where('giroId',$giro)->first();
    	$test = serialize($giro);
    	return unserialize($test);
    }
    public function store(Request $request)
    {
    	$request->validate([
    		'giroId'=>'required',
    		'namaBank'=>'required',
    		'account'=>'required',
    		'tanggalJatuhTempo'=>'required',
    		'jumlah'=>'required',
    	]);

    	$giro = Giro::create([
    		'giroId'=>$request->giroId,
    		'machineId'=>UniqueMachineID(),
    		'namaBank'=>$request->namaBank,
    		'account'=>$request->account,
    		'tanggalJatuhTempo'=>$request->tanggalJatuhTempo,
    		'jumlah'=>$request->jumlah
    	]);
    	return $giro;
    }
    public function update(Request $request,$giro)
    {
    	// dd($request->all());
    	$giro=Giro::where('giroId',$giro)->first();
    	$giro->update([
    		'giroId'=>$request->giroId,
    		'machineId'=>UniqueMachineID(),
    		'namaBank'=>$request->namaBank,
    		'account'=>$request->account,
    		'tanggalJatuhTempo'=>$request->tanggalJatuhTempo,
    		'jumlah'=>$request->jumlah
    	]);
    	return $giro;
    }
    public function destroy($giro)
    {
        $giro = Giro::where('giroId',$giro);
    	$giro->delete();
    	return response()->json(["Data has been deleted."]);
    }
}

function UniqueMachineID($salt = "") {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $temp = sys_get_temp_dir().DIRECTORY_SEPARATOR."diskpartscript.txt";
        if(!file_exists($temp) && !is_file($temp)) file_put_contents($temp, "select disk 0\ndetail disk");
        $output = shell_exec("diskpart /s ".$temp);
        $lines = explode("\n",$output);
        $result = array_filter($lines,function($line) {
            return stripos($line,"ID:")!==false;
        });
        if(count($result)>0) {
            $result = array_shift(array_values($result));
            $result = explode(":",$result);
            $result = trim(end($result));       
        } else $result = $output;       
    } else {
        $result = shell_exec("blkid -o value -s UUID");  
        if(stripos($result,"blkid")!==false) {
            $result = $_SERVER['HTTP_HOST'];
        }
    }   
    return md5($salt.md5($result));
}