<?php

namespace App\Http\Controllers;
use App\Pelunasan;
use App\Giro;
use Illuminate\Http\Request;
use DB;
class PelunasanController extends Controller
{
	public function index()
	{
    	return Pelunasan::all();
	}
	public function show(Pelunasan $pelunasan)
	{
		$pelunasan->detail = json_decode($pelunasan->detail);
		return $pelunasan;
	}
	public function store(Request $request)
	{	
		$totalHutang = 0;
		$totalGiro = 0;
		$newArray = [];
		$penjual = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$request->keterangan)->first()->penjual;

		$seluruhHutang = DB::connection('mysql3')->table('hutangs')->where('penjual',$penjual);
		
		$giros = Giro::where('machineId',UniqueMachineID())->get();

		$giroId = '';
		$namaBank = '';
		$account = '';
		$tanggalJatuhTempo = '';
		$jumlah = '';
		foreach ($giros as $giro) {
			$giroId = $giroId . $giro->giroId . ",";
			$namaBank = $namaBank . $giro->namaBank . ",";
			$account = $account . $giro->account . ",";
			$tanggalJatuhTempo = $tanggalJatuhTempo . $giro->tanggalJatuhTempo.",";
			$jumlah = $jumlah . $giro->jumlah . ",";
		}

		$object = (object) [
				'giroId' =>substr($giroId, 0, -1),
				'namaBank'=>substr($namaBank, 0, -1),
				'account'=>substr($account, 0, -1),
				'tanggalJatuhTempo'=>substr($tanggalJatuhTempo, 0, -1),
				'jumlah'=>substr($jumlah, 0, -1)
		];
		// return $object;
		// 	array_push($newArray,$object);

		foreach ($giros as $giro) {
			$totalGiro = $totalGiro+$giro->jumlah;
		}

		if (count($giros)==0) {
		 	return response()->json(["Giro is currently empty"]);
		}

		$encode = json_encode($object);

		$request->validate([
			'pelunasanId'=>'required',
			'keterangan'=>'required',
			'bayarKepada'=>'required',
			'jumlah'=>'required',
			'tanggal'=>'required',
			// 'keterangan'=>'required',
			'diterimaOleh'=>'required',
		]);

		if ($request->jumlah != $totalGiro) {
			return response()->json(["Jumlah dan total jumlah giro berbeda."]);
		}
		
		$bonArray =(explode(",",$request->keterangan));

		foreach ($bonArray as $bonId) {
			$hutang = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$bonId)->first()->hargaTotal;
			$totalHutang =  $totalHutang+$hutang;
		}
		if($request->jumlah < $totalHutang){
			return response()->json(["Total hutang is greater than jumlah yang mau dibayar."]);
		}
		$pelunasan = Pelunasan::create([
			'pelunasanId'=>$request->pelunasanId,
			'keterangan'=>$request->keterangan,
			'bayarKepada'=>$request->bayarKepada,
			'jumlah'=>$request->jumlah,
			'tanggal'=>$request->tanggal,
			'detail'=>$encode,
			'diterimaOleh'=>$request->diterimaOleh,
		]);

		foreach ($bonArray as $bonId) {
			$hutang = DB::connection('mysql2')->table('suppliers')->where('nomorBon',$bonId);
			$hutang->update(['lunas'=>true]);
		}

		if (isset($seluruhHutang->get()[0])) {
			if($seluruhHutang->first()->total != 0){
				$seluruhHutang->update([
					'total'=>$seluruhHutang->first()->total - $totalHutang
				]);
			}
		}

		Giro::where('machineId',UniqueMachineID())->delete();

		return $pelunasan;
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